<?php

namespace DaydreamLab\JJAJ\Requests;

class BaseSearchRequest extends ListRequest
{
    public function rules()
    {
        $rules = [
        ];

        return array_merge(parent::rules(), $rules);
    }


    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
