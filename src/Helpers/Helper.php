<?php
namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Str;

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

    public static function convertTableName($input)
    {
        $input_snake = Str::snake($input);
        $items = explode('_', $input_snake);
        $snake = '';

        for ($i = 0 ; $i < count($items) ; $i++) {


            if ($i == 0 || $i == count($items) - 1) {
                $snake .=ucfirst($items[$i] . 's');
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

        return $type;
    }



}