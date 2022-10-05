<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Exceptions\BaseException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Throwable;

trait ApiJsonResponse
{
    protected $code = null;

    public function formatResponse($data, $resource, $wrapItems)
    {
        if (!$data) {
            $response = $data;
        } elseif (gettype($data) == 'boolean') {
            $response = null;
        } elseif (gettype($data) == 'array') {
            $response['items'] = $data;
        } elseif ($data instanceof \stdClass) {
            $response['items'] = $resource
                ? new $resource($data)
                : $data;
        } elseif ($data instanceof Model) {
            $response['items'] = $resource
                ? new $resource($data)
                : $data;
        } elseif ($data instanceof Collection) {
            $response =  $resource
                ? new $resource($data, $wrapItems)
                : $data;
        } elseif ($data instanceof LengthAwarePaginator) {
            $response =  new $resource($data);
        } elseif ($data instanceof MessageBag) {
            $response['items'] = $data;
        } else {
        }

        return  $response;
    }


    public function handleException(Throwable $t)
    {
        if ($t instanceof BaseException) {
            $this->service->status = $t->status;
            $this->service->response = $t->response;
            $this->modelName = $t->modelName ?: $this->modelName;
            $this->code = $t->getCode();
        } else {
            $errorResponse =  [
                'type' => get_class($t),
                'line' => $t->getLine(),
                'file' => $t->getFile(),
                'message' => $t->getMessage(),
                'trace' => collect($t->getTrace())->take(10)
            ];
            $this->service->status = 'CatchException';
            $this->service->response = $errorResponse;
            $this->error = true;
        }
    }


    public function response($status, $response, $trans_params = [], $resource = null, $wrapItems = true, $debug = false)
    {
        $statusString   = Str::upper(Str::snake($status));
        $package        = isset($this->package) ? $this->package : null;
        $modelName      = isset($this->modelName) ? $this->modelName : null;
        $error          = isset($this->error) ? $this->error : false;
        $data           = $this->formatResponse($response, $resource, $wrapItems);
        $r              = [];
        $lowerPackage   = Str::lower($package);
        $lowerModelName = Str::lower($modelName);

        $code = config("constants.default.{$statusString}");
        $message = trans("jjaj::default.{$statusString}", $trans_params);

        if (!$code) {
            $code = $package
                ? config("constants.{$lowerPackage}.{$lowerModelName}.{$statusString}")
                : config("constants.{$lowerModelName}.{$statusString}");

            $message = $package
                ? trans("{$lowerPackage}::{$lowerModelName}.{$statusString}", $trans_params)
                : trans("{$lowerModelName}.{$statusString}", $trans_params);
            $responseStatusString = Str::upper($modelName) . '_' . $statusString;
            if (!$code) {
                $code = config('constants.default.UNDEFINED_STATUS');
                $r['status'] = 'UNDEFINED_STATUS';
                $r['message'] = trans("jjaj::default.UNDEFINED_STATUS", ['status' => $responseStatusString]);
            } else {
                $r['status'] = $responseStatusString;
                $r['message'] = str_replace('{$ModelName}', $modelName, $message);
                $r['data'] = $data ?: null;
            }
        } else {
            $r['status'] = ($modelName && !$error)
                ? Str::upper($modelName) . '_' . $statusString
                : $statusString;

            $searchStr = (isset($this->localeModelName) && config('app.locale') != 'en')
                ? '{$ModelName} '
                : '{$ModelName}';
            $replaceStr = $this->localeModelName ?? $modelName;
            $r['message'] = str_replace($searchStr, $replaceStr, $message);
//            $r['message'] = str_replace('{$ModelName}', $modelName, $message);

            $pkgMessageKey = $package
                ? "{$lowerPackage}::{$lowerModelName}.{$statusString}"
                : "{$lowerModelName}.{$statusString}";
            $pkgMessage = trans($pkgMessageKey, $trans_params);
            if ($pkgMessage && $pkgMessage != $pkgMessage) {
                $r['message'] = $pkgMessage;
            }

            if ($error) {
                $r['data'] = null;
                if ($debug || config('app.debug')) {
                    $r['error'] = isset($data['items'])
                        ? $data['items']
                        : $data;
                }
            } else {
                $r['data'] = $data ?: null;
            }
        }
        $r['code'] = $code;

        return response()->json($r, $this->code ?: $code);
    }
}
