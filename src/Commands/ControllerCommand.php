<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Routing\Console\ControllerMakeCommand;

class ControllerCommand extends ControllerMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:controller {name} {--admin} {--front} {--componentBase} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Controller';


    protected $type = 'Controller';


    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Controllers';
    }

    protected function getStub()
    {
        if($this->option('front')) {
            return __DIR__.'/../Controllers/Stubs/controller.front.stub';
        } elseif ($this->option('admin')) {
            return __DIR__.'/../Controllers/Stubs/controller.admin.stub';
        } elseif ($this->option('component')) {
            return $this->option('componentBase')
                ? __DIR__.'/../Controllers/Stubs/controller.component.base.stub'
                : __DIR__.'/../Controllers/Stubs/controller.component.stub';
        } else {
            return __DIR__.'/../Controllers/Stubs/controller.stub';
        }
    }


    protected function buildClass($name)
    {
        try {
            $stub = $this->files->get($this->getStub());
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }

        if ($this->option('component')) {
            $name = str_replace('App\Controllers\\', '', $name);
        }

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    protected function replaceScaffold(&$stub, $name)
    {
        $controller = str_replace($this->getNamespace($name).'\\', '', $name);
        $model      = str_replace('Controller', '', $controller);
        $type       = CommandHelper::getType($name);
        $component  = $this->option('component');

        if ($component) {
            $service_path = 'DaydreamLab\\'.$component;
            $request_path = 'DaydreamLab\\'.$component;
        } else {
            $service_path = 'App';
            $request_path = 'App';
        }

        if ($this->option('front')) {
            $site = 'Front';
        }
        elseif ($this->option('admin')) {
            $site = 'Admin';
        } else {
            $site = 'Base';
        }


        if ($this->option('front') || $this->option('admin')) {
            $stub  = str_replace('DummySite', $site, $stub);
        }

        if($this->option('component')) {
            $stub  = str_replace('DummyComponentBaseClass', $component.'Controller' , $stub);
            $stub  = str_replace('DummyComponentBasePath', $service_path.'\\Controllers\\'.$component.'Controller' , $stub);
            //$stub  = str_replace('DummyComponentServicePath', $service_path.'\\Services\\'.$component.'Service' , $stub);
            if (!$this->option('componentBase')) {
                $stub  = str_replace('DummyComponentServicePath', $service_path.'\\Services\\'.$component.'Service' , $stub);
                $stub  = str_replace('DummyComponentServiceClass', $component.'Service' , $stub);
            }
            $stub = str_replace('{package}', $this->option('component'), $stub);

        } else {
            $stub = str_replace('{package}', '', $stub);
        }

        $stub = str_replace('{modelName}', $model, $stub);
        $stub = str_replace('{modelType}', $site, $stub);
        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyService', $model.'Service', $stub);
        $stub  = str_replace('DummyPathService', $service_path, $stub);
        $stub  = str_replace('DummyPathRequest', $request_path, $stub);
        $stub  = str_replace('DummyStorePostRequest', $model.'StorePost', $stub);
        $stub  = str_replace('DummyRemovePostRequest', $model.'RemovePost', $stub);
        $stub  = str_replace('DummyStatePostRequest', $model.'StatePost', $stub);
        $stub  = str_replace('DummySearchPostRequest', $model.'SearchPost', $stub);
        $stub  = str_replace('DummyOrderingPostRequest', $model.'OrderingPost', $stub);
        $stub  = str_replace('DummyCheckoutPostRequest', $model.'CheckoutPost', $stub);

        return $this;
    }
}
