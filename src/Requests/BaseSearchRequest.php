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


    public function validated()
    {
        $validated = parent::validated();

        return $validated;
    }
}
