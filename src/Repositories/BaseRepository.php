<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

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
        return $this->model->find($id);
    }


    public function findBy($field, $operator, $value)
    {
        return $this->model->where($field, $operator, $value)->get();
    }


    public function update($item)
    {
        return $this->model->find($item['id'])->update($item);
    }

}