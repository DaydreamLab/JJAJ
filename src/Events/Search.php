<?php

namespace DaydreamLab\JJAJ\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Search
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $input;

    public $user;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($input, $user)
    {
        $this->user     = $user;
        $this->input    = $input;
    }

}
