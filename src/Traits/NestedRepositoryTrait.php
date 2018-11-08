<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
                    $new   = $this->create($input->toArray());
                    return $new->afterNode($last_child)->save() ? $new : false;
                }
                else
                {
                    $ordering =  1;
                    $input->put('ordering', $ordering);
                    $new   = $this->create($input->toArray());
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


            $children =  $parent->children()->get();
            if ($children->get('items'))
            {
                $input->put('ordering', $children->last()->ordering + 1);
            }
            else
            {
                $input->put('ordering', 1);
            }


            $new = $this->create($input->toArray());

            return $parent->appendNode($new) ? $new : false;
        }
    }


    public function findTargetNode($node, $difference)
    {
        $origin_count   = ($node->descendants)->count() + 1;
        $target         = $difference < 0 ? $node->getPrevSibling() : $node->getNextSibling();
        $new_diff       = $difference < 0 ?  $difference + $origin_count : $difference - $origin_count;

        if ($new_diff!= 0)
        {
            return $this->findTargetNode($target, $new_diff);
        }
        else {
            return $target;
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
                $selected            = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
                $origin_ordering     = $modified->ordering;
                $modified->ordering  = $selected->ordering;
                $interval_items = $this->findOrderingInterval($input->parent_id, $origin_ordering, $input->ordering);

                // node 向上移動
                if ($input->ordering < $origin_ordering) {
                    if (!$modified->beforeNode($selected)->save())
                    {
                        return false;
                    }
                    if (!$this->siblingsOrderingChange($interval_items, 'add'))
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

                    if (!$this->siblingsOrderingChange($interval_items, 'minus'))
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


    public function orderingNested(Collection $input, $orderingKey)
    {
        $item   = $this->find($input->id);
        $origin = $item->ordering;

        $target_item    = $this->findTargetNode($item, $input->index_diff);
        $item->ordering = $target_item->ordering;

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
            {
                if ($sibling->ordering >= $item->ordering && $sibling->ordering < $origin)
                {
                    $sibling->ordering ++;
                    if (!$sibling->save()) return false;
                }
            }
        }

        return true;
    }


    public function removeNested(Collection $input)
    {
        foreach ($input->ids as $id)
        {
            $item     = $this->find($id);
            $siblings = $item->getNextSiblings();
            $siblings->each(function ($item, $key) {
                $item->ordering--;
                $item->save();
            });

            $result = $this->delete($id);
            if (!$result)
            {
                break;
            }
        }
        return $result;
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


    public function searchNested(Collection $input)
    {
        $limit      = !InputHelper::null($input, 'limit')    ? $input->limit    : $this->model->getLimit();

        //add
        $query      = $this->getQuery($input);
        $query      = $query->where('title', '!=', 'ROOT');
        $items      = $query->orderBy('_lft', 'asc')->get();
        $paginate   = $this->paginate($items, $limit);

//        $query      = $this->model->where('title', '!=', 'ROOT');
//        $tree       = $query->orderBy('ordering', 'asc')->get()->toFlatTree();
//        $copy       = new Collection($tree);
//        $paginate   = $this->paginate($copy, $limit);

        return $paginate;
    }

}