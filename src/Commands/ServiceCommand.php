<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;

class ServiceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:service {name} {--table=} {--admin} {--front}';

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
        if($this->option('front')) {
            return __DIR__.'/../Services/Stubs/service.front.stub';
        }
        elseif ($this->option('admin')) {
            return __DIR__.'/../Services/Stubs/service.admin.stub';
        }
        else {
            return __DIR__.'/../Services/Stubs/service.stub';
        }

    }

    protected function replaceScaffold(&$stub, $name)
    {
        $service = str_replace($this->getNamespace($name).'\\', '', $name);
        $model   = substr_replace($service, '', strrpos($service, 'Service'));;
        $type    = CommandHelper::getType($name);
        if ($this->option('front')) {
            $site = 'Front';
        }
        elseif ($this->option('admin')) {
            $site = 'Admin';
        }

        if ($this->option('front') || $this->option('admin')) {
            $parent_service        = CommandHelper::getParent($service);
            $parent_namespace   = CommandHelper::getParentNameSpace($this->getNamespace($name));
            $stub  = str_replace('DummyParentNamespace', $parent_namespace.$parent_service, $stub);
            $stub  = str_replace('DummyFrontService', $parent_service, $stub);
            $stub  = str_replace('DummyAdminService', $parent_service, $stub);
            $stub  = str_replace('DummySite', $site, $stub);
        }
        else {

        }
        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyRepository', $model . 'Repository' , $stub);





        return  $this;
    }
}
