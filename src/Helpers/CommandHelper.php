<?php

namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Str;

class CommandHelper
{
    public static function convertTableName($name)
    {
        return Str::snake(Str::plural($name));
    }


    public static function getMainName($name, $type)
    {
        return str_replace($type, '', $name);
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