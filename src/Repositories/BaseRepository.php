<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use DaydreamLab\User\Models\Asset\Admin\AssetAdmin;
use DaydreamLab\User\Models\Asset\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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


    public function isNested()
    {
        $trait = 'Kalnoy\Nestedset\NodeTrait';
        $this_trait     = class_uses($this->model);
        $parent_trait   = class_uses(get_parent_class($this->model));

        return (in_array($trait, $this_trait) || in_array($trait, $parent_trait)) ? true : false;
    }



    public function ordering(Collection $input)
    {
        $item   = $this->find($input->id);
        $origin = $item->ordering;
        $item->ordering = $origin + $input->index_diff;

        if ($input->index_diff >= 0)
        {
            $update_items = $this->findByChain(['ordering', 'ordering'], ['>=', '<'], [$origin, $origin + $input->index_diff]);
            $result = $update_items->each(function ($item) {
                $item->ordering--;
                return $item->save();
            });
        }
        else
        {
            $update_items = $this->findByChain(['ordering', 'ordering'], ['>=', '<'], [$origin + $input->index_diff, $origin]);
            $result = $update_items->each(function ($item) {
                $item->ordering++;
                return $item->save();
            });
        }

        return $result ? $item->save() : $result;
    }


    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginate = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        $paginate = $paginate->setPath(url()->current());

        return $paginate;
    }



    public function search(Collection $input)
    {
        $order_by   = !InputHelper::null($input, 'order_by') ? $input->order_by : $this->model->getOrderBy();
        $limit      = !InputHelper::null($input, 'limit')    ? $input->limit    : $this->model->getLimit();
        $order      = !InputHelper::null($input, 'order')    ? $input->order    : $this->model->getOrder();
        $state      = !InputHelper::null($input, 'state')    ? $input->state    : 1;
        $language   = !InputHelper::null($input, 'language') ? $input->language : 'tw';
        $access     = !InputHelper::null($input, 'access')   ? $input->access   : '8';


        $query = $this->model;

        if ($this->isNested() && $input->count() == 1 && !InputHelper::null($input, 'limit'))
        {
            $query = $query->where('title', '!=', 'ROOT');
            $copy  = new Collection($query->orderBy('ordering', 'asc')->get()->toFlatTree());
            $paginate = $this->paginate($copy, $limit);

            return $paginate;
        }


        foreach ($input->toArray() as $key => $item)
        {
            if ($key != 'limit' && $key !='order' && $key !='order_by' && $key !='state')
            {
                if ($key == 'search')
                {
                    $query = $query->where(function ($query) use ($item) {

                        $query->where('title', 'LIKE', '%%'.$item.'%%');
                        if (Schema::hasColumn($this->model->getTable(), 'introtext'))
                        {
                            $query->orWhere('introtext', 'LIKE', '%%'.$item.'%%');
                        }
                        if (Schema::hasColumn($this->model->getTable(), 'description'))
                        {
                            $query->orWhere('description', 'LIKE', '%%'.$item.'%%');
                        }
                    });
                }
                else
                {
                    if ($item != null)
                    {
                        $query = $query->where("$key", '=', $item);
                    }
                }
            }
        }


        if (Schema::hasColumn($this->model->getTable(), '_lft'))
        {
            $query = $query->where('title', '!=', 'ROOT');
        }

        if (Schema::hasColumn($this->model->getTable(), 'state'))
        {
            $query = $query->where('state', '=', $state);
        }

        if (Schema::hasColumn($this->model->getTable(), 'language'))
        {
            $query = $query->where('language', '=', $language);
        }

        return $query->orderBy($order_by, $order)->paginate($limit);
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