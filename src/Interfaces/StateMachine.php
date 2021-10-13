<?php

namespace DaydreamLab\JJAJ\Interfaces;

interface StateMachine
{
    public function getStateKey();

    public function graph();

    public function transition($from, $to, $key);
}