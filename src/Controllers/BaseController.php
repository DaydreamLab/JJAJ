<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Services\BaseService;
use DaydreamLab\JJAJ\Traits\ApiJsonResponse;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use ApiJsonResponse;

    protected $service;

    protected $package;

    protected $modelName;

    protected $modelType;

    protected $error = false;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }
}
