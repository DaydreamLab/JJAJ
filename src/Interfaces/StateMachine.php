<?php

namespace DaydreamLab\JJAJ\Interfaces;

interface StateMachine
{
    public function getStateKey($key = null);

    public function graph();

    public function transition($to, $key);
}
