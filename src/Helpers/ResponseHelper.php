<?php

namespace DaydreamLab\JJAJ\Helpers;

use DaydreamLab\JJAJ\Exceptions\GogoWalkApiResponseException;
use Illuminate\Support\Str;

class ResponseHelper
{
    public static function genResponse($statusString, $data, $package, $modelName, $trans_params = [], $error = false)
    {
        $code = config("constants.default.{$statusString}");
        $message = trans("jjaj::default.{$statusString}", $trans_params);
        if (!$code) {
            $lowerPackage = Str::lower($package);
            $lowerModelName = Str::lower($modelName);
            if ($package) {
                $code = config("constants.{$lowerPackage}.{$lowerModelName}.{$statusString}");
                $message = trans("{$lowerPackage}::{$lowerModelName}.{$statusString}", $trans_params);
            } else {
                $code = config("constants.{$lowerModelName}.{$statusString}");
                $message = trans("{$lowerModelName}.{$statusString}", $trans_params);
            }

            $responseStatusString = Str::upper($modelName).'_'.$statusString;
            if (!$code) {
                $code = config('constants.default.UNDEFINED_STATUS');
                $response['status'] = 'UNDEFINED_STATUS';
                $response['message'] = trans("jjaj::default.UNDEFINED_STATUS",  ['status' => $responseStatusString]);
            } else {
                $response['status'] = $responseStatusString;
                $response['message'] = str_replace('{$ModelName}', $modelName, $message);
                if ($data) {
                    $response['data']= $data;
                } else {
                    $response['data'] = null;
                }
            }
        } else {
            if ($modelName && !$error) {
                $response['status'] = Str::upper($modelName).'_'.$statusString;
            } else {
                $response['status'] = $statusString;
            }
            $response['message'] = str_replace('{$ModelName}', $modelName, $message);

            if ($error) {
                $response['data']= null;
                if (config('app.debug')) {
                    $response['error'] = $data['items'];
                }
            } else {
                $response['data'] = $data ?: null;
            }
        }

        return response()->json($response, $code);
    }



    public function throwApiResponse($status, $data = null, $transParams = [])
    {
        throw new GogoWalkApiResponseException($this->genApiResponse(
            Str::upper(Str::snake($status)),
            $this->formatResponse($data),
            $this->modelName,
            $this->package,
            $transParams = [],
            true
        ));
    }


    public static function genStatus($string)
    {
        return Str::upper(Str::snake($string));
    }
}
