<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Http\Request;

trait AuthApiUser
{
    /**
     * @param Request $request
     */
    public function user($request)
    {
        return $request->user('api');
    }
}
