<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Services\BaseService;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected $service;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }
}