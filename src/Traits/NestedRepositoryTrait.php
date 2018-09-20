<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;

trait NestedRepositoryTrait
{
    public function addNested(Collection $input)
    {

        if (!InputHelper::null($input, 'parent_id'))
        {
            if (!InputHelper::null($input, 'ordering'))
            {
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();

                $new      = $this->create($input->toArray());
                $new->beforeNode($selected)->save();

                $siblings = $new->getNextSiblings();

                return $this->siblingsOrderingChange($siblings, 'add') ? $new : false;
            }
            else
            {
                $parent     = $this->find($input->parent_id);
                $last_child = $parent->children()->get()->last();
                if ($last_child)
                {
                    $ordering = $last_child->ordering + 1;
                    $input->put('ordering', $ordering);
                    $new   = $this->add($input);
                    return $new->afterNode($last_child)->save() ? $new : false;
                }
                else
                {
                    $ordering =  1;
                    $input->put('ordering', $ordering);
                    $new   = $this->add($input);
                    return $parent->appendNode($new) ? $new : false;
                }
            }
        }
        else
        {
            if ($input->get('extension') != '')
            {
                $parent = $this->findByChain(['title', 'extension'],['=', '='],['ROOT', $input->get('extension')])->first();
            }
            else
            {
                $parent = $this->find(1);
            }

            $last_child =  $parent->children()->get()->last();
            $input->put('ordering', $last_child->ordering + 1);

            $new = $this->add($input);

            return $parent->appendNode($new) ? $new : false;
        }
    }


    public function modifyNested(Collection $input)
    {
        $modified = $this->find($input->id);
        $parent   = $this->find($input->parent_id);

        // 有更改parent
        if ($modified->parent_id != $input->parent_id)
        {
            if (!InputHelper::null($input, 'ordering'))
            {
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
                $modified->beforeNode($selected);
                $modified->ordering = $input->ordering;
                if (!$modified->save())
                {
                    return false;
                }

                $siblings = $modified->getNextSiblings();
                if (!$this->siblingsOrderingChange($siblings, 'add'))
                {
                    return false;
                }
            }
            else
            {
                $last =  $parent->children()->get()->last();
                $modified->afterNode($last);
                $modified->ordering =  $last->ordering + 1;
                if (!$modified->save())
                {
                    return false;
                }
            }
        }
        else
        {
            //有改 ordering
            if ($input->ordering != $modified->ordering) {
                $selected       = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
                $interval_items = $this->findOrderingInterval($input->parent_id, $modified->ordering, $input->ordering);

                // node 向上移動
                if ($input->ordering < $modified->ordering) {
                    if (!$modified->beforeNode($selected)->save())
                    {
                        return false;
                    }
                    if (!$this->siblingOrderingChange($interval_items, 'add'))
                    {
                        return false;
                    }
                }
                else
                {
                    if (!$modified->afterNode($selected)->save())
                    {
                        return false;
                    }

                    if (!$this->siblingOrderingChange($interval_items, 'add'))
                    {
                        return false;
                    }
                }
            }
        }
        // 防止錯誤修改到樹狀結構
        $input->forget('parent_id');
        $input->forget('ordering');

        return $modify = $this->update($input->toArray());
    }


    public function orderingNested(Collection $input)
    {
        $item   = $this->find($input->id);
        $origin = $item->ordering;

        $target_item    = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$item->parent_id, ($item->ordering + $input->index_diff)])->first();
        $item->ordering = $origin + $input->index_diff;

        if ($input->index_diff >= 0)
        {
            if(!$item->afterNode($target_item)->save()) {
                return false;
            }
            $siblings   = $item->getPrevSiblings();
            foreach ($siblings as $sibling)
            {
                if ($sibling->ordering > $origin && $sibling->ordering <= $item->ordering)
                {
                    $sibling->ordering --;
                    if (!$sibling->save()) return false;
                }
            }
        }
        else
        {
            if(!$item->beforeNode($target_item)->save()) {
                return false;
            }
            $siblings   = $item->getNextSiblings();
            foreach ($siblings as $sibling)
            { Helper::show($sibling->title);
                if ($sibling->ordering >= $item->ordering && $sibling->ordering < $origin)
                {
                    $sibling->ordering ++;
                    if (!$sibling->save()) return false;
                }
            }
        }

        return true;
    }


    public function siblingsOrderingChange($siblings, $action = 'add')
    {
        foreach ($siblings as $sibling)
        {
            $action == 'add' ? $sibling->ordering++ : $sibling->ordering--;

            if (!$sibling->save())
            {
                return false;
            }
        }

        return true;
    }

}