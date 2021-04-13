<?php

namespace Smart\Common\Comment;

/**
 * 解析rule
 * Class RuleParser
 */
class RuleParser
{
    /**
     * @var false|string[]
     */
    protected $rule;

    protected $max;
    protected $min;
    protected $in;

    /**
     * rules中的字段类型和php数据类型的对应关系，
     * 没有列出的rule类型，默认对应着php的string类型
     * @var string[]
     */
    protected $ruleTypeToPhpType = [
        'integer' => 'int',
        'bool' => 'bool',
        'boolean' => 'bool',
        'array' => 'array',
        'numeric' => 'float',
        'digits' => 'float',
        'digits_between' => 'float',
    ];

    /**
     * rules中的字段类型和显示给用户的字段类型的对应关系，
     * 没有列出的rule类型，默认对应着String
     * @var string[]
     */
    protected $ruleTypeToViewType = [
        'string' => 'string',
        'integer' => 'integer',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'numeric' => 'numeric',
        'digits' => 'numeric',
        'digits_between' => 'numeric',
        'email' => 'email',
        'url' => 'url',
        'array' => 'array',
        'json' => 'json',
    ];

    /**
     * RuleParser constructor.
     * @param $rule
     */
    public function __construct($rule)
    {
        if (is_string($rule)) {
            $rule = explode('|', $rule);
        }
        $this->rule = $this->strtolower($rule);
        $this->parseRule();
    }

    /**
     * 是否必须
     * @return bool
     */
    public function required()
    {
        return (bool)in_array('required', $this->rule);
    }

    /**
     * 类型
     * @return string
     */
    public function type()
    {
        $typeInRule = $this->typeInRule();
        foreach ($this->ruleTypeToViewType as $ruleType => $viewType) {
            if (in_array($ruleType, $typeInRule)) {
                return $viewType;
            }
        }

        foreach ($typeInRule as $type) {
            if (class_exists($type)) {
                return $type;
            }
        }

        return 'string';
    }

    /**
     * 获取对应的php语法中定义的数据类型
     * @return string
     */
    public function phpType()
    {
        foreach ($this->ruleTypeToPhpType as $ruleType => $phpType) {
            if (in_array($ruleType, $this->typeInRule())) {
                return $phpType;
            }
        }

        return 'string';
    }

    /**
     * 类型详情
     * @return string
     */
    public function typeDetail()
    {
        $type = $this->type();
        if ('string' == $type) {
            $max = $this->max();
            $min = $this->min();
            $tmp = [];
            if ($min > 0) {
                $tmp[] = $min;
            }
            if ($max > 0) {
                $tmp[] = $max;
            }

            if (!empty($tmp)) {
                return 'string(' . implode(',', $tmp) . ')';
            }
        }

        return $type;
    }

    /**
     * 获取max的值
     * @return mixed
     */
    public function max()
    {
        return $this->max;
    }

    /**
     * 获取min的值
     * @return mixed
     */
    public function min()
    {
        return $this->min;
    }

    /**
     * 获取in的值
     * @return mixed
     */
    public function in()
    {
        return $this->in;
    }

    /**
     * 获取默认值
     */
    public function defaultValue()
    {
        return null;
    }

    /**
     * @return string[]
     */
    protected function typeInRule()
    {
        $tmp = [];
        foreach ($this->rule as $k => $value) {

            if (is_string($value)) {
                if (preg_match('/|/', $value)) {
                    $arr = explode('|', $value);
                    $tmp[] = array_shift($arr);
                    continue;
                }

                $arr = explode(':', $value);
                $tmp[] = array_shift($arr);
                continue;
            }
        }
        return $tmp;
    }

    /**
     * 解析rule
     */
    protected function parseRule()
    {
        foreach ($this->rule as $rule) {
            if (is_callable($rule)) {
                continue;
            }

            if (!preg_match('/:/', $rule)) {
                continue;
            }
            list($name, $params) = explode(':', $rule);
            if ('max' === $name) {
                $this->max = (int)str_ireplace(['(', ')'], '', $params);
            }
            if ('min' === $name) {
                $this->min = (int)str_ireplace(['(', ')'], '', $params);
            }

            if ('in' === $name) {
                $this->in = explode(',', $params);
            }
        }
    }

    /**
     * 首字母大写
     * @param $rule
     * @return mixed]
     */
    protected function ucfirst($rule)
    {
        foreach ($rule as &$name) {
            $name = ucfirst($name);
        }

        return $rule;
    }

    /**
     * 转换成小写
     * @param $rule
     * @return mixed
     */
    protected function strtolower($rule)
    {
        foreach ($rule as &$name) {
            //数据类型有可能是类名，如果是类名称，则不转化
            if (is_callable($name) or class_exists($name)) {
                continue;
            }
            $name = strtolower($name);
        }

        return $rule;
    }
}
