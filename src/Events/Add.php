<?php

namespace DaydreamLab\JJAJ\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Add
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'add';

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
    public function __construct(Model $model, $type, $input, $user)
    {
        $this->model    = $model;
        $this->user     = $user;
        $this->type     = $type;
        $this->result   = $model ? 'success' : 'fail';
        $this->input    = $input;
    }


}
