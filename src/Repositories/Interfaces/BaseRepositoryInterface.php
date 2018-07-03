<?php
namespace DaydreamLab\JJAJ\Models\Repositories\Interfaces;

interface BaseRepositoryInterface {


    public function all();


    public function create($data);


    public function find($id);


    public function findBy($field, $operator, $value);


    public function delete($id);


    public function update($item);

}