<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Throwable;

class ForbiddenException extends BaseException
{
    public function __construct($message = "", $code = null, Throwable $previous = null, $modelName = null)
    {
        parent::__construct($message, 403, $previous);
        $this->status = $message;
        $this->response = $code;
        $this->modelName = $modelName;
    }
}
