<?php

namespace DaydreamLab\JJAJ\Requests;

class BaseFeaturedOrderingRequest extends AdminRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return parent::authorize();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'id'                => 'required|integer',
            'featuredOrdering'  => 'nullable|integer'
        ];

        return array_merge(parent::rules(), $rules);
    }


    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        $validated->put('featured_ordering', $validated->get('featuredOrdering') ?: 0);
        $validated->forget('featuredOrdering');

        return $validated;
    }
}
