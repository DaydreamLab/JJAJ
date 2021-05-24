<?php

namespace DaydreamLab\JJAJ\Resources;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Traits\AuthApiUser;
use DaydreamLab\JJAJ\Traits\FormatDateTime;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseResourceCollection extends ResourceCollection
{
    use FormatDateTime, AuthApiUser;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function toArray($request)
    {
        $resource = $this->resource->toArray();
        unset($resource['data']);

        if($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return [
                'items'         => $this->collection,
                'pagination'    => $resource,
            ];
        } else {
            return [
                'items'         => $this->collection
            ];
        }
    }
}
