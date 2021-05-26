<?php

namespace Smart\Common\Comment;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionException;
use Smart\Common\Exceptions\ResourceMissDataException;

/**
 * FormRequest解析器
 */
class FormRequestResolver extends Resolver
{
    /**
     * @var FormRequest
     */
    public $request;

    /**
     * FormRequestResolver constructor.
     * @param FormRequest $request
     */
    public function __construct(FormRequest $request)
    {
        parent::__construct($request);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function fields()
    {
        $rules = $this->request->rules();
        if (!$rules) {
            return [];
        }

        $data = [];
        foreach ($rules as $attribute => $rule) {
            if (!preg_match('/\./', $attribute)) {
                $data[$attribute] = $this->parseRule($attribute, (array)$rule);
            }
        }

        return $data;
    }

    /**
     * 列表页获取数据
     * @param ResourceResolver $resolver
     * @return array
     * @throws ResourceMissDataException
     * @throws ReflectionException
     */
    public function listFields(ResourceResolver $resolver = null)
    {
        return array_merge(
            $this->convertToFilter(),
            $this->viewFields($resolver)
        );
    }


    /**
     * 下载功能的action请求参数
     * @return array
     * @throws ReflectionException
     */
    public function downloadFields()
    {
        return $this->convertToFilter();
    }

    /**
     * 详情接口要获取的字段
     * （理论上，只要支持get请求方式，就可以支持用户按需获取）
     * @param null|ResourceResolver $resolver
     * @return array
     * @throws ResourceMissDataException
     * @throws ReflectionException
     */
    public function viewFields(ResourceResolver $resolver = null)
    {
        $query = $this->config();

        $attributes = $resolver ? array_keys($resolver->fields()) : [];
        $relations = $resolver ? $resolver->getRelationsFields() : [];
        $data = [
            $query['fieldsName'] => (array)new FieldObject([
                'required' => false,
                'type' => 'string',
                'default' => '*',
                'comment' => '要获取的字段，推荐按需获取，多个字段用英文逗号分隔。字段释义见返回值。',
                'options' => $attributes,
            ]),
        ];

        if ($relations) {
            $data = array_merge($data, [
                $query['relationName'] => (array)new FieldObject([
                    'required' => false,
                    'isRelation' => true,
                    'type' => 'string',
                    'default' => null,
                    'comment' => '扩展对象字段，多个对象用英文逗号分隔。推荐按需获取。',
                    'options' => $relations,
                ]),
            ]);
        }

        return $data;
    }

    /**
     * 分页查询参数
     * @return array[]
     */
    public function pageFields()
    {
        return [
            'page' => (array)new FieldObject([
                'required' => false,
                'isRelation' => false,
                'type' => 'integer',
                'default' => 1,
                'comment' => '页码',
                'options' => [],
            ]),
            'per_page' => (array)new FieldObject([
                'required' => false,
                'isRelation' => false,
                'type' => 'integer',
                'default' => 15,
                'comment' => '每页显示条数',
                'options' => [],
            ]),
        ];
    }

    /**
     * @return array[]
     */
    public function sortFields()
    {
        $query = $this->config();
        $fields = $this->request->sorts();
        if (count($fields) > 1) {
            $comment = '排序字段，支持复合排序。多个排序字段用英文逗号分隔，倒序字段前面加减号"-"，例如：<code>sort=type,-id</code>"表示查询结果按照type正序排列，id倒序排列。';
        } else {
            $comment = '排序字段，倒叙排列字段前面加"-"，例如：<code>sort=-id</code>';
        }

        return [
            $query['sortName'] => (array)new FieldObject([
                'required' => false,
                'isRelation' => false,
                'type' => 'string',
                'default' => null,
                'comment' => $comment,
                'options' => $fields,
            ]),
        ];
    }

    /**
     * 是否需要先登录
     *
     * @return bool
     */
    public function authorize()
    {
        if (method_exists($this->request, 'authorize')) {
            return $rules = $this->request->authorize();
        }

        return false;
    }

    /**
     * 将request里面的input参数名称包裹上filter标签
     * @return array
     * @throws ReflectionException
     */
    protected function convertToFilter()
    {
        $query = $this->config();

        $input = $this->fields();

        $tmp = [];
        if ($input) {
            foreach ($input as $attribute => $detail) {
                $key = $query['filterName'] . '[' . $attribute . ']';
                $tmp[$key] = $detail;
            }
        }

        return $tmp;
    }

    /*protected function sort(){
        $query = $this->config();
    }*/

    /**
     * @param $attribute
     * @param $rule
     * @return array
     * @throws ReflectionException
     */
    protected function parseRule($attribute, $rule)
    {
        $attributes = $this->request->attributes();
        $comment = $attributes[$attribute] ?? $attribute;

        $parser = new RuleParser($rule);
        $in = $parser->in();

        if ($options = $this->attributeOptions($attribute)) {

            //如果rule规则中有in规则，则从$options中取出in规则部分
            if ($in) {
                $tmp = [];
                foreach ($options as $k1 => $v1) {
                    $k1 = (string)$k1;
                    foreach ($in as $v2) {
                        $v2 = (string)$v2;
                        if ($k1 === $v2) {
                            $tmp[$k1] = $v1;
                        }
                    }
                }

                $options = $tmp;
            }

            $comment .= '。可选值：' . $this->print($options);
        }

        return (array)new FieldObject([
            'required' => $parser->required(),
            'type' => $parser->typeDetail(),
            'default' => $parser->defaultValue(),
            'options' => $in ? $in : array_keys($options),
            'comment' => $comment,
        ]);
    }

    /**
     * 获取属性可选值
     * @param $attribute
     * @return array
     * @throws ReflectionException
     */
    protected function attributeOptions($attribute)
    {
        $constName = strtoupper($attribute);

        //首先检查当前的request类中有没有相关的常量
        if ($this->hasConstInClass($constName, $this->request)) {
            $const = $this->getClassConstants($this->request);

            return (array)$const[$constName];
        }

        if (!method_exists($this->request, 'model')) {
            return [];
        }

        $model = $this->request->model();
        if ($model and $this->hasConstInClass($constName, $model)) {
            $const = $this->getClassConstants($model);

            return (array)$const[$constName];
        }

        return [];
    }
}
