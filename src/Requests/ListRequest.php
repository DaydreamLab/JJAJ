<?php

namespace DaydreamLab\JJAJ\Requests;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    protected $searchKeys = ['title', 'description'];

    public function rules()
    {
        return [
            'search'        => 'nullable|string',
            'searchKeys'    => 'nullable|array',
            'page'          => 'nullable|integer',
            'limit'         => 'nullable|integer',
            'orderBy'      => 'nullable|string',
            'order'      => [
                'nullable',
                Rule::in(['asc', 'desc'])
            ],
            'paginate'      => [
                'nullable',
                Rule::in([0,1])
            ],
        ];
    }


    public function validated()
    {
        $validated = parent::validated();

        if (!$validated->get('searchKeys')) {
            $validated->put('searchKeys', $this->searchKeys);
        }

        if (!$validated->get('paginate')) {
            $validated->put('paginate', 1);
        }

        if ($orderBy = $validated->get('orderBy')) {
            $validated->put('order_by', $orderBy);
            $validated->forget('orderBy');
        }

        return $validated;
    }
}
