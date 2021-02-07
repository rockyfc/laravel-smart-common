<?php

namespace Smart\Common\Sources;

use Smart\Common\Exceptions\SmartSourceNotFoundException;

interface SmartModel
{
    /**
     * @return array
     */
    public function attributesRules(): array;

    /**
     * @return array
     */
    public function fillableAttributesRules(): array;

    /**
     * @return array
     */
    public function attributesLabels(): array;

    /**
     * @throws SmartSourceNotFoundException
     * @return SmartModel|SmartModelAttributes
     */
    public function newSmartSource();
}
