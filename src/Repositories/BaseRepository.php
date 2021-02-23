<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Closure;

class BaseRepository implements BaseRepositoryInterface
{

    protected $model;

    protected $package = null;

    protected $modelName = 'Base';

    protected $ignore_keys = ['limit', 'order_by', 'order', 'state', 'search_keys'];

    protected $order_by_ignore_keys = ['index', 'id', 'created_at', 'updated_at'];


    public function __construct(Model $model)
    {
        $this->model = $model;
    }


    public function add(Collection $input)
    {
        if ($this->model->hasAttribute('ordering')) {
            $last = $this->getLatestOrdering($input);
            if ($last) {
                $inputOrdering = $input->get('ordering');

                if (!$inputOrdering) {
                    $input->put('ordering', $last->ordering + 1);
                } else {
                    if ($inputOrdering >= $last->ordering) {
                        $input->put('ordering', $last->ordering + 1);
                    } else {
                        if ($inputOrdering <= 1) {
                            $input->put('ordering', 1);
                        }

                        $update_items = $this->getOrderingUpdateItems($input,
                           'ordering',
                            (int)$input->get('ordering') - 1,
                            $last->ordering
                        );

                        $result = $update_items->each(function ($item, $key) {
                            $item->ordering++;
                            return $item->save();
                        });
                    }
                }
            } else {
                $input->put('ordering', 1);
            }
        }

        $input = $input->only($this->model->getFillable());
        $item = $this->create($input->toArray());

        return $item;
    }


    /**
     * 取得所有資料
     * @return BaseModel[]|\Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function all()
    {
        return $this->model->all();
    }


    public function checkout($item, $user)
    {
        if ($item->locked_by == 0
            || $item->locked_by == $user->id
            || $user->higherPermissionThan($item->locked_by)) {

            $item->locked_by = 0;
            $item->locked_at = null;

            return $this->update($item, $item);
        } else {
            return false;
        }
    }


    /**
     * 建立資料
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }


    /**
     * @param $id
     * @param null|Model $model
     * @return boolean
     */
    public function delete($id, $model = null)
    {
        if ($model === null) {
            $model = $this->model->find($id);
            return $model ? $model->delete() : false;
        } else {
            return $model->delete();
        }
    }


    /**
     * @param $id
     * @param array $eagers
     *
     */
    public function find($id, $eagers = [])
    {
        return count($eagers)
            ? $this->model->with($eagers)->find($id)
            : $this->model->find($id);
    }


    public function findBy($field, $operator, $value, $eagers = [])
    {
        return count($eagers)
            ? $this->model->with($eagers)->where($field, $operator, $value)->get()
            : $this->model->where($field, $operator, $value)->get();
    }


    public function findBySpecial($type, $key, $value, $eagers = [])
    {
        return count($eagers)
            ? $this->model->with($eagers)->{$type}($key, $value)->get()
            : $this->model->{$type}($key, $value)->get();
    }


    public function findByChain($fields, $operators, $values, $eagers = [])
    {
        $model = count($eagers) ? $this->model->with($eagers) : $this->model;

        foreach ($fields as $key => $field) {
            $model = $model->where($field, $operators[$key], $values[$key]);
        }
        return $model->get();
    }


    // 取出所有欲刪除之 item 後的所有 items
    public function findDeleteSiblings($ordering)
    {
        return $this->model->where('ordering', '>', $ordering)->get();
    }


    public function findOrderingInterval($parent_id, $origin, $modified)
    {
        $query = $this->model->where('parent_id', $parent_id);
        if ($origin > $modified) {   // 排序向上移動
            $query = $query->where('ordering', '>=', $modified)
                ->where('ordering', '<', $origin);
        } else { // 排序向下移動
            $query = $query->where('ordering', '>', $origin)
                ->where('ordering', '<=', $modified);
        }

        return $query->get();
    }


    public function fixTree()
    {
        $this->model->fixTree();
    }


    public function getLatestOrdering(Collection $input)
    {
        return $this->model
            ->orderBy($input->get('orderingKey') ?: 'ordering', 'desc')
            ->limit(1)
            ->first();
    }


    public function getModel()
    {
        return $this->model;
    }


    // 找出除了自己以外的需要更新的項目
    public function getOrderingUpdateItems(Collection $input, $orderingKey, $origin, $final, $extraRules = [])
    {
        $keys = [$orderingKey, $orderingKey];

        if ($origin > $final) {
            $operators = ['>=', '<'];
            $values = [$final, $origin];
        } else {
            $operators = ['>', '<='];
            $values = [$origin, $final];
        }

        if ($this->model->hasAttribute('category_id')) {
            $keys[] = 'category_id';
            $operators[] = '=';
            $values[] = $input->get('category_id');
        }

        foreach ($extraRules as $extraRule) {
            $keys[] = $extraRule[0];
            $operators[] = $extraRule[1];
            $values[] = $extraRule[2];
        }

        return $this->findByChain($keys, $operators, $values);
    }


    public function getQuery(Collection $input)
    {
        $query = $this->model;
        foreach ($input->toArray() as $key => $item) {
            if (!in_array($key, $this->ignore_keys)) {
                if ($key == 'search' && !InputHelper::null($input, 'search')) {
                    $query = $query->where(function ($query) use ($item, $input) {
                        $search_keys = $input->get('search_keys');

                        foreach ($search_keys as $search_key) {
                            $query->orWhere($search_key, 'LIKE', '%%' . $item . '%%');
                        }

                    });
                } elseif ($key == 'special_queries') {
                    foreach ($item as $q) {
                        if (count($q) == 2) {
                            foreach ($q['callback'] as $c) {
                                $query = $query->{$c['type']}($c['key'], $c['operator'], $c['value']);
                            }
                        } elseif (count($q) == 3) {
                            if (array_key_exists('type', $q)) {

                                $query = $query->{$q['type']}($q['key'], $q['value']);
                            } else {
                                $query = $query->where($q['key'], $q['operator'], $q['value']);
                            }
                        } elseif (count($q) == 4) {
                            $query = $query->{$q['type']}($q['key'], $q['operator'], $q['value']);
                        }
                    }
                } elseif ($key == 'without_root') {
                    $query = $query->where('title', '!=', 'ROOT');
                } elseif ($key == 'where') {
                    if (gettype($item) == 'array') {
                        foreach ($item as $q) {
                            $query = $query->where($q['key'], $q['operator'], $q['value']);
                        }
                    } elseif ($item instanceof Closure) {
                        $query = $query->where($item);
                    } else {
                        $query = $query->where($item);
                    }
                } elseif($key == 'whereIn') {
                    $query = $query->whereIn($item['key'], $item['value']);
                } elseif ($key == 'whereHas') {
                    foreach ($item as $q) {
                        $query = $query->whereHas($q['relation'], $q['callback']);
                    }
                } elseif ($key == 'orWhereHas') {
                    foreach ($item as $q) {
                        $query = $query->orWhereHas($q['relation'], $q['callback']);
                    }
                } elseif ($key == 'eagers') {
                    foreach ($input->get('eagers') as $eager_key => $eager) {
                        $query = $query->with($eager);
                    }
                } elseif ($key == 'loads') {
                    foreach ($input->get('loads') as $load) {
                        $query = $query->load($load);
                    }
                } else {
                    if ($item != null) {
                        // 需要重寫這段
                        if ($this->isNested()) {
                            if ($key == 'id') {
                                $category = $this->find($input->get('id'));
                                $query = $query->where('_lft', '>=', $category->_lft)
                                    ->where('_rgt', '<=', $category->_rgt);
                            } else if ($key == 'tag_id') {
                                $tag = $this->find($input->get('tag_id'));
                                $query = $query->where('_lft', '>', $tag->_lft)
                                    ->where('_rgt', '>', $tag->_rgt);
                            } else {
                                $query = $query->where("$key", '=', $item);
                            }
                        } else {
//                            if ($key == 'category_id')
//                            {
//                                $query = $query->whereIn('category_id', $item);
//                            }
//                            else
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
        $this_trait = class_uses($this->model);
        $parent_trait = class_uses(get_parent_class($this->model));

        return (in_array($trait, $this_trait) || in_array($trait, $parent_trait))
            ? true
            : false;
    }


    public function lock($id)
    {
        $user = Auth::guard('api')->user();

        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        $item->lock_by = $user->id;
        $item->lock_at = now();

        return $item->save();
    }


    /**
     * 取得參數某 key 的所有id
     * @param $params
     * @param $key
     * @return array
     */
    public function getParamsIds($params, $key)
    {
        $ids = [];
        foreach ($params[$key] as $param) {
            $ids[] = $param->id;
        }

        return $ids;
    }


    public function getRelation($model, $relation)
    {
        return $model->getRelationValue($relation);
    }


    public function ordering(Collection $input, $item)
    {
        $orderingKey = $input->get('orderingKey');
        $input_order = $input->get('order');
        $origin = $item->{$orderingKey};
        $diff = $input->get('index_diff');

        $latestItem = $this->getLatestOrdering($input);

        if ($input_order == 'asc') {
            $final = $origin + $diff;
            // 有最新項目（也就是不是沒資料）並且 ordering 超出界線
            if ($latestItem && ($final <= 0 || $final > $latestItem->{$orderingKey})) {
                return false;
            }
            $item->{$orderingKey} = $final;
            $update_items = $this->getOrderingUpdateItems($input, $orderingKey, $origin, $item->{$orderingKey}, $input->get('extraRules') ?: []);
            if ($update_items->count()) {
                $update_items->each(function ($update_item) use ($orderingKey, $input_order, $diff) {
                    $diff < 0 ? $update_item->{$orderingKey}++ : $update_item->{$orderingKey}--;
                    return $update_item->save();
                });
            }
        } else {
            $final = $origin - $diff;
            // 有最新項目（也就是不是沒資料）並且 ordering 超出界線
            if ($latestItem && ($final <= 0 || $final > $latestItem->{$orderingKey})) {
                return false;
            }
            $item->{$orderingKey} = $origin - $diff;
            $update_items = $this->getOrderingUpdateItems($input, $orderingKey, $origin, $item->{$orderingKey}, $input->get('extraRules') ?: []);
            if ($update_items->count()) {
                $update_items->each(function ($update_item) use ($orderingKey, $input_order, $diff) {
                    $diff < 0 ? $update_item->{$orderingKey}-- : $update_item->{$orderingKey}++;
                    return $update_item->save();
                });
            }
        }

        return $item->save();
    }


    public function paginate($items, $perPage = 25, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $perPage = $perPage ?: 1000000;

        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginate = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            $options
        );

        if (count($options)) {
            $url = url()->current() . '?';
            $counter = 0;
            foreach ($options as $key => $option) {
                $url .= $key . '=' . $option;
                $counter++;
                $counter != count($options) ? $url .= '&' : true;
            }
            $paginate = $paginate->setPath($url);
        } else {
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
        $order_by = InputHelper::getCollectionKey($input, 'order_by', $this->model->getOrderBy());
        if (!$this->model->hasAttribute($order_by)
            && !in_array($order_by, $this->order_by_ignore_keys)) {
            throw new HttpResponseException(
                ResponseHelper::genResponse('INPUT_INVALID', [
                    'order_by' => $order_by],
                    $this->package,
                    $this->modelName
                )
            );
        }
        $limit = (int)InputHelper::getCollectionKey($input, 'limit', $this->model->getLimit());
        $order = InputHelper::getCollectionKey($input, 'order', $this->model->getOrder());
        $state = InputHelper::getCollectionKey($input, 'state', [0, 1]);
        $language = InputHelper::getCollectionKey($input, 'language', '');

        $query = $this->getQuery($input);

        if ($this->model->hasAttribute('state')
            && $this->model->getTable() != 'users') {
            if (is_array($state)) {
                $query = $query->whereIn('state', $state);
            } else {
                $query = $query->where('state', '=', $state);
            }
        }

        if ($this->model->hasAttribute('language')) {
            if ($language != '') {
                $query = $query->where('language', $language);
            }
        }

        if ($this->isNested()) { //重組出樹狀
            $query = $query->orderBy('_lft', $order);
        } else {
            $query = $query->orderBy($order_by, $order);
            if ($this->model->hasAttribute('publish_up')) {
                $query = $query->orderBy('publish_up', 'desc');
            }
        }

        if ($limit == 0) {
            $items = $paginate
                ? $query->paginate(1000000)
                : $query->get();
        } else {
            $items = $paginate
                ? $query->paginate($limit)
                : $query->get();
        }

        return $items;
    }


    /**
     * @param $item
     * @param $state
     * @param string $key
     * @return boolean
     */
    public function state($item, $state, $key = 'state')
    {
        $item->{$key} = $state;

        return $item->save();
    }


    public function unlock($item)
    {
        $item->lock_by = 0;
        $item->lock_at = null;

        return $item->save();
    }


    public function update($item, $model = null)
    {
        if ($model !== null) {
            if ($item != $model) {
                foreach ($item as $key => $value) {
                    if ($model->hasAttribute($key)) {
                        $model->{$key} = $value;
                    }
                }
            }

            return $model->save();
        } else {
            return $this->model->find($item['id'])->update($item);
        }
    }


    public function whereHas($relation, Closure $closure)
    {
        return $this->model->whereHas($relation, $closure);
    }


    public function with($relations)
    {
        return $this->model->with($relations);
    }
}
