<?php

namespace DaydreamLab\JJAJ\Traits;

trait FormatFileSize
{
    public function formatFileSize($size)
    {
        if ($size < 1000) {
            return $size.'Bytes';
        } elseif ($size > 1000 && $size < pow(1000, 2)) {
            return ceil($size / 1000) . 'KB';
        } elseif ($size > pow(1000, 2) && $size < pow(1000,3)) {
            return round($size / pow(1000, 2) . $size % pow(1000,2), 2) . 'MB';
        } else {
            return round($size / pow(1000, 3) . $size % pow(1000,3), 2) . 'GB';
        }
    }
}
