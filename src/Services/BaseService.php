<?php

namespace DaydreamLab\JJAJ\Services;

use Carbon\Carbon;
use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use DaydreamLab\User\Models\User\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class BaseService
{
    public $response = null;

    public $status;

    protected $access_ids;

    protected $eagers = [];

    protected $loads = [];

    protected $repo;

    protected $search_keys = [];

    protected $type;

    protected $user;

    protected $viewlevels;

    protected $asset;

    protected $except_model = ['UniqueVisitor', 'UniqueVisitorCounter', 'Log', 'FormFront'];


    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
        $this->user = Auth::guard('api')->user();

        if ($this->user)
        {
            $this->access_ids = $this->user->access_ids;
        }
        else
        {
            //限制前台選單
            $this->viewlevels = config('cms.item.front.viewlevels');
            $this->access_ids = config('cms.item.front.access_ids');
        }
    }


    public function checkAction($item, $method, $diff = false)
    {
        if(env('SEEDING')) return true;

        if (!$diff)
        {
            if (!$this->canAction($method))
            {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake('UserInsufficientPermission')),
                        ['model' => $this->type, 'methods' => $method]
                    )
                );
            }
        }
        else
        {
            if (($item->created_by == $this->user->id && !$this->canAction($method.'Own')))
            {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake('UserInsufficientPermission')),
                        ['model' => $this->type, 'methods' => $method.'Own']
                    )
                );
            }

            if (($item->created_by != $this->user->id && !$this->canAction($method.'Other')))
            {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake('UserInsufficientPermission')),
                        ['model' => $this->type, 'methods' => $method.'Other']
                    )
                );
            }
        }
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
        if (!in_array($this->type, $this->except_model))
        {
            $this->canAction('add');
        }

        $model = $this->repo->add($input);

        if ($model) {
            $model = $this->find($model->id);
            $this->status =  Str::upper(Str::snake($this->type.'CreateSuccess'));
            $this->response = $model;
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'CreateFail'))
                )
            );
        }

        return $model;
    }


    public function canAction(...$methods)
    {
        if ((in_array('add', $methods) || in_array('search', $methods)) && env('SEEDING')) return true;

        if ($this->isSite()) return true;

        foreach ($this->user->groups as $group)
        {
            if ($group->canAction($this->type, $methods))
            {
                return true;
            }
        }

        throw new HttpResponseException(
            ResponseHelper::genResponse(
                Str::upper(Str::snake('UserInsufficientPermission')),
                env('APP_ENV') == 'local' ? ['model' => $this->type, 'methods' => $methods] : null
            )
        );
    }


    public function canAccess($item_access, $user_access_ids)
    {
        if (env('SEEDING')) return true;
        $user_access_ids = $user_access_ids ? $user_access_ids : [];
        if(!in_array($item_access, $user_access_ids))
        {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake('UserInsufficientPermission')),
                    'viewlevel insufficient'
                )
            );
        }
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function checkout(Collection $input, $diff = false)
    {
        $result = false;
        foreach ($input->get('ids') as $id)
        {
            $item  = $this->find($id);
            $this->checkAction($item, 'checkout');

            $result = $this->repo->checkout($item);

            if (!$result) break;
        }

        if ($result) {
            $this->status =  Str::upper(Str::snake($this->type.'CheckoutSuccess'));
            $this->response = null;
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'CheckoutFail'))
                )
            );
        }
        return $result;
    }

    /**
     * @param Collection $input
     * @return bool
     */
    public function checkAliasExist(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias') && $this->repo->getModel()->getTable() != 'extrafields')
        {
            $same = null;
            if ($this->repo->getModel()->hasAttribute('language'))
            {
                if (InputHelper::null($input, 'language'))
                {
                    $same = $this->findByChain(['alias', 'language'], ['=', '='],[$input->get('alias'), config('global.locale')])->first();
                }
                else
                {
                    $same = $this->findByChain(['alias', 'language'], ['=', '='],[$input->get('alias'), $input->get('language')])->first();
                }
            }
            else
            {
                $same = $this->findBy('alias', '=', $input->get('alias'))->first();
            }

            if ($same && $same->id != $input->get('id'))
            {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake($this->type.'StoreWithExistAlias'))
                    )
                );
            }
        }

        return false;
    }


    public function checkItem($id, $diff = false)
    {
        $item  = $this->find($id);

        if($item)
        {
            if ($item->hasAttribute('access'))
            {
                $this->canAccess($item->access, $this->access_ids);
            }

            $this->checkAction($item, 'get', $diff);
        }
        else
        {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'ItemNotExist')),
                    ['id'=> $id]
                )
            );
        }

        return $item;
    }

    public function checkLocked($item)
    {
        if ($item->locked_by && $item->locked_by != $this->user->id && !$this->user->higherPermissionThan($item->locked_by))
        {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'IsLocked')),
                    (object) $this->user->only('email', 'full_name', 'nickname')
                )
            );
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
        return count($this->eagers) ? $this->repo->with($this->eagers)->find($id) : $this->repo->find($id);
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
        return count($this->eagers) ? $this->repo->with($this->eagers)->findBy($filed, $operator, $value)
                                    : $this->repo->findBy($filed, $operator, $value);
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findByChain($fields, $operators, $values)
    {
        return count($this->eagers) ? $this->repo->with($this->eagers)->findByChain($fields , $operators, $values)
                                    : $this->repo->findByChain($fields , $operators, $values);
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function findBySpecial($type, $key, $value)
    {
        return count($this->eagers) ? $this->repo->with($this->eagers)->findBySpecial($type, $key, $value)
                                    : $this->repo->findBySpecial($type, $key, $value);
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
    public function getItem($id, $diff = false)
    {
        $item = $this->checkItem($id, $diff);

        $this->checkLocked($item);

        if ($item->hasAttribute('locked_by'))
        {
            $item->locked_by = $this->user->id;
            $item->locked_at = Carbon::now()->toDateTimeString();
        }

        $this->update($item, $item);

        $this->status   = Str::upper(Str::snake($this->type.'GetItemSuccess'));
        $this->response = $item;

        return $item;
    }


    public function getItemByAlias(Collection $input)
    {
        $item = $this->search($input)->first();

        if($item) {
            if($item->hasAttribute('hits'))
            {
                $item->hits++;
                $this->update($item, $item);
            }

            $this->status   = Str::upper(Str::snake($this->type.'GetItemSuccess'));
            $this->response = $item;
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'ItemNotExist'))
                )
            );
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
        if($item) {
            $this->status   = Str::upper(Str::snake($this->type.'GetItemSuccess'));
            $this->response = $item;
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'GetItemFail'))
                )
            );
        }

        return $item;
    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
    public function getList()
    {
        $items = $this->all();

        $this->status   = Str::upper(Str::snake($this->type.'GetListSuccess'));
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


    public function isSite()
    {
        return !strrpos($this->type, 'Admin');
    }


    /**
     * @param Collection $input
     * @return bool
     */
    public function modify(Collection $input, $diff = false)
    {
        $item = $this->checkItem($input->get('id'), $diff);

        $this->checkAction($item, 'edit', $diff);

        $update = $this->update($input->toArray(), $item);

        if ($update) {

            $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
            $this->response = null;
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'UpdateFail'))
                )
            );
        }

        return $update;
    }


    public function ordering(Collection $input, $diff = false)
    {
        if (!$input->has('orderingKey'))
        {
            $input->put('orderingKey', 'ordering');
        }

        $item = $this->checkItem($input->get('id'), $diff);
        $this->checkAction($item, 'edit', $diff);

        if ($this->repo->isNested())
        {
            $result = $this->repo->orderingNested($input);
            if($result) {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingNestedSuccess'));
            }
            else {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake($this->type.'UpdateOrderingNestedFail'))
                    )
                );
            }
        }
        else
        {
            $result = $this->repo->ordering($input);
            if($result) {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingSuccess'));
            }
            else {
                throw new HttpResponseException(
                    ResponseHelper::genResponse(
                        Str::upper(Str::snake($this->type.'UpdateOrderingFail'))
                    )
                );
            }
        }

        return $result;
    }


    public function paginationFormat($items)
    {
        $data = [];
        if (array_key_exists('data', $items))
        {
            $data['data'] = $items['data'];
            unset($items['data']);
            $data['pagination'] = $items;
        }
        else
        {
            $data['data'] = $items;
            $data['paginate'] = [];
        }

        return $data;
    }


    public function remove(Collection $input, $diff = false)
    {
        $result = false;
        foreach ($input->ids as $id)
        {
            $item = $this->checkItem($id, $diff);
            $this->checkAction($item, 'delete', $diff);
            $result_relations = $this->removeMapping($item);

            // 若有排序的欄位則要調整 ordering 大於刪除項目的值
            if ($this->repo->getModel()->hasAttribute('ordering'))
            {
                $delete_siblings = $this->repo->findDeleteSiblings($item->ordering, $item);
                foreach ($delete_siblings as $delete_sibling)
                {
                    $delete_sibling->ordering--;
                    $this->update($delete_sibling, $delete_sibling);
                }
            }

            $result = $this->repo->delete($item, $item);
            if (!$result || !$result_relations)
            {
                break;
            }
        }

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteSuccess'));
        }
        else {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type.'DeleteFail'))
                )
            );
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
        $this->canAction('search');

        $special_queries = $input->get('special_queries') ?: [];

        if ($this->repo->getModel()->hasAttribute('access'))
        {
            $input->put('special_queries', array_merge($special_queries ,
                [[
                    'type' => 'whereIn',
                    'key'  => 'access',
                    'value'=> $this->access_ids
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

        $this->status   = Str::upper(Str::snake($this->type.'SearchSuccess'));
        $this->response = $items;

        return $items;
    }


    public function setStoreDefaultInput(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias'))
        {
            if (InputHelper::null($input, 'alias'))
            {
                $encode = urlencode($input->title);
                $alias = Str::lower(Str::random(20));
            }
            else
            {
                $alias = $input->get('alias');
            }

            $input->put('alias', Str::lower($alias));
        }

        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access'))
        {
            $input->put('access', 1);
        }

        if ($this->repo->getModel()->hasAttribute('language') && InputHelper::null($input, 'language'))
        {
            $input->put('language', config('global.locale'));
        }

        if ($this->repo->getModel()->hasAttribute('params') && InputHelper::null($input, 'params'))
        {
            $input->put('params', (object)[]);
        }

        if ($this->repo->getModel()->hasAttribute('extrafields') && InputHelper::null($input, 'extrafields'))
        {
            $input->put('extrafields', []);
        }

        return $input;
    }


    public function state(Collection $input, $diff = false)
    {
        $result = false;
        foreach ($input->get('ids') as $id)
        {
            $item  = $this->checkItem($id, $diff);
            $this->checkAction($item, 'updateState', $diff);

            $result = $this->repo->state($item, $input->state);
            if (!$result) break;
        }

        if ($input->state == '1') {
            $action = 'Publish';
        }
        elseif ($input->state == '0') {
            $action = 'Unpublish';
        }
        elseif ($input->state == '-1') {
            $action = 'Archive';
        }
        elseif ($input->state == '-2') {
            $action = 'Trash';
        }

        $this->status = $result ? Str::upper(Str::snake($this->type. $action . 'Success'))
            : Str::upper(Str::snake($this->type. $action . 'Fail'));

        return $result;
    }


    public function store(Collection $input, $diff = false)
    {
        $input = $this->setStoreDefaultInput($input);

        $this->checkAliasExist($input);

        if (InputHelper::null($input, 'id')) {
            return $this->add($input);
        }
        else {
            return $this->modify($input, $diff);
        }
    }


    public function storeKeysMap(Collection $input)
    {
        $mainKey = $mapKey = null;
        foreach ($input->keys() as $key) {
            if (gettype($input->{$key}) == 'array') {
                $mapKey = $key;
            }
            else {
//                if ($key!= 'created_by')
//                {
                    $mainKey = $key;
//                }
            }
        }

        $delete_items = $this->findBy($mainKey, '=', $input->{$mainKey});

        if ($delete_items->count() > 0) {
            $data = [];
            foreach ($delete_items as $item) {
                $data['ids'][] = $item->id;
            }
            if (!$this->remove(Helper::collect($data))) {
                return false;
            }
        }


        if (count($input->{$mapKey}) > 0) {
            foreach ($input->{$mapKey} as $id) {
                $asset = $this->add(Helper::collect([
                    $mainKey        => $input->{$mainKey},
                    Str::substr($mapKey, 0, -1) => $id,
                ]));
                if (!$asset) {
                    return false;
                }
            }
        }
        return true;
    }


    public function traverseTitle(&$categories, $prefix = '-', &$str = '')
    {
        $categories  = $categories->sortBy('order');
        foreach ($categories as $category) {
            $str = $str . PHP_EOL.$prefix.' '.$category->title;
            $this->traverseTitle($category->children, $prefix.'-', $str);
        }
        return $str;
    }


    public function update($data, $model = null)
    {
        if(!$this->repo->update($data, $model))
        {
            throw new HttpResponseException(
                ResponseHelper::genResponse(
                    Str::upper(Str::snake($this->type . 'UpdateFail'))
                )
            );
        }

        return true;
    }

}