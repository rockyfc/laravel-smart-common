<?php

namespace Smart\Common\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Smart\Common\Sources\Adapters\HttpRequestAdapter;
use Smart\Common\Sources\Adapters\ModelAdapter;
use Smart\Common\Sources\SmartModelAttributes;

trait SmartAttributes
{
    /**
     * @return SmartModelAttributes
     */
    public function newSmartSource()
    {
        if ($this instanceof Model) {
            return new ModelAdapter($this);
        }

        return new HttpRequestAdapter($this);
    }

    /**
     * @return array
     */
    public function fillableAttributesRules(): array
    {
        return $this->newSmartSource()->fillableAttributesRules();
    }
}
