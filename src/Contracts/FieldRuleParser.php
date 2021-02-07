<?php

namespace Smart\Common\Contracts;

/**
 * 解析字段的Rule
 */
interface FieldRuleParser
{
    /**
     * 是否必须
     * @return bool
     */
    public function required(): bool;

    /**
     * 获取显示给用户端显示的字段类型
     * @return string
     */
    public function type(): string;

    /**
     * 获取对应的php语法中定义的数据类型
     * @return string
     */
    public function phpType(): string;

    /**
     * 获取显示给用户端显示的字段类型的详细信息
     *
     * 例如：从type()方法获取到的字段类型为string，则本方法处理后的字段类型可能为string(10,20),
     * 是type()，min()，max()方法的综合。
     * @return string
     */
    public function typeDetail(): string;

    /**
     * 获取max的值
     * @return mixed
     */
    public function max(): int;

    /**
     * 获取min的值
     * @return mixed
     */
    public function min(): int;

    /**
     * 获取in的值
     * @return array
     */
    public function in(): array;

    /**
     * 获取默认值
     * @return mixed
     */
    public function defaultValue();
}
