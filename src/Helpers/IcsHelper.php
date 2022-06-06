<?php

namespace DaydreamLab\JJAJ\Helpers;

use Carbon\Carbon;

class IcsHelper {

    public static function generator($params)
    {
        return 'BEGIN:VCALENDAR'. PHP_EOL .
            'VERSION:2.0'.  PHP_EOL .
            'PRODID:' . config('app.name') . PHP_EOL .
            'NAME:' . $params['title'] . PHP_EOL .
            'BEGIN:VEVENT'. PHP_EOL .
            'DTEND:' . Carbon::parse($params['DTEND'])->tz($params['tz'])->format('Ymd\This') . PHP_EOL .
            'UID:' . md5($params['title']) . PHP_EOL .
            'DTSTAMP:' . now()->format('Ymd\This') . PHP_EOL .
            'LOCATION:' . addslashes($params['locationName']) . PHP_EOL .
            'DESCRIPTION:' . addslashes($params['title']) . PHP_EOL .
            'URL;VALUE=URI:' . $params['url'] . PHP_EOL .
            'SUMMARY:' . addslashes($params['title']) . PHP_EOL .
            'DTSTART:' .  Carbon::parse($params['DTSTART'])->tz($params['tz'])->format('Ymd\This') . PHP_EOL .
            'END:VEVENT' . PHP_EOL .
            'END:VCALENDAR';
    }
}
