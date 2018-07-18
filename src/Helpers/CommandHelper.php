<?php

namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Str;

class CommandHelper
{
    public static function convertTableName($name)
    {
        $input_snake = Str::snake($name);
        $items = explode('_', $input_snake);
        $snake = '';

        for ($i = 0 ; $i < count($items) ; $i++) {
            if ($i == 0 || $i == count($items) - 1) {
                if (substr($items[$i],-1) == 'y') {
                    $snake .= substr($items[$i],-1, -1) . 'ies';
                }
                else {
                    $snake .=ucfirst($items[$i] . 's');
                }

            }
            else {
                $snake .=ucfirst($items[$i]);
            }
        }

        return Str::snake($snake);
    }


    public static function getType($name)
    {
        $nameapace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $str = explode('\\', $nameapace);
        $type = array_pop($str);
        if ($type == 'Admin' || $type == 'Front') {
            return array_pop($str);
        }

        return $type;
    }


    public static function getParent($name)
    {
        return str_replace('Admin', '',  str_replace('Front', '', $name));
    }

    public static function getParentNameSpace($namespace)
    {
        return str_replace('Admin', '',  str_replace('Front', '', $namespace));
    }
}