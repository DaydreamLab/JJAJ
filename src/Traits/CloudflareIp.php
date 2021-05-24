<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Http\Request;

trait CloudflareIp
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getCloudflareIp($request)
    {
        return isset($_SERVER['HTTP_CF_CONNECTING_IP'])
            ? $_SERVER['HTTP_CF_CONNECTING_IP']
            : $request->ip();
    }
}
