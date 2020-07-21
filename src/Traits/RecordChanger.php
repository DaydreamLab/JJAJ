<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Support\Facades\Auth;

trait RecordChanger
{
    public static function boot()
    {
        parent::boot();

        $user = Auth::guard('api')->user();

        static::creating(function ($item) use($user)
        {
            if (!$item->created_by)
            {
                if ($user) {
                    if ($user->token()->name == 'Merchant Member') {
                        $item->created_by = 999999990;
                    } else {
                        $item->created_by = $user->id;
                    }
                } else {
                    $item->created_by = 99999993;
                }
            }
        });


        static::updating(function ($item) use ($user) {
            if ($user && $user->token()->name != 'Merchant Member') {
                if ($user->token()->name == 'Merchant Member') {
                    $item->updated_by = 999999990;
                } else {
                    $item->updated_by = $user->id;
                }
            } else {
                if (!$item->updated_by) {
                    $item->updated_by = 999999993;
                }
            }
        });
    }
}