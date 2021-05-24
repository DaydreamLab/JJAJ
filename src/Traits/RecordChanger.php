<?php

namespace DaydreamLab\JJAJ\Traits;

trait RecordChanger
{
    /**
     * 999999990: 前台使用者
     * 999999991: 系統
     * 999999992: 外部呼叫
     * 999999993: 未知或沒有設定
     * */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $user = auth()->guard('api')->user();
            if (!$item->created_by) {
                if ($user) {
                    $item->created_by = $user->id;
                } else {
                    $item->created_by = 99999993;
                }
            }
        });


        static::updating(function ($item) {
            $user = auth()->guard('api')->user();

            if ($user) {
                $item->updated_by = $user->id;
            } else {
                if (!$item->updated_by) {
                    $item->updated_by = 999999993;
                }
            }
        });
    }
}
