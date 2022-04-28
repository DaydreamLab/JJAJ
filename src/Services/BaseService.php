<?php

namespace DaydreamLab\JJAJ\Services;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;
use DaydreamLab\JJAJ\Exceptions\UnauthorizedException;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use DaydreamLab\JJAJ\Traits\ActionHook;
use DaydreamLab\JJAJ\Traits\FormatDateTime;
use DaydreamLab\JJAJ\Traits\LoggedIn;
use DaydreamLab\JJAJ\Traits\Mapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class BaseService
{
    use FormatDateTime, Mapping, ActionHook, LoggedIn;

    public $response = null;

    public $status = '';

    public $transParams = [];

    protected $package = null;

    protected $modelName = 'Base';

    protected $repo;

    protected $service_name = null;

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return $this->repo->all();
    }

    /**
     * @param Collection $input
     * @return BaseModel | bool
     */
    public function add(Collection $input)
    {
        $this->beforeAdd($input);
        $model = $this->repo->add($input);
        $this->addMapping($model, $input);
        $this->afterAdd($input, $model);

        $this->status = 'CreateSuccess';
        $this->response = $model->refresh();

        return  $this->response;
    }



    public function addNested(Collection $input)
    {
        $this->checkPathExist($input);
        $this->beforeAdd($input);

        $model = $this->repo->addNested($input);

        $this->addMapping($model, $input);
        $this->afterAdd($input, $model);

        $this->status = 'CreateNestedSuccess';
        $this->response = $model->refresh();

        return $model;
    }



    public function canAccess($item_access)
    {
        if (config('app.seeding'))  {
            return true;
        }

        $user = $this->getUser();
        if ($user) {
            $userAccessIds = $user->accessIds;
            if (!in_array($item_access, $userAccessIds)) {
                throw new UnauthorizedException('InsufficientPermissionView', null, null, $this->modelName);
            }
        } else {
            throw new UnauthorizedException('Unauthorized', null, null, $this->modelName);
        }

        return true;
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function checkAliasExist(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias')
            && $input->get('alias')
            && $this->repo->getModel()->getTable() != 'extrafields'
        ) {
            $same = null;
            $q = new QueryCapsule();
            $q = $q->where('alias', $input->get('alias'));
            if ($this->repo->getModel()->hasAttribute('language')) {
                $q = $q->where('language', $input->get('language') ?: config('app.locale'));
            }
            $same = self::search(collect(['q' => $q]))->first();

            if ($same && $same->id != $input->get('id')) {
                throw new ForbiddenException('StoreWithExistAlias', ['alias' => $same->alias]);
            }
        }

        return false;
    }


    public function checkItem($input)
    {
        $item = $this->find($input->get('id'), $input->get('q'));
        if (!$item) {
            throw new NotFoundException('ItemNotExist', [
                $this->repo->getModel()->getPrimaryKey() => $input->get('id')
            ], null, $this->modelName);
        }

        if ($item->hasAttribute('access')) {
            $this->canAccess($item->access);
        }

        $this->afterCheckItem($input, $item);

        return $item;
    }


    public function checkLocked($item)
    {
        $user = $this->getUser();
        if ($item->locked_by
            && $item->locked_by != $user->id
            && $user
            && !$user->higherPermissionThan($item->locker)
        ) {
            throw new ForbiddenException('InsufficientPermissionRestore', [
                'lockerName' => $item->lockerName,
                'locked_at' => $this->getDateTimeString($item->locked_at, $this->getUser()->timezone)
            ], null, $this->modelName);
        }

        return true;
    }


    /**
     * 檢查槽狀結構路徑是否重複
     * @param Collection $input
     * @param $item
     * @return false
     * @throws ForbiddenException
     */
    public function checkPathExist(Collection $input, $item = null)
    {
        $inputParentId = $input->get('parentId') ?: $input->get('parent_id');

        if($this->getModel()->hasAttribute('path') && $this->getModel()->hasAttribute('alias')) {
            if (!$input->get('alias') && $item) {
                $input->put('alias', $item->alias);
            } elseif (!$input->get('alias') && !$item) {
                $input->put('alias', Str::lower(Str::random(8)));
            }
            $alias = $input->get('alias');

            # 要多檢查更換parent 狀態下，多語言的路徑狀況
            $path = '';
            if ($item && $item->parent_id == $inputParentId) {
                $path = $item->parent
                        ? $item->parent->path . '/' . $input->get('alias')
                        :  '/' . $input->get('alias');
            } elseif ($item && $item->parent_id != $inputParentId) {
                $newParent = $inputParentId ? $this->repo->find($inputParentId) : null;
                $path = $newParent ? $newParent->path . '/' . $alias : '/' . $alias;
            } else {
                if ($inputParentId) {
                    $parent = $this->repo->find($inputParentId);
                    $path = $parent ? $parent->path : '';
                }
                $path .= '/' . $alias;
            }

            $language_options = ['*'];
            $language = $input->get('language') ? $input->get('language') : config('daydreamlab.global.locale');
            if ($language != '*') {
                $language_options[] = $language;
            }
            $q = new QueryCapsule();

            if ($this->getModel()->hasAttribute('language') && $input->get('language')) {
                $q = $q->whereIn('language', $language_options);
            }

            if ($this->getModel()->hasAttribute('host')  && $input->get('host')) {
                $q = $q->where('host', $input->get('host'));
            }

            if ($input->get('extension')  && $input->get('extension')) {
                $q = $q->where('extension', $input->get('extension'));
            }

            if ($input->get('content_type')  && $input->get('content_type')) {
                $q = $q->where('content_type', $input->get('content_type'));
            }

            $q = $q->where('path', $path);

            $same = $this->search(collect(['q' => $q]))->first();

            if ($same && $same->id != $input->get('id')) {
                throw new ForbiddenException('StoreNestedWithExistPath',  [
                    'path' => $path,
                    'alias' => $alias
                ], null, $this->modelName);
            }

            $input->put('path', $path);
        }

        return true;
    }


    /**
     * @param array $data
     * @return BaseModel | bool
     */
    public function create($data)
    {
        return $this->repo->create($data);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->repo->delete($id);
    }

    /**
     * @param Collection $input
     * @return null
     * @throws InternalServerErrorException
     * @throws NotFoundException
     */
    public function featured(Collection $input)
    {
        $result = false;

        foreach ($input->get('ids') as $id) {
            $item = $this->checkItem(collect(['id' => $id]));
            $maxFeaturedOrdering = null;
            $q = new QueryCapsule();
            $updateData = [];
            if ($input->get('featured') == 1) {
                if (!$item->featured_ordering) {
                    $q = $q->where('featured', 1)
                        ->max('featured_ordering');
                    $maxFeaturedOrdering = $this->search(collect(['q' => $q]));
                    $updateData['featured_ordering'] = $maxFeaturedOrdering + 1;
                }
            } else {
                # 避免重複下架已下架項目
                if ($item->featured_ordering) {
                    $updateData['featured_ordering'] = null;
                    $q = $q->where('featured_ordering', '>', $item->featured_ordering);

                    $siblings = $this->search(collect(['q' => $q]));
                    $siblings->each(function ($sibling) {
                        $sibling->featured_ordering--;
                        $sibling->save();
                    });
                }
            }

            $updateData['featured'] = $input->get('featured');

            $result =  $this->repo->update($item, $updateData);
            if(!$result) break;
        }

        $action = $input->get('featured') == 0
            ? 'Unfeatured'
            : 'Featured';
        if ($result) {
            $this->status   = $action.'Success';
            $this->response = null;
        } else {
            throw new InternalServerErrorException($action.'Fail', null, null, $this->modelName);
        }

        return $this->response;
    }


    public function featuredOrdering(Collection $input)
    {
        return self::ordering($input);
    }


    /**
     * @param $id
     * @return BaseModel | bool
     */
    public function find($id, QueryCapsule $q = null)
    {
        return $this->repo->find($id, $q);
    }


    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findBy($filed, $operator, $value, QueryCapsule $q = null)
    {
        return $this->repo->findBy($filed, $operator, $value, $q);
    }


    /**
     * @param $id
     * @return bool|BaseModel
     */
    public function getItem($input)
    {
        $item = $this->checkItem($input);

        $canLock = $this->checkLocked($item);
        if ($canLock && $item->hasAttribute('locked_by')) {
            $data = [
                'locked_by' => $this->getUser()->id,
                'locked_at' => now()->toDateTimeString()
            ];
            $this->repo->update($item, $data);
        }

        $this->status = 'GetItemSuccess';
        $this->response = $item->refresh();

        return $this->response;
    }


    public function getItemByAlias(Collection $input)
    {
        $item = $this->repo->findBy('alias', '=', $input->get('alias'))->first();
        if (!$item) {
            throw new NotFoundException('ItemNotExist', null, null, $this->modelName);
        }

        if ($item->hasAttribute('hits')) {
            $item->hits++;
            $this->update($item, $item);
        }

        $this->status = 'GetItemSuccess';
        $this->response = $item;

        return $item;
    }

    /**
     * @param Collection $input
     * @return BaseModel | bool
     */
    public function getItemByPath(Collection $input)
    {
        $item = $this->search($input)->first();
        if ($item) {
            $this->status = 'GetItemSuccess';
            $this->response = $item;
        } else {
            throw new NotFoundException('GetItemFail');
        }

        return $item;
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function getList(Collection $input)
    {
        $items = $this->repo->all();

        $this->status = 'GetListSuccess';
        $this->response = $items;

        return $items;
    }


    /**
     * @return BaseModel|\Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->repo->getModel();
    }


    public function getServiceName()
    {
        if (property_exists($this, 'service_name')) {
            $str = explode('\\', get_class($this));
            $this->service_name = end($str);
        } else {
            return get_class($this);
        }

        return $this->service_name;
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function modify(Collection $input)
    {
        $item = $this->checkItem($input);

        $this->beforeModify($input, $item);

        $update = $this->repo->modify($item, $input);
        $this->modifyMapping($item, $input);

        $item = $item->refresh();
        $this->afterModify($input, $item);

        if ($this->getModel()->hasAttribute('locked_by')) {
            $this->repo->restore($item, $this->getUser());
        }

        $this->status = 'UpdateSuccess';
        $this->response = $update;

        return $update;
    }



    public function modifyNested(Collection $input)
    {
        $item = $this->checkItem(collect([ 'id' => $input->get('id')]));

        if (!$input->get('alias') && $this->getModel()->hasAttribute('alias')) {
            $input->put('alias', $item->alias);
        }

        $this->checkLocked($item);
        $this->checkPathExist($input, $item);

        $modify = $this->repo->modifyNested($input, $item);
        if ($modify) {
            $this->modifyMapping($item, $input);
            $this->status   = 'UpdateNestedSuccess';
            $this->response = $modify->refresh();
        } else {
            throw new InternalServerErrorException('UpdateNestedFail', null, [], $this->modelName);
        }

        return $this->response;
    }


    /**
     * 修改項目排序
     * - 一般排序
     * - 精選排序
     * @param Collection $input
     * @return mixed
     * @throws ForbiddenException
     * @throws InternalServerErrorException
     * @throws NotFoundException
     */
    public function ordering(Collection $input)
    {
        $item = $this->checkItem($input);

        $key = $input->has('ordering') ? 'ordering' : 'featured_ordering';

        $result = $this->repo->ordering($input, $item, $key);

        $action = $key == 'ordering' ? 'Ordering' : 'FeaturedOrdering';
        if (!$result) {
            throw new InternalServerErrorException($action.'Fail', null, null, $this->modelName);
        } else {
            $this->status = $action . 'Success';
        }

        return $result;
    }


    public function orderingNested(Collection $input)
    {
        $item = $this->checkItem($input);

        $result = $this->repo->orderingNested($input, $item);

        $this->status = $result
            ? 'OrderingNestedSuccess'
            : 'OrderingNestedFail';
        $this->response = $result;

        return $this->response;
    }


    public function paginationFormat($items)
    {
        $data = [];
        if (array_key_exists('data', $items)) {
            $data['data'] = $items['data'];
            unset($items['data']);
            $data['pagination'] = $items;
        } else {
            $data['data'] = $items;
            $data['paginate'] = [];
        }

        return $data;
    }


    public function remove(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $q = $input->get('q')
                ? clone ($input->get('q'))
                : new QueryCapsule();
            $item = $this->checkItem(collect(['id' => $id, 'q' => $q]));
            $this->beforeRemove($input, $item);
            $this->removeMapping($item);
            // 若有排序的欄位則要調整 ordering 大於刪除項目的值
            if ($this->getModel()->hasAttribute('ordering') && ($item->ordering != null) ) {
                 $this->repo->handleDeleteOrdering($item->ordering, 'ordering');
            }

            if ($this->getModel()->hasAttribute('featured') && $item->featured == 1) {
                $this->repo->handleDeleteOrdering($item->featured_ordering, 'featured_ordering');
            }

            $result = $this->repo->delete($item);
            if (!$result) {
                break;
            }
        }

        if ($result) {
            $this->status = 'DeleteSuccess';
        }

        return $result;
    }


    public function removeNested(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $item = $this->checkItem(collect(['id' => $id]));
            $this->removeMapping($item);
            $this->repo->handleDeleteNestedOrdering($item);
            $result = $this->repo->delete($item);
            if(!$result) {
                break;
            }
        }

        if($result) {
            $this->status = 'DeleteNestedSuccess';
        } else {
            throw new InternalServerErrorException('DeleteNestedFail', null, null, $this->modelName);
        }

        return $result;
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function restore(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $q = $input->get('q')
                ? clone ($input->get('q'))
                : new QueryCapsule();
            $item = $this->checkItem(collect(['id' => $id, 'q' => $q]));
            $result = $this->repo->restore($item, $this->getUser());
        }

        $this->status = 'RestoreSuccess';
        $this->response = null;

        return $result;
    }


    /**
     * @param Collection $input
     * @return \Illuminate\Database\Eloquent\Collection|static[]|integer
     */
    public function search(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('access')) {
            $accessIds = $this->getUser()
                ? $this->getUser()->accessIds
                : (config('daydreamlab.cms.item.front.access_ids') ?: [1]);
            $q = $input->get('q') ? $input->get('q') : new QueryCapsule();
            $q = $q->whereIn('access', $accessIds);
            $input->put('q', $q);
        }

        $items = $this->repo->search($input);

        $this->status = 'SearchSuccess';
        $this->response = $items;

        return $items;
    }


    public function setStoreDefaultInput(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias')) {
            if ($input->has('alias')) {
                $input->put('alias', str_replace(' ', '_', Str::lower($input->get('alias'))));
            } else {
                if (InputHelper::null($input, 'id')) {
                    $input->put('alias', Str::lower(Str::random()));
                }
            }
        }

        if ($this->repo->getModel()->hasAttribute('state') && $input->get('state') === null) {
            $input->forget('state');
        }

        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access')) {
            $input->put('access', config('daydreamlab.cms.default_viewlevel_id') ?? 1);
        }

        if ($this->repo->getModel()->hasAttribute('language') && InputHelper::null($input, 'language')) {
            $input->put('language', config('daydreamlab.global.locale'));
        }

        if ($this->repo->getModel()->hasAttribute('params') && InputHelper::null($input, 'params')) {
            $input->put('params', (object)[]);
        }

        if ($this->repo->getModel()->hasAttribute('extrafields') && InputHelper::null($input, 'extrafields')) {
            $input->put('extrafields', []);
            $search = '';
            foreach ($input->get('extrafields') as $extrafield) {
                $search .= $extrafield['value'] . ' ';
            }
            $input->put('extrafields_search', $search);
        }

        return $input;
    }


    public function state(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $input->put('id', $id);
            $q = $input->get('q')
                ? clone ($input->get('q'))
                : new QueryCapsule();
            $tempInput = clone ($input->except(['q', 'ids']));
            $tempInput->put('q', $q);
            $tempInput->put('id', $id);

            $item = $this->checkItem($tempInput);

            $this->beforeState($input->get('state'), $item);

            $result = $this->repo->state($item, $input->get('state'));

            $this->afterState($input, $item);
        }

        if ($input->get('state') == '1') {
            $action = 'Published';
        } elseif ($input->get('state') == '0') {
            $action = 'Unpublished';
        } elseif ($input->get('state') == '-1') {
            $action = 'Archive';
        } elseif ($input->get('state') == '-2') {
            $action = 'Trash';
        } else {
            $action = '';
        }

        $this->status = $result
            ? $action . 'Success'
            : $action . 'Fail';

        return $result;
    }


    public function store(Collection $input)
    {
        $input = $this->setStoreDefaultInput($input);

        if ($input->has('extrafields')) {
            $extrafields = $input->get('extrafields');
            $extrafields_data = [];
            foreach ($extrafields as $extrafield) {
                $temp = [];
                $temp['id'] = $extrafield['id'];
                $temp['value'] = $extrafield['value'];
                if (isset($extrafield['params'])) {
                    $temp['params'] = $extrafield['params'];
                } else {
                    $temp['params'] = [];
                }
                $extrafields_data[] = $temp;
            }
            $input->put('extrafields', $extrafields_data);
        }

        $this->checkAliasExist($input);

        if (InputHelper::null($input, 'id')) {
            return $this->add($input);
        } else {
            return $this->modify($input);
        }
    }


    /**
     * 儲存槽狀結構項目
     * @param Collection $input
     * @return mixed
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function storeNested(Collection $input)
    {
        $parent_id = $input->get('parentId') ?: $input->get('parent_id');
        $parent = $parent_id ? $this->repo->find($parent_id) : null;

        // 設定初始值
        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access')) {
            $input->put('access', $parent ? $parent->access : config('daydreamlab.cms.default_viewlevel_id'));
        }
        $input  = $this->setStoreDefaultInput($input);

        if (InputHelper::null($input, 'id')) {
            return $this->addNested($input);
        } else {
            return $this->modifyNested($input);
        }
    }


    public function traverseTitle(&$categories, $prefix = '-', &$str = '')
    {
        $categories = $categories->sortBy('order');
        foreach ($categories as $category) {
            $str = $str . PHP_EOL . $prefix . ' ' . $category->title;
            $this->traverseTitle($category->children, $prefix . '-', $str);
        }
        return $str;
    }


    public function update($model, $data)
    {
        return $this->repo->modify($model, $data);
    }
}
