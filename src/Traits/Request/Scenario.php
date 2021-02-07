<?php

namespace Smart\Common\Traits\Request;

trait Scenario
{
    /**
     * 当前场景
     * @var string
     */
    public $scenario;

    /**
     * 验证之前的准备
     */
    public function prepareForValidation()
    {
        parent::prepareForValidation();

        //将接口名称当做当前场景
        $this->setScenario(
            $this->route()->getActionMethod()
        );
    }

    /**
     * 设置当前场景
     * @param $scenario
     * @return $this
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;

        return $this;
    }
}
