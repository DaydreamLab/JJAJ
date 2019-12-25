<?php
namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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


    public static function setEnv($data = [])
    {
        if (!count($data)) {
            return;
        }

        $pattern = '/([^\=]*)\=[^\n]*/';

        $envFile = base_path() . '/.env';
        $lines = file($envFile);
        $newLines = [];
        $line_counter = 0;
        foreach ($lines as $line) {
            preg_match($pattern, $line, $matches);

            if (!count($matches)) {
                $newLines[] = $line;
                $line_counter++;
                continue;
            }

            if (!key_exists(trim($matches[1]), $data)) {
                $newLines[] = $line;
                $line_counter++;
                continue;
            }

            $line = trim($matches[1]) . "={$data[trim($matches[1])]}\n";
            unset($data[trim($matches[1])]);
            $newLines[] = $line;
        }

        // 處理新增 key
        foreach ($data as $key => $value)
        {
            $newLines[] = trim($key) . "={$value}\n";
        }

        $newContent = implode('', $newLines);
        file_put_contents($envFile, $newContent);
    }


    public static function flushLog()
    {
        DB::connection()->flushQueryLog();
    }


    public static function startLog()
    {
        DB::connection()->enableQueryLog();
    }


    public static function showLog()
    {
        self::show(DB::getQueryLog());
    }


    public static function showLogCount()
    {
        self::show(count(DB::getQueryLog()));
    }


    public static function stopLog()
    {
        DB::connection()->disableQueryLog();
    }
}