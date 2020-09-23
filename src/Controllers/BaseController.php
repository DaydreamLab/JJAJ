<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Services\BaseService;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected $service;

    protected $package;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }


    public function response()
    {
        return ResponseHelper::response($this->service->status, $this->service->response);
    }
}