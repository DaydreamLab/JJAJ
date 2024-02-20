<?php

namespace DaydreamLab\JJAJ\Requests;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use Illuminate\Validation\Rule;

class ListRequest extends BaseRequest
{
    protected $searchKeys = ['title', 'description'];

    public function rules()
    {
        $rules = [
            'search'        => 'nullable|string',
            'searchKeys'    => 'nullable|array',
            'page'          => 'nullable|integer',
            'limit'         => 'nullable|integer',
            'orderBy'       => 'nullable|string',
            'order'         => [
                'nullable',
                Rule::in(['asc', 'desc'])
            ],
            'paginate'      => [
                'nullable',
                Rule::in([0,1])
            ],
        ];

        return array_merge(parent::rules(), $rules);
    }


    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (!$validated->get('searchKeys')) {
            $validated->put('searchKeys', $this->searchKeys);
        }

        $paginate = $validated->get('paginate');
        $paginate === 0 || $paginate === '0'
            ? $validated->put('paginate', 0)
            : $validated->put('paginate', 1);

        if ($orderBy = $validated->get('orderBy')) {
            $validated['q'] = $this->q->orderBy($orderBy, $validated->get('order') ? : 'desc');
            $validated->forget('orderBy');
            $validated->forget('order');
        }

        return $validated;
    }
}
