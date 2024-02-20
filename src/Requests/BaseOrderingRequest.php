<?php

namespace DaydreamLab\JJAJ\Requests;

class BaseOrderingRequest extends AdminRequest
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
            'id'            => 'required|integer',
            'ordering'      => 'nullable|integer',
        ];

        return array_merge(parent::rules(), $rules);
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
