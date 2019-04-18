<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Models\Repositories\Interfaces\BaseRepositoryInterface;
use DaydreamLab\User\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;


class BaseRepository implements BaseRepositoryInterface
{

    /**
     * @var BaseModel
     */
    protected $model;

    protected $ignore_keys = ['limit', 'order_by', 'order', 'state', 'search_keys'];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function add(Collection $input)
    {
        if ($this->model->hasAttribute('ordering'))
        {
            $query = $this->model;

            if($this->model->hasAttribute('category_id'))
            {
                $query = $query->where('category_id', $input->get('category_id'));
            }

            // get last data collection
            $data = $query->orderBy('ordering', 'desc')->limit(1)->get();
            if ($data->count())
            {
                $last       = $data->first();
                $ordering   = $input->get('ordering');

                if (InputHelper::null($input, 'ordering'))
                {
                    $input->put('ordering', $last->ordering + 1);
                }
                else
                {
                    if ($ordering >= $last->ordering)
                    {
                        $input->put('ordering', $last->ordering + 1);
                    }
                    else
                    {
                        if ($ordering <= 0)
                        {
                            $input->put('ordering', 1);
                        }

                        $update_items = $this->model->where('ordering', '>=', $ordering)
                                                    ->where('ordering', '<=', $last->ordering)
                                                    ->get();
                        $result = $update_items->each(function ($item, $key){
                            $item->ordering++;
                            return $this->update($item, $item);
                        });

                        if (!$result)
                        {
                            return $result;
                        }
                    }
                }
            }
            else
            {
                $input->put('ordering', 1);
            }
        }

        $item = $this->create($input->toArray());

        return $item;
    }


    public function all()
    {
        return $this->model->all();
    }


    public function checkout($input)
    {
        /**
         * @var User $user
         */
        $user = Auth::guard('api')->user();


        foreach ($input->ids as $id)
        {
            $item = $this->model->find($id);
            if ($item->locked_by == 0 || $item->locked_by == $user->id || $user->higherPermissionThan($item->locked_by))
            {
                $item->locked_by = 0;
                $item->locked_at = null;
                return $this->update($item, $item);
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


    public function delete($id, $model = null)
    {
        if ($model === null)
        {
            $item = $this->model->find($id);

            return $item ? $item->delete() : false;
        }
        else
        {
            return $model->delete();
        }
    }


    public function find($id)
    {
        return $this->model->find($id);
    }


    public function findBy($field, $operator, $value)
    {
        return $this->model->where($field, $operator, $value)->get();
    }


    public function findBySpecial($type, $key, $value)
    {
        return $this->model->{$type}($key, $value)->get();
    }


    public function findByChain($fields, $operators, $values)
    {
        $model = $this->model;
        foreach ($fields as $key => $field) {
            $model = $model->where($field , $operators[$key], $values[$key]);
        }
        return $model->get();
    }


    // 取出所有欲刪除之 item 後的所有 items
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
            if (!in_array($key, $this->ignore_keys))
            {
                if ($key == 'search' && !InputHelper::null($input, 'search'))
                {
                    $query = $query->where(function ($query) use ($item, $input) {

                        $search_keys = $input->get('search_keys');

                        foreach ($search_keys as $search_key)
                        {
                            $query->orWhere($search_key, 'LIKE', '%%'.$item.'%%');
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
                elseif ($key == 'without_root')
                {
                    $query = $query->where('title', '!=', 'ROOT');
                }
                elseif ($key == 'where')
                {
                    foreach ($item as $q)
                    {
                        $query = $query->where($q['key'], $q['operator'], $q['value']);
                    }
                }

                elseif ($key == 'eagers')
                {
                    foreach ($input->get('eagers') as $eager)
                    {
                        $query = $query->with($eager);
                    }

                }
                elseif ($key == 'loads')
                {
                    foreach ($input->get('loads') as $load)
                    {
                        $query = $query->load($load);
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
//                            if ($key == 'category_id')
//                            {
//                                $query = $query->whereIn('category_id', $item);
//                            }
//                            else
//                            {
                                $query = $query->where("$key", '=', $item);
//                            }
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


    public function getParamsIds($params, $key)
    {
        $ids = [];
        foreach ($params[$key] as $param)
        {
            $ids[] = $param->id;
        }

        return $ids;
    }


    // Get model's relation
    public function getRelation($model, $relation)
    {
        return $model->getRelationValue($relation);
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


        if (count($options))
        {
            $url = url()->current() . '?';
            $counter = 0;
            foreach ($options as $key => $option)
            {
                $url .= $key . '=' .$option;
                $counter++;
                $counter != count($options) ? $url.= '&' : true;
            }
            $paginate = $paginate->setPath($url);
        }
        else
        {
            $paginate = $paginate->setPath(url()->current());
        }

        return $paginate;
    }


    /**
     * @param Collection $input
     * @param bool $paginate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|static[]
     */
    public function search(Collection $input, $paginate = true)
    {
        $order_by   = InputHelper::getCollectionKey($input, 'order_by', $this->model->getOrderBy());
        $limit      = (int)InputHelper::getCollectionKey($input, 'limit', $this->model->getLimit());
        $order      = InputHelper::getCollectionKey($input, 'order', $this->model->getOrder());
        $state      = (int)InputHelper::getCollectionKey($input, 'state', [0,1]);
        $language   = InputHelper::getCollectionKey($input, 'language', '') ;

        $query = $this->getQuery($input);

        if ($this->model->hasAttribute('state') && $this->model->getTable() != 'users')
        {
            if (is_array($state))
            {
                $query = $query->whereIn('state', $state);
            }
            else{
                $query = $query->where('state', '=', $state);
            }
        }

        if ($this->model->hasAttribute('language'))
        {
            if ($language != '')
            {
                $query = $query->where('language', $language);
            }
        }




        if ($this->isNested()) //重組出樹狀
        {
            $items = $paginate ? $query->orderBy('_lft', $order)->paginate($limit)
                                : $query->orderBy('_lft', $order)->get();
        }
        else
        {
            $query = $query->orderBy($order_by, $order);
            if ($this->model->hasAttribute('publish_up'))
            {
                $query = $query->orderBy('publish_up', 'desc');
            }

            $items = $paginate ? $query->paginate($limit)
                                : $query->get();
        }

        return $items;
    }


    public function state($id, $state, $key = 'state')
    {
        $item = $this->find($id);
        if ($item) {
            $item->{$key} = $state;
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


    public function update($item, $model = null)
    {
        if ($model !== null)
        {
            if ($item != $model)
            {
                foreach ($item as $key => $value)
                {
                    $model->{$key} = $value;
                }
            }

            return $model->save();
        }
        else
        {
            return $this->model->find($item['id'])->update($item);
        }
    }


    public function with($relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }
}