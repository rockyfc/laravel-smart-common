<?php

namespace Smart\Common\Exceptions;

use Throwable;

/**
 * 路由未能匹配到action的异常
 */
class SmartSourceNotFoundException extends \Exception
{
    public function __construct($message = '禁止使用', $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
