<?php

namespace DaydreamLab\JJAJ\Resources;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseResourceCollection extends ResourceCollection
{
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

        if($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {
            return [
                'items'         => $this->collection,
                'pagination'    => $resource,
                'records'       => $this->collection->count()
            ];
        }
        else
        {
            return [
                'items'         => $this->collection,
                'records'       => $this->collection->count()
            ];
        }
    }
}
