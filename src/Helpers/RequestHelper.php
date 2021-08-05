<?php

namespace DaydreamLab\JJAJ\Helpers;

use Carbon\Carbon;
use DaydreamLab\JJAJ\Exceptions\GogoWalkApiResponseException;
use Illuminate\Support\Str;

class RequestHelper
{
    # 處理 belongsToManny 陣列
    public static function handleIntermediateRelation($values)
    {
        if (!is_array($values)) {
            return  [];
        }

        $data = [];
        foreach ($values as $value) {
            $id = $value['id'];
            unset($value['id']);
            $intermediate = $value;
            $data[$id] = $intermediate;
        }

        return $data;
    }


    public static function handleSlidshow($values)
    {
        if (!is_array($values)) {
            return  [];
        }

        $data = [];
        foreach ($values as $value) {
            if (!isset($value['path'])) {
                continue;
            } else {
                $temp = [];
                $temp['path'] = $value['path'];
                $temp['default'] = isset($value['default']) ? $value['default'] : 0;
                $data[] = $temp;
            }
        }

        return  $data;
    }

    /**
     * @param $time
     * @param string $tz
     * @return string
     */
    public static function toSystemTime($time, $tz = 'Asia/Taipei')
    {
        return Carbon::parse($time, $tz)->tz(config('app.timezone'))->toDateTimeString();
    }
}
