<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Exceptions\BaseException;
use DaydreamLab\JJAJ\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class BaseController extends Controller
{
    protected $service;

    protected $package;

    protected $modelName;

    protected $modelType;

    protected $error = false;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }


    public function formatResponse($data)
    {
        if (!$data) {
            $response = $data;
        } elseif (gettype($data) == 'boolean') {
            $response = null;
        } elseif (gettype($data) == 'array'){
            $response['items'] = $data;
        }  elseif ($data instanceof Model) {
            $response['items'] = $data;
        } elseif ($data instanceof Collection) {
            $response['items'] = $data;
        } elseif ($data instanceof ResourceCollection) {
            $response = $data;
        } elseif ($data instanceof JsonResource) {
            $response['items'] = $data;
        } else {
        }
        
        return  $response;
    }


    public function handleException(Throwable $t)
    {
        if ($t instanceof BaseException) {
            $this->service->status = $t->status;
            $this->service->staatus = $t->response;
        } else {
            $errorResponse =  [
                'type' => get_class($t),
                'line' => $t->getLine(),
                'file' => $t->getFile(),
                'message' => $t->getMessage()
            ];
            $this->service->status = 'CatchException';
            $this->service->response = $errorResponse;
            $this->error = true;
        }
    }


    public function response($status, $response, $trans_params = [])
    {
        $statusString   = Str::upper(Str::snake($status));
        $package        = $this->package;
        $modelName      = $this->modelName;
        $error          = $this->error;
        $data           = $this->formatResponse($response);
        $r              = [];

        $code = config("constants.default.{$statusString}");
        $message = trans("jjaj::default.{$statusString}", $trans_params);
        if (!$code) {
            $lowerPackage = Str::lower($package);
            $lowerModelName = Str::lower($modelName);

            $code = $package
                ? config("constants.{$lowerPackage}.{$lowerModelName}.{$statusString}")
                : config("constants.{$lowerModelName}.{$statusString}");

            $message = $package
                ? trans("{$lowerPackage}::{$lowerModelName}.{$statusString}", $trans_params)
                : trans("{$lowerModelName}.{$statusString}", $trans_params);

            $responseStatusString = Str::upper($modelName).'_'.$statusString;
            if (!$code) {
                $code = config('constants.default.UNDEFINED_STATUS');
                $r['status'] = 'UNDEFINED_STATUS';
                $r['message'] = trans("jjaj::default.UNDEFINED_STATUS",  ['status' => $responseStatusString]);
            } else {
                $r['status'] = $responseStatusString;
                $r['message'] = str_replace('{$ModelName}', $modelName, $message);
                $r['data'] = $data ?: null;
            }
        } else {
            $r['status'] = ($modelName && !$error)
                ? Str::upper($modelName).'_'.$statusString
                : $statusString;
            $r['message'] = str_replace('{$ModelName}', $modelName, $message);

            if ($error) {
                $r['data']= null;
                if (config('app.debug')) {
                    $r['error'] = $data['items'];
                }
            } else {
                $r['data'] = $data ?: null;
            }
        }

        return response()->json($r, $code);
    }
}
