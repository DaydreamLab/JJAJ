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
        $parent        = $this->find($input->parent_id);

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

        }



//        // 有更改 parent
//        if ($node->parent_id != $input->parent_id) {
//            if ($input->get('ordering') != null && $input->get('ordering') != '') {
//                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
//                $node->beforeNode($selected);
//                $node->ordering = $input->ordering;
//                $node->save();
//                $update = $this->find($input->id);
//                $this->siblingOrderingChange($update->getNextSiblings(), 'add');
//            }
//            else {
//                $last =  $parent->children()->get()->last();
//                $node->afterNode($last);
//                $node->ordering =  $last->ordering + 1;
//                $node->save();
//            }
//
//        }
//        else {
//            // 有改 ordering
//            if ($input->ordering != $node->ordering) {
//                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
//                $interval_items = $this->findOrderingInterval($input->parent_id, $node->ordering, $input->ordering);
//
//                // node 向上移動
//                if ($input->ordering < $node->ordering) {
//                    $node->beforeNode($selected)->save();
//                    $this->siblingOrderingChange($interval_items, 'add');
//                }
//                else {
//                    $node->afterNode($selected)->save();
//                    $this->siblingOrderingChange($interval_items, 'minus');
//                }
//            }
//            // 防止錯誤修改到樹狀結構
//            $input->forget('parent_id');
//        }
//
//        $modify = $this->modify($input->except(['parent_id']));

    }


    public function orderingNested(Collection $input)
    {
        $item   = $this->find($input->id);
        $origin = $item->ordering;

        $target_item    = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$item->parent_id, ($item->ordering + $input->index_diff)])->first();
        $item->ordering = $origin + $input->index_diff;
        if(!$item->beforeNode($target_item)->save()) {
            return false;
        }

        $siblings   = $item->getNextSiblings();

        if ($input->index_diff >= 0)
        {
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
            foreach ($siblings as $sibling)
            {
                if ($sibling->ordering >= $item->ordering && $sibling->ordering < $origin)
                {
                    $sibling->ordering ++;
                    if (!$sibling->save()) return false;
                }
            }
        }
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