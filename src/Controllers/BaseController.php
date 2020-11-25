<?php

namespace DaydreamLab\JJAJ\Controllers;

use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use DaydreamLab\JJAJ\Services\BaseService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BaseController extends Controller
{
    protected $service;

    protected $package;

    protected $modelName;

    protected $modelType;

    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }


    public function response($status, $response)
    {
        return ResponseHelper::genResponse1(
            $status,
            $this->formatResponse($response),
            $this->package,
            $this->modelName,
            $this->modelName
        );
    }


    public function formatResponse($data)
    {
        if ($data === null) {
            return null;
        }

        if (gettype($data) == 'array') {
            if (array_key_exists('data', $data)) {
                $response['items'] = $data['data'];
            } else {
                $response['items'] = $data;
            }

            if (array_key_exists('pagination', $data)) {
                $response['pagination'] = $data['pagination'];
                $response['records'] = count($data['data']);
            } else {
                $response['records'] = count($data);
            }

            if (array_key_exists('filter', $data)) {
                $response['filter'] = $data['filter'];
            }
        } elseif (gettype($data) == 'string') {
            /** 不可刪除此區 */
//            $response['items']      = $data;
//            $response['message']    = $data;
//            $response['records']    = count([$data]);

            $response = $data;
        } elseif (gettype($data) === 'boolean') {
            return null;
        } elseif (get_class($data) == 'Illuminate\Database\Eloquent\Collection' ||
            get_class($data) == 'Illuminate\Support\Collection') {
            if ($data->has('statistics')) {
                $response['statistics'] = $data->get('statistics');
                $data->forget('statistics');
            }
            $response['items'] = array_values($data->toArray());
            //pagination非原裝Collection內容物，CursorPaginator的資料是硬塞的
            if (property_exists($data, 'pagination')) {
                $response['pagination'] = $data->pagination;
            }
            $response['records'] = count($data);
        } elseif (get_class($data) == 'Illuminate\Pagination\LengthAwarePaginator') {
            if ($data->has('statistics')) {
                $response['statistics'] = $data->get('statistics');
                $data->forget('statistics');
            }
            $temp = $data->toArray();

            //$response['items']      = array_values($temp['data']);
            if (isset($temp['data']['data'])) {
                $response['items'] = $temp['data']['data'];
                unset($temp['data']['data']);
                $response['pagination'] = $temp['data'];
            } elseif (isset($temp['data'])) {
                $response['items'] = $temp['data'];
                unset($temp['data']);
                $response['pagination'] = $temp;
            }

            //$response['records']    = count($data);
            $response['records'] = count($response['items']);
        } elseif (get_class($data) == 'Kalnoy\Nestedset\Collection') {
            $temp = $data->toArray();
            if (array_key_exists('pagination', $temp)) {
                $pagination = $temp['pagination'];
                unset($temp['pagination']);
                $response['pagination'] = $pagination;
            }
            $items = $temp;
            $response['items'] = $items;
            $response['records'] = count($items);
        } elseif (Str::contains(get_class($data), 'ResourceCollection')) {

            return $data;
        } elseif (gettype($data) == 'object' && isset($data->collection) && get_class($data->collection) == 'Illuminate\Support\Collection') {
            $response['items'] = $data;
            $response['records'] = $data->collection->count();
        } elseif (get_class($data) == 'Juampi92\CursorPagination\CursorPaginator') {
            $orgDataArray = $data->toArray();
            $temp = [];
            $data_count = 0;
            foreach ($orgDataArray['data'] as $notice) {
                $temp[] = $notice;
                $data_count++;
            }
            $response['items'] = $temp;
            unset($orgDataArray['data']);
            $response['pagination'] = $orgDataArray;
            if (property_exists($data, 'unread_total')) {
                $response['unread_total'] = $data->unread_total;
            }
            $response['records'] = $data_count;
        } else {

            $response['items'] = $data;
            $response['records'] = 1;
        }

        return $response;

    }
}
