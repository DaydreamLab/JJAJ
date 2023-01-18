<?php

namespace DaydreamLab\JJAJ\Database;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\String_;

class QueryCapsule
{
    public $extraRelations = [];

    public $extraSearch = [];

    public $doesntHave = [];

    public $having = [];

    public $limit = null;

    public $load = [];

    public $max = null;

    public $page = 1;

    public $clearOrderBy = 0;

    public $paginate = false;

    public $select = [];

    public $orderBy = null;

    public $order = null;

    public $orWhere = [];

    public $orWhereHas = [];

    public $toSql = false;

    public $with = [];

    public $withCount = [];

    public $withSum = [];

    public $where = [];

    public $whereBetween = [];

    public $whereHas = [];

    public $whereDoesntHave = [];

    public $whereIn = [];

    public $whereNotIn = [];

    public $whereNull = [];

    public $whereNotNull = [];

    public $whereRaw = [];

    public $sharedLock = false;

    public $lockForUpdate = false;


    public function exec($model)
    {
        $q = $model;

        if (count($this->extraRelations)) {
            foreach ($this->extraRelations as $k => $extraRelation) {
                $q = $q->{$extraRelation}();
            }
        }

        if (count($this->doesntHave)) {
            foreach ($this->doesntHave as $doesntHave) {
                $q = $q->doesntHave(...$doesntHave);
            }
        }

        if (count($this->load)) {
            foreach ($this->load as $load) {
                $q = $q->load(...$load);
            }
        }

        if (count($this->having)) {
            foreach ($this->having as $having) {
                $q = $q->having(...$having);
            }
        }

        if (count($this->orWhere)) {
            foreach ($this->orWhere as $orWhere) {
                $q = $q->orWhere(...$orWhere);
            }
        }

        if (count($this->orWhereHas)) {
            foreach ($this->orWhereHas as $orWhereHas) {
                $q = $q->orWhereHas(...$orWhereHas);
            }
        }

        if (count($this->select)) {
            $q = $q->select($this->select);
        }

        if (count($this->with)) {
            foreach ($this->with as $with) {
                $q = $q->with(...$with);
            }
        }

        if (count($this->withCount)) {
            $q = $q->withCount(...$this->withCount);
        }


        if (count($this->withSum)) {
            foreach ($this->withSum as $withSum) {
                $q = $q->withSum(...$withSum);
            }
        }

        if (count($this->where)) {
            foreach ($this->where as $where) {
                $q = $q->where(...$where);
            }
        }

        if (count($this->whereBetween)) {
            foreach ($this->whereBetween as $whereBetween) {
                $q = $q->whereBetween($whereBetween[0], $whereBetween[1], $whereBetween[2]);
            }
        }

        if (count($this->whereIn)) {
            foreach ($this->whereIn as $whereIn) {
                $q = $q->whereIn(...$whereIn);
            }
        }


        if (count($this->whereNotIn)) {
            foreach ($this->whereNotIn as $whereNotIn) {
                $q = $q->whereNotIn(...$whereNotIn);
            }
        }

        if (count($this->whereHas)) {
            foreach ($this->whereHas as $whereHas) {
                $q = $q->whereHas(...$whereHas);
            }
        }

        if (count($this->whereDoesntHave)) {
            foreach ($this->whereDoesntHave as $whereDoesntHave) {
                $q = $q->whereDoesntHave(...$whereDoesntHave);
            }
        }

        if (count($this->whereNull)) {
            foreach ($this->whereNull as $whereNull) {
                $q = $q->whereNull($whereNull);
            }
        }

        if (count($this->whereNotNull)) {
            foreach ($this->whereNotNull as $whereNotNull) {
                $q = $q->whereNotNull($whereNotNull);
            }
        }

        if (count($this->whereRaw)) {
            foreach ($this->whereRaw as $whereRaw) {
                $q = $q->whereRaw($whereRaw);
            }
        }

        if (!$this->clearOrderBy) {
            $q = $q->orderBy(
                !$this->orderBy
                    ? $model->getOrderBy()
                    : $this->orderBy,
                !$this->order
                    ? $model->getOrder()
                    : $this->order
            );
        }

        if ($this->max) {
            return $q->max($this->max);
        }

        if ($this->lockForUpdate) {
            $q = $q->lockForUpdate();
        }

        if ($this->sharedLock) {
            $q = $q->sharedLock();
        }

        if ($this->toSql) {
            $sql = $q->toSql();
            $bindings = $q->getBindings();
            $sqlStr = Str::replaceArray('?', $bindings, $sql);
            return $sqlStr;
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


    public function extraSearch($data)
    {
        $this->extraSearch[] = $data;

        return $this;
    }


    public function getQuery(Collection $input)
    {
        $searchKeys = $input->get('searchKeys') ?: [];
        $input = $input->except(['searchKeys']);
        foreach ($input as $key => $value) {
            if ($key == 'search' && $value) {
                $this->where(function ($q) use ($value, $searchKeys) {
                    foreach ($searchKeys as $searchKey) {
                        $searchKey instanceof \Closure
                            ? $q->orWhere($searchKey)
                            : $q->orWhere($searchKey, 'LIKE', "%%$value%%");
                    }
                    foreach ($this->extraSearch as $extraSearch) {
                        $extraSearch instanceof \Closure
                            ? $q->orWhere($extraSearch)
                            : $q->orWhere(...$extraSearch);
                    }
                });
            } elseif ($key == 'limit') {
                $this->limit = $value;
            } elseif ($key == 'paginate') {
                $this->paginate = $value;
            } elseif ($key == 'page') {
                $this->page = $value;
            } elseif ($key == 'q') {
                #do nothing
            } elseif ($key == 'extraRelations') {
                $this->extraRelations = array_merge($this->extraRelations, $value);
            } else {
                if ($value !== '' && $value !== null) {
                    $this->where($key, $value);
                }
            }
        }

        return $this;
    }


    public function having(...$data): QueryCapsule
    {
        $this->having[] = $data;

        return $this;
    }


    public function doesntHave(...$data)
    {
        $this->doesntHave[] = $data;
        return $this;
    }


    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }


    public function load(...$data): QueryCapsule
    {
        $this->load[] = $data;

        return $this;
    }


    public function lockForUpdate()
    {
        $this->lockForUpdate = true;

        return $this;
    }


    public function max($data)
    {
        $this->max = $data;

        return $this;
    }


    public function orderBy($orderBy, $order)
    {
        if ($orderBy) {
            $this->orderBy = $orderBy;
        }

        if ($order) {
            $this->order = $order;
        }

        return $this;
    }


    public function orWhere(...$data): QueryCapsule
    {
        $this->orWhere[] = $data;

        return $this;
    }


    public function orWhereHas(...$data): QueryCapsule
    {
        $this->orWhereHas[] = $data;

        return $this;
    }


    public function page($data)
    {
        $this->page = $data;

        return $data;
    }


    public function select($data): QueryCapsule
    {
        $this->select = array_merge($this->select, $data);

        return $this;
    }


    public function sharedLock()
    {
        $this->sharedLock = true;

        return $this;
    }


    public function toSql()
    {
        $this->toSql = true;

        return $this;
    }


    public function with(...$data): QueryCapsule
    {
        $this->with[] = $data;

        return $this;
    }


    public function where(...$data): QueryCapsule
    {
        $this->where[] = $data;

        return $this;
    }


    public function whereBetween($key, $floor, $ceil): QueryCapsule
    {
        $this->whereBetween[] = [$key, $floor, $ceil];

        return $this;
    }


    public function whereHas(...$data): QueryCapsule
    {
        $this->whereHas[] = $data;

        return $this;
    }


    public function whereDoesntHave(...$data): QueryCapsule
    {
        $this->whereDoesntHave[] = $data;

        return $this;
    }


    public function whereIn(...$data): QueryCapsule
    {
        $this->whereIn[] = $data;

        return $this;
    }


    public function whereNotIn(...$data): QueryCapsule
    {
        $this->whereNotIn[] = $data;

        return $this;
    }


    public function whereNull($data): QueryCapsule
    {
        $this->whereNull[] = $data;

        return $this;
    }


    public function whereNotNull($data): QueryCapsule
    {
        $this->whereNotNull[] = $data;

        return $this;
    }


    public function whereRaw($data): QueryCapsule
    {
        $this->whereRaw[] = $data;

        return $this;
    }


    public function withCount($relation): QueryCapsule
    {
        $this->withCount[] = $relation;

        return $this;
    }


    public function withSum($relation, $key): QueryCapsule
    {
        $this->withSum[] = [$relation, $key];

        return $this;
    }
}
