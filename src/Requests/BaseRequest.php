<?php

namespace DaydreamLab\JJAJ\Requests;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

class BaseRequest extends FormRequest
{
    protected $apiMethod = null;

    protected $modelName;

    protected $package;

    public function authorize()
    {
        if (config('app.seeding')) {
            return true;
        } else {
            if (!$this->apiMethod) {
                return true;
            } else {
                $apis = $this->user()->apis;
                if ($this->apiMethod == 'store'. $this->modelName) {
                    $method = $this->get('id')
                        ? 'edit' . $this->modelName
                        : 'add' . $this->modelName;
                    return $apis->filter(function ($api) use ($method) {
                        return $api->method == $method;
                    })->count();
                } else {
                    return $apis->filter(function ($api) {
                        return $api->method == $this->apiMethod;
                    })->count();
                }
            }
        }
    }


    protected function failedValidation(Validator $validator)
    {
        if (config('app.debug')) {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake('InvalidInput')),
                    $validator->errors(),
                    $this->package,
                    $this->modelName
                ));
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake('InvalidInput')),
                    $validator->errors(),
                    $this->package,
                    $this->modelName
                ));
        }
    }

    public function user($guard = 'api')
    {
        return parent::user($guard);
    }

    public function validated()
    {
        return collect(parent::validated());
    }
}
