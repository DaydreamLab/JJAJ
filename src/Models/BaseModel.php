<?php

namespace DaydreamLab\JJAJ\Models;

use DaydreamLab\User\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{

    protected $order_by = 'id';

    protected $limit = 25;

    protected $order = 'desc';

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


    public function getDepthAttribute()
    {
        return $this->ancestors->count();
    }


    public function getLimit()
    {
        return $this->limit;
    }


    public function getOrder()
    {
        return $this->order;
    }


    public function getOrderBy()
    {
        return $this->order_by;
    }


    public function getTreeTitleAttribute()
    {
        $depth = $this->depth;
        $str = '';
        for ($i = 0 ; $i < $depth - 1 ; $i++) {
            $str .= ' | ';
        }
        return $depth - 1 == 0 ? $this->title : $str . ' - ' . $this->title;
    }


    public function setLimit($limit)
    {
        if ($limit && $limit != ''){
            $this->limit = $limit;
        }
    }

    public function setOrder($order)
    {
        if ($order && $order != ''){
            $this->order = $order;
        }
    }


    public function setOrderBy($order_by)
    {
        if ($order_by && $order_by != ''){
            $this->order_by = $order_by;
        }
    }


    public function creator()
    {
        $creator = $this->belongsTo(User::class, 'id', 'created_by')->first();
        return $creator ? $creator->nickname : null;
    }


    public function updater()
    {
        $updater =  $this->belongsTo(User::class, 'id', 'updated_by')->first();
        return $updater ? $updater->nickname : null;
    }
}