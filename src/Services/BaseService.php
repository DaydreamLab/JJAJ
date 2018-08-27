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
            $ids = [];
            foreach ($delete_items as $item) {
                $ids[] = $item->id;
            }
            if ($this->remove(Helper::collect($ids))) {
                return false;
            }
        }

        if ($input->{$mapKey} -> count() > 0) {
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
        if (!$input->has('id') || ($input->has('id') && $input->id == '')) {
            if ($input->has('parent_id') && $input->parent_id != '') {
                $node   = $this->add($input->toArray());
                $parent = $this->find($input->parent_id);
                $parent->prependNode($node);
                $this->status = Str::upper(Str::snake($this->type.'CreateSuccess'));
                $this->response = $node;
                return $node;
            }
            else {
                $root = $this->find(1);
                $node = $this->add($input->toArray());
                $root->prependNode($node);
                $this->status = Str::upper(Str::snake($this->type.'CreateSuccess'));
                $this->response = $node;
                return $node;
            }
        }
        else {
            $node = $this->find($input->id);
            if ($node->parent_id == $input->parent_id) {
                return $this->modify($input->toArray());
            }
            else {
                $new_parent = $this->find($input->parent_id);

                if ($new_parent->prependNode($node)) {
                    $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
                    return true;
                }
                else {
                    $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
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


    public function update($data)
    {
        return $this->repo->update($data);
    }


}