<?php

namespace Smart\Common\Comment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Routing\Route;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;
use Smart\Common\Exceptions\ResourceMissDataException;
use Smart\Common\Exceptions\RouteMissActionException;
use Smart\Common\Services\ConfigService;

/**
 * 解析action
 */
class ActionComment extends Comment
{
    /**
     * @var string
     */
    protected $actionName;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var ControllerComment
     */
    protected $controllerComment;

    /**
     * @var \ReflectionMethod
     */
    protected $reflector;

    /**
     * @var ResourceResolver
     */
    protected $resourceResolver;

    /**
     * @var FormRequestResolver
     */
    protected $requestResolver;

    /**
     * ActionComment constructor.
     * @param ControllerComment $controllerComment
     * @param Route $route
     * @throws \ReflectionException|RouteMissActionException
     */
    public function __construct(ControllerComment $controllerComment, Route $route)
    {
        $this->actionName = $route->getActionMethod();
        $this->controllerComment = $controllerComment;
        $this->reflector = $this->controllerComment->actionByName($this->actionName);
        $this->route = $route;

        if (!$this->reflector) {
            throw new RouteMissActionException();
        }

        $docComment = $this->reflector->getDocComment();

        if ($docComment) {
            $factory = DocBlockFactory::createInstance();
            $this->docblock = $factory->create(
                $docComment,
                (new ContextFactory())->createFromReflector($this->reflector)
            );
        }

        // 解析FormRequest对象
        $this->parseValidFormRequest();

        // 解析Resource对象
        $this->parseValidResource();
    }

    /**
     * @throws \ReflectionException
     * @throws ResourceMissDataException
     * @return array
     */
    public function input()
    {
        if (!$this->requestResolver) {
            return [];
        }

        // 如果是GET请求，并且是获取一个集合数据的话，需要做筛选，排序，以及按需获取的的特殊处理
        // if (in_array('GET', $this->route->methods) and $this->isCollectionAction()) {
        if (in_array('GET', $this->route->methods) and $this->hasPaginator()) {
            $data = $this->requestResolver->listFields($this->resourceResolver);
            // 是否存在排序
            if ($this->hasSort()) {
                $data = array_merge($data, $this->requestResolver->sortFields());
            }

            // 是否存在分页
            // if ($this->hasPaginator()) {
            $data = array_merge($data, $this->requestResolver->pageFields());
        // }
        }
        // 下载文件的请求
        elseif (in_array('GET', $this->route->methods) and $this->isDownloadAction()) {
            $data = $this->requestResolver->downloadFields();
        }
        // 如果是单纯的get请求，需要支持按需获取
        elseif (in_array('GET', $this->route->methods)) {
            $data = array_merge(
                $this->requestResolver->viewFields($this->resourceResolver),
                $this->requestResolver->fields()
            );
        }
        // get之外的其他请求不支持按需获取
        else {
            $data = $this->requestResolver->fields();
        }

        return [
            // 'name' => $param->getName(),
            'class' => get_class($this->requestResolver->request),
            'input' => $data,
        ];
    }

    /**
     * 返回值
     * @throws \ReflectionException
     * @throws ResourceMissDataException
     * @return array|null
     */
    public function output()
    {
        if (!$this->resourceResolver) {
            return null;
        }

        return [
            // 'name' => $resource::class,
            'class' => get_class($this->resourceResolver->resource),
            'output' => $this->resourceResolver->fields(),
        ];
    }

    /**
     * 获取url所需的参数
     * @param Route $route
     * @return array
     */
    public function uriParams(Route $route)
    {
        $params = $route->parameterNames();
        $fields = $route->bindingFields();
        $tmp = [];
        foreach ($this->reflector->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (ConfigService::routeFormat() == 'underline') {
                $name = $this->unSnake($name);
            }

            if (!in_array($name, $params)) {
                continue;
            }

            preg_match_all('/\\{(.*?)\\}/', $route->uri(), $rs);

            // 如果能获取到class，基本可以判定，这个类是一个model类
            $parameterObj = $parameter->getClass();
            $required = false;
            if ($parameterObj instanceof \ReflectionClass) {
                /** @var Model $model */
                $model = $parameterObj->newInstance();
                $column = isset($fields[$name]) ? $fields[$name] : $model->getRouteKeyName();
                // $comment = $model->getTable() . '表中对应的' . $column . '字段值。';
                $comment = $column;
                $type = 'Integer';
                if (in_array($name, $rs[1]) or !$parameter->allowsNull()) {
                    $required = true;
                }
                // $required = !$parameter->allowsNull();
                $default = null;
            } else {
                $comment = '';
                $type = $default = null;
                if ($param = $this->paramTagByVar($name)) {
                    $comment = $param->getDescription()->render();
                    $type = (string)$param->getType();
                }

                try {
                    if ($default = $parameter->getDefaultValue()) {
                        $flag = false;
                    } else {
                        $flag = true;
                    }
                } catch (\ReflectionException $e) {
                    $flag = true;
                }

                if (in_array($name, $rs[1]) or true == $flag) {
                    $required = true;
                }
            }

            $tmp[$name] = (array)new FieldObject([
                'type' => $type,
                'default' => $default,
                'required' => $required,
                'comment' => $comment,
            ]);

            continue;
        }

        return $tmp;
    }

    /**
     * 获取一个有效的FormRequest类
     * @throws \ReflectionException
     * @return string
     */
    public function getValidFormRequestClass()
    {
        $params = $this->reflector->getParameters();

        foreach ($params as $param) {
            if ($param->getClass() === null) {
                continue;
            }

            $class = $param->getClass()->getName();

            if (!$this->isFormRequestClass($class)) {
                continue;
            }

            return $class;
        }
    }

    /**
     * 获取一个有效的FormRequest对象
     * @throws \ReflectionException
     * @return FormRequest|null
     */
    public function getValidFormRequestInstance()
    {
        if ($class = $this->getValidFormRequestClass()) {
            $request = new $class();
            if (method_exists($request, 'setScenario')) {
                $request->setScenario($this->actionName);
            }

            return $request;
        }
    }

    /**
     * 解析一个有效的FormRequest
     * @throws \ReflectionException
     * @return FormRequestResolver
     */
    public function parseValidFormRequest()
    {
        if ($formRequest = $this->getValidFormRequestInstance()) {
            return $this->parseFormRequest($formRequest);
        }
    }

    /**
     * 从头see 标签中获取资源类
     * @return array|null
     */
    public function getClassesFromSeeTag()
    {
        if (!$this->docblock or !$this->docblock->hasTag('see')) {
            return [];
        }
        $tmp = [];

        /** @var See[] $tags */
        $tags = $this->docblock->getTagsByName('see');
        // print_r($tags);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if ($desc = $tag->getDescription()) {
                    // $tmp[] = $desc->render();
                }

                if (!$tag instanceof See) {
                    continue;
                }

                /** @var DocBlock\Tags\Reference\Fqsen $obj */
                $obj = $tag->getReference();
                if (!$obj instanceof DocBlock\Tags\Reference\Fqsen) {
                }

                $class = (string)$obj;
                /*if (!$this->isSystemResource($class)) {
                    $tmp[] = $class;
                }*/
                $tmp[] = $class;
            }
        }

        return $tmp;
    }

    /**
     * @return array
     */
    public function getResourcesFromReturnTag()
    {
        if (!$this->docblock) {
            return [];
        }

        $tmp = [];

        /** @var DocBlock\Tags\Return_[] $tags */
        $tags = $this->docblock->getTagsByName('return');
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if ($desc = $tag->getDescription()) {
                    // $tmp[] = $desc->render();
                }

                /** @var Object_ $type */
                if ($type = $tag->getType()) {
                    if (is_array($type)) {
                        $type = $type[0];
                    }

                    if ($type instanceof Compound) {
                        /** @var Compound $type */
                        // return $tmp[] = $type;
                        /** @var Object_ $item */
                        foreach ($type as $item) {
                            if ($resource = $this->parseObject($item)) {
                                $tmp[] = $resource;
                            }
                        }

                        continue;
                    }

                    if ($resource = $this->parseObject($type)) {
                        $tmp[] = $resource;

                        continue;
                    }
                }
            }
        }

        return $tmp;
    }

    /**
     * 将蛇形命名改成下划线命名
     * @param $camelCaps
     * @param string $separator
     * @return string
     */
    protected function unSnake($camelCaps, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . $separator . '$2', $camelCaps));
    }

    /**
     * 解析一个FormRequest
     * @param FormRequest $request
     * @return FormRequestResolver
     */
    protected function parseFormRequest(FormRequest $request)
    {
        if (method_exists($request, 'setScenario')) {
            $request->setScenario($this->actionName);
        }
        $this->requestResolver = new FormRequestResolver($request);

        return $this->requestResolver;
    }

    /**
     * 解析一个有效的Resource
     * @return ResourceResolver|null
     */
    protected function parseValidResource()
    {
        if ($resource = $this->getValidResource()) {
            return $this->parseResource($resource);
        }
    }

    /**
     * @return array|JsonResource|mixed
     */
    protected function getValidResource()
    {
        /** @var ReflectionParameter[] $params */
        /*$params = array_filter((array)$this->getResourcesFromReturnTag(), function ($class) {
            if (!$this->isSystemResource($class)) {
                return true;
            }
        });

        $params = (array)$this->getResourcesFromReturnTag();

        if (!$params) {

            $params = array_filter((array)$this->getClassesFromSeeTag(), function ($class) {
                //echo $class."\n";
                if (!$this->isSystemResource($class)) {
                    return true;
                }
            });
        }*/

        $params = array_merge(
            (array)$this->getResourcesFromReturnTag(),
            (array)$this->getClassesFromSeeTag()
        );

        if (!$params) {
            return [];
        }

        foreach ($params as $class) {
            try {
                if (!$this->isCustomResource($class)) {
                    continue;
                }

                $resource = new $class([]);
                if (property_exists($resource, 'isDocAccessor')) {
                    $resource->isDocAccessor = true;
                }
            } catch (\Exception $e) {
                continue;
            }

            return $resource;
        }
    }

    /**
     * 获取某一个类的属性名称
     * @param $class
     * @throws \ReflectionException
     * @return array
     */
    protected function getClassProperties($class)
    {
        $r = new \ReflectionClass($class);
        $properties = [];
        foreach ($r->getProperties() as $property) {
            $properties[] = $property->name;
        }

        return $properties;
    }

    /**
     * 解析一个资源类
     * @param JsonResource $resource
     * @return ResourceResolver
     */
    protected function parseResource(JsonResource $resource)
    {
        if (method_exists($resource, 'setScenario')) {
            $resource->setScenario($this->actionName);
        }
        $this->resourceResolver = new ResourceResolver($resource, request(), $this->reflector->getName());

        return $this->resourceResolver;
    }

    /**
     * @param $object
     * @return string|null
     */
    protected function parseObject($object)
    {
        if (!$object instanceof Object_) {
            return null;
        }

        $class = (string)$object->getFqsen();
        if (!$class) {
            return null;
        }

        return $class;
    }

    /**
     * 判断是否是资源类
     * @param $class
     * @return bool
     */
    protected function isSystemResource($class)
    {
        $class = trim($class, '\\');
        if (in_array($class, [
            AnonymousResourceCollection::class,
            ResourceCollection::class,
        ])) {
            return true;
        }
    }

    /**
     * 判断类是否是用户自定义的资源类
     * @param $class
     * @throws \ReflectionException
     * @return bool
     */
    protected function isCustomResource($class)
    {
        if ($this->isSystemResource($class)) {
            return false;
        }
        $this->parentClass($class, $parents);

        return in_array(JsonResource::class, (array)$parents);
    }

    /**
     * 判断是分页
     * @param $class
     * @throws \ReflectionException
     * @return bool
     */
    protected function isPaginator($class)
    {
        if (AbstractPaginator::class == $class) {
            return true;
        }
        $this->parentClass($class, $parents);

        return in_array(AbstractPaginator::class, (array)$parents);
    }

    /**
     * 是否存在分页
     * @throws \ReflectionException
     * @return bool
     */
    protected function hasPaginator()
    {
        foreach ((array)$this->getClassesFromSeeTag() as $class) {
            if ($this->isPaginator($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断当前action是否支持分页
     * @throws \ReflectionException
     * @return bool
     */
    protected function hasSort()
    {
        $request = $this->getValidFormRequestInstance();
        if (method_exists($request, 'sorts')) {
            $fields = $request->sorts();
            if (is_array($fields) and !empty($fields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取某一个类的所有父类
     * @param $class
     * @param $map
     * @throws \ReflectionException
     */
    protected function parentClass($class, &$map = [])
    {
        if (!class_exists($class)) {
            return;
        }

        $ref = new \ReflectionClass($class);
        if ($parent = $ref->getParentClass()) {
            $map[] = trim($parent->getName(), '\\');
            $this->parentClass($parent->getName(), $map);
        }
    }

    /**
     * @param $class
     * @throws \ReflectionException
     * @return bool
     */
    protected function isFormRequestClass($class)
    {
        $this->parentClass($class, $parents);

        return in_array(FormRequest::class, (array)$parents);
    }

    /**
     * 判断当前action是否是一个列表
     *
     * 一般的，返回集合的action都被视为是列表action，
     * 列表action应当支持按需获取、自定义排序、查询条件、关联对象的获取
     * @throws \ReflectionException
     * @return bool
     */
    protected function isCollectionAction()
    {
        $resource = $this->getResourcesFromReturnTag();
        if ($resource) {
            $resourceClass = array_pop($resource);
            $this->parentClass($resourceClass, $classes);
            if (in_array('Illuminate\Http\Resources\Json\ResourceCollection', (array)$classes)) {
                return true;
            }
        }
    }

    /**
     * 是否是下载文件的action
     * @return bool
     */
    protected function isDownloadAction()
    {
        $response = $this->getResourcesFromReturnTag();
        if (!isset($response[0])) {
            return false;
        }

        $classes = [
            '\Symfony\Component\HttpFoundation\BinaryFileResponse',
            '\Symfony\Component\HttpFoundation\StreamedResponse',
        ];

        return in_array($response[0], $classes);
    }
}
