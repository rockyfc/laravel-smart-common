<?php

namespace Smart\Common\Traits\Model;

trait AttributesLabels
{
    /**
     * 每一个字段的中文释义
     * @return array
     */
    public function attributesLabels()
    {
        return [];
    }

    /**
     * 获取允许被外部注入的属性的labels
     * @return array
     */
    public function fillableAttributesLabels()
    {
        $labels = [];
        foreach ($this->attributesLabels() as $column => $label) {
            if ($this->isFillable($column)) {
                $labels[$column] = $label;
            }
        }

        return $labels;
    }
}
