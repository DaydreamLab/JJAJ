<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Support\Str;

trait Mapping
{
    public function addMapping($item, $input)
    {
        $input->keys()->filter(function ($key) {
            return substr($key, -3, 3) === 'Ids';
        })->values()->each(function ($key) use ($item, $input) {
            $relation = Str::plural(substr($key, 0, -3));
            if (count($paramIds = $input->get($key) ?: [])) {
                $item->{$relation}()->attach($paramIds);
            }
        });

        return ;
    }


    public function modifyMapping($item, $input)
    {
        $input->keys()->filter(function ($key) {
            return substr($key, -3, 3) === 'Ids';
        })->values()->each(function ($key) use ($item, $input) {
            $relation = Str::plural(substr($key, 0, -3));
            if (count($paramIds = $input->get($key) ?: [])) {
                $item->{$relation}()->sync($paramIds);
            }
        });
        return ;
    }


    public function removeMapping($item)
    {
        return ;
    }
}
