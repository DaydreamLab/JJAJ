<?php

namespace DaydreamLab\JJAJ\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class GogoWalkApiResponseException extends HttpResponseException
{
    public function __construct(Response $response)
    {
        parent::__construct($response);
    }
}