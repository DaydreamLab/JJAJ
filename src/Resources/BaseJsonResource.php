<?php

namespace DaydreamLab\JJAJ\Resources;

use DaydreamLab\JJAJ\Traits\AuthApiUser;
use DaydreamLab\JJAJ\Traits\FormatDateTime;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    use FormatDateTime, AuthApiUser;
}
