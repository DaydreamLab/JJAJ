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

class Ordering
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'ordering';

    public $item_id;

    public $payload;

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
        $this->item_id  = $input->id;
        $this->payload  = json_encode(['index_diff' => $input->index_diff]);

        if ($input->has('orderingKey'))
        {
            $orderingKey = $input->get('orderingKey');
        }

        if ($orderingKey == 'featured_ordering')
        {
            $this->action = 'featured_ordering';
        }
    }

}
