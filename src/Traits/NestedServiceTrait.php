<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\Ec\Models\Product\Admin\ProductAdmin;
use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait NestedServiceTrait
{
    public function addNested(Collection $input)
    {
        $this->canAction('add');
        $item = $this->repo->addNested($input);
        if ($item)
        {

            $item = $this->find($item->id);
            $this->status    = Str::upper(Str::snake($this->type.'CreateNestedSuccess'));
            $this->response  = $item;
        }
        else
        {
            $this->throwResponse($this->type.'CreateNestedFail');
        }

        return $item;
    }


    public function checkPathExist(Collection $input, $parent)
    {
        if($this->repo->getModel()->hasAttribute('path') && $this->repo->getModel()->getTable() != 'assets')
        {
            $copy = $input->toArray();

            $same = $this->repo->findMultiLanguageItem(Helper::collect($copy));
            if ($same && $same->id != $input->get('id'))
            {
                $this->throwResponse($this->type.'StoreNestedWithExistPath',  ['path' => $input->get('path')]);
            }
        }

        return false;
    }


    public function modifyNested(Collection $input, $parent, $item)
    {
        $modify = $this->repo->modifyNested($input, $parent, $item);
        if ($modify)
        {
            $this->status   = Str::upper(Str::snake($this->type.'UpdateNestedSuccess'));
            $this->response = null;
        }
        else
        {
            $this->throwResponse($this->type.'UpdateNestedFail');
        }

        return $modify;
    }


//    public function orderingNested(Collection $input)
//    {
//        $modify = $this->repo->orderingNested($input);
//        if ($modify)
//        {
//            $this->status   = Str::upper(Str::snake($this->type.'UpdateOrderingNestedSuccess'));
//            $this->response = null;
//        }
//        else
//        {
//            $this->status   = Str::upper(Str::snake($this->type.'UpdateOrderingNestedFail'));
//            $this->response = null;
//        }
//
//        return $modify;
//
//    }


    public function removeNested(Collection $input)
    {
        foreach ($input->ids as $id)
        {
            $item = $this->checkItem($id);
            $this->canAction('delete', $item);
            $result = $this->repo->removeNested($item);

            if(!$result) break;
        }

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteNestedSuccess'));
        }
        else{
            $this->throwResponse($this->type.'DeleteNestedFail');
        }

        return $result;
    }


    public function setStoreNestedDefaultInput($input, $parent)
    {
        $input = $this->setStoreDefaultInput($input);

        if ($this->repo->getModel()->hasAttribute('path') && InputHelper::null($input, 'path'))
        {
            $input->put('path', $parent->path . '/' .$input->get('alias'));
        }

        if ($this->repo->getModel()->hasAttribute('extrafields') && !InputHelper::null($input, 'extrafields'))
        {
            $search = '';
            foreach ($input->extrafields as $extrafield)
            {
                $search .= $extrafield['value'] . ' ';
            }
            $input->put('extrafields_search', $search);
        }

        if ($this->repo->getModel()->hasAttribute('params') && InputHelper::null($input, 'params'))
        {
            $input->put('params', []);
        }

        return $input;
    }

    public function storeNested(Collection $input)
    {
        // 取得 parent
        $parent_id = $input->has('parent_id') ? $input->get('parent_id') : 1;
        $parent = $this->checkItem(collect(['id' => $parent_id]));
        // 設定初始值
        $input  = $this->setStoreNestedDefaultInput($input, $parent);
        // 檢查多語言下的 path
        $this->checkPathExist($input, $parent);

        if (InputHelper::null($input, 'id'))
        {
            return $this->addNested($input);
        }
        else
        {
            $input->put('locked_by', 0);
            $input->put('locked_at', null);
            $item = $this->checkItem($input->get('id'));

            return $this->modifyNested($input, $parent, $item);
        }
    }
}