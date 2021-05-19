<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Exception;
use Throwable;

class BaseException extends Exception
{
    public $status;

    public $response;

    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
