<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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


    public function findByChain($fields, $operators, $values)
    {
        $model = $this->model;
        foreach ($fields as $key => $field) {
            $model = $model->where($field , $operators[$key], $values[$key]);
        }
        return $model->get();
    }


    public function findOrderingInterval($parent_id, $origin, $modified)
    {
        $query = $this->model->where('parent_id', $parent_id);
        if($origin > $modified) {   // 排序向上移動
            $query = $query->where('ordering', '>=', $modified)->where('ordering', '<', $origin);
        }
        else { // 排序向下移動
            $query = $query->where('ordering', '>', $origin)->where('ordering', '<=', $modified);
        }
        return $query->get();
    }


    public function fixTree()
    {
        $this->model->fixTree();
    }

    public function search(Collection $input)
    {
        $order_by   = $input->has('order_by') ? $input->order_by : $this->model->getOrderBy();
        $limit      = $input->has('limit')    ? $input->limit    : $this->model->getLimit();
        $order      = $input->has('order')    ? $input->order    : $this->model->getOrder();

        $collection = $this->model;
        foreach ($input->toArray() as $key => $item) {
            if ($key != 'limit' && $key !='order' && $key !='order_by') {
                if ($key == 'search') {
                        $collection = $collection->where(function ($query) use ($item){
                        $query->where('title', 'LIKE', '%%'.$item.'%%');
                        if (Schema::hasColumn($this->model->getTable(), 'introtext')) {
                            $query->orWhere('introtext', 'LIKE', '%%'.$item.'%%');
                        }
                        if (Schema::hasColumn($this->model->getTable(), 'description')) {
                            $query->orWhere('description', 'LIKE', '%%'.$item.'%%');
                        }
                    });
                }
                else {
                    if ($item != null) {
                        $collection = $collection->where("$key", '=', $item);
                    }
                }
            }
        }

        if(Schema::hasColumn( $this->model->getTable(), '_lft')) {
            if (Schema::hasColumn( $this->model->getTable(), 'title')) {
                $collection = $collection->where('title', '!=', 'ROOT');
            }
            else {
                $collection = $collection->where('name', '!=', 'ROOT');
            }

            if ($input->has('order_by') && $input->has('order')) {
                $collection = $collection->orderBy($order_by, $order);
            }
            else {
                $collection = $collection->orderBy('ordering', 'asc');
            }

            $paginate = $collection->paginate($limit);
            $flatTree = $paginate->toFlatTree();

            $temp = $paginate->toArray();
            unset($temp['data']);
            $flatTree->put('pagination', $temp);

            return $flatTree;
        }

        return $collection->orderBy($order_by, $order)->paginate($limit);
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