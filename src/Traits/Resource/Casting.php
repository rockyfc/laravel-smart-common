<?php

namespace Smart\Common\Traits\Resource;

use Illuminate\Http\Request;
use Smart\Common\Comment\RuleParser;

/**
 * 资源的返回值进行强制类型转化
 */
trait Casting
{
    /**
     * 将资源转换为数组
     * @param Request $request
     * @throws \Exception
     * @return array
     */
    public function toArray($request)
    {
        return $this->castArray(
            $this->output($request),
            $this->attributesRules()
        );
    }

    /**
     * 转化数组中的元素的类型为attributesRules方法中约定的类型
     * @param array $data
     * @param array $rules
     * @param null $parent
     * @return array
     * @see attributesRules()
     */
    protected function castArray(array $data, array $rules, $parent = null)
    {
        foreach ($data as $attribute => &$value) {
            if (is_array($value)) {
                $value = $this->castArray($value, $rules, $attribute);
            }
            if ($parent) {
                $attribute = $parent . '.' . $attribute;
            }
            if (isset($rules[$attribute])) {
                $value = $this->castAttribute($attribute, $value, $rules);
            }
        }

        return $data;
    }

    /**
     * 将某个字段的值强转为attributesRules方法中约定的类型
     * @param $attribute
     * @param $value
     * @param $rules
     * @return array|bool|float|int|string
     * @see attributesRules()
     */
    protected function castAttribute($attribute, $value, $rules)
    {
        if (is_object($value) and !($value instanceof \Illuminate\Support\Carbon)) {
            return $value;
        }

        $parser = new RuleParser($rules[$attribute]);
        $type = strtolower($parser->phpType());

        if ($type === 'int') {
            //如果是时间类型，则特殊处理
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->timestamp;
            }

            return (int)$value;
        }

        if ($type === 'float') {
            return (float)$value;
        }

        if ($type === 'bool') {
            return (bool)$value;
        }

        if ($type === 'string') {
            return (string)$value;
        }

        if ($type === 'array') {
            return (array)$value;
        }

        return $value;
    }
}
