<?php

namespace Smart\Common\Traits\Model;

use Illuminate\Support\Facades\Validator;

trait AttributesRules
{
    /**
     * 表字段的验证规则
     *
     * @param mixed $isBail
     * @var array
     */
    // public $rules = [];

    /**
     * 根据验证规则验证当前model中的字段是否合法
     *
     * @param array|null $attributes 要验证的属性
     * @param bool $isBail 如果你希望在某个属性第一次验证失败后停止运行验证规则，则将此参数设置为true
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validate(array $attributes = null, $isBail = true)
    {
        if (null == $attributes or [] == $attributes) {
            // 只验证被修改了的字段
            // $attributes = $this->getDirty();

            // 验证所有字段
            $attributes = $this->attributes;
        }

        $rules = [];
        foreach ($this->attributesRules() as $attribute => $rule) {
            if (!in_array($attribute, array_keys($attributes))) {
                continue;
            }
            if (in_array($attribute, $this->getGuarded())) {
                continue;
            }
            if (is_string($rule)) {
                $rules[$attribute] = (true == $isBail) ? 'bail|' . $rule : $rule;

                continue;
            }

            $rules[$attribute] = $rule;
        }

        return Validator::make($attributes, $rules, [], $this->attributesLabels());
    }

    /**
     * 获取允许被外部注入的属性以及rule
     * @return array
     */
    public function fillableAttributesRules()
    {
        $rules = [];
        foreach ($this->attributesRules() as $column => $rule) {
            if ($this->isFillable($column)) {
                $rules[$column] = $rule;
            }
        }

        return $rules;
    }
}
