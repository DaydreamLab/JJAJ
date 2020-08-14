<?php

namespace DaydreamLab\JJAJ\Migration;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;

class BaseMigrationCreator extends MigrationCreator
{
    public function __construct(Filesystem $files, $customStubPath = null)
    {
        parent::__construct($files, $customStubPath);
    }

    protected function getStub($table, $create)
    {
        return $this->files->get(__DIR__.'/Stubs/create.stub');
    }
}