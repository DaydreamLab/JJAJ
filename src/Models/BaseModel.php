<?php

namespace DaydreamLab\JJAJ\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{

    protected static $order_by = 'id';

    protected static $limit = 25;

    protected static $ordering = 'desc';

    protected static function boot()
    {
        parent::boot();

        $user = Auth::guard('api')->user();

        static::creating(function ($item) use($user) {
            if ($user) {
                $item->created_by = $user->id;
            }
        });

        static::updating(function ($item) use ($user) {
            if ($user) {
                $item->updated_by = $user->id;
            }
        });
    }


    public function getLimit()
    {
        return self::$limit;
    }


    public function getOrdering()
    {
        return self::$ordering;
    }


    public function getOrderBy()
    {
        return self::$order_by;
    }


    public static function setLimit($limit)
    {
        if ($limit && $limit != ''){
            self::$limit = $limit;
        }
    }

    public static function setOrdering($ordering)
    {
        if ($ordering && $ordering != ''){
            self::$ordering = $ordering;
        }
    }


    public static function setOrderBy($order_by)
    {
        if ($order_by && $order_by != ''){
            self::$order_by = $order_by;
        }
    }
}