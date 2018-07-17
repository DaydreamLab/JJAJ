<?php

namespace DaydreamLab\JJAJ\Commands;


use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;


class ModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:model {name} {--front} {--admin}';

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
        $stub = $this->files->get($this->getStub());
exit();
        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    protected function replaceScaffold(&$stub, $name)
    {
        $model = str_replace($this->getNamespace($name).'\\', '', $name);
Helper::show($model);
        $stub  = str_replace('DummyTable', Helper::convertTableName($model), $stub);

        //$stub  = str_replace('DummyFrontModel', Helper::convertTableName($model), $stub);

        return $this;
    }
}
