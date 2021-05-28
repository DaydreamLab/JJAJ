<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Exceptions\OutOfBoundException;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;
use function Psy\sh;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    protected $package = null;

    protected $modelName = '';

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }

    /**
     * 新增項目
     * @param Collection $input
     * @return mixed
     * @throws InternalServerErrorException
     */
    public function add(Collection $input)
    {
        if ($this->model->hasAttribute('ordering')) {
            $this->handleAddOrdering($input, 'ordering');
        }

        if ($this->model->hasAttribute('featured') && $input->get('featured')) {
            $this->handleAddOrdering($input, 'featured_ordering');
        }

        $fillableData = $this->model->getFillable();
        $input = $input->only($fillableData);

        $item = $this->create($input->toArray());

        if(!$item) {
            throw new InternalServerErrorException('CreateFail', $fillableData, null, $this->modelName);
        }


        return $item;
    }


    /**
     * 新增槽狀結構項目
     * @param Collection $input
     * @return false|mixed
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws NotFoundException
     */
    public function addNested(Collection $input)
    {
        if (!InputHelper::null($input, 'parent_id')) {
            if (!InputHelper::null($input, 'ordering')) {
                $q = $input->get('q') ?: new QueryCapsule();
                $q = $q->where('parent_id', $input->get('parent_id'))
                    ->where('ordering', $input->get('ordering'));

                $selected = $this->search(collect(['q' => $q]))->first();
                $new      = $this->create($input->toArray());
                $selected ? $new->beforeNode($selected)->save() : true;

                return $this->handleNextSiblingsOrdering($new->refresh(), 'add') ? $new : false;
            } else {
                $parent     = $this->find($input->get('parent_id'));
                if (!$parent) {
                    throw new NotFoundException('ItemNotExist', [
                        'parent_id' => $input->get('parent_id')
                    ], null, $this->modelName);
                }
                $last_child = $parent->children->last();
                if ($last_child) {
                    $lastOrdering = $last_child->ordering + 1;
                    $input->put('ordering', $lastOrdering);
                    $new   = $this->create($input->toArray());

                    return $new->afterNode($last_child)->save() ? $new : false;
                } else {
                    $ordering =  1;
                    $input->put('ordering', $ordering);
                    $new   = $this->create($input->toArray());
                    return $parent->appendNode($new) ? $new : false;
                }
            }
        } else {
            # 代表 model = category
            if ($this->model->hasAttribute('extension')) {
                if($input->get('extension') != '') {
                    $q = new QueryCapsule();
                    $q = $q->where('title', 'ROOT')
                        ->where('extension', $input->get('extension'));
                    $parent = $this->search(collect(['q' => $q]))->first();
                    if (!$parent) {
                        throw new ForbiddenException('InvalidInput', [
                            'extension' => $input->get('extension'),
                            'parent_id' => null
                        ], null, $this->modelName);
                    }
                    $newNode = $this->create($input->toArray());

                    return $parent->appendNode($newNode) ? $newNode : false;
                } else {
                    throw new ForbiddenException('InvalidInput', [
                        'extension' => null,
                        'parent_id' => null
                    ], null, $this->modelName);
                }
            } else {
                $q = new QueryCapsule();
                $q =$q->whereNull('parent_id')
                    ->max('ordering');

                $lastOrdering = $this->search(collect(['q' => $q]));
                $input->put('ordering', $lastOrdering +1);

                return $this->create($input->toArray());
            }
        }
    }



    /**
     * 取得所有資料
     * @return BaseModel[]|\Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function all()
    {
        return $this->model->all();
    }


    /**
     * 檢查槽狀結構路徑是否重複
     * @param Collection $input
     * @param $parent
     * @return false
     * @throws ForbiddenException
     */
    public function checkPathExist(Collection $input, $parent)
    {
        if($this->repo->getModel()->hasAttribute('path') && $input->get('alias') && $this->repo->getModel()->getTable() != 'assets') {
            $same = $this->repo->findMultiLanguageItem($input);
            if ($same && $same->id != $input->get('id')) {
                throw new ForbiddenException('StoreNestedWithExistPath',  ['path' => $input->get('path')], null, $this->modelName);
            }
        }

        return false;
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
     * 刪除項目
     * @param $id
     * @param null|Model $model
     * @return bool
     */
    public function delete($model)
    {
        $result = $model->delete();
        if (!$result) {
            throw new InternalServerErrorException('DeleteFail', null,null, $this->modelName);
        }

        return $result;
    }


    /**
     * 藉由 pk 找尋項目
     * @param $value
     * @param QueryCapsule|null $q
     * @return mixed
     */
    public function find($value, QueryCapsule $q = null)
    {
        $q = $q ?? new QueryCapsule();
        $primaryKey = $this->model->getPrimaryKey();
        $q->where($primaryKey, $value);

        return $q->exec($this->model)->first();
    }


    /**
     * 藉由 field 找尋項目
     * @param $field
     * @param $operator
     * @param $value
     * @param QueryCapsule|null $q
     * @return mixed
     */
    public function findBy($field, $operator, $value, QueryCapsule $q = null)
    {
        $q = $q ?? new QueryCapsule();
        $q->where($field, $operator, $value);

        $result = $q->exec($this->model);

        return $result;
    }


    /**
     * 找尋槽狀結構下，變更排序的目標項目
     * @param $input
     * @param null $parent
     * @return mixed
     */
    public function findModifyOrderingTargetNode($input, $parent = null)
    {
        $q = new QueryCapsule();
        if ($parent) {
            $q = $q->where('parent_id', $parent->id);
        }

        if ($input->get('ordering') === 0 || $input->get('ordering') === '0') {
            $q = $q->limit(1)
                ->orderBy('ordering', 'asc');
        } elseif ($input->get('ordering')) {
            $q = $q->where('ordering', $input->get('ordering'));
        } else {
            $q = $q->limit(1)
                ->orderBy('ordering', 'desc');
        }

        return $this->search(collect(['q' => $q]))->first();
    }


    /**
     * 找出多語言下，路徑重複的項目
     * @param $input
     * @return mixed
     */
    public function findMultiLanguageItem($input)
    {
        $language_options = ['*'];
        $language = !InputHelper::null($input, 'language') ? $input->get('language') : config('daydreamlab.global.locale');
        if ($language != '*') {
            $language_options[] = $language;
        }

        $query = $this->model;

        // table = menu
        if ($this->getModel()->hasAttribute('host')) {
            $query = $query
                ->where('host', $input->get('host'))
                ->whereIn('language', $language_options);
            $query = !InputHelper::null($input, 'path')
                ? $query->where('path', $input->get('path'))
                : $query;
        } else {
            if ($this->getModel()->hasAttribute('language')) {
                $query = $query
                    ->whereIn('language', $language_options);
                $query = !InputHelper::null($input, 'path')
                    ? $query->where('path', $input->get('path'))
                    : $query;
            } else {
                $query = $query->where('path', $input->get('path'));
            }
        }

        return $query->first();
    }



    public function fixTree()
    {
        $this->model->fixTree();
    }


    public function getModel()
    {
        return $this->model;
    }


    /**
     * 處理新增項目的排序問題
     * - 有輸入排序: 則該排序之後所有項目，排序+1
     * - 沒有輸入排序: 變成最大的排序
     * @param Collection $input
     * @param $key
     */
    public function handleAddOrdering(Collection &$input, $key)
    {
        $q = new QueryCapsule();
        $inputOrdering = $input->get($key);
        if ($inputOrdering !== null) {
            $q = $q->where($key, '>', $inputOrdering);
            $updateItems = $this->search(collect(['q' => $q]));
            $updateItems->each(function ($item) use ($key){
                $item->{$key}++;
                $item->save();
            });
            $input->put($key, $inputOrdering + 1);
        } else {
            $q = $q->max($key);
            $maxFeaturedOrdering = $this->search(collect(['q' => $q]));
            $input->put($key, $maxFeaturedOrdering + 1);
        }
    }


    /**
     * 處理刪除項目時排序問題
     * - 該排序後面的所有項目排序-1
     * @param $ordering
     * @param $key
     */
    public function handleDeleteOrdering($ordering, $key)
    {
        $q = new QueryCapsule();

        $q = $q->where($key, '>', $ordering);

        $updateItems = $this->search(collect(['q' => $q]));
        $updateItems->each(function ($item) use ($key) {
            $item->{$key}--;
            $item->save();
            if ($item->save()) {
                throw new InternalServerErrorException('OrderingFail', null, null, $this->modelName);
            }
        });
    }


    /**
     * 處理槽狀結構下，刪除項目的排序問題
     * @param $item
     */
    public function handleDeleteNestedOrdering($item)
    {
        $item->getNextSiblings()->each(function ($sibling){
            $sibling->ordering--;
            if (!$sibling->save()) {
                throw new InternalServerErrorException('OrderingNestedFail', null, null, $this->modelName);
            }
        });
    }



    public function handleNextSiblingsOrdering($item, $action)
    {
        $item->getNextSiblings()->each(function ($sibling) use ($action) {
           $action == 'add'
               ? $sibling->ordering++
               : $sibling->ordering--;
            if (!$sibling->save()) {
                throw new InternalServerErrorException('OrderingNestedFail', null, null, $this->modelName);
            }
        });

        return true;
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


    /**
     * 修改項目
     * @param $model
     * @param Collection $data
     * @return mixed
     * @throws InternalServerErrorException
     */
    public function modify($model, Collection $data)
    {
        if ($model->hasAttribute('ordering') && $model->ordering != $data->get('ordering')) {
            $q = new QueryCapsule();
            if ($model->ordering > $data->get('ordering')) {
                $q = $q->where('ordering', '>', $data->get('ordering'))
                    ->where('ordering', '<', $model->ordering);
                $updateItems = $this->search(collect(['q' => $q]));
                $updateItems->each(function ($item) {
                   $item->ordering++;
                   $item->save();
                });
            } else {
                $q = $q->where('ordering', '>', $model->ordering)
                    ->where('ordering', '<=', $data->get('ordering'));
                $updateItems = $this->search(collect(['q' => $q]));
                $updateItems->each(function ($item) {
                    $item->ordering--;
                    $item->save();
                });
            }
            $data->put('ordering', $data->get('ordering') + 1);
        }

        if ($model->hasAttribute('featured')) {
            if ($model->featured == 0 && $data->get('featured') == 1) {
                $this->handleOrdering($data, 'featured_ordering');
            } elseif ($model->featured == 1 && $data->get('featured') == 0) {
                $q = new QueryCapsule();
                $q = $q->where('featured_ordering', '>', $model->featured_ordering);
                $updateItems = $this->search(collect(['q' => $q]));
                $updateItems->each(function ($item) {
                    $item->featured_ordering--;
                    $item->save();
                });
                $data->put('featured_ordering', null);
            }
        }

        $fillable = $this->getFillableInput($data);
        $result = $this->update($model, $fillable);;
        if (!$result) {
            throw new InternalServerErrorException('UpdateFail', $data, null, $this->modelName);
        }
        return $result;
    }


    /**
     * 修改槽狀結構項目
     * @param Collection $input
     * @param $parent
     * @param $item
     * @return mixed
     * @throws InternalServerErrorException
     */
    public function modifyNested(Collection $input, $parent, $item)
    {
        // 如果更換了 parent
        if ($item->parent_id != $input->get('parent_id') && $this->model->hasAttribute('ordering')) {
            $this->handleNextSiblingsOrdering($item, 'sub');
        }

        $targetNode = $this->model->hasAttribute('ordering')
            ? $this->findModifyOrderingTargetNode($input, $parent)
            : null;
        if ($targetNode) {
            $item->parent_id = $targetNode->parent ? $targetNode->parent_id : null;
            if (in_array($input->get('ordering'), [0, '0'])) {
                $item->beforeNode($targetNode);
                $input->put('ordering', $input->get('ordering') + 1);
            } else {
                $item->afterNode($targetNode);
                $input->put('ordering', $targetNode->ordering + 1);
            }
        } else {
            if ($this->model->hasAttribute('ordering')) {
                if ($parent) {
                    $input->put('ordering', $parent->children->count());
                } else {
                    $q = new QueryCapsule();
                    $q = $q->whereNull('parent_id');
                    $input->put('ordering', $this->search(collect(['q' => $q]))->count() + 1);
                }
            }
        }

        $result = $this->update($item, $this->getFillableInput($input));
        $this->handleNextSiblingsOrdering($item->refresh(), 'add');
        if (!$result) {
            throw new InternalServerErrorException('UpdateNestedFail', [], null, $this->modelName);
        }

        return $item->refresh();
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
            throw new InternalServerErrorException('StateFail', [$pk => $item->{$pk}], null, $this->modelName);
        }

        return $result;
    }


    public function update($item, array $data)
    {
        return $item->update($data);
    }
}
