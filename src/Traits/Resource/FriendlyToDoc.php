<?php

namespace Smart\Common\Traits\Resource;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;

trait FriendlyToDoc
{
    /**
     * 访问当前对象的是否是文档程序。
     * @var bool
     */
    public $isDocAccessor = false;

    /**
     * @var Model
     */
    private $model;

    /**
     * 重写，防止没有找到成员变量的时候报错，单纯为了文档程序能正常解析toArray函数
     * @param string $key
     * @return mixed|null
     */
    public function __get($key)
    {
        if (!$this->isDocAccessor) {
            return parent::__get($key);
        }

        if ($this->resource instanceof MissingValue) {
            return null;
        }

        if (!$this->resource or !isset($this->resource->{$key})) {
            return null;
        }

        return parent::__get($key);
    }

    /**
     * 获取model
     * @throws \Exception
     * @return Model
     */
    public function model()
    {
        if (property_exists($this, 'modelClass') and !empty($this->modelClass)) {
            $this->model = new $this->modelClass();
        }

        return $this->model;
    }

    /**
     * 字段释义
     * @throws \Exception
     * @return array
     */
    public function attributesLabels()
    {
        $model = $this->model();
        if (!$model) {
            return [];
        }

        if (method_exists($model, 'attributesLabels')) {
            return $model->attributesLabels();
        }

        return [];
    }

    /**
     * 返回字段规则。
     * 在返回值中，不需要太详细的rules，只需要字段类型即可。
     * @throws \Exception
     * @return array|mixed
     */
    public function attributesRules()
    {
        $model = $this->model();
        if (!$model) {
            return [];
        }

        if (method_exists($model, 'attributesRules')) {
            return $model->attributesRules();
        }

        return [];
    }

    /**
     * @param string $relationship
     * @param null $value
     * @param null $default
     * @return array|MissingValue|mixed
     */
    protected function whenLoaded($relationship, $value = null, $default = null)
    {
        if ($this->isDocAccessor and !$this->resource) {
            return [];
        }

        return parent::whenLoaded(...func_get_args());
    }
}
