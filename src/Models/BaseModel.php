<?php

namespace DaydreamLab\JJAJ\Models;

use DaydreamLab\JJAJ\Helpers\Helper;
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


    public function creator()
    {
        $creator = $this->hasOne(User::class, 'id', 'created_by')->first();

        return $creator;
    }


    public function getCreatorAttribute()
    {
        $creator = $this->creator() ;

        return $creator ? $creator->nickname : null;
    }


    public function getDepthAttribute()
    {
        return $this->ancestors->count();
    }


    public function getLimit()
    {
        return $this->limit;
    }


    public function getLockerAttribute()
    {
        return $this->locker();
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
        for ($j = 0 ; $j < $depth -1; $j++) {
            $str .= '.';
        }

        $str .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        if($depth !== 0)
        {
            $str .= '<sup>|_</sup> ';
        }

        //return $depth - 1 == 0 ? $this->title : $str . ' '. $this->title;
        return $depth == 0 || $depth == 1 ? $this->title : $str . ' '. $this->title;
    }


    public function getTreeListTitleAttribute()
    {
        $depth = $this->depth;
        $str = '';
        for ($j = 0 ; $j < $depth ; $j++) {
            $str .= '-';
        }

        return $depth == 0  ? $this->title : $str . ' '. $this->title;
    }


    public function getUpdaterAttribute()
    {
        $updater = $this->updater();
        return $updater ? $updater->nickname : null;
    }


    public function locker()
    {
        $locker =  $this->hasOne(User::class, 'id', 'locked_by')->first();
        return $locker ? $locker->nickname : null;
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


    public function updater()
    {
        $updater =  $this->hasOne(User::class, 'id', 'updated_by')->first();
        return $updater;
    }
}