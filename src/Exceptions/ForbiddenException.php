<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Throwable;

class ForbiddenException extends BaseException
{
    public function __construct($message = "", $code = null, Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
        $this->status = $message;
        $this->response = $code;
    }
}
