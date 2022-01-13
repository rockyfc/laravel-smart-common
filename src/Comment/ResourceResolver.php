<?php

namespace Smart\Common\Comment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use ReflectionException;
use Smart\Common\Exceptions\ResourceMissDataException;

/**
 * Resource解析器
 */
class ResourceResolver extends Resolver
{
    /**
     * @var JsonResource
     */
    public $resource;

    /**
     * @var FormRequest
     */
    public $request;

    /**
     * @var null|string
     */
    public $actionName;

    /**
     * 关联字段数组
     * @var array
     */
    protected $relationsFields = [];

    /**
     * ResourceResolver constructor.
     * @param JsonResource $resource
     * @param Request $request
     * @param null|string $actionName
     */
    public function __construct(JsonResource $resource, Request $request, string $actionName = 'index')
    {
        $this->resource = $resource;
        $this->request = $request;
        $this->actionName = $actionName;
    }

    /**
     * 获取所有属性字段的基本信息
     * @return array
     * @throws ReflectionException
     * @throws ResourceMissDataException
     */
    public function fields()
    {
        $array = $this->toArray();
        if (!$array or count($array) < 1) {
            $scenario = $this->actionName;
            if (property_exists($this->resource, 'scenario')) {
                $scenario = $this->resource->scenario;
            }
            $error = get_class($this->resource) . '资源类没有找到' . $scenario . '场景的返回值，或者返回值为空';

            throw new ResourceMissDataException($error);
        }

        $relations = null;
        //判断一下，资源类中是否存在relations方法，如果存在，则从资源类中获取资源对应关系
        if (method_exists($this->resource, 'relations')) {
            $relations = (array)$this->resource->relations();
        }

        $labels = (array)$this->resourceLabels();
        $rules = (array)$this->resourceRules();


        $data = [];
        foreach ($array as $attribute => $value) {
            $comment = isset($labels[$attribute]) ? $labels[$attribute] : null;

            if ($options = $this->attributeOptions($attribute)) {
                $comment .= '。可选值：' . $this->print($options);
            }

            $type = 'string';
            if (isset($rules[$attribute])) {
                $parser = new RuleParser($rules[$attribute]);
                $type = $parser->typeDetail();
            } else{
                $type = RuleParser::guessType($attribute);
            }

            if (is_array($value)) {
                $type = '[ ]';
            }

            if (is_array($relations)) {
                if (isset($relations[$attribute])) {
                    $this->addToRelationsFields($attribute, $relations[$attribute], $comment);

                    continue;
                }
            } else {
                if ($value instanceof ResourceCollection) {
                    $this->addToRelationsFields($attribute, $value->collects, $comment);

                    continue;
                }
                if ($value instanceof JsonResource) {
                    $this->addToRelationsFields($attribute, get_class($value), $comment);

                    continue;
                }
            }


            $data[$attribute] = [
                //'required' => null,
                'type' => $type,
                'options' => array_keys($options),
                'comment' => $comment,
            ];
        }

        return $data;
    }

    /**
     * 获取关联对象字段
     * @return array
     */
    public function getRelationsFields()
    {
        return $this->relationsFields;
    }

    /**
     * @return array
     * @throws ResourceMissDataException
     */
    protected function parseResourceToArrayMethod()
    {
        $array = [];
        try {
            //当解析toArray产生异常的时候，说明toArray()方法中代码中写法较为复杂，文档程序解释不了
            $array = $this->resource->toArray(request());
        } catch (\Throwable $e) {
            //如果toArray解析有问题，则检查用户是否自行实现了fields方法，如果实现了则从fields方法中获取要返回的字段
            if (method_exists($this->resource, 'fields')) {
                $columns = array_values($this->resource->fields());
                $array = array_combine($columns, $columns);
            } else {
                $msg = get_class($this->resource) . '::toArray()方法解析失败，请创建' . get_class($this->resource) . '::fields()函数来声明返回值';
                throw new ResourceMissDataException($msg);
            }
        }

        return $array;
    }

    /**
     * 格式化返回值
     * @return array
     */
    protected function toArray()
    {
        $array = $this->parseResourceToArrayMethod();
        $data = [];
        foreach ($array as $attribute => $val) {
            if (is_array($val)) {
                $data[$attribute] = [];
                foreach ($val as $i => $v) {
                    $data[$attribute . '.' . $i] = $v;
                }

                continue;
            }

            $data[$attribute] = $val;
        }

        return $data;
    }

    /**
     * 添加关联对象字段
     * @param $attribute
     * @param $type
     * @param $comment
     */
    protected function addToRelationsFields($attribute, $type, $comment)
    {
        $this->relationsFields[$attribute] = [
            'type' => $type,
            'comment' => $comment,
        ];
    }

    /**
     * 获取字段释义
     * @return array
     */
    protected function resourceLabels()
    {
        if (method_exists($this->resource, 'attributesLabels')) {
            return $this->resource->attributesLabels();
        }

        return [];
    }

    /**
     * 获取字段验证规则
     * @return array
     */
    protected function resourceRules()
    {
        if (method_exists($this->resource, 'attributesRules')) {
            return $this->resource->attributesRules();
        }

        return [];
    }

    /**
     * 获取属性可选值。
     *
     * 首先检查当前的resource类中有没有相关的常量，如果没有则检查
     * @param $attribute
     * @return null|array
     * @throws ReflectionException
     */
    protected function attributeOptions($attribute)
    {
        $constName = strtoupper($attribute);

        //首先检查当前的resource类中有没有相关的常量
        if ($this->hasConstInClass($constName, $this->resource)) {
            $const = $this->getClassConstants($this->resource);

            return (array)$const[$constName];
        }

        if (!method_exists($this->resource, 'model') or !$this->resource->model()) {
            return [];
        }

        //然后检查当前resource中的model中有没有相关的常量
        $model = $this->resource->model();
        if ($this->hasConstInClass($constName, $model)) {
            $const = $this->getClassConstants($model);

            return (array)$const[$constName];
        }

        return [];
    }

    /**
     * 代码字符串格式化
     * @param $data
     * @return string
     */
    protected function print($data)
    {
        $str = print_r($data, true);
        $str = str_replace("\n", '<br/>', $str);
        $str = str_replace(' ', '&nbsp;', $str);

        return '<br><code>' . $str . '</code>';
    }
}
