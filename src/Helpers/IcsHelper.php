<?php

namespace DaydreamLab\JJAJ\Helpers;

use Carbon\Carbon;

class IcsHelper {

    public static function generator($params)
    {
        return self::filter_linelimit('BEGIN:VCALENDAR'. PHP_EOL .
            'VERSION:2.0'.  PHP_EOL .
            'PRODID:' . config('app.name') . PHP_EOL .
            'NAME:' . $params['title'] . PHP_EOL .
            'BEGIN:VEVENT'. PHP_EOL .
            'DTEND:' . Carbon::parse($params['DTEND'])->tz($params['tz'])->format('Ymd\THis') . PHP_EOL .
            'UID:' . md5($params['title']) . PHP_EOL .
            'DTSTAMP:' . now()->format('Ymd\This') . PHP_EOL .
            'LOCATION:' . addslashes($params['locationName']) . PHP_EOL .
            'DESCRIPTION:' . addslashes($params['title']) . PHP_EOL .
            'URL;VALUE=URI:' . $params['url'] . PHP_EOL .
            'SUMMARY:' . addslashes($params['title']) . PHP_EOL .
            'DTSTART:' .  Carbon::parse($params['DTSTART'])->tz($params['tz'])->format('Ymd\THis') . PHP_EOL .
            'END:VEVENT' . PHP_EOL .
            'END:VCALENDAR');
    }


    public static function filter_linelimit($input, $lineLimit = 70)
    {
        // Variables
        $output = '';
        $line = '';
        $pos = 0;

        // Iterate over string
        while ($pos < strlen($input)) {
            // Find newlines
            $newLinepos = strpos($input, "\n", $pos + 1);
            if (!$newLinepos)
                $newLinepos = strlen($input);
            $line = substr($input, $pos, $newLinepos - $pos);

            if (strlen($line) <= $lineLimit) {
                $output .= $line;
            } else {
                // First line cut-off limit is $lineLimit
                $output .= substr($line, 0, $lineLimit);
                $line = substr($line, $lineLimit);

                // Subsequent line cut-off limit is $lineLimit - 1 due to the leading white space
                $output .= "\n " . substr($line, 0, $lineLimit - 1);

                while (strlen($line) > $lineLimit - 1){
                    $line = substr($line, $lineLimit - 1);
                    $output .= "\n " . substr($line, 0, $lineLimit - 1);
                }
            }
            $pos = $newLinepos;
        }

        return $output;
    }
}
