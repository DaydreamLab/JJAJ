<?php

namespace DaydreamLab\JJAJ\Events;


use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;


class State
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $action = 'state';

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

        $state = $input->state;

        if ($state == 0)
        {
            $this->action = 'unpublished';
        }
        elseif ($state == 1)
        {
            $this->action = 'published';
        }
        elseif ($state == -1)
        {
            $this->action = 'archived';
        }
        elseif ($state == -2)
        {
            $this->action = 'trashed';
        }
    }

}
