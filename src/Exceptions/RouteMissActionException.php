<?php

namespace Smart\Common\Exceptions;

use Throwable;

/**
 * 路由未能匹配到action的异常
 */
class RouteMissActionException extends \Exception
{
    public function __construct($message = '路由未能匹配到action', $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
