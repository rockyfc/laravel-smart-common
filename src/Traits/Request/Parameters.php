<?php

namespace Smart\Common\Traits\Request;

use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait Parameters
{
    /**
     * 获取数据查询条件
     * @return mixed
     */
    public function getFilter()
    {
        return (array)$this->input('filter', []);
    }

    public function getRelations()
    {
        return (string)$this->input('relations');
    }

    /**
     * 获取关联对象字段
     * @throws ValidationException
     * @return mixed
     */
    public function getFilteredRelations()
    {
        try {
            $rs = [];
            if ($relations = $this->input('relations')) {
                foreach (explode(',', $relations) as $relation) {
                    //$callback(Str::camel($relation));
                    $rs[$relation] = function ($query) {
                        //默认给每个关联查询都加上一个limit，防止超大数据查询
                        $query->limit(15);
                    };
                }
            }

            return $rs;
        } catch (RelationNotFoundException $e) {
            throw  ValidationException::withMessages(['relations' => 'relations参数不正确']);
        }
    }

    /**
     * 获取每页显示的数量
     * @return mixed
     */
    public function getPerPage()
    {
        return $this->input('per_page');
    }

    /**
     * 解析排序值
     * @throws ValidationException
     */
    public function getResolvedSorts()
    {
        $sort = $this->getSort();
        if (!$sort) {
            return [];
        }
        $arr = explode(',', $sort);
        $allowed = $this->sorts();

        $rs = [];
        foreach ($arr as $value) {
            $value = trim($value);
            if (!$value) {
                continue;
            }

            $type = 'asc';
            if (preg_match('/-/', $value)) {
                $type = 'desc';
                $value = trim($value, '-');
            }

            if (!in_array($value, $allowed)) {
                throw  ValidationException::withMessages(['relations' => $value . '字段不允许排序']);
            }

            $rs[] = [$value, $type];
        }

        return $rs;
    }

    /**
     * 获取客户端请求的字段
     * @return mixed
     */
    public function getFields()
    {
        return (string)$this->input('fields', '*');
    }

    /* public function getFilteredRelations2()
     {
         if($relations = $this->getRelations()){
             $relations = explode(',',$relations);
         }

         $fields = $this->getFields();
         foreach($fields as $field){

         }
     }*/

    protected function parseRelation()
    {
    }

    /**
     * 获取排序
     * @return string
     */
    protected function getSort()
    {
        return (string)$this->input('sort');
    }
}
