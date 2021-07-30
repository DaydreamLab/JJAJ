<?php

namespace DaydreamLab\JJAJ\Helpers;

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
}
