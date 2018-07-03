<?php

namespace DaydreamLab\JJAJ\Commands;


use Illuminate\Console\GeneratorCommand;


class ModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daydreamlab:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model';



    protected function getStub()
    {
        return base_path().'/DaydreamLab/Models/Stubs/model.stub';
    }



    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    protected function replaceScaffold(&$stub, $name)
    {
        $model = str_replace($this->getNamespace($name).'\\', '', $name);

        $stub  = str_replace('DummyTable', strtolower($model.'s'), $stub);

        return $this;
    }
}
