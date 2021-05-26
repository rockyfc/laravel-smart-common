<?php

namespace Smart\Common\Services;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionException;
use Smart\ApiDoc\Services\ConfigService;
use Smart\Common\Comment\ActionComment;
use Smart\Common\Comment\ControllerComment;
use Smart\Common\Comment\ResourceComment;
use Smart\Common\Comment\ResourceResolver;
use Smart\Common\Exceptions\ResourceMissDataException;
use Smart\Common\Exceptions\RouteMissActionException;

class DocService
{
    public $error = [];

    /**
     * 获取所有相关路由的controller
     *
     * @return array
     */
    public function controllers()
    {
        $routers = $this->validRoutes();
        $data = [];
        foreach ($routers as $route) {
            //获取当前的controller对象
            $controller = $route->getController();

            $controllerName = get_class($controller);
            if (in_array($controllerName, $data)) {
                continue;
            }
            $data[] = $controllerName;
        }

        return $data;
    }

    /**
     * 获取所有controller注释
     * @throws ReflectionException
     * @return array
     */
    public function controllerComments()
    {
        $controllers = $this->controllers();
        if (!$controllers) {
            return [];
        }

        $data = [];
        foreach ($controllers as $class) {
            $data[$class] = $this->controllerComment($class);
        }

        return $data;
    }

    /**
     * 获取某个controller的注释
     * @param $controllerName
     * @throws ReflectionException
     * @return array
     */
    public function controllerComment($controllerName)
    {
        $comment = new ControllerComment($controllerName);

        return [
            'controller' => $controllerName,
            'title' => $comment->title() ?? $controllerName,
            'desc' => $comment->desc(),
            'deprecated' => [
                'isDeprecated' => $comment->isDeprecated(),
                'desc' => $comment->deprecatedTag(),
            ],
            'author' => $comment->author(),
        ];
    }

    /**
     * 获取一个控制器的所有路由
     * @param string $controllerName
     * @return array
     */
    public function actionRoutesByController(string $controllerName)
    {
        $routes = $this->validRoutes();
        $data = [];
        foreach ($routes as $route) {
            $controller = $route->getController();
            if (get_class($controller) != $controllerName) {
                continue;
            }

            $data[] = $route;
        }

        return $data;
    }

    /**
     * 根据关键字查找action，
     * @param null $keyword
     * @throws ReflectionException
     * @return array
     */
    public function actions($keyword = null)
    {
        $routes = $this->validRoutes();
        $data = [];
        foreach ($routes as $route) {
            try {
                $info = $this->actionCommentByRoute($route);
                if ($this->filterAction($info, $keyword)) {
                    $data[] = $info;
                }

                continue;
            } catch (RouteMissActionException $e) {
                $this->error[$route->getActionName()] = '路由' . $route->uri() . '未能匹配到Action：' . $route->getActionName();
            } catch (ResourceMissDataException $e) {
                $this->error[$route->getActionName()] = '路由' . $route->uri() . ' ' . $route->getActionName() . ' : ' . $e->getMessage();
            }
        }

        return $this->arraySort($data, 'created_at');
        //return $data;
    }

    /**
     * 根据action获取一个action
     * @param $name
     * @param \Illuminate\Routing\Route $route
     * @throws ReflectionException
     * @throws ResourceMissDataException
     * @return array
     */
    public function action($name, &$route = null)
    {
        $routes = $this->validRoutes();
        foreach ($routes as $route) {
            try {
                if ($route->getActionName() !== $name) {
                    continue;
                }

                return $this->actionCommentByRoute($route);
            } catch (RouteMissActionException $e) {
                $this->error[] = $route->uri() . '未能匹配到Action' . $route->getActionName();
            }
        }

        return [];
    }

    /**
     * @param $actionInfo
     * @param null $keyword
     * @return bool
     */
    public function filterAction($actionInfo, $keyword = null)
    {
        $keyword = str_replace('/', '\/', $keyword);
        if (!$keyword) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['title'])) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['name'])) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['desc'])) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['controller']['controller'])) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['action'])) {
            return true;
        }

        if (preg_match('/' . $keyword . '/', $actionInfo['controller']['title'])) {
            return true;
        }

        foreach ($actionInfo['author'] as $author) {
            if (preg_match('/' . $keyword . '/', $author['authorName'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据一个类名获取其中的接口列表
     * @param string $controllerName
     * @throws ReflectionException|ResourceMissDataException
     * @return array
     */
    public function actionCommentsByController(string $controllerName)
    {
        $routes = $this->actionRoutesByController($controllerName);
        $data = [];
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routes as $route) {
            try {
                $data[] = $this->actionCommentByRoute($route);
            } catch (RouteMissActionException $e) {
                $this->error[] = $route->uri() . '未能匹配到Action' . $route->getActionName();
            }
        }

        return $data;
    }

    /**
     * 根据路由获取一个action注释信息
     * @param \Illuminate\Routing\Route $route
     * @throws ReflectionException|RouteMissActionException
     * @throws ResourceMissDataException
     * @return array
     */
    public function actionCommentByRoute(\Illuminate\Routing\Route $route)
    {
        $controllerName = get_class($route->getController());
        $controllerComment = new ControllerComment($controllerName);
        //echo get_class($route->getController()).'::'.$route->getActionMethod();
        $comment = new ActionComment(
            $controllerComment,
            $route
        );

        return [
            'action' => $route->getActionName(),
            'controller' => $this->controllerComment($controllerName),
            'name' => $route->uri(),
            'title' => $comment->title() ?? $route->getActionMethod(),
            'desc' => $comment->desc(),
            'methods' => $route->methods(),
            'created_at' => $comment->date(),
            //'tags' => $comment->tags(),
            'deprecated' => [
                'isDeprecated' => $comment->isDeprecated(),
                'desc' => $comment->deprecatedTag(),
            ],

            'author' => $comment->author(),
            'uriParams' => $comment->uriParams($route),
            'commonRequest' => $this->commonRequest(),
            'request' => $comment->input(),
            'response' => $comment->output(),
        ];
    }

    /**
     * @param $resourceClass
     * @throws ReflectionException|ResourceMissDataException
     * @return array
     */
    public function resource($resourceClass)
    {
        $resource = new $resourceClass([]);
        if (property_exists($resource, 'isDocAccessor')) {
            $resource->isDocAccessor = true;
        }

        $comment = new ResourceComment($resourceClass);

        $resolver = new ResourceResolver($resource, request());

        return [
            'title' => $comment->title(),
            'name' => $resourceClass,
            'desc' => $comment->desc(),
            'deprecated' => [
                'isDeprecated' => $comment->isDeprecated(),
                'desc' => $comment->deprecatedTag(),
            ],
            'author' => $comment->author(),
            'fields' => $resolver->fields(),
            'relationsFields' => $resolver->getRelationsFields(),
        ];
    }

    /**
     * 获取带有有效Controller的路由
     *
     * @return \Illuminate\Routing\Route[]
     */
    public function validRoutes()
    {
        $routers = $this->filter();
        $data = [];
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routers as $route) {
            $action = $route->getAction();

            if (!isset($action['controller'])) {
                continue;
            }

            //获取当前的controller对象
            $controller = $route->getController();

            if (!($controller instanceof Controller)) {
                continue;
            }

            if (!method_exists($controller, $this->method($route))) {
                $this->error[] = $route->uri() . '未能匹配到Action' . $route->getActionName();

                continue;
            }

            $data[] = $route;
        }

        return $data;
    }

    public function apiPrefix()
    {
        return ConfigService::filters();
    }

    public function commonRequest()
    {
        return ConfigService::commonParams();
    }

    public function docPrefix()
    {
        return ConfigService::prefix();
    }

    /**
     * @param \Illuminate\Routing\Route $route
     * @return mixed|string
     */
    protected function method(\Illuminate\Routing\Route $route)
    {
        list($namespace, $action) = explode('@', $route->getActionName());

        return $action;
    }

    /**
     * 取出用户感兴趣的路由
     *
     * @return array
     */
    protected function filter()
    {
        $routers = [];

        /** @var \Illuminate\Routing\Route $route */
        foreach (Route::getRoutes() as $route) {
            if ($this->apiPrefix()
                and $this->apiPrefix() != $this->docPrefix()
                and !Str::startsWith($route->getAction()['prefix'], $this->apiPrefix())) {
                continue;
            }
            //echo $route->uri() . '-------' . $route->getAction()['prefix'] . "\n";
            $routers[] = $route;
        }
        //exit;
        return $routers;
    }

    /**
     * 排序
     * @param $array
     * @param $keys
     * @param int $sort
     * @return mixed
     */
    protected static function arraySort($array, $keys, $sort = SORT_DESC)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);

        return $array;
    }
}
