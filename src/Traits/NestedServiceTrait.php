<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait NestedServiceTrait
{
    public function addNested(Collection $input)
    {
        $item = $this->repo->addNested($input);
        if ($item)
        {
           $this->status    = Str::upper(Str::snake($this->type.'CreateNestedSuccess'));
           $this->response  = $item;
        }
        else
        {
           $this->status    = Str::upper(Str::snake($this->type.'CreateNestedFail'));
           $this->response  = null;
        }
        return $item;
    }


    public function checkPathExist(Collection $input)
    {
        if($this->tablePropertyExist('path'))
        {
            $same = $this->findBy('path', '=', $input->get('path'))->first();
            if ($same && $same->id != $input->get('id'))
            {
                $this->status =  Str::upper(Str::snake($this->type.'StoreNestedWithExistPath'));
                $this->response = false;
                return true;
            }
        }

        return false;
    }


    public function modifyNested(Collection $input)
    {
        $modify = $this->repo->modifyNested($input);
        if ($modify)
        {
            $this->status   = Str::upper(Str::snake($this->type.'UpdateNestedSuccess'));
            $this->response = null;
        }
        else
        {
            $this->status   = Str::upper(Str::snake($this->type.'UpdateNestedFail'));
            $this->response = null;
        }

        return $modify;
    }


    public function orderingNested(Collection $input , $orderingKey = 'ordering')
    {
        $modify = $this->repo->orderingNested($input, $orderingKey);
        if ($modify)
        {
            $this->status   = Str::upper(Str::snake($this->type.'UpdateOrderingNestedSuccess'));
            $this->response = null;
        }
        else
        {
            $this->status   = Str::upper(Str::snake($this->type.'UpdateOrderingNestedFail'));
            $this->response = null;
        }

        return $modify;

    }


    public function removeNested(Collection $input)
    {
        $result = $this->repo->removeNested($input);
        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteNestedSuccess'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteNestedFail'));
        }

        return $result;
    }


    public function storeNested(Collection $input)
    {
        if ($this->checkPathExist($input))
        {
            $this->status = Str::upper(Str::snake($this->type)) . '_STORE_NESTED_WITH_EXIST_PATH';
            $this->response = null;

            return false;
        }

        if (InputHelper::null($input, 'id')) {
            return $this->addNested($input);
        }
        else {
            $input->put('lock_by', 0);
            $input->put('lock_at', null);
            return $this->modifyNested($input);
        }
    }

}