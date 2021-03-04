<?php

namespace DaydreamLab\JJAJ\Traits;

use Carbon\Carbon;

trait FormatDateTime
{
    public function getDateTimeString($dateTime, $tz, $format = null)
    {
        return  $dateTime
            ? ($format
                ? self::parse($dateTime, $tz)->format($format)
                : self::parse($dateTime, $tz)->toDateTimeString()
            )
            : null;
    }


    public function parse($dateTime, $tz)
    {
        return Carbon::parse($dateTime, config('app.timezone'))->tz($tz);
    }
}