<?php

namespace DaydreamLab\JJAJ\Traits;

use Carbon\Carbon;

trait FormatDateTime
{
    public function getDateTimeString($dateTime, $tz = 'Asia/Taipei', $format = 'Y-m-d H:i:s')
    {
        return  $dateTime
            ? ($format
                ? self::parse($dateTime, $tz)->format($format)
                : self::parse($dateTime, $tz)->toDateTimeString()
            )
            : null;
    }


    public function parse($dateTime, $tz = 'Asia/Taipei')
    {
        return Carbon::parse($dateTime, 'UTC')->tz($tz);
    }
}
