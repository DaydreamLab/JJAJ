<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;

trait NestedServiceTrait
{
    public function addNested(Collection $input)
    {
        $item = $this->repo->addNested($input);
        if ($item) {
            $this->addMapping($item, $input);
            $item = $item->refresh();
            $this->status    = 'CreateNestedSuccess';
            $this->response  = $item->refresh();
        } else {
            throw new InternalServerErrorException('CreateNestedFail', null, [], $this->modelName);
        }

        return $item->refresh();
    }


    public function checkPathExist(Collection $input, $parent)
    {
        if($this->repo->getModel()->hasAttribute('path') && $input->get('alias') && $this->repo->getModel()->getTable() != 'assets') {
            $same = $this->repo->findMultiLanguageItem($input);
            if ($same && $same->id != $input->get('id')) {
                throw new ForbiddenException('StoreNestedWithExistPath',  ['path' => $input->get('path')], null, $this->modelName);
            }
        }

        return false;
    }


    public function modifyNested(Collection $input, $parent, $item)
    {
        if (!$input->get('alias')) {
            $input->put('alias', $item->alias);
        }

        $modify = $this->repo->modifyNested($input, $parent, $item);
        if ($modify) {
            $this->modifyMapping($item, $input);
            $this->status   = 'UpdateNestedSuccess';
            $this->response = $item->refresh();
        } else {
            throw new InternalServerErrorException('UpdateNestedFail', null, [], $this->modelName);
        }

        return $this->response;
    }


    public function removeNested(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $item = $this->checkItem(collect(['id' => $id]));
            $this->removeMapping($item);
            $result = $this->repo->removeNested($item);

            if(!$result) break;
        }

        if($result) {
            $this->status = 'DeleteNestedSuccess';
        } else {
            throw new InternalServerErrorException('DeleteNestedFail', null, null, $this->modelName);
        }

        return $result;
    }


    public function setStoreNestedDefaultInput($input, $parent)
    {
        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access')) {
            $input->put('access', $parent ? $parent->access : config('daydreamlab.cms.default_viewlevel_id'));
        }

        $input = $this->setStoreDefaultInput($input);

        return $input;
    }


    public function storeNested(Collection $input)
    {
        $parent_id = $input->get('parent_id');
        $parent = $parent_id ? $this->repo->find($parent_id) : null;

        // 設定初始值
        $input  = $this->setStoreNestedDefaultInput($input, $parent);
        // 檢查多語言下的 path
        $this->checkPathExist($input, $parent);

        if (InputHelper::null($input, 'id')) {
            return $this->addNested($input);
        } else {
            $item = $this->checkItem(collect([ 'id' => $input->get('id')]));
            $this->checkLocked($item);
            $input->put('locked_by', null);
            $input->put('locked_at', null);
            return $this->modifyNested($input, $parent, $item);
        }
    }
}
