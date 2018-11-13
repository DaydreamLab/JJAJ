<?php

namespace DaydreamLab\JJAJ\Services;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BaseService
{
    protected $user;

    protected $viewlevels;

    protected $access_ids;

    protected $repo;

    protected $type;

    public $status;

    public $response;

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
            $this->viewlevels = config('cms.item.front.viewlevels');
            $this->access_ids = config('cms.item.front.access_ids');
        }
    }


    public function all()
    {
        return $this->repo->all();
    }


    public function add(Collection $input)
    {
        if ($this->tablePropertyExist('alias') && $this->getModel()->getTable() != 'extrafields')
        {
            $same = $this->findBy('alias', '=', $input->get('alias'))->first();
            if ($same)
            {
                $this->status =  Str::upper(Str::snake($this->type.'CreateWithExistAlias'));
                $this->response = false;
                return false;
            }
        }

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


    public function create($data)
    {
        return $this->repo->create($data);
    }


    public function delete($id)
    {
        return $this->repo->delete($id);
    }


    public function find($id)
    {
        return $this->repo->find($id);
    }


    public function filterItems($items, $limit)
    {
        return $this->repo->paginate($items, $limit);
    }


    public function findBy($filed, $operator, $value)
    {
        return $this->repo->findBy($filed, $operator, $value);
    }


    public function findByChain($fields, $operators, $values)
    {
        return $this->repo->findByChain($fields , $operators, $values);
    }


    public function findOrderingInterval($parent_id, $origin, $modified)
    {
        return $this->repo->findOrderingInterval($parent_id, $origin, $modified);
    }


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


    public function getItemByPath($path)
    {
        $item = $this->findBy('path', '=', $path)->first();

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



    public function getModel()
    {
        return $this->getRepo()->getModel();
    }


    public function getRepo()
    {
        return $this->repo;
    }


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
        $data['data'] = $items['data'];
        unset($items['data']);
        $data['pagination'] = $items;

        return $data;
    }


    public function remove(Collection $input)
    {
        foreach ($input->ids as $id)
        {
            if (Helper::tablePropertyExist($this->getModel(), 'ordering'))
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


    public function search(Collection $input)
    {
        $special_queries = $input->get('special_queries') ?: [];
        if ($this->tablePropertyExist('access'))
        {
            $input->put('special_queries', array_merge($special_queries ,
                [[
                    'type' => 'whereIn',
                    'key'  => 'access',
                    'value'=> $this->access_ids
                ]]
            ));
        }

        $items = $this->repo->search($input);

        $this->status   = Str::upper(Str::snake($this->type.'SearchSuccess'));
        $this->response = $items;

        return $items;
    }


    public function store(Collection $input)
    {
        if (InputHelper::null($input, 'id')) {
            return $this->add($input);
        }
        else {
            $input->put('locked_by', 0);
            $input->put('locked_at', null);
            return $this->modify($input);
        }
    }


    public function state(Collection $input)
    {
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
        elseif ($input->state == '0') {
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


    public function storeKeysMap(Collection $input)
    {
        $mainKey = $mapKey = null;
        foreach ($input->keys() as $key) {
            if (gettype($input->{$key}) == 'array') {
                $mapKey = $key;
            }
            else {
                if ($key!= 'created_by')
                {
                    $mainKey = $key;
                }
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
                    $mainKey    => $input->{$mainKey},
                    Str::substr($mapKey, 0, -1) => $id,
                    'created_by'   => $this->user ? $this->user->id : 1
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


    public function tablePropertyExist($property)
    {
        return Schema::hasColumn($this->getModel()->getTable(), $property);
    }


    public function update($data)
    {
        return $this->repo->update($data);
    }

}