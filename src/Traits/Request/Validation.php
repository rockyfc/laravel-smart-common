<?php

namespace Smart\Common\Traits\Request;

use Illuminate\Database\Eloquent\Model;

trait Validation
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * 获取model
     * @return Model
     */
    public function model()
    {
        if (!$this->model) {
            $this->model = new $this->modelClass();
        }

        return $this->model;
    }

    /**
     * 是否认证成功
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 字段验证规则
     * @return array
     */
    public function modelRules()
    {
        $model = $this->model();
        if ($model and method_exists($model, 'fillableAttributesRules')) {
            return $model->fillableAttributesRules();
        }

        return [];
    }

    /**
     * 验证失败的时候提示语格式
     * ```php
     *  return [
     *      'required' => ':attribute 必填.'
     *  ];
     * ```
     * @return array
     */
    public function messages()
    {
        return parent::messages();
    }

    /**
     * 返回属性字段和名称的键值对
     * ```php
     *  return [
     *      'title' => '标题',
     *      'content' => '内容',
     *  ];
     * ```
     *
     * @return array
     */
    public function attributes()
    {
        $model = $this->model();
        if ($model and method_exists($model, 'attributesLabels')) {
            return $model->attributesLabels();
        }

        return [];
    }
}
