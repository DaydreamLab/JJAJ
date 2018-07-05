<?php

namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Support\Facades\Config;

class ResponseHelper
{
    public static function genResponse($status, $data = null)
    {
        $type = strtolower(explode('_', $status)[0]);
        $config = config('constants.'.$type.'.'.$status);
        $config['data'] = $data;
        $config['status'] = $status;

        if (array_key_exists('message', (array)$data)) {
            $config['message'] = $data['message'];
            //$config['data'] = null;
        }
        return response()->json($config, $config['code']);
    }

    public static function format($data)
    {
        if (gettype($data) == 'array' ) {
            $response['items']      = $data;
            $response['records']    = count([$data]);
        }
        elseif(gettype($data) == 'string') {
            $response['items']      = $data;
            //$response['items']      = null;
            $response['message']    = $data;
            $response['records']    = count([$data]);
        }
        elseif (get_class($data) == 'Illuminate\Database\Eloquent\Collection' ||
            get_class($data) == 'Illuminate\Support\Collection') {

            if ($data->has('statistics')) {
                $response['statistics'] = $data->get('statistics');
                $data->forget('statistics');
            }
            $response['items']      = array_values($data->toArray());
            $response['records']    = count($data);
        }
        elseif (get_class($data) == 'Illuminate\Pagination\LengthAwarePaginator') {
            if ($data->has('statistics')) {
                $response['statistics'] = $data->get('statistics');
                $data->forget('statistics');
            }
            $temp = $data->toArray();
            $response['items']      = $temp['data'];
            unset($temp['data']);
            $response['pagination'] = $temp;
            $response['records']    = count($data);
        }
        else {
            $response['items']      = $data;
            $response['records']    = 1;
        }

        return $response;
    }

}