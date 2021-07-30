<?php

namespace DaydreamLab\JJAJ\Resources;

use DaydreamLab\JJAJ\Traits\FormatDateTime;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseJsonResource extends JsonResource
{
    use FormatDateTime;
}
