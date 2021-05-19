<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends BaseException
{
    public function __construct($message = "", $code = null, Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
        $this->status = $message;
        $this->response = $code;
    }
}
