<?php

namespace DaydreamLab\JJAJ\Services;

use Carbon\Carbon;

use DaydreamLab\JJAJ\Database\QueryCapsule;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Exceptions\InternalServerErrorException;
use DaydreamLab\JJAJ\Exceptions\NotFoundException;
use DaydreamLab\JJAJ\Exceptions\UnauthorizedException;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Models\BaseModel;
use DaydreamLab\JJAJ\Repositories\BaseRepository;
use DaydreamLab\JJAJ\Traits\ApiJsonResponse;
use DaydreamLab\JJAJ\Traits\FormatDateTime;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BaseService
{
    use FormatDateTime;

    public $response = null;

    public $status = '';

    protected $package = null;

    protected $modelName = 'Base';

    protected $modelType = 'Base';

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
        $model = $this->repo->add($input);
        $this->addMapping($model, $input);

        $this->status = 'CreateSuccess';
        $this->response = $model;

        return $model;
    }


    public function addMapping($item, $input)
    {
    }


    public function afterCheckItem($item)
    {

    }


    public function beforeRemove($item)
    {
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
            $q = $input->get('q') ?: new QueryCapsule();
            $q = $q->where('alias', $input->get('alias'));
            if ($this->repo->getModel()->hasAttribute('language')) {
                $q = $q->where('language', $input->get('language') ?: config('app.locale'));
            }
            $same = $this->search(collect(['q' => $q]))->first();

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

        $this->afterCheckItem($item);

        return $item;
    }


    public function checkLocked($item)
    {
        $user = $this->getUser();
        if ($item->locked_by
            && $item->locked_by != $this->user->id
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


    public function featured(Collection $input)
    {
        $result = false;
        foreach ($input->get('ids') as $id) {
            $item = $this->checkItem(collect(['id' => $id]));
            $result =  $this->repo->update($item, ['featured' => $input->get('featured')]);
            if(!$result) break;
        }

        $action = $input->get('featured') == 0
            ? 'Unfeatured'
            : 'Featured';
        if ($result) {
            $this->status   = $action.'Success';
        } else {
            throw new InternalServerErrorException($action.'Fail', null, null, $this->modelName);
        }

        return $result;
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
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
//    public function findByChain($fields, $operators, $values)
//    {
//        return $this->repo->findByChain($fields, $operators, $values, $this->eagers);
//    }

    /**
     * @param $filed
     * @param $operator
     * @param $value
     * @return Collection
     */
//    public function findBySpecial($type, $key, $value)
//    {
//        return $this->repo->findBySpecial($type, $key, $value, $this->eagers);
//    }

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

        $canLock = $this->checkLocked($item);
        if ($canLock && $item->hasAttribute('locked_by')) {
            $data = collect([
                'locked_by' => $this->getUser()->id,
                'locked_at' => Carbon::now()->toDateTimeString()
            ]);
            $this->update($item, $data);
        }

        $this->status = 'GetItemSuccess';
        $this->response = $item->refresh();

        return $this->response;
    }


    public function getItemByAlias(Collection $input)
    {
        $item = $this->repo->findBy('alias', '=', $input->get('alias'));
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

        $update = $this->repo->modify($item, $input);
        $this->modifyMapping($item, $input);

        $this->status = 'UpdateSuccess';
        $this->response = $update;

        return $update;
    }


    public function modifyMapping($item, $input)
    {
        return true;
    }


    public function ordering(Collection $input)
    {
        $item = $this->checkItem($input);

        if ($this->repo->isNested()) {
            $result = $this->repo->orderingNested($input, $item);
            $this->status = 'OrderingNestedSuccess';
        } else {
            $result = $this->repo->ordering($input, $item);
            $this->status = 'OrderingSuccess';
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
            $this->beforeRemove($item);
            $result_relations = $this->removeMapping($item);
            // 若有排序的欄位則要調整 ordering 大於刪除項目的值
            if ($this->repo->getModel()->hasAttribute('ordering')) {
                $delete_siblings = $this->repo->findDeleteSiblings($item->ordering);
                foreach ($delete_siblings as $delete_sibling) {
                    $delete_sibling->ordering--;
                    $this->update($delete_sibling, $delete_sibling);
                }
            }

            $result = $this->repo->delete($item);
            if (!$result || !$result_relations) {
                break;
            }
        }

        if ($result) {
            $this->status = 'DeleteSuccess';
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
        if ($this->repo->getModel()->hasAttribute('access')) {
            $accessIds = $this->getUser()
                ? $this->getUser()->accessIds
                : (config('daydreamlab.cms.item.front.access_ids') ?: [1]);

            $input->put('q', $input->get('q')->whereIn('access', $accessIds));
        }

        $items = $this->repo->search($input);

        $this->status = 'SearchSuccess';
        $this->response = $items;

        return $items;
    }


    public function setStoreDefaultInput(Collection $input)
    {
        if ($this->repo->getModel()->hasAttribute('alias')) {
            $input->put('alias', Str::lower($input->get('alias')));
        }

        if ($this->repo->getModel()->hasAttribute('state') && $input->get('state') === null) {
            $input->forget('state');
        }

        if ($this->repo->getModel()->hasAttribute('access') && InputHelper::null($input, 'access')) {
            $input->put('access', config('daydreamlab.cms.default_viewlevel_id'));
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
            $item = $this->checkItem($input->except('ids'));

            $result = $this->repo->state($item, $input->get('state'));
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

//
//    public function throwResponse($status, $response = null, $input = null, $trans_params = [])
//    {
//        $statusString = Str::upper(Str::snake($status));
//        $this->hook($input, $statusString, $response);
//
//        if (config('app.debug')) {
//            $bt = debug_backtrace();
//            $trace = array_shift($bt);
//            if (gettype($response) == 'array') {
//                $response['debug']['file'] = $trace['file'];
//                $response['debug']['line'] = $trace['line'];
//                $response['debug']['function'] = $trace['function'];
//            } elseif (gettype($response) == 'object') {
//                $temp['file'] = $trace['file'];
//                $temp['line'] = $trace['line'];
//                $temp['function'] = $trace['function'];
//                $response->debug = $temp;
//            } elseif (gettype($response) == 'string') {
//                $temp['file'] = $trace['file'];
//                $temp['line'] = $trace['line'];
//                $temp['function'] = $trace['function'];
//                $temp['response'] = $response;
//                $response = $temp;
//            }
//        }
//
//        throw new HttpResponseException(ResponseHelper::genResponse(
//            $statusString,
//            $response,
//            $this->package,
//            $this->modelName,
//            $trans_params
//        ));
//    }


    public function update($model, $data)
    {
        return $this->repo->modify($model, $data);
    }
}
