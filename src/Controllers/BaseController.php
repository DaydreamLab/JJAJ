<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Exceptions\BaseException;
use DaydreamLab\JJAJ\Services\BaseService;
use DaydreamLab\JJAJ\Traits\ApiJsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

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
