<?php

namespace DaydreamLab\JJAJ\Repositories;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\Interfaces\BaseRepositoryInterface;
use DaydreamLab\JJAJ\Traits\FormatDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;

class BaseRepository implements BaseRepositoryInterface
{
    use FormatDateTime;

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

        if ($this->model->hasAttribute('featured') && $input->get('featured') == 1) {
            $this->handleAddOrdering($input, 'featured_ordering');
        }

        $fillableData = $this->model->getFillable();

        $input = $input->only($fillableData);

        $item = $this->create($input->toArray());
        if (!$item) {
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
        $inputParentId = $input->get('parentId') ?: $input->get('parent_id');
        $inputOrdering = $input->get('ordering');
        if ($inputParentId) {
            $parent = $this->find($inputParentId);
            if (!$parent) {
                throw new NotFoundException('ItemNotExist', ['parent_id' => $inputParentId], null, $this->modelName);
            }
            if ($inputOrdering) {
                $children = $parent->children()->get();
                $selected = $children->where('ordering', $inputOrdering)->first();
                if (!$selected) {
                    $input->put('ordering', $children->count() + 1);
                }
                $new      = $this->create($input->toArray());
                if ($selected) {
                    $new->beforeNode($selected)->save();
                    $this->handleNextSiblingsOrdering($new, 'add');
                } else {
                    $parent->appendNode($new);
                }

                return $new;
            } else {
                if ($inputOrdering !== null) {
                    $q = new QueryCapsule();
                    $q = $q->where('parent_id', $inputParentId)
                        ->where('ordering', $inputOrdering);

                    $targetNode = $this->search(collect(['q' => $q]))->first();

                    if ($targetNode) {
                        $this->handleAddOrdering($input, 'ordering');
                        $new = $this->create($this->getFillableInput($input));
                        return $targetNode->beforeNode($new)->save() ? $new : null;
                    } else {
                        return $this->handleLastChildOrdering($input, $parent);
                    }
                } else {
                    return $this->handleLastChildOrdering($input, $parent);
                }
            }
        } else {
            $q = new QueryCapsule();
            $q = $q->whereNull('parent_id');
            if ($inputOrdering) {
                if ($this->getModel()->hasAttribute('path') && $this->getModel()->hasAttribute('alias')) {
                    if ($input->get('extension') != '') {
                        $q = $q->where('extension', $input->get('extension'));
                    } else {
                        throw new ForbiddenException('InvalidInput', [
                            'extension' => null,
                            'parent_id' => null
                        ], null, $this->modelName);
                    }
                }

                $rootItems = $this->search(collect(['q' => $q]));
                $targetNode = $rootItems->where('ordering', $inputOrdering)->first();
                $input->put('ordering', $targetNode ? $inputOrdering + 1 : $rootItems->count());

                $node = $this->create($input->toArray());
                if ($targetNode) {
                    $node->afterNode($targetNode)->save();
                }
                $this->handleNextSiblingsOrdering($node, 'add');
                return $node;
            } else {
                $q = $q->max('ordering');
                $maxOrdering = $this->search(collect(['q' => $q]));
                $input->put('ordering', $maxOrdering + 1);

                return  $this->create($input->toArray());
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
            throw new InternalServerErrorException('DeleteFail', null, null, $this->modelName);
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
    public function findModifyOrderingTargetNode($input, $node = null)
    {
        $inputParentId = $input->get('parentId') ?: $input->get('parent_id');
        $q = new QueryCapsule();
        if (!$inputParentId) {
            $q = $q->where('parent_id', null);
        } elseif ($node->parent_id == $inputParentId) {
            $q = $q->where('parent_id', $node->parent_id);
        } else {
            $q = $q->where('parent_id', $inputParentId);
        }

        if ($input->get('ordering') !== null) {
            if ($input->get('ordering')) {
                $q = $q->where('ordering', $input->get('ordering'));
            } else {
                $q = $q->limit(1)
                    ->orderBy('ordering', 'asc');
            }
        } else {
            $q = $q->limit(1)
                ->orderBy('ordering', 'desc');
        }

        return $this->search(collect(['q' => $q]))->first();
    }


    /**
     * 修正樹狀結構排序問題
     * @param array $nodes
     * @return bool
     */
    public function fixNestedOrdering($nodes = [])
    {
        foreach ($nodes as $key => $node) {
            if ($node->children->count()) {
                $this->fixNestedOrdering($node->children);
            }
            $node->ordering = $key + 1;
            $node->save();
        }

        return true;
    }


    /**
     * 修正樹狀結構排序問題
     */
    public function fixTree()
    {
        $this->model->fixTree();
    }


    /**
     * 取得輸入的 ordering 值並檢查是否有超過可設定範圍
     * @param $inputOrdering
     * @param $maxOrdering
     * @return int
     */
    public function getInputOrderingValue($inputOrdering, $maxOrdering)
    {
        return $inputOrdering === null
            ? $maxOrdering + 1
            : (
                $inputOrdering <= 1
                    ? 1
                    : ($inputOrdering <= $maxOrdering ? $inputOrdering : $maxOrdering + 1)
            );
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
        if ($this->model->hasAttribute('category_id')) {
            $q->where('category_id', $input->get('category_id'));
        }
        $maxOrdering = $q->max($key)->exec($this->model);
        $inputOrdering = $this->getInputOrderingValue($input->get($key), $maxOrdering);
        $q = new QueryCapsule();
        if ($this->model->hasAttribute('category_id')) {
            $q->where('category_id', $input->get('category_id'));
        }
        $q->where($key, '>=', $inputOrdering)
            ->timestamps(false)
            ->increment($key, 1)
            ->exec($this->model);

        $input->put($key, $inputOrdering);
    }


    /**
     * 處理刪除項目時排序問題
     * - 該排序後面的所有項目排序-1
     * @param Collection $input
     * @param $item
     * @param $key
     */
    public function handleDeleteOrdering(Collection &$input, $item, $key)
    {
        $q = new QueryCapsule();
        $q->where($key, '>', $item->{$key});
        if ($this->model->hasAttribute('category_id')) {
            $q->where('category_id', $item->category_id);
        }
        $q->timestamps(false)
            ->decrement($key, 1)
            ->exec($this->model);
    }


    /**
     * 處理槽狀結構下，刪除項目的排序問題
     * @param $item
     */
    public function handleDeleteNestedOrdering($item)
    {
        $item->getNextSiblings()->each(function ($sibling) {
            $sibling->ordering--;
            $sibling->timestamps = false;
            if (!$sibling->save()) {
                throw new InternalServerErrorException('OrderingNestedFail', null, null, $this->modelName);
            }
        });
    }


    /**
     * 處理槽狀結構下，最後一個子結點排序問題
     * @param Collection $input
     * @param $parent
     * @return false|mixed
     */
    public function handleLastChildOrdering(Collection $input, $parent)
    {
        $last_child = $parent->children->last();
        if ($last_child) {
            $lastOrdering = $last_child->ordering + 1;
            $input->put('ordering', $lastOrdering);
            $new   = $this->create($input->toArray());

            return $new->afterNode($last_child)->save() ? $new : false;
        } else {
            $ordering =  1;
            $input->put('ordering', $ordering);
            $new = $this->create($this->getFillableInput($input));
            return $parent->appendNode($new) ? $new : false;
        }
    }


    /**
     * 處理修改項目時，非槽狀結構的排序問題
     * @param Collection $input
     * @param $node
     * @param $key
     */
    public function handleModifyOrdering(Collection &$input, $node, $key)
    {
        if ($this->model->hasAttribute('category_id')) {
            $maxOrdering = (new QueryCapsule())
                ->where('category_id', $input->get('category_id'))
                ->max($key)->exec($this->model);
            $inputOrdering = $this->getInputOrderingValue($input->get($key), $maxOrdering);
            if ($input->get('category_id') == $node->category_id) {
                $nodeOrdering = $node->{$key};
                if ($key == 'featured_ordering' && $input->get('featured') == 0) {
                    if ($nodeOrdering) {
                        (new QueryCapsule())->where('category_id', $node->category_id)
                            ->where($key, '>=', $nodeOrdering)
                            ->orderBy($key, 'asc')
                            ->timestamps(false)
                            ->decrement($key, 1)
                            ->exec($this->model);
                    }
                } else {
                    $nodeOrdering = $nodeOrdering ?? $maxOrdering + 1;
                    if ($nodeOrdering > $inputOrdering) {
                        (new QueryCapsule())->where('category_id', $node->category_id)
                            ->where($key, '>=', $inputOrdering)
                            ->where($key, '<', $nodeOrdering)
                            ->orderBy($key, 'asc')
                            ->timestamps(false)
                            ->increment($key, 1)
                            ->exec($this->model);
                    } else {
                        (new QueryCapsule())->where('category_id', $node->category_id)
                            ->where($key, '>', $nodeOrdering)
                            ->where($key, '<=', $inputOrdering)
                            ->orderBy($key, 'asc')
                            ->timestamps(false)
                            ->decrement($key, 1)
                            ->exec($this->model);
                    }
                }
            } else {
                # 處理原分類因移出所以遞減
                $nodeOrdering = $node->{$key};
                if ($nodeOrdering) {
                    (new QueryCapsule())->where('category_id', $node->category_id)
                        ->where($key, '>', $nodeOrdering)
                        ->orderBy($key, 'asc')
                        ->timestamps(false)
                        ->decrement($key, 1)
                        ->exec($this->model);
                }

                # 處理新分類因新增且有設定排序則遞增
                if (($key != 'featured_ordering' || $input->get('featured') != 0) && $inputOrdering) {
                    # 處理新分類排序
                    (new QueryCapsule())->where('category_id', $input->get('category_id'))
                        ->where($key, '>=', $inputOrdering)
                        ->orderBy($key, 'asc')
                        ->timestamps(false)
                        ->increment($key, 1)
                        ->exec($this->model);
                }
            }
        } else {
            $q = new QueryCapsule();
            $maxOrdering = $q->max($key)->exec($this->model);
            $nodeOrdering = $node->{$key};
            $inputOrdering = $this->getInputOrderingValue($input->get($key), $maxOrdering);
            if ($key == 'featured_ordering' && $input->get('featured') == 0) {
                if ($nodeOrdering) {
                    (new QueryCapsule())->where($key, '>', $nodeOrdering)
                        ->orderBy($key, 'asc')
                        ->timestamps(false)
                        ->decrement($key, 1)
                        ->exec($this->model);
                }
            } else {
                $nodeOrdering = $nodeOrdering ?? $maxOrdering + 1;
                if ($nodeOrdering > $inputOrdering) {
                    (new QueryCapsule())->where($key, '>=', $inputOrdering)
                        ->where($key, '<', $nodeOrdering)
                        ->orderBy($key, 'asc')
                        ->timestamps(false)
                        ->increment($key, 1)
                        ->exec($this->model);
                } else {
                    (new QueryCapsule())->where($key, '>', $nodeOrdering)
                        ->where($key, '<=', $inputOrdering)
                        ->orderBy($key, 'asc')
                        ->timestamps(false)
                        ->decrement($key, 1)
                        ->exec($this->model);
                }
            }
        }

        if ($key == 'featured_ordering' && $input->get('featured') == 0) {
            $input->put($key, null);
        } else {
            $input->put($key, $inputOrdering);
        }
    }


    /**
     * 處理槽狀結構下，下個同層排序問題
     * @param $item
     * @param $action
     * @return bool
     * @throws InternalServerErrorException
     */
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


    /**
     * 鎖定編輯中的項目
     * @param $id
     * @return false
     */
    public function lock($id)
    {
        $user = Auth::guard('api')->user();

        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        $item->lock_by = $user->id;
        $item->lock_at = now()->toDateTimeString();

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
        if ($model->hasAttribute('ordering')) {
            $this->handleModifyOrdering($data, $model, 'ordering');
        }

        if ($model->hasAttribute('featured_ordering')) {
            $this->handleModifyOrdering($data, $model, 'featured_ordering');
        }

        $fillable = $this->getFillableInput($data);
        $result = $this->update($model, $fillable);
        ;
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
    public function modifyNested(Collection $input, $item)
    {
        if ($item->featured == 1) {
            # 精選->非精選，修改精選的排序
            if ($input->get('featured') == 0) {
                $input->put('featured_ordering', null);
                $this->ordering($input, $item, 'featured_ordering');
                $item = $item->referesh();
            }
        } else {
            # 非精選->精選，找出精選最大值
            if ($input->get('featured') == 1) {
                $q = new QueryCapsule();
                $q->max('featured_ordering');
                $maxFeaturedOrdering = $this->search(collect(['q' => $q]));
                $maxFeaturedOrdering = $maxFeaturedOrdering + 1;
                $input->put('featured_ordering', $maxFeaturedOrdering);
            }
        }

        $this->update($item, $this->getFillableInput($input->except(['ordering', 'parent_id'])));
        $this->orderingNested($input, $item);

        return $item;
    }


    /**
     * 處理非槽狀結構的排序問題
     * @param Collection $input
     * @param $model
     * @param $key
     * @return mixed
     * @throws ForbiddenException
     */
    public function ordering(Collection $input, $model, $key)
    {
        if ($key == 'featured_ordering' && $model->featured == 0) {
            throw new ForbiddenException('IsNotFeatured', ['id' => $model->id], null, $this->modelName);
        }

        $this->handleModifyOrdering($input, $model, $key);

        $result = $this->update($model, $input->only($key)->all());

        return $result;
    }


    /**
     * 處理槽狀結構排序問題
     * @param Collection $input
     * @param $node
     * @return mixed
     * @throws NotFoundException
     */
    public function orderingNested(Collection $input, $node)
    {
        $inputParentId = $input->get('parent_id');
        $inputOrdering = $input->get('ordering');
        $q = new QueryCapsule();
        if (!$node->parent_id) {
            if (!$input->get('parent_id')) {
                if ($inputOrdering === null) {
                    $siblings = $q->where('parent_id', null)
                        ->orderBy('ordering', 'asc')
                        ->exec($this->model);
                    $targetNode = $siblings->last();
                    if ($targetNode && $targetNode != $node) {
                        $node->afterNode($targetNode);
                    }
                } else {
                    $siblings =  $q->where('parent_id', null)
                        ->orderBy('ordering', 'asc')
                        ->exec($this->model);
                    if ($inputOrdering === '0' || $inputOrdering === 0) {
                        $targetNode = $siblings->first();
                        if ($targetNode && $targetNode != $node) {
                            $node->beforeNode($targetNode)->save();
                        }
                    } else {
                        $targetNode = $siblings->where('ordering', $inputOrdering)->first();
                        if ($targetNode && $targetNode != $node) {
                            $node->afterNode($targetNode);
                        }
                    }
                }
            } else {
                $newParent = $this->find($inputParentId);
                if (!$newParent) {
                    throw new NotFoundException('ItemNotExist', ['id' => $inputParentId], null, $this->modelName);
                }

                $node->parent_id = $newParent->id;
                $siblings = $newParent->children()->get();
                if ($inputOrdering === null) {
                    $targetNode = $siblings->last();
                    if ($targetNode) {
                        $node->afterNode($targetNode);
                    } else {
                        $node->ordering = 1;
                        return $newParent->appendNode($node);
                    }
                } else {
                    if ($inputOrdering === '0' || $inputOrdering === 0) {
                        $targetNode = $siblings->first();
                        if ($targetNode) {
                            $node->beforeNode($targetNode);
                        } else {
                            return $newParent->appendNode($node);
                        }
                    } else {
                        $targetNode = $siblings->where('ordering', $inputOrdering)->first();
                        if ($targetNode && $targetNode != $node) {
                            $node->afterNode($targetNode);
                        } else {
                            throw new NotFoundException('ItemNotExist', [
                                'parent_id' => $inputParentId,
                                'ordering' => $inputOrdering
                            ], null, $this->modelName);
                        }
                    }
                }
            }
        } else {
            if (!$input->get('parent_id')) {
                $node->parent_id = null;
                $q = new QueryCapsule();
                $siblings = $q->where('parent_id', null)
                    ->orderBy('ordering', 'asc')
                    ->exec($this->model);
                if ($inputOrdering === null) {
                    if (!$siblings->count()) {
                        $input->put('ordering', 1);
                    } else {
                        $node->afterNode($siblings->last());
                    }
                } else {
                    if ($inputOrdering === '0' || $inputOrdering === 0) {
                        if (!$siblings->first()) {
                            $node->parent_id = null;
                        } else {
                            $node->beforeNode($siblings->first());
                        }
                    } else {
                        $targetNode = $siblings->where('ordering', $inputOrdering)->first();
                        if ($targetNode && $targetNode != $node) {
                            $node->afterNode($targetNode);
                        } else {
                            throw new NotFoundException('ItemNotExist', [
                                'parent_id' => $inputParentId,
                                'ordering' => $inputOrdering
                            ], null, $this->modelName);
                        }
                    }
                }
            } else {
                if ($inputParentId == $node->parent_id) {
                    $parent = $node->parent;
                    $siblings = $parent->children()->get();
                    if ($inputOrdering === null) {
                        if ($siblings->last()) {
                            $node->afterNode($siblings->last());
                        } else {
                            return $parent->appendNode($node);
                        }
                    } else {
                        if ($inputOrdering === 0 || $inputOrdering === '0') {
                            if ($siblings->first()) {
                                $node->beforeNode($siblings->first());
                            } else {
                                return $parent->appendNode($node);
                            }
                        } else {
                            $targetNode = $siblings->where('ordering', $inputOrdering)->first();
                            if ($targetNode && $targetNode != $node) {
                                $node->afterNode($targetNode);
                            } else {
                                throw new NotFoundException('ItemNotExist', [
                                    'parent_id' => $inputParentId,
                                    'ordering' => $inputOrdering
                                ], null, $this->modelName);
                            }
                        }
                    }
                } else {
                    $newParent = $this->find($inputParentId);
                    if (!$newParent) {
                        throw new NotFoundException('ItemNotExist', ['id' => $inputParentId,], null, $this->modelName);
                    }

                    $node->parent_id = $newParent->id;
                    $siblings = $newParent->children()->get();
                    if ($inputOrdering === null) {
                        if ($siblings->count()) {
                            $node->afterNode($siblings->last());
                        } else {
                            return $newParent->appendNode($node);
                        }
                    } else {
                        if ($inputOrdering === 0 || $inputOrdering === '0') {
                            if ($siblings->first()) {
                                $node->beforeNode($siblings->first());
                            } else {
                                return $newParent->appendNode($node);
                            }
                        } else {
                            $targetNode = $siblings->where('ordering', $inputOrdering)->first();
                            if ($targetNode && $targetNode != $node) {
                                $node->afterNode($targetNode);
                            } else {
                                throw new NotFoundException('ItemNotExist', [
                                    'parent_id' => $inputParentId,
                                    'ordering' => $inputOrdering
                                ], null, $this->modelName);
                            }
                        }
                    }
                }
            }
        }

        $result = $node->save();
        $this->fixNestedOrdering($this->model->defaultOrder()->get()->toTree());

        return $result;
    }


    /**
     * 資料分頁
     * @param $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = 25, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $perPage = $perPage ?: 1000000;

        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginate = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
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
     * 回存項目
     * @param $item
     * @param $user
     * @return mixed
     * @throws InternalServerErrorException
     */
    public function restore($item, $user)
    {
        if (
            config('app.seeding')
            || $item->locked_by == 0
            || ($item->locked_by == $user->id)
            || ($user->higherPermissionThan($item->locker))
        ) {
            $data = [
                'locked_by' => null,
                'locked_at' => null,
            ];
            $item->timestamps = false;
            return $this->update($item, $data);
        } else {
            throw new InternalServerErrorException('InsufficientPermissionRestore', [
                'item_id'   => $item->id,
                'lockerName' => $item->lockerName,
                'locked_at' => $this->getDateTimeString($item->locked_at, $user->timezone)
            ], null, $this->modelName);
        }
    }


    /**
     * 搜尋項目
     * @param Collection $data
     * @return mixed
     */
    public function search(Collection $data)
    {
        if (!$data->has('limit')) {
            $data->put('limit', $this->model->getPerPage());
        }

        $q = $data->has('q')
            ? $data->get('q')
            : new QueryCapsule();

        $q = $q->getQuery($data->except('q'));
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
