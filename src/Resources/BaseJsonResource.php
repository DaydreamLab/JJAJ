<?php

namespace DaydreamLab\JJAJ\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    public function formatDateTime($dateTime, $tz, $format = 'Y-m-d H:i:s')
    {
        if (!$dateTime) {
            return  null;
        }

        return $format
            ? self::parse($dateTime, $tz)->format($format)
            : self::parse($dateTime, $tz)->toDateTimeString();
    }


    public function parse($dateTime, $tz)
    {
        return Carbon::parse($dateTime, config('app.timezone'))->tz($tz);
    }
}
