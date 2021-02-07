<?php

namespace Smart\Common\Exceptions;

use Throwable;

/**
 * Class ResourceMissDataException
 */
class ResourceMissDataException extends \Exception
{
    public function __construct($message = '资源类没有找到返回值', $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
