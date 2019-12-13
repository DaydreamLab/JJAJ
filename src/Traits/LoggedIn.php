<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Support\Facades\Auth;

trait LoggedIn
{

    public function getUser()
    {
        if($this->user == null)
        {
            $this->user = Auth::guard('api')->user();
        }

        return $this->user;
    }


    public function setUser($user)
    {
        $this->user = $user;
    }
}