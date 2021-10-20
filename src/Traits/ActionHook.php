<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Support\Collection;

trait ActionHook
{
    public function afterCheckItem(Collection $input, $item)
    {

    }

    public function afterAdd(Collection $input, $item)
    {

    }


    public function afterModify(Collection $input, $item)
    {

    }


    public function afterRemove(Collection $input, $item)
    {

    }


    public function afterState(Collection $input, $item)
    {

    }


    public function beforeAdd(Collection &$input)
    {

    }


    public function beforeModify(Collection $input, $item)
    {

    }


    public function beforeRemove(Collection $input, $item)
    {

    }


    public function beforeState($state, $item)
    {

    }
}
