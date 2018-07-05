<?php

namespace DaydreamLab\JJAJ\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;


class BaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(ResponseHelper::genResponse('INPUT_INVALID', $validator->errors()));
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