<?php

namespace DaydreamLab\JJAJ\Services;


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


    public function remove(Collection $input)
    {
        foreach ($input->ids as $id) {
            $result = $this->repo->delete($id);
            if (!$result) {
              break;
            }
        }
        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'RemoveSuccess'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'RemoveFail'));
        }
        return $result;
    }


    public function store(Collection $input)
    {
        if ($input->id) {
            $model = $this->create($input->toArray());
            if ($model) {
                $this->status =  Str::upper(Str::snake($this->type.'CreateSuccess'));;
            }
            else {
                $this->status =  Str::upper(Str::snake($this->type.'CreateFail'));;
            }
        }
        else {
            $update = $this->update($input->toArray());
            if ($update) {

                $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
            }
            else {
                $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
            }
        }
    }


    public function trash(Collection $input)
    {
        foreach ($input->ids as $id) {
            $result = $this->repo->trash($id);
            if (!$result) {
               break;
            }
        }

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'TrashSuccess'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'TrashFail'));
        }
        return $result;
    }


    public function update($data)
    {
        return $this->repo->update($data);
    }


}