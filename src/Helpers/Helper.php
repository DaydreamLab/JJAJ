<?php
namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Helper {

    public static function show($data)
    {
        $args = func_get_args();
        // Print Multiple values
        if (count($args) > 1)
        {
            $prints = array();
            $i = 1;
            foreach ($args as $arg)
            {
                $prints[] = "[Value " . $i . "]\n" . print_r($arg, 1);
                $i++;
            }
            echo '<pre>' . implode("\n\n", $prints) . '</pre>';
        }
        else
        {
            // Print one value.
            echo '<pre>' . print_r($data, 1) . '</pre>';
        }
    }


    public static function collect($data)
    {
        $collect = new Collection($data);
        $collect->each(function ($item, $key) use ($collect) {
            $collect->{$key} = $item;
        });
        return $collect;
    }

    public static function tablePropertyExist($model, $property)
    {
        return Schema::hasColumn($model->getTable(), $property);
    }

    public static function hasPermission($item_viewlevel, $user_viewlevel)
    {
        return count(array_intersect($item_viewlevel, $user_viewlevel)) === count($item_viewlevel) ? 1 : 0;
    }

}