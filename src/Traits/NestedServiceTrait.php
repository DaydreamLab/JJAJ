<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;
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


    public function storeNested(Collection $input)
    {
        if (InputHelper::null($input, 'id')) {
            return $this->addNested($input);
        }
        else {
            return $this->modifyNested($input);
        }
    }



}