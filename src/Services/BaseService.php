<?php

namespace DaydreamLab\JJAJ\Services;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
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


    public function add($data)
    {
        $model = $this->create($data);
        if ($model) {
            $this->status =  Str::upper(Str::snake($this->type.'CreateSuccess'));;
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'CreateFail'));;
        }
        return $model;
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


    public function getOrder($parent) {
        $descendants = $parent->descendants;
        if ($descendants->count() > 0) {
            $last = $descendants->sortBy('order')->last();
            return $last->order + 1 ;
        }
        else {
            return 1;
        }
    }

    public function modify($data)
    {
        $update = $this->update($data);
        if ($update) {

            $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
        }
        else {
            $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
        }
        return $update;
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


    public function store(Collection $input)
    {
        if (!$input->has('id') || ($input->has('id') && $input->id == '')) {
            return $this->add($input->toArray());
        }
        else {
            return $this->modify($input->toArray());
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
                $asset = $this->add([
                    $mainKey    => $input->{$mainKey},
                    Str::substr($mapKey, 0, -1) => $id
                ]);
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
        if (!$input->has('id') || ($input->has('id') && $input->id == '')) {
            if ($input->has('parent_id') && $input->parent_id != '') {
                $parent = $this->find($input->parent_id);
                $input->put('order', $this->getOrder($parent));
                $node   = $this->add($input->toArray());
                $parent->prependNode($node);
                $this->status = Str::upper(Str::snake($this->type.'CreateSuccess'));
                $this->response = $node;
                return $node;
            }
            else {
                if ($input->has('extension') && $input->get('extension') != '') {
                    $root = $this->findByChain(['title', 'extension'],['=', '='],['ROOT', $input->extension])->first();
                }
                else {
                    $root = $this->find(1);
                }
                $input->put('order', $this->getOrder($root));
                $node = $this->add($input->toArray());
                $root->prependNode($node);
                $this->status = Str::upper(Str::snake($this->type.'CreateSuccess'));
                $this->response = $node;
                return $node;
            }
        }//編輯
        else {
            $node = $this->find($input->id);
            if ($node->parent_id == $input->parent_id) {
                return $this->modify($input->toArray());
            }
            else {
                // 處理搬移的 order 問題
                $items = $this->findByChain(['parent_id', 'order'], ['=', '>'], [$node->parent_id, $node->order]);
                $items->each(function ($item, $key) {
                   $item->order = $item->order - 1;
                   $item->save();
                });

                $new_parent = $this->find($input->parent_id);

                $input->forget('order');
                $input->put('order', $this->getOrder($new_parent));
                $this->modify($input->toArray());

                if ($new_parent->prependNode($node)) {
                    $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
                    $this->response = null;
                    return true;
                }
                else {
                    $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
                    $this->response = null;
                    return false;
                }
            }
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