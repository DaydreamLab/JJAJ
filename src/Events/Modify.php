<?php

namespace DaydreamLab\JJAJ\Events;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class Modify
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'modify';

    public $input;

    public $model;

    public $result;

    public $type;

    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Model $model, $type, $result, $input, $user)
    {
        $this->model    = $model;
        $this->user     = $user;
        $this->type     = $type;
        $this->result   = $result ? 'success' : 'fail';
        $this->input    = $input;
    }

}
