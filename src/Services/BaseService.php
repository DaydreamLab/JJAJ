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


    public function create($data)
    {
        return $this->repo->create($data);
    }


    public function delete($id)
    {
        return $this->repo->delete($id);
    }


    public function find($id, $hit = false)
    {

        $item = $this->repo->find($id);
        if($item) {

            if ($hit) {
                $item->hits = $item->hits + 1;
                $item->save();
            }

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
        if ($input->get('id') == null || $input->get('id') == '') {
            return $this->add($input);
        }
        else {
            return $this->modify($input);
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