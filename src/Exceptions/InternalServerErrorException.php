<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Exception;
use Throwable;

class InternalServerErrorException extends BaseException
{
    public function __construct($message = "", $code = null, Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
        $this->status = $message;
        $this->response = $code;
    }
}
