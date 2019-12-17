<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Support\Facades\Auth;

trait LoggedIn
{
    public function getAccessIds()
    {
        if(!$this->access_ids)
        {
            if($this->getUser())
            {
                $this->access_ids = $this->getUser()->access_ids;
            }
            else
            {
                $this->access_ids = config('cms.item.front.access_ids');
            }
        }

        return $this->access_ids;
    }


    public function getUser()
    {
        if($this->user == null)
        {
            $this->user = Auth::guard('api')->user();
        }

        return $this->user;
    }



    public function getViewlevels()
    {
        if(!$this->viewlevels)
        {
            if($this->getUser())
            {
                $this->viewlevels = $this->getUser()->viewlevels;
            }
            else
            {
                $this->viewlevels = config('cms.item.front.viewlevels');
            }
        }

        return $this->viewlevels;
    }


    public function setAccessIds($access_ids)
    {
        $this->access_ids = $access_ids;
    }



    public function setLoggedIn($user, $access_ids = null, $viewlevels = null)
    {
        $this->user = $user;
        $this->access_ids = $this->access_ids ?: $this->getUser()->access_ids;
        $this->viewlevels = $this->viewlevels ?: $this->getUser()->viewlevels;
    }


    public function setUser($user)
    {
        $this->user = $user;
    }


    public function setViewlevels($viewlevels)
    {
        $this->viewlevels = $viewlevels;
    }
}