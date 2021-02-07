<?php

namespace Smart\Common\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 接口返回值的字段过滤，允许用户按需获取
 *
 * @author Rocky
 */
class ApiActiveFilter
{
    /**
     * 分页和
     * @var
     */
    public $links;

    /**
     * meta信息
     * @var
     */
    public $meta;

    /**
     * @var Request
     */
    protected $request;

    /**
     * 输入参数名称
     * @var string
     */
    protected $fieldsName = 'fields';

    /**
     * @param $request
     * @param Closure $next
     * @param null $guard
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $this->request = $request;

        /** @var JsonResponse $response */
        $response = $next($request);
        if (!($response instanceof JsonResponse)) {
            return $response;
        }

        $resource = $response->getData();
        $data = $this->toArray($resource);

        //如果带有分页信息，一定会存在data索引
        if (isset($data['links'])) {
            $data['data'] = $this->filter($data['data']);

            return $response->setData($data);
        }

        //判断用户是否设置了外层数据包裹
        if (JsonResource::$wrap) {
            if (!isset($data[JsonResource::$wrap])) {
                return $response->setData($data);
            }
            $data = $data[JsonResource::$wrap];
        }

        //如果第一个所谓是数字，说明是一个list
        if (isset($data[0])) {
            return $response->setData($this->filter($data));
        }

        $tpl = $this->fieldArray();
        $rootFields = $this->extractRootFields($tpl) ?: ['*'];

        return $response->setData(
            $this->filterItem($data, $rootFields)
        );
    }

    /**
     * 过滤一个结果集
     *
     * @param array $list
     * @param null|string $prefix
     * @return array
     */
    protected function filter(array $list, string $prefix = null)
    {
        $tpl = $this->fieldArray();
        $rootFields = $this->extractRootFields($tpl) ?: ['*'];
        $data = [];
        foreach ($list as $item) {
            $data[] = $this->filterItem($item, $rootFields);
        }

        return $data;
    }

    /**
     * 过滤掉一条资源
     * @param $item
     * @param array $rootFields
     * @param null $prefix
     * @return array
     */
    protected function filterItem($item, array $rootFields = [], $prefix = null)
    {
        $tpl = $this->fieldArray();
        if ([] == $rootFields or $rootFields == ['*']) {
            return $item;
        }

        $tmp = [];
        foreach ($item as $column => $value) {
            if (!in_array($column, $rootFields)) {
                continue;
            }

            if (is_int($column)) {
                $rootFields = $this->extractFieldsFor($tpl, $prefix);
                $rootFields = $this->extractRootFields($rootFields);

                $tmp[$column] = $this->filterItem($value, $rootFields, $prefix);

                continue;
            }

            if (is_array($value)) {
                $prefix = $prefix ? $prefix . '.' . $column : $column;
                $rootFields = $this->extractFieldsFor($tpl, $prefix);
                $rootFields = $this->extractRootFields($rootFields);

                $tmp[$column] = $this->filterItem($value, $rootFields, $prefix);

                continue;
            }

            $tmp[$column] = $value;
        }

        return $tmp;
    }

    /**
     * 将结果集转化成数组
     * @param $data
     * @return array
     */
    protected function toArray(&$data)
    {
        $data = (array)$data;
        foreach ($data as $key => &$item) {
            if (is_object($item) or is_array($item)) {
                $this->toArray($item);

                continue;
            }
        }

        return $data;
    }

    /**
     * 获取查询字符串中的相关参数
     * @return mixed
     */
    protected function rawFields()
    {
        return $this->request->input($this->fieldsName, '*');
    }

    /**
     * 将查询字符串格式化为数组
     * @return array|string[]
     */
    protected function fieldArray()
    {
        $strField = $this->rawFields();
        if (empty($strField)) {
            return ['*'];
        }
        $arr = explode(',', $strField);

        return $this->removeEmpty($arr);
    }

    /**
     * 获取数组根节点字段
     * @param array $fields
     * @return array
     */
    protected function extractRootFields(array $fields)
    {
        $result = [];

        foreach ($fields as $field) {
            $result[] = current(explode('.', $field, 2));
        }

        if (in_array('*', $result, true)) {
            $result = [];
        }

        return array_unique($result);
    }

    /**
     * 根据根节点，获取子集字段
     * @param array $fields
     * @param $rootField
     * @return array
     */
    protected function extractFieldsFor(array $fields, $rootField)
    {
        $result = [];

        foreach ($fields as $field) {
            if (0 === strpos($field, "{$rootField}.")) {
                $result[] = preg_replace('/^' . preg_quote($rootField, '/') . '\./i', '', $field);
            }
        }

        return array_unique($result);
    }

    /**
     * 去空
     * @param array $array
     * @return array
     */
    protected function removeEmpty(array $array)
    {
        return array_filter($array, function ($value) {
            $value = trim($value);
            //去空
            if ('' === $value or null === $value) {
                return false;
            }

            return true;
        });
    }
}
