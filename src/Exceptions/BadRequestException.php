<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Exception;
use Throwable;

class BadRequestException extends BaseException
{
    public function __construct($message = "", $code = null, Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
        $this->status = $message;
        $this->response = $code;
    }
}
