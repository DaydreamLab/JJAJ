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
                $selected ? $new->beforeNode($selected)->save() : true;

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

            if ($children->count())
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

    // 用在檢查多語言相同 path 狀況
    public function findMultiLanguageItem($input)
    {
        $language = !InputHelper::null($input, 'language') ? $input->get('language') : config('global.locale');

        $query = $this->model;

        if ( $this->getModel()->hasAttribute('host'))
        {
            $query = $query
                ->where('path', $input->get('path'))
                ->where('host', $input->get('host'))
                ->whereIn('language', ['*', $language]);
        }
        else
        {
            if ($this->getModel()->hasAttribute('language'))
            {
                $query = $query
                    ->where('path', $input->get('path'))
                    ->whereIn('language', ['*', $language]);
            }
            else
            {
                $query = $query
                    ->where('path', $input->get('path'));
            }
        }

        return $query->first();
    }


    public function findMultiLanguageNestedItem($input)
    {
        $language   = !InputHelper::null($input, 'language') ? $input->get('language') : config('global.locale');
        $query      = $this->model;
        $item_path  = $input->get('parent_path') . '/' . $input->get('alias');

        if ($this->getModel()->hasAttribute('language'))
        {
            $query = $query->whereIn('language', ['*', $language]);
        }

        if ( $this->getModel()->hasAttribute('host'))
        {
            $query = $query->where('host', $input->get('host'));
        }

        if ( $this->getModel()->hasAttribute('path'))
        {
            $query = $query->where('path', $input->get('path'));
        }
        else
        {
            $query = $query->where('path', $item_path);
        }

        return $query->first();
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
        $origin = $this->find($input->id);
        $parent = $this->find($input->parent_id);

        if ($origin->parent_id != $input->parent_id)
        {
            // 修改同層的 ordering
            $origin_next_siblings = $origin->getNextSiblings();
            if (!$this->siblingsOrderingChange($origin_next_siblings, 'sub'))
            {
                return false;
            }

            $parent->appendNode($origin);

            $input->forget('ordering');
            $input->put('ordering', $parent->children->count() + 1);
        }

        return $modify = $this->update($input->toArray());
    }


    public function orderingNested(Collection $input)
    {
        $item   = $this->find($input->id);
        $origin = $item->{$input->get('orderingKey')};

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
                    $input->get('order') == 'asc' ? $sibling->ordering-- : $sibling->ordering++;
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