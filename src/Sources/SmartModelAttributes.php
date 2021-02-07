<?php

namespace Smart\Common\Sources;

use Doctrine\DBAL\Schema\Column;

/**
 * Interface Attributes
 */
interface SmartModelAttributes
{
    /**
     * @return Column[]
     */
    public function fields();

    /**
     * @return Column[]
     */
    public function filters();

    /**
     * @return Column[]
     */
    public function pages();

    /**
     * @return Column[]
     */
    public function relations();

    /**
     * @return Column[]
     */
    public function output();
}
