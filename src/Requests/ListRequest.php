<?php

namespace DaydreamLab\JJAJ\Requests;

use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'search'        => 'nullable|string',
            'limit'         => 'nullable|integer',
            'order_by'      => 'nullable|string',
            'order'      => [
                'nullable',
                Rule::in(['asc', 'desc'])
            ],
            'paginate'      => [
                'nullable',
                Rule::in([0,1])
            ],
            'cursorPaginate' => [
                'nullable',
                Rule::in([0, 1])
            ]
        ];
    }
}
