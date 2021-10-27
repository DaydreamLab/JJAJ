<?php

namespace DaydreamLab\JJAJ\Traits;

trait StringReplaceable
{
    public function replaceArray()
    {
        return [];
    }

    public function replace($string)
    {
        foreach ($this->replaceArray() as $key => $value) {
            $string = str_replace($key, $value, $string);
        }

        return $string;
    }
}
