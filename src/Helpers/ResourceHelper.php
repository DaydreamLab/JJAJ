<?php

namespace DaydreamLab\Dddream\Helpers;

use Carbon\Carbon;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Support\Str;

class ResourceHelper
{
    public static function getDateTimeString($dateTime, $tz, $format = null)
    {
        return $format
            ? self::parse($dateTime, $tz)->format($format)
            : self::parse($dateTime, $tz)->toDateTimeString();
    }


    public static function getImage($image)
    {
        return Str::substr($image, 0, 4) == 'http'
            ? $image
            : ($image
                ? Str::substr(config('app.dingsomething.admin.url'), 0, -1) . $image
                : null
            );
    }


    public static function getMemberInfo($member)
    {
        $str = explode('-', $member->phone);
        return [
            'email' => $member->email,
            'fullName' => $member->fullName,
            'gender' => $member->gender,
            'phoneCode' => $str[0],
            'phoneNumber' => $str[1],
            'merchantNote' => nl2br($member->merchantNote)
        ];
    }


    public static function getRefund($order)
    {
        if ($order->refund == 0) {
            if ($order->paymentMethod == 'Credit') {
                // 有付款記錄回傳 1
                return $order->paymentDetail ? 1 : 0;
            } else if ($order->paymentMethod == 'Linepay') {
                return 1;
            } else {
                return 1;
            }
        }

        return $order->refund;
    }


    public static function parse($dateTime, $tz)
    {
        return Carbon::parse($dateTime, config('app.timezone'))->tz($tz);
    }
}
