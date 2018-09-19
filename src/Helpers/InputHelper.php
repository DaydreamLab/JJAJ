<?php

namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Collection;

class InputHelper
{
    public static function null(Collection $input, $key)
    {
        return $input->get($key) == null || $input->get($key) == '' ? true : false;
    }

}