<?php

namespace DaydreamLab\JJAJ\Requests;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;


class BaseRequest extends FormRequest
{
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function authorize()
    {
        return true;
    }


    protected function failedValidation(Validator $validator)
    {
        if (env('APP_DEBUG')) {
            throw new HttpResponseException(ResponseHelper::genResponse('INPUT_INVALID', $validator->errors()));
        }
        else {
            throw new HttpResponseException(ResponseHelper::genResponse('INPUT_INVALID', null));
        }
    }

    public function rulesInput()
    {
        $need = [];
        $keys = array_keys($this->rules());

        foreach ($keys as $key) {
            if (!preg_match('/.+\*+/', $key)){
                $need[] = $key;
            }
        }

        $collect = collect($this->only($need));
        $collect->each(function ($item, $key) use ($collect) {
            $collect->{$key} = $item;
        });
        return $collect;
    }
}