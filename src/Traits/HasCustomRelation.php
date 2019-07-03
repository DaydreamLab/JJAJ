<?php

namespace DaydreamLab\JJAJ\Traits;


use DaydreamLab\JJAJ\Helpers\Helper;

trait HasCustomRelation
{
    private static $custom_relations = [];


    public function __call($name, $arguments)
    {
        if (static::hasCustomRelation($name))
        {
            return call_user_func(static::$custom_relations[$name], $this);
        }

        return parent::__call($name, $arguments);
    }



    public function __get($name)
    {
        if (static::hasCustomRelation($name))
        {
            if ($this->relationLoaded($name))
            {
                return $this->relations[$name];
            }
            return $this->getRelationshipFromMethod($name);
        }

        return parent::__get($name);
    }



    public static function addCustomRelation($name, $closure)
    {
        static::$custom_relations[$name] = $closure;
    }



    public static function hasCustomRelation($name)
    {
        return array_key_exists($name, static::$custom_relations);
    }
}
