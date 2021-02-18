<?php

namespace DaydreamLab\JJAJ\Traits;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;
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
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->get('parent_id'), $input->get('ordering')])->first();

                $new      = $this->create($input->toArray());
                $selected ? $new->beforeNode($selected)->save() : true;

                $siblings = $new->getNextSiblings();

                return $this->siblingsOrderingChange($siblings, 'add') ? $new : false;
            }
            else
            {
                $parent     = $this->find($input->get('parent_id'));
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
            if ($this->model->hasAttribute('extension'))
            {
                if($input->get('extension') != '')
                {
                    $parent = $this->findByChain(['title', 'extension'],['=', '='],['ROOT', $input->get('extension')])->first();
                }
                else
                {
                    $parent = $this->find(1);
                }
            }
            else
            {
                // 這邊是拿來擴充 nestedSet 的(例如：Dddream 的 product category)
                if ($this->model->hasAttribute('merchant_id'))
                {
                    $parent = $this->find($input->get('parent_id'));
                    if(!$parent)
                    {
                        return $this->create($input->toArray());
                    }
                }
                else
                {
                    $parent = $this->findBy('title', '=', 'ROOT')->first();
                }
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
        $language_options = ['*'];
        $language = !InputHelper::null($input, 'language') ? $input->get('language') : config('daydreamlab.global.locale');
        if ($language != '*')
        {
            $language_options[] = $language;
        }

        $query = $this->model;

        // table = menu
        if ( $this->getModel()->hasAttribute('host'))
        {
            $query = $query
                ->where('host', $input->get('host'))
                ->whereIn('language', $language_options);
            $query = !InputHelper::null($input, 'path') ? $query->where('path', $input->get('path')) : $query;
        }
        else
        {
            if ($this->getModel()->hasAttribute('language'))
            {
                $query = $query
                    ->whereIn('language', $language_options);
                $query = !InputHelper::null($input, 'path') ? $query->where('path', $input->get('path')) : $query;
            }
            else
            {
                $query = $query
                    ->where('path', $input->get('path'));
            }
        }

        return $query->first();
    }


    public function findTargetNode($node, $input)
    {
        $diff = $input->get('index_diff');
        $order = $input->get('order') ?: 'asc';

        if ($order == 'asc') {
            if ($diff < 0) {
                return $node->getPrevSiblings()
                    ->where('ordering', $node->ordering + $diff)
                    ->first();
            } else {
                return $node->getNextSiblings()
                    ->where('ordering', $node->ordering + $diff)
                    ->first();
            }
        } else {
            if ($diff < 0) {
                return $node->getNextSiblings()
                    ->where('ordering', $node->ordering - $diff)
                    ->first();
            } else {
                return $node->getPrevSiblings()
                    ->where('ordering', $node->ordering - $diff)
                    ->first();
            }
        }
    }


    public function modifyNested(Collection $input, $parent, $item)
    {
        // 如果更換了 parent
        if ($item->parent_id != $parent->id)
        {
            // 修改同層的 ordering
            $item_next_siblings = $item->getNextSiblings();
            if (!$this->siblingsOrderingChange($item_next_siblings, 'sub')) {
                return false;
            }

            $parent->appendNode($item);

            $input->forget('ordering');
            $input->put('ordering', $parent->children->count());
        }

        return $modify = $this->update($input->toArray(), $item);
    }


    public function orderingNested(Collection $input, $item)
    {
        $target_item = $this->findTargetNode($item, $input);
        if (!$target_item)
            return false;

        // 這邊會有 call by reference 問題，先存起來原始值
        $targetOrdering = $target_item->ordering;
        if ($input->get('index_diff') < 0) {
            if ($input->get('order') == 'asc') {
                $siblings = $item
                    ->getPrevSiblings()
                    ->filter(function ($sibling) use ($item, $target_item) {
                        return $sibling->ordering >= $target_item->ordering
                            && $sibling->ordering < $item->ordering;
                    });
                if(!$item->beforeNode($target_item)->save()) {
                    return false;
                }
            } else {
                $siblings = $item
                    ->getNextSiblings()
                    ->filter(function ($sibling) use ($item, $target_item) {
                        return $sibling->ordering > $item->ordering
                            && $sibling->ordering <= $target_item->ordering;
                    });
                if(!$item->afterNode($target_item)->save()) {
                    return false;
                }
            }

            if(!$item->beforeNode($target_item)->save()) {
                return false;
            }
            $item = $item->refresh();

            $input->get('order') == 'asc'
                ? $this->siblingsOrderingChange($siblings, 'add')
                : $this->siblingsOrderingChange($siblings, 'sub');
        } else {
            if ($input->get('order') == 'asc') {
                $siblings = $item
                    ->getNextSiblings()
                    ->filter(function ($sibling) use ($item, $target_item) {
                        return $sibling->ordering > $item->ordering
                            && $sibling->ordering <= $target_item->ordering;
                    });
                if(!$item->afterNode($target_item)->save()) {
                    return false;
                }
            } else {
                $siblings = $item
                    ->getPrevSiblings()
                    ->filter(function ($sibling) use ($item, $target_item) {
                        return $sibling->ordering >= $target_item->ordering
                            && $sibling->ordering < $item->ordering;
                    });
                if(!$item->beforNode($target_item)->save()) {
                    return false;
                }
            }

            $item = $item->refresh();

            $input->get('order') == 'asc'
                ? $this->siblingsOrderingChange($siblings, 'sub')
                : $this->siblingsOrderingChange($siblings, 'add');
        }

        $item->ordering = $targetOrdering;
        $item->save();

        return true;
    }


    public function removeNested($item)
    {
        $siblings = $item->getNextSiblings();
        $siblings->each(function ($item, $key) {
            $item->ordering--;
            $item->save();
        });

        $result = $this->delete($item, $item);

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

}