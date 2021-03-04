<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Support\Facades\Auth;

trait LoggedIn
{
    protected $access_ids = [];
    
    protected $user = null;

    public function getAccessIds()
    {
        if(!$this->access_ids) {
            if($this->getUser()) {
                $this->access_ids = $this->getUser()->access_ids;
            } else {
                $this->access_ids = config('daydreamlab.cms.item.front.access_ids');
            }
        }

        return $this->access_ids;
    }


    public function getUser()
    {
        if ($this->user == null) {
            $this->user = Auth::guard('api')->user();
        }

        return $this->user;
    }


    public function setAccessIds($access_ids)
    {
        $this->access_ids = $access_ids;
    }



    public function setLoggedIn($user, $access_ids = null)
    {
        $this->user = $user;
        $this->access_ids = $access_ids ?: $this->getUser()->access_ids;
    }


    public function setUser($user)
    {
        $this->user = $user;
    }
}