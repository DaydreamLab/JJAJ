<?php

namespace DaydreamLab\JJAJ\Requests;

use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'limit'         => 'nullable|integer',
            'order_by'      => 'nullable|string',
            'order'      => [
                'nullable',
                Rule::in(['asc', 'desc'])
            ]
        ];
    }
}