<?php

namespace Smart\Common\Comment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionException;

/**
 * 解析器
 */
class Resolver
{
    /**
     * @var FormRequest
     */
    protected $request;

    /**
     * Resolver constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 中是否存在指定的常量数组
     * @param $constName
     * @param $class
     * @throws ReflectionException
     * @return bool
     */
    protected function hasConstInClass($constName, $class)
    {
        return array_key_exists($constName, $const = $this->getClassConstants($class))
            and is_array($const[$constName]);
    }

    /**
     * 获取类中的所有常量
     *
     * @param $class
     * @throws ReflectionException
     * @return array
     */
    protected function getClassConstants($class)
    {
        $ref = new \ReflectionClass($class);

        return $ref->getConstants();
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

    /**
     * 获取配置
     * @return array
     */
    protected function config()
    {
        return array_merge([
            'fieldsName' => 'fields',
            'filterName' => 'filter',
            'relationName' => 'relations',
            'sortName' => 'sort',
        ], config('doc.query'));
    }
}
