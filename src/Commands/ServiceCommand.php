<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\GeneratorCommand;

class ServiceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate service';


    protected $type = 'Service';


    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    public function getStub()
    {
        return __DIR__.'/../Services/Stubs/service.stub';
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $Service = str_replace($this->getNamespace($name).'\\', '', $name);

        $model = str_replace('Service', '', $Service);

        $stub  = str_replace('DummyRepository', $model . 'Repository' , $stub);

        return  $this;
    }
}
