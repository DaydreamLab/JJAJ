<?php

namespace DaydreamLab\JJAJ\Services;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use DaydreamLab\User\Models\Asset\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BaseService
{
    protected $repo;

    protected $type;

    public $status;

    public $response;

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }

    public function all()
    {
        return $this->repo->all();
    }


    public function add(Collection $input)
    {
        $model = $this->create($input->toArray());
        if ($model) {
            $this->status =  Str::upper(Str::snake($this->type.'CreateNestedSuccess'));
            $this->response = $model;
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'CreateNestedFail'));
            $this->response = null;
        }
        return $model;
    }


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
                $input->put('ordering',$last_child->ordering + 1);
                $node   = $this->add($input);
                $node->afterNode($last_child)->save();
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


    public function create($data)
    {
        return $this->repo->create($data);
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }

    public function find($id)
    {
        $item = $this->repo->find($id);
        if($item) {
            $this->status   = Str::upper(Str::snake($this->type.'FindSuccess'));
            $this->response = $item;
        }
        else {
            $this->status   = Str::upper(Str::snake($this->type.'FindFail'));
            $this->response = $item;
        }

        return $item;
    }


    public function findBy($filed, $operator, $value)
    {
        return $this->repo->findBy($filed, $operator, $value);
    }


    public function findByChain($fields, $operators, $values)
    {
        return $this->repo->findByChain($fields , $operators, $values);
    }


    public function findOrderingInterval($parent_id, $origin, $modified)
    {
        return $this->repo->findOrderingInterval($parent_id, $origin, $modified);
    }


    public function modify(Collection $input)
    {
        $update = $this->update($input->toArray());
        if ($update) {
            $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
            $this->response = null;
        }
        else {
            $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
            $this->response = null;
        }
        return $update;
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


    public function remove(Collection $input)
    {
        foreach ($input->ids as $id) {
            $result = $this->repo->delete($id);
            if (!$result) {
              break;
            }
        }
        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteSuccess'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteFail'));
        }
        return $result;
    }


    public function search(Collection $input)
    {
        $result         = $this->repo->search($input);
        $this->status   = Str::upper(Str::snake($this->type.'SearchSuccess'));
        $this->response = $result;

        return $result;
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


    public function store(Collection $input)
    {
        if ($input->get('id') == null || $input->get('id') == '') {
            return $this->add($input);
        }
        else {
            return $this->modify($input);
        }
    }


    public function storeKeysMap(Collection $input)
    {
        $mainKey = $mapKey = null;
        foreach ($input->keys() as $key) {
            if (gettype($input->{$key}) == 'array') {
                $mapKey = $key;
            }
            else {
                $mainKey = $key;
            }
        }

        $delete_items = $this->findBy($mainKey, '=', $input->{$mainKey});
        if ($delete_items->count() > 0) {
            $data = [];
            foreach ($delete_items as $item) {
                $data['ids'][] = $item->id;
            }
            if (!$this->remove(Helper::collect($data))) {
                return false;
            }
        }

        if (count($input->{$mapKey}) > 0) {
            foreach ($input->{$mapKey} as $id) {
                $asset = $this->add(Helper::collect([
                    $mainKey    => $input->{$mainKey},
                    Str::substr($mapKey, 0, -1) => $id
                ]));
                if (!$asset) {
                    return false;
                }
            }
        }

        return true;
    }


    public function storeNested(Collection $input)
    {
        // 新增
        if ($input->get('id') == null || $input->get('id') == '') {
            return $this->addNested($input);
        }//編輯
        else {
            return $this->modifyNested($input);
        }
    }


    public function state(Collection $input)
    {
        foreach ($input->ids as $key => $id) {
            $result = $this->repo->state($id, $input->state);
            if (!$result) {
                break;
            }
        }

        if ($input->state == '1') {
            $action = 'Publish';
        }
        elseif ($input->state == '0') {
            $action = 'Unpublish';
        }
        elseif ($input->state == '0') {
            $action = 'Archive';
        }
        elseif ($input->state == '-2') {
            $action = 'Trash';
        }

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type. $action . 'Success'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type. $action . 'Fail'));
        }
        return $result;
    }


    public function traverseTitle(&$categories, $prefix = '-', &$str = '')
    {
        $categories  = $categories->sortBy('order');
        foreach ($categories as $category) {
            $str = $str . PHP_EOL.$prefix.' '.$category->title;
            $this->traverseTitle($category->children, $prefix.'-', $str);
        }
        return $str;
    }


    public function tree($extension)
    {
        $tree = $this->findBy('extension', '=', $extension)->toTree();
        $this->status =  Str::upper(Str::snake($this->type . 'GetTreeSuccess'));
        $this->response = $tree;

        return $tree;
    }


    public function update($data)
    {
        return $this->repo->update($data);
    }


}