<?php

namespace DaydreamLab\JJAJ\Services;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
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


    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
        $this->user = Auth::guard('api')->user();

        if ($this->user)
        {
            $this->viewlevels = $this->user->viewlevels;
            $this->access_ids = $this->user->access_ids;
        }
        else
        {
            //限制前台選單
            $this->viewlevels = config('cms.item.front.viewlevels');
            $this->access_ids = config('cms.item.front.access_ids');
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
        $model = $this->repo->add($input);
        if ($model) {
            $this->status =  Str::upper(Str::snake($this->type.'CreateSuccess'));
            $this->response = $model;
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'CreateFail'));
            $this->response = null;
        }

        return $model;
    }

    /**
     * @param Collection $input
     * @return bool
     */
    public function checkout(Collection $input)
    {
        $checkout = $this->repo->checkout($input);
        if ($checkout) {
            $this->status =  Str::upper(Str::snake($this->type.'CheckoutSuccess'));
            $this->response = null;
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'CheckoutFail'));
            $this->response = null;
        }
        return $checkout;
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
                $this->status =  Str::upper(Str::snake($this->type.'StoreWithExistAlias'));
                $this->response = false;
                return true;
            }
        }
        else
        {
            return false;
        }

        return false;
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
    public function getItem($id)
    {
        $item = $this->find($id);

        if($item) {
            $this->status   = Str::upper(Str::snake($this->type.'GetItemSuccess'));
            $this->response = $item;
        }
        else {
            $this->status   = Str::upper(Str::snake($this->type.'GetItemFail'));
            $this->response = null;
        }

        return $item;
    }


    public function getItemByAlias(Collection $input)
    {
        $item = $this->search($input)->first();

        if($item) {
            $item->hits++;
            $this->update($item, $item);
            $this->status   = Str::upper(Str::snake($this->type.'GetItemSuccess'));
            $this->response = $item;
        }
        else {
            $this->status   = Str::upper(Str::snake($this->type.'GetItemFail'));
            $this->response = null;
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
            $this->status   = Str::upper(Str::snake($this->type.'GetItemFail'));
            $this->response = null;
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

    /**
     * @param Collection $input
     * @return bool
     */
    public function modify(Collection $input)
    {
        $update = $this->update($input->toArray());
        if ($update) {
            $this->status = Str::upper(Str::snake($this->type.'UpdateSuccess'));
            $this->response = null;
        }
        else {
            $this->status = Str::upper(Str::snake($this->type.'UpdateFail'));
            $this->response = null;
        }

        return $update;
    }


    public function ordering(Collection $input, $orderingKey = 'ordering')
    {
        if ($input->has('orderingKey'))
        {
            $orderingKey = $input->orderingKey;
        }

        if ($this->repo->isNested())
        {
            $result = $this->repo->orderingNested($input, $orderingKey);
            if($result) {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingNestedSuccess'));
            }
            else {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingNestedFail'));
            }
        }
        else {
            $result = $this->repo->ordering($input, $orderingKey);
            if($result) {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingSuccess'));
            }
            else {
                $this->status =  Str::upper(Str::snake($this->type.'UpdateOrderingFail'));
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


    public function remove(Collection $input)
    {
        foreach ($input->ids as $id)
        {
            if ($this->repo->getModel()->hasAttribute('ordering'))
            {
                $item   = $this->find($id);
                $next_siblings = $this->repo->findDeleteSiblings($item->ordering);
                $next_siblings->each(function ($item, $key) {
                    $item->ordering--;
                    $item->save();
                });
            }

            $result = $this->repo->delete($id);
            if (!$result)
            {
                break;
            }
        }

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteSuccess'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type.'DeleteFail'));
        }
        return $result;
    }

    /**
     * @param Collection $input
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function search(Collection $input)
    {
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
        $paginate = $input->get('paginate') === false ? false : true;
        $input->forget('paginate');
        Helper::startLog();
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
                $alias = Str::random(36);
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

        if ($this->repo->getModel()->hasAttribute('description') && !InputHelper::null($input, 'description'))
        {
            $input->put('description', nl2br($input->description));
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


    public function state(Collection $input)
    {
        $result = false;
        foreach ($input->ids as $key => $id) {
            $result = $this->repo->state($id, $input->state);
            if (!$result) {
                break;
            }
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

        if($result) {
            $this->status =  Str::upper(Str::snake($this->type. $action . 'Success'));
        }
        else {
            $this->status =  Str::upper(Str::snake($this->type. $action . 'Fail'));
        }
        return $result;
    }


    public function store(Collection $input)
    {
        $input = $this->setStoreDefaultInput($input);
        if ($this->checkAliasExist($input))
        {
            return false;
        }

        if (InputHelper::null($input, 'id')) {
            return $this->add($input);
        }
        else {
            $input->put('locked_by', 0);
            $input->put('locked_at', null);

            return $this->modify($input);
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
        return $this->repo->update($data, $model);
    }

}