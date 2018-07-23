<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }


    public function all()
    {
        return $this->model->all();
    }


    public function create($data)
    {
        return $this->model->create($data);
    }


    public function delete($id)
    {
      return $this->model->find($id)->delete();
    }


    public function find($id)
    {
        return $this->model->find($id)->first();
    }


    public function findBy($field, $operator, $value)
    {
        return $this->model->where($field, $operator, $value)->get();
    }


    public function search(Collection $input)
    {
        $order_by   = $input->has('order_by') ? $input->order_by : $this->model->getOrderBy();
        $limit      = $input->has('limit') ? $input->limit : $this->model->getLimit();
        $ordering   = $input->has('ordering') ? $input->ordering : $this->model->getOrdering();

        $collection = $this->model;
        foreach ($input->toArray() as $key => $item) {
            if ($key != 'limit' && $key !='ordering' && $key !='order_by') {
                $collection = $collection->where("$key", '=', $item);
            }
        }

        return $collection->orderBy($order_by, $ordering)->paginate($limit);
    }


    public function state($id, $state)
    {
        $item = $this->find($id);
        if ($item) {
            $item->state = $state;
            return $item->save();
        }
        else {
            return false;
        }
    }

    public function update($item)
    {
        return $this->model->find($item['id'])->update($item);
    }

}