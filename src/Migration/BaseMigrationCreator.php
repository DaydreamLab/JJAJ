<?php

namespace DaydreamLab\JJAJ\Migration;

use Illuminate\Database\Migrations\MigrationCreator;

class BaseMigrationCreator extends MigrationCreator
{
    protected function getStub($table, $create)
    {
        return $this->files->get(__DIR__.'/Stubs/create.stub');
    }
}