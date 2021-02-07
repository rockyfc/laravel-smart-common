<?php

namespace Smart\Common\Traits\Resource;

trait Scenario
{
    /**
     * 当前场景
     * @var string
     */
    public $scenario;

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
