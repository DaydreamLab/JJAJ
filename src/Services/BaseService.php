<?php

namespace DaydreamLab\JJAJ\Services;


use DaydreamLab\JJAJ\Repositories\BaseRepository;

class BaseService
{
    protected $repo;

    protected $status;

    protected $response;

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }

    public function all()
    {
        return $this->repo->all();
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
        return $this->repo->find($id);
    }

    public function findBy($filed, $operator, $value)
    {
        return $this->repo->findBy($filed, $operator, $value);
    }

    public function update($data)
    {
        return $this->repo->update($data);
    }


}