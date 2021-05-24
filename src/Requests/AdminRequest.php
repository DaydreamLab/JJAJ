<?php

namespace DaydreamLab\JJAJ\Requests;

class AdminRequest extends BaseRequest
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


    public function rules()
    {
        return [];
    }
}
