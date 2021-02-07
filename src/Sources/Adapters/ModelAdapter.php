<?php

namespace Smart\Common\Sources\Adapters;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\IntegerType;
use Illuminate\Database\Eloquent\Model;
use Smart\Common\Exceptions\SmartSourceNotFoundException;
use Smart\Common\Sources\SmartModel;
use Smart\Common\Sources\SmartModelAttributes;

class ModelAdapter implements SmartModelAttributes, SmartModel
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $columns;

    /**
     * Laravel Model 适配器
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return $this->getTableColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function filters()
    {
        return $this->getTableColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function pages()
    {
        return [
            new Column('page', new IntegerType(), ['default' => 1]),
            new Column('per_page', new IntegerType(), ['default' => $this->model->getPerPage()]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function relations()
    {
        return $this->getTableColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function output()
    {
        return $this->getTableColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function fillableAttributesRules(): array
    {
        $rules = [];
        foreach ($this->attributesRules() as $column => $rule) {
            if ($this->model->isFillable($column)) {
                $rules[$column] = $rule;
            }
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributesLabels(): array
    {
        if ($this->model instanceof SmartModel) {
            return (array)$this->model->attributesLabels();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function attributesRules(): array
    {
        if ($this->model instanceof SmartModel) {
            return (array)$this->model->attributesRules();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function newSmartSource(): SmartModelAttributes
    {
        throw new SmartSourceNotFoundException('禁止访问');
    }

    /**
     * 获取数据表的Schema信息
     *
     * @return array|Column[]
     */
    protected function getTableColumns()
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $this->columns = [];
        $schema = $this->model->getConnection()->getDoctrineSchemaManager();

        try {
            $schema->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            $schema->getDatabasePlatform()->registerDoctrineTypeMapping('tinyint', 'integer');
            $this->columns = $schema->listTableColumns($this->model->getTable());
        } catch (DBALException $e) {
        }

        return $this->columns;
    }
}
