<?php

namespace DaydreamLab\JJAJ\Commands;


use Illuminate\Console\Command;

class BaseCommand extends Command
{

    public function getJson($path)
    {
        return json_decode(file_get_contents($path), true);
    }
}
