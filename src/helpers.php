<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('show')) {
    function show($data)
    {
        $args = func_get_args();
        // Print Multiple values
        if (count($args) > 1) {
            $prints = array();
            $i = 1;
            foreach ($args as $arg) {
                $prints[] = "[Value " . $i . "]\n" . print_r($arg, 1);
                $i++;
            }
            echo '<pre>' . implode("\n\n", $prints) . '</pre>';
        } else {
            // Print one value.
            echo '<pre>' . print_r($data, 1) . '</pre>';
        }
    }
}

if (!function_exists('startLog')) {
    function startLog()
    {
        DB::connection()->enableQueryLog();
    }
}

if (!function_exists('showLog')) {
    function showLog()
    {
        show(DB::getQueryLog());
    }
}

if (!function_exists('flushLog')) {
    function flushLog()
    {
        DB::connection()->flushQueryLog();
    }
}

if (!function_exists('stopLog')) {
    function stopLog()
    {
        DB::connection()->disableQueryLog();
    }
}
