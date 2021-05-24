<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\User\Models\User\User;

trait UserInfo
{
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }


    public function locker()
    {
        return $this->hasOne(User::class, 'id', 'locked_by');
    }


    public function updater()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function getCreatorNameAttribute()
    {
        return $this->creator ? $this->creator->name : null;
    }


    public function getLockerNameAttribute()
    {
        return $this->locker ? $this->locker->name : null;
    }


    public function getUpdaterNameAttribute()
    {
        return $this->updater ? $this->updater->name : null;
    }
}
