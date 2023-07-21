<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('show')) {
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

if (!function_exists('showLogTime')) {
    function showLogTime()
    {
        $sum = 0;
        foreach (DB::getQueryLog() as $log) {
            $sum += $log['time'];
        }

        show('time:' . $sum);
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

if (!function_exists('showLogCount')) {
    function showLogCount($str = null)
    {
        show($str ? $str . ':' . count(DB::getQueryLog()) : count(DB::getQueryLog()));
    }
}

if (!function_exists('getJson')) {
    function getJson($path, $assoc = true)
    {
        return json_decode(file_get_contents($path), $assoc);
    }
}


if (! function_exists('getClosuresInfo')) {
    function getClosuresInfo($closures)
    {
        $result = [];
        foreach ($closures as $closure) {
            $reflection = new ReflectionFunction($closure);
            $filename = $reflection->getFileName();
            $startLine = $reflection->getStartLine();
            $endLine = $reflection->getEndLine();

            $lines = file($filename);
            $result[] = implode("", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
        }

        return $result;
    }
}


if (! function_exists('arrayToXmlStr')) {
    function arrayToXmlStr($array, $rootElement = '<?xml version="1.0" encoding="UTF-8"?><root></root>', $xml = null)
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement($rootElement ?: '<root/>');
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                arrayToXmlStr($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, $value);
            }
        }

        return $xml->asXML();
    }
}


if (! function_exists('xmlStrToArray')) {
    function xmlStrToArray($xmlStr)
    {
        $xml = simplexml_load_string($xmlStr, "SimpleXMLElement", LIBXML_NOCDATA);
        return json_decode(json_encode($xml), true);
    }
}
