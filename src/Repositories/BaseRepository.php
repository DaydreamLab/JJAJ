<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function add(Collection $input)
    {

        if (Helper::tablePropertyExist($this->model, 'ordering'))
        {
            if (InputHelper::null($input, 'ordering'))
            {
                $query = $this->model;

                if(Helper::tablePropertyExist($this->model, 'category_id'))
                {
                    $query = $query->where('category_id', $input->category_id);
                }


                $last = $query->orderBy('ordering', 'desc')->get()->first();
                if ($last)
                {
                    $input->forget('ordering');
                    $input->put('ordering', $last->ordering + 1);
                }
                else
                {
                    $input->forget('ordering');
                    $input->put('ordering', 1);
                }
            }
        }

        return $this->create($input->toArray());
    }


    public function all()
    {
        return $this->model->all();
    }


    public function checkout($input)
    {
        $user = Auth::guard('api')->user();
        foreach ($input->ids as $id)
        {
            $item = $this->model->find($id);
            if ($item->locked_by == 0 || $item->locked_by == $user->id || $user->groups->contains('title', 'Super User'))
            {
                $item->locked_by = 0;
                $item->locked_at = null;
                if(!$item->save())
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }

        return true;
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


    // 取出  ordering 大於刪除之item 後所有 items
    public function findDeleteSiblings($ordering)
    {
        return $this->findBy('ordering', '>', $ordering);
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


    public function getModel()
    {
        return $this->model;
    }


    public function getQuery(Collection $input)
    {
        $query = $this->model;

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
                elseif ($key == 'special_queries')
                {
                    foreach ($item as $q)
                    {
                        $query = $query->{$q['type']}($q['key'], $q['value']);
                    }
                }
                else
                {
                    if ($item != null)
                    {
                        // 需要重寫這段
                        if ($this->isNested())
                        {
                            if ($key == 'id')
                            {
                                $category = $this->find($input->id);
                                $query = $query->where('_lft', '>=', $category->_lft)
                                                ->where('_rgt', '<=', $category->_rgt);
                            }
                            else if ($key == 'tag_id')
                            {
                                $tag = $this->find($input->tag_id);
                                $query = $query->where('_lft', '>', $tag->_lft)
                                    ->where('_rgt', '>', $tag->_rgt);
                            }
                            else
                            {
                                $query = $query->where("$key", '=', $item);
                            }
                        }
                        else
                        {

                            if ($key == 'category_id')
                            {
                                $query = $query->whereIn('category_id', $item);
                            }
                            else
                            {
                                $query = $query->where("$key", '=', $item);
                            }
                        }

                    }
                }
            }
        }

        return $query;
    }


    public function isNested()
    {
        $trait = 'Kalnoy\Nestedset\NodeTrait';
        $this_trait     = class_uses($this->model);
        $parent_trait   = class_uses(get_parent_class($this->model));

        return (in_array($trait, $this_trait) || in_array($trait, $parent_trait)) ? true : false;
    }


    public function lock($id)
    {
        $user = Auth::guard('api')->user();

        $item = $this->find($id);
        if (!$item)
        {
            return false;
        }

        $item->lock_by = $user->id;
        $item->lock_at = now();

        return $item->save();
    }


    public function ordering(Collection $input, $orderingKey)
    {
        $item   = $this->find($input->id);
        $origin = $item->{$orderingKey};
        $item->{$orderingKey} = $origin + $input->index_diff;

        if ($input->index_diff >= 0)
        {
            $update_items = $this->findByChain([$orderingKey, $orderingKey], ['>', '<='], [$origin, $origin + $input->index_diff]);
            $result = $update_items->each(function ($item) use ($orderingKey) {
                $item->{$orderingKey}--;
                return $item->save();
            });
        }
        else
        {
            $update_items = $this->findByChain([$orderingKey, $orderingKey], ['>=', '<'], [$origin + $input->index_diff, $origin]);
            $result = $update_items->each(function ($item) use ($orderingKey){
                $item->{$orderingKey}++;
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
        $order_by   = !InputHelper::null($input, 'order_by') ? $input->get('order_by') : $this->model->getOrderBy();
        $limit      = !InputHelper::null($input, 'limit')    ? $input->get('limit')    : $this->model->getLimit();
        $order      = !InputHelper::null($input, 'order')    ? $input->get('order')    : $this->model->getOrder();
        $state      = !InputHelper::null($input, 'state')    ? $input->get('state')    : [0,1];
        $language   = !InputHelper::null($input, 'language') ? $input->get('language') : ['All','tw'];

        $query = $this->getQuery($input);

        if (Schema::hasColumn($this->model->getTable(), 'state') && $this->model->getTable() != 'users')
        {
            if (is_array($state))
            {
                $query = $query->whereIn('state', $state);
            }
            else{
                $query = $query->where('state', '=', $state);
            }
        }

        if (Schema::hasColumn($this->model->getTable(), 'language')&& $this->model->getTable() != 'users')
        {
            if (is_array($language))
            {
                $query = $query->whereIn('language', $language);
            }
            else
            {
                $query = $query->where('language', '=', $language);
            }

        }

        if ($this->isNested()) //重組出樹狀
        {
            $query = $query->where('title', '!=', 'ROOT');
            $items = $query->orderBy('_lft', $order)->paginate($limit);
        }
        else
        {
            $items = $query->orderBy($order_by, $order)->paginate($limit);
        }
        return $items;
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


    public function unlock($id)
    {
        $item = $this->find($id);
        if (!$item)
        {
            return false;
        }

        $item->lock_by = 0;
        $item->lock_at = null;

        return $item->save();
    }


    public function update($item)
    {
        return $this->model->find($item['id'])->update($item);
    }

}