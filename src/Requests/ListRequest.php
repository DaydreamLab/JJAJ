<?php

namespace DaydreamLab\Requests;

use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'limit'         => 'nullable|integer',
            'ordering'      => [
                'nullable',
                'alpha',
                Rule::in(['asc', 'desc'])
            ]
        ];
    }
}