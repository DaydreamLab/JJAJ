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
        if($this->tablePropertyExist('path'))
        {
            $same = $this->findBy('path', '=', $input->get('path'))->first();
            if ($same)
            {
                $this->status =  Str::upper(Str::snake($this->type.'CreateNestedWithExistPath'));
                $this->response = false;
                return false;
            }
        }

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


    public function orderingNested(Collection $input)
    {
        $modify = $this->repo->orderingNested($input);
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