<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    protected $package = null;

    protected $modelName = 'Base';


    public function __construct(BaseModel $model)
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

        $fillableData = $this->model->getFillable();
        $input = $input->only($fillableData);
        $item = $this->create($input->toArray());
        if(!$item) {
            throw new InternalServerErrorException('CreateFail', $fillableData);
        }


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


    public function restore($item, $user)
    {
        $result =  $this->update($item, [
            'lock_by' => null,
            'lock_at' => null,
        ]);

        if (!$result) {
            $pk = $this->model->getPrimaryKey();
            throw new InternalServerErrorException('RestoreFail', ['pk' => $pk]);
        }

        return $result;
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
     * @return bool
     */
    public function delete($model)
    {
        $result = $model->delete();
        if (!$result) {
            throw new InternalServerErrorException('DeleteFail');
        }

        return $result;
    }


    public function find($value, QueryCapsule $q = null)
    {
        $q = $q ?? new QueryCapsule();
        $primaryKey = $this->model->getPrimaryKey();
        $q->where($primaryKey, $value);

        $result = $q->exec($this->model);
        if (!$result->count()) {
            throw new NotFoundException('ItemNotExist', [$primaryKey => $value]);
        }

        return $result->first();
    }


    public function findBy($field, $operator, $value, QueryCapsule $q = null)
    {
        $q = $q ?? new QueryCapsule();
        $q->where($field, $operator, $value);

        $result = $q->exec($this->model);

        return $result;
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
        // 這邊根據排序方向，要反著取才有辦法拿到最新的那個item
        if ($orderDir = $input->get('order')) {
            $orderDir = $orderDir == 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $orderDir = 'desc';
        }

        return $this->model
            ->orderBy('ordering', $orderDir)
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


    public function getFillableInput(Collection $input)
    {
        return $input->only($this->model->getFillable())->all();
    }


    public function ordering(Collection $input, $item)
    {
        $orderingKey = 'ordering';
        $input_order = $input->get('order');
        $origin = $item->ordering;
        $diff = $input->get('index_diff');

        $latestItem = $this->getLatestOrdering($input);
        if ($input_order == 'asc') {
            $final = $origin + $diff;
            // 有最新項目（也就是不是沒資料）並且 ordering 超出界線
            if ($latestItem && ($final <= 0 || $final > $latestItem->ordering)) {
                return false;
            }

            $item->ordering = $final;
            $update_items = $this->getOrderingUpdateItems($input, $orderingKey, $origin, $item->{$orderingKey}, $input->get('extraRules') ?: []);
            if ($update_items->count()) {
                $update_items->each(function ($update_item) use ($orderingKey, $input_order, $diff) {
                    $diff < 0 ? $update_item->{$orderingKey}++ : $update_item->{$orderingKey}--;
                    return $update_item->save();
                });
            }
        } else {
            $final = $origin - $diff;
            echo $final;
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


    public function modify($model, $data)
    {
        $fillable = $this->getFillableInput($data);
        $result = $this->update($model, $fillable);;
        if (!$result) {
            throw new InternalServerErrorException('UpdateFail', $data);
        }
        return $result;
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


    public function search(Collection $data)
    {
        if (!$data->has('limit')) {
            $data->put('limit', $this->model->getPerPage());
        }

        $q = $data->has('q')
            ? $data->get('q')
            : new QueryCapsule();

        $q->getQuery($data->except('q'));

        $result = $q->exec($this->model);

        return $result;
    }


    /**
     * @param $item
     * @param $state
     * @param string $key
     * @return bool
     */
    public function state($item, $state, $key = 'state')
    {
        $result =  $this->update($item, [$key => $state]);
        if (!$result) {
            $pk = $this->model->getPrimaryKey();
            throw new InternalServerErrorException('StateFail', [$pk => $item->{$pk}]);
        }

        return $result;
    }


    public function update($item, array $data)
    {
        return $item->update($data);
    }
}
