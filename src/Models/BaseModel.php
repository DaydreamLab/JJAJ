<?php

namespace DaydreamLab\JJAJ\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $limit = 25;

    /*
     * true: should search language
     * false: shouldn't search language
     */
    protected $language = false;

    protected $order_by = 'id';

    protected $order = 'desc';

    protected $state = true;


    public static function boot()
    {
        parent::boot();
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
        for ($j = 0 ; $j < $depth -1; $j++) {
            $str .= '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }


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


    public function hasAttribute($attribute)
    {
        return in_array($attribute, $this->fillable);
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
}
