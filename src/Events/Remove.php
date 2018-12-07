<?php

namespace DaydreamLab\JJAJ\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class Remove
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'remove';

    public $item_ids = [];

    public $result;

    public $user;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($type, $result, $input, $user)
    {
        $this->user     = $user;
        $this->result   = $result ? 'success' : 'fail';
        $this->type     = $type;
        $this->item_ids = $input->ids;
    }

}
