<?php

namespace DaydreamLab\JJAJ\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
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

    public function setLimit($limit)
    {
        if ($limit && $limit != ''){
            self::$limit = $limit;
        }
    }

    public function setOrdering($ordering)
    {
        if ($ordering && $ordering != ''){
            self::$ordering = $ordering;
        }
    }
}