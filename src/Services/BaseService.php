<?php

namespace DaydreamLab\JJAJ\Services;

use Carbon\Carbon;
use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BaseService
{
    public $response = null;

    public $status = '';

    protected $package = null;

    protected $modelName = 'Base';

    protected $modelType = 'Base';

    protected $eagers = [];

    protected $loads = [];

    protected $repo;

    protected $search_keys = [];

    protected $type;

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
        $model = $this->repo->add($input);
        if ($model) {
            $this->addMapping($model, $input);
            $this->status = 'CreateSuccess';
            $this->response = $model->refresh();
        } else {
            $this->throwResponse('CreateFail', null, $input);
        }

        return $model;
    }


    public function addMapping($item, $input)
    {
    }


    public function afterCheckItem($item)
    {
    }


    public function canAction($method, $item = null)
    {
        if ($this->isSite() || config('app.seeding')) return true;

        // 這邊為了特化 dddream 使用所以特化成這樣（利用有沒有item）
        if ($item) {
            foreach ($this->user->groups as $group) {
                if ($group->canAction($this->getServiceName(), $method, $item)) {
                    return true;
                }
            }
        } else {
            foreach ($this->user->groups as $group) {
                if (in_array($method, $group->accessResource['apis'])) {
                    return true;
                }
            }
        }

        $this->throwResponse('InsufficientPermission',
            null,
            null,
            ['method' => $method, 'model' => $this->modelName]
        );
    }


    public function canAccess($item_access, $user_access_ids)
    {
        if (config('app.seeding')) return true;
        $user_access_ids = $user_access_ids ? $user_access_ids : [];
        if (!in_array($item_access, $user_access_ids)) {
            $this->throwResponse('InsufficientPermissionView');
        }

        return true;
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function checkout(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $item = $this->checkItem(collect(['id' => $id]));

            $result = $this->repo->checkout($item);

            if (!$result) break;
        }

        if ($result) {
            $this->status = 'CheckoutSuccess';
            $this->response = null;
        } else {
            $this->throwResponse('CheckoutFail');
        }

        return $result;
    }

    /**
     * @param Collection $input
     * @return bool
     */
    public function checkAliasExist(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias') && $this->repo->getModel()->getTable() != 'extrafields') {
            $same = null;
            if ($this->repo->getModel()->hasAttribute('language')) {
                if (InputHelper::null($input, 'language')) {
                    $same = $this->findByChain(['alias', 'language'], ['=', '='], [$input->get('alias'), config('daydreamlab.global.locale')])->first();
                } else {
                    $same = $this->findByChain(['alias', 'language'], ['=', '='], [$input->get('alias'), $input->get('language')])->first();
                }
            } else {
                $same = $this->findBy('alias', '=', $input->get('alias'))->first();
            }

            if ($same && $same->id != $input->get('id')) {
                $this->throwResponse('StoreWithExistAlias');
            }
        }

        return false;
    }


    public function checkItem($input)
    {
        $item = $this->find($input->get('id'));
        if ($item) {
            if ($item->hasAttribute('access')) {
                $this->canAccess($item->access, $this->getAccessIds());
            }
        } else {
            $this->throwResponse('ItemNotExist', ['id' => $input->get('id')]);
        }

        $this->afterCheckItem($item);

        return $item;
    }

    public function checkLocked($item)
    {
        if ($item->locked_by
            && $item->locked_by != $this->user->id
            && !$this->user->higherPermissionThan($item->locked_by)) {
            $this->throwResponse('IsLocked', (object)$this->user->only('email', 'full_name', 'nickname'));
        }
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
     * @param $id
     * @return BaseModel | bool
     */
    public function find($id)
    {
        return $this->repo->find($id, $this->eagers);
    }

    /**
     * @param $items
     * @param $limit
     * @return $this|\Illuminate\Pagination\LengthAwarePaginator
     */
    public function filterItems($items, $limit)
    {
        return $this->repo->paginate($items, $limit);
    }


    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findBy($filed, $operator, $value)
    {
        return $this->repo->findBy($filed, $operator, $value, $this->eagers);
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findByChain($fields, $operators, $values)
    {
        return $this->repo->findByChain($fields, $operators, $values, $this->eagers);
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findBySpecial($type, $key, $value)
    {
        return $this->repo->findBySpecial($type, $key, $value, $this->eagers);
    }

    /**
     * @param $parent_id
     * @param $origin
     * @param $modified
     * @return Collection
     */
    public function findOrderingInterval($parent_id, $origin, $modified)
    {
        return $this->repo->findOrderingInterval($parent_id, $origin, $modified);
    }


    /**
     * @param $id
     * @return bool|BaseModel
     */
    public function getItem($input)
    {
        $item = $this->checkItem($input);

        $this->checkLocked($item);

        if ($item->hasAttribute('locked_by')) {
            $data = [
                'locked_by' => $this->user->id,
                'locked_at' => Carbon::now()->toDateTimeString()
            ];
            $this->update($data, $item);
        }

        $this->status = 'GetItemSuccess';
        $this->response = $item->refresh();

        return $this->response;
    }


    public function getItemByAlias(Collection $input)
    {
        $item = $this->search($input)->first();
        if ($item) {
            if ($item->hasAttribute('hits')) {
                $item->hits++;
                $this->update($item, $item);
            }

            $this->status = 'GetItemSuccess';
            $this->response = $item;
        } else {
            $this->throwResponse('ItemNotExist');
        }

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
            $this->throwResponse( 'GetItemFail');
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
        if (!$this->service_name) {
            $str = explode('\\', get_class($this));
            $this->service_name = end($str);
        }

        return $this->service_name;
    }


    public function hook($input, $status, $response)
    {
        if ($input
            && ((gettype($input) == 'array' && array_key_exists('log', $input))
                || (gettype($input) == 'object' && property_exists($input, 'log')))
        ) {
            $log = $input->log;
            $log->status = $status;
            $log->response = $response;
            $log->save();
        }

        return true;
    }


    public function isSite()
    {
        return !strrpos($this->type, 'Admin');
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function modify(Collection $input)
    {
        $item = $this->checkItem($input);

        $update = $this->update($input->toArray(), $item);

        if ($update) {

            $this->modifyMapping($item, $input);
            $this->status = 'UpdateSuccess';
            $this->response = $update;
        } else {
            $this->throwResponse( 'UpdateFail');
        }

        return $update;
    }


    public function modifyMapping($item, $input)
    {
        return true;
    }


    public function ordering(Collection $input)
    {
        if (!$input->has('orderingKey')) {
            $input->put('orderingKey', 'ordering');
        }

        $item = $this->checkItem($input);

        if ($this->repo->isNested()) {
            $result = $this->repo->orderingNested($input, $item);
            if ($result) {
                $this->status = 'UpdateOrderingNestedSuccess';
            } else {
                $this->throwResponse('UpdateOrderingNestedFail');
            }
        } else {
            $result = $this->repo->ordering($input, $item);
            if ($result) {
                $this->status = 'UpdateOrderingSuccess';
            } else {
                $this->throwResponse('UpdateOrderingFail');
            }
        }

        return $result;
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
            $item = $this->checkItem(collect(['id' => $id]));

            $result_relations = $this->removeMapping($item);
            // 若有排序的欄位則要調整 ordering 大於刪除項目的值
            if ($this->repo->getModel()->hasAttribute('ordering')) {
                $delete_siblings = $this->repo->findDeleteSiblings($item->ordering, $item);
                foreach ($delete_siblings as $delete_sibling) {
                    $delete_sibling->ordering--;
                    $this->update($delete_sibling, $delete_sibling);
                }
            }

            $result = $this->repo->delete($id, $item);
            if (!$result || !$result_relations) {
                break;
            }
        }

        if ($result) {
            $this->status = 'DeleteSuccess';
        } else {
            $this->throwResponse('DeleteFail');
        }
        return $result;
    }


    public function removeMapping($item)
    {
        return true;
    }


    /**
     * @param Collection $input
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function search(Collection $input)
    {
        $special_queries = $input->get('special_queries') ?: [];

        if ($this->repo->getModel()->hasAttribute('access') && $this->access_ids) {
            $input->put('special_queries', array_merge($special_queries,
                [[
                    'type' => 'whereIn',
                    'key' => 'access',
                    'value' => $this->access_ids
                ]]
            ));
        }

        $input->put('search_keys', $this->search_keys);
        $input->put('eagers', $this->eagers);
        $input->put('loads', $this->loads);

        // 處理搜尋結果是否要分頁

        $paginate = $input->has('paginate') ? $input->get('paginate') : true;

        $input->forget('paginate');

        $items = $this->repo->search($input, $paginate);

        $this->status = 'SearchSuccess';
        $this->response = $items;

        return $items;
    }


    public function setStoreDefaultInput(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias')) {
            if (InputHelper::null($input, 'alias')) {
                $encode = urlencode($input->get('title'));
                $alias = Str::lower(Str::random(20));
            } else {
                $alias = $input->get('alias');
            }

            $input->put('alias', Str::lower($alias));
        }

        if ($this->repo->getModel()->hasAttribute('state') && $input->get('state') === null) {
            $input->forget('state');
        }

        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access')) {
            $input->put('access', 1);
        }

        if ($this->repo->getModel()->hasAttribute('language') && InputHelper::null($input, 'language')) {
            $input->put('language', config('daydreamlab.global.locale'));
        }

        if ($this->repo->getModel()->hasAttribute('params') && InputHelper::null($input, 'params')) {
            $input->put('params', (object)[]);
        }

        if ($this->repo->getModel()->hasAttribute('extrafields') && InputHelper::null($input, 'extrafields')) {
            $input->put('extrafields', []);
        }

        return $input;
    }


    public function state(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $input->put('id', $id);
            $item = $this->checkItem($input->except('ids'));

            $result = $this->repo->state($item, $input->get('state'));
            if (!$result) break;
        }

        if ($input->get('state') == '1') {
            $action = 'Published';
        } elseif ($input->get('state') == '0') {
            $action = 'Unpublished';
        } elseif ($input->get('state') == '-1') {
            $action = 'Archive';
        } elseif ($input->get('state') == '-2') {
            $action = 'Trash';
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


    public function traverseTitle(&$categories, $prefix = '-', &$str = '')
    {
        $categories = $categories->sortBy('order');
        foreach ($categories as $category) {
            $str = $str . PHP_EOL . $prefix . ' ' . $category->title;
            $this->traverseTitle($category->children, $prefix . '-', $str);
        }
        return $str;
    }


    public function throwResponse($status, $response = null, $input = null, $trans_params = [])
    {
        $statusString = Str::upper(Str::snake($status));
        $this->hook($input, $statusString, $response);

        if (config('app.debug')) {
            $bt = debug_backtrace();
            $trace = array_shift($bt);
            if (gettype($response) == 'array') {
                $response['debug']['file'] = $trace['file'];
                $response['debug']['line'] = $trace['line'];
                $response['debug']['function'] = $trace['function'];
            } elseif (gettype($response) == 'object') {
                $temp['file'] = $trace['file'];
                $temp['line'] = $trace['line'];
                $temp['function'] = $trace['function'];
                $response->debug = $temp;
            } elseif (gettype($response) == 'string') {
                $temp['file'] = $trace['file'];
                $temp['line'] = $trace['line'];
                $temp['function'] = $trace['function'];
                $temp['response'] = $response;
                $response = $temp;
            }
        }

        throw new HttpResponseException(ResponseHelper::genResponse(
            $statusString,
            $response,
            $this->package,
            $this->modelName,
            $trans_params
        ));
    }


    public function unlock($id)
    {
        $item = $this->find($id);
        if (!$item) {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    'ItemNotExist',
                    ['id' => $id],
                    $this->package,
                    $this->modelName
                )
            );
        }
    }


    public function update($data, $model = null)
    {
        $result = $this->repo->update($data, $model);
        if (!$result) {
            $this->throwResponse('UpdateFail');
        } else {
            $this->status = 'UpdateSuccess';
        }

        return $result ;
    }
}
