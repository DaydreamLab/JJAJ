<?php

namespace DaydreamLab\JJAJ\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait NestedServiceTrait
{
    public function addNested(Collection $input)
    {
        // 有指定 parent node
        if ($input->has('parent_id') && $input->parent_id != '') {
            $parent = $this->find($input->parent_id);

            // 有 ordering
            if ($input->get('ordering') != null && $input->get('ordering') != '') {
                // 新 node 的 ordering 為 $selected 的 ordering
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();

                $input->put('ordering', $selected->ordering);
                $node     = $this->add($input);
                $node->beforeNode($selected)->save();
                $siblings = $node->getNextSiblings();

                $this->siblingOrderingChange($siblings, 'add');
            }
            else { // 沒有 ordering
                $last_child =  $parent->children()->get()->last();
                if ($last_child) {
                    $ordering = $last_child->ordering + 1;
                    $input->put('ordering', $ordering);
                    $node   = $this->add($input);
                    $node->afterNode($last_child)->save();
                }
                else {
                    $ordering =  1;
                    $input->put('ordering', $ordering);
                    $node   = $this->add($input);
                    $parent->appendNode($node);
                }
            }
        }
        else {
            if ($input->get('extension') != '') {
                $parent = $this->findByChain(['title', 'extension'],['=', '='],['ROOT', $input->get('extension')])->first();
            }
            else {
                $parent = $this->find(1);
            }
            $last_child =  $parent->children()->get()->last();
            $input->put('ordering', $last_child->ordering + 1);

            $node = $this->add($input);
            $parent->appendNode($node);
        }

        if ($node) {
            $this->status =  Str::upper(Str::snake($this->type.'CreateNestedSuccess'));
            $this->response = $node;
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'CreateNestedFail'));
            $this->response = null;
        }
        return $node;
    }


    public function modifyNested(Collection $input)
    {
        $node   = $this->find($input->id);
        $parent = $this->find($input->parent_id);

        // 有更改 parent
        if ($node->parent_id != $input->parent_id) {
            if ($input->get('ordering') != null && $input->get('ordering') != '') {
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
                $node->beforeNode($selected);
                $node->ordering = $input->ordering;
                $node->save();
                $update = $this->find($input->id);
                $this->siblingOrderingChange($update->getNextSiblings(), 'add');
            }
            else {
                $last =  $parent->children()->get()->last();
                $node->afterNode($last);
                $node->ordering =  $last->ordering + 1;
                $node->save();
            }
            // 前面已經修改過了，避免再一次在 update 時更改
            $input->forget('ordering');
        }
        else {
            // 有改 ordering
            if ($input->ordering != $node->ordering) {
                $selected = $this->findByChain(['parent_id', 'ordering'], ['=', '='], [$input->parent_id, $input->ordering])->first();
                $interval_items = $this->findOrderingInterval($input->parent_id, $node->ordering, $input->ordering);

                // node 向上移動
                if ($input->ordering < $node->ordering) {
                    $node->beforeNode($selected)->save();
                    $this->siblingOrderingChange($interval_items, 'add');
                }
                else {
                    $node->afterNode($selected)->save();
                    $this->siblingOrderingChange($interval_items, 'minus');
                }
            }
            // 防止錯誤修改到樹狀結構
            $input->forget('parent_id');
        }

        $modify = $this->modify($input->except(['parent_id']));
        if ($modify) {
            $this->status = Str::upper(Str::snake($this->type.'UpdateNestedSuccess'));
            $this->response = null;
            return true;
        }
        else {
            $this->status = Str::upper(Str::snake($this->type.'UpdateNestedFail'));
            $this->response = null;
            return false;
        }
    }



    public function siblingOrderingChange($siblings, $action = 'add')
    {
        foreach ($siblings as $sibling) {
            if ($action == 'add') {
                $sibling->ordering = $sibling->ordering + 1;
            }
            else {
                $sibling->ordering = $sibling->ordering - 1;
            }
            $sibling->save();
        }
    }


    public function storeNested(Collection $input)
    {
        if ($input->get('id') == null || $input->get('id') == '') {
            return $this->addNested($input);
        }
        else {
            return $this->modifyNested($input);
        }
    }
}