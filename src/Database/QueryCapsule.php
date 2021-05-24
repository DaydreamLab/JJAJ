<?php

namespace DaydreamLab\JJAJ\Database;

use Illuminate\Support\Collection;

class QueryCapsule
{
    public $extraRelations = [];

    public $has = [];

    public $load = [];

    public $select = [];

    public $orWhere = [];

    public $where = [];

    public $with = [];

    public $withCount = [];

    public $whereHas = [];

    public $whereIn = [];

    public $orWhereHas = [];

    public $limit = null;

    public $paginate = false;

    public function exec($model)
    {
        $q = $model;

        if (count($this->select)) {
            $q = $q->select($this->select);
        }

        if (count($this->where)) {
            foreach ($this->where as $where) {
                $q = $q->where(...$where);
            }
        }

        if (count($this->orWhere)) {
            foreach ($this->orWhere as $orWhere) {
                $q = $q->orWhere(...$orWhere);
            }
        }

        if (count($this->whereIn)) {
            foreach ($this->whereIn as $whereIn) {
                $q = $q->whereIn(...$whereIn);
            }
        }

        if (count($this->whereHas)) {
            foreach ($this->whereHas as $whereHas) {
                $q = $q->whereHas(...$whereHas);
            }
        }

        if (count($this->orWhereHas)) {
            foreach ($this->orWhereHas as $orWhereHas) {
                $q = $q->orWhereHas(...$orWhereHas);
            }
        }

        if (count($this->with)) {
            foreach ($this->with as $with) {
                $q = $q->with(...$with);
            }
        }

        if (count($this->load)) {
            foreach ($this->load as $load) {
                $q = $q->load(...$load);
            }
        }

        if (count($this->withCount)) {
            foreach ($this->withCount as $withCount) {
                $q = $q->withCount(...$withCount);
            }
        }

        if (count($this->has)) {
            foreach ($this->has as $has) {
                $q = $q->has(...$has);
            }
        }

        if (count($this->extraRelations)) {
            foreach ($this->extraRelations as $k => $extraRelation) {
                $q = $q->{$extraRelation}();
            }
        }

        if ($this->paginate) {
            return $this->limit
                ? $q->paginate($this->limit)
                : $q->get();
        } else {
            return $this->limit
                ? $q->limit($this->limit)->get()
                : $q->get();
        }
    }

    public function getQuery(Collection $input)
    {
        $searchKeys = $input->get('searchKeys') ?: [];
        $input = $input->except(['searchKeys']);
        foreach ($input as $key => $value) {
            if ($key == 'search') {
                foreach ($searchKeys as $searchKey) {
                    $this->orWhere($searchKey, 'LIKE', "%$value%");
                }
            } elseif ($key == 'limit') {
                $this->limit = $value;
            } elseif ($key == 'paginate') {
                $this->paginate = $value;
            } elseif ($key == 'q') {
                #do nothing
            } elseif ($key == 'extraRelations') {
                $this->extraRelations = array_merge($this->extraRelations, $value);
            } else {
                $this->where($key, $value);
            }
        }
    }


    public function load(...$data) : QueryCapsule
    {
        $this->load[] = $data;

        return $this;
    }


    public function orWhere(...$data) : QueryCapsule
    {
        $this->orWhere[] = $data;

        return $this;
    }


    public function orWhereHas(...$data) : QueryCapsule
    {
        $this->orWhereHas[] = $data;

        return $this;
    }


    public function select(...$data) : QueryCapsule
    {
        $this->select[] = $data;

        return $this;
    }


    public function with(...$data) : QueryCapsule
    {
        $this->with[] = $data;

        return $this;
    }


    public function where(...$data) : QueryCapsule
    {
        $this->where[] = $data;

        return $this;
    }


    public function whereIn(...$data) : QueryCapsule
    {
        $this->whereIn[] = $data;

        return $this;
    }


    public function whereHas(...$data) : QueryCapsule
    {
        $this->whereHas[] = $data;

        return $this;
    }
}
