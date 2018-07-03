<?php

namespace DaydreamLab\JJAJ\Commands;


use DaydreamLab\Helpers\Helper;
use Illuminate\Routing\Console\ControllerMakeCommand;

class ControllerCommand extends ControllerMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daydreamlab:controller {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Controller';



    protected function getStub()
    {
        return base_path().'/DaydreamLab/Controllers/stubs/controller.stub';
    }


    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $Controller = str_replace($this->getNamespace($name).'\\', '', $name);

        $Model = str_replace('Controller', '', $Controller);

        $Service = $Model. 'Service';

        $service = strtolower($Model).'Service';


        $stub  = str_replace('DummyService', $Service, $stub);

        $stub  = str_replace('dummyService', $service, $stub);


        return $this;
    }
}
