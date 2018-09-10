<?php

namespace DaydreamLab\JJAJ\Commands;


use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;


class ModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:model {name} {--table=} {--front} {--admin} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model';

    protected $type = 'Model';

    protected function getStub()
    {
        if($this->option('front')) {
            return __DIR__.'/../Models/Stubs/model.front.stub';
        }
        elseif ($this->option('admin')) {
            return __DIR__.'/../Models/Stubs/model.admin.stub';
        }
        else {
            return __DIR__.'/../Models/Stubs/model.stub';
        }
    }


    protected function buildClass($name)
    {
        try {
            $stub = $this->files->get($this->getStub());
        }
        catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }

        if ($this->option('component')) {
            $name = str_replace('App\\', '', $name);
        }
        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    protected function replaceScaffold(&$stub, $name)
    {
        $model = str_replace($this->getNamespace($name).'\\', '', $name);

        if ($this->option('front') || $this->option('admin')) {
            $parent_model       = CommandHelper::getParent($model);
            $parent_namespace   = CommandHelper::getParentNameSpace($this->getNamespace($name));
            $stub  = str_replace('DummyParentNamespace', $parent_namespace.$parent_model, $stub);
            $stub  = str_replace('DummyFrontModel', $parent_model, $stub);
            $stub  = str_replace('DummyAdminModel', $parent_model, $stub);
            $stub  = str_replace('DummyTable', CommandHelper::convertTableName($parent_model), $stub);
        }
        else {
            $stub  = str_replace('DummyTable', CommandHelper::convertTableName($model), $stub);
        }

        return $this;
    }
}
