<?php

namespace DaydreamLab\JJAJ\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface {


    public function all();


    public function create(array $data);


    public function find($id);


    public function findBy($field, $operator, $value);


    public function delete(Model $model);


//    public function update(array $item);

}
