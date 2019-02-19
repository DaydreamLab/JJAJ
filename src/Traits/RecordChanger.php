<?php

namespace DaydreamLab\JJAJ\Traits;

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
                    $item->created_by = $user->id;
                }
                else
                {
                    $item->created_by = 1;
                }
            }
        });


        static::updating(function ($item) use ($user) {
            if ($user) {
                $item->updated_by = $user->id;
            }
        });
    }
}