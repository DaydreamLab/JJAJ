<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Filesystem\Filesystem;
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


    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

    }


    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Controllers';
    }

    protected function getStub()
    {
        if ($this->option('component')) {
            return $this->option('componentBase')
                ? __DIR__.'/../Controllers/Stubs/controller.component.base.stub'
                : __DIR__.'/../Controllers/Stubs/controller.component.stub';
        } elseif($this->option('front')) {
            return __DIR__.'/../Controllers/Stubs/controller.front.stub';
        } elseif ($this->option('admin')) {
            return __DIR__.'/../Controllers/Stubs/controller.admin.stub';
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
        $controllerClass = str_replace($this->getNamespace($name).'\\', '', $name);
        $model  = str_replace('Controller', '', $controllerClass);
        $modelName = str_replace('Front', '', str_replace('Admin', '', $model));
        $component  = $this->option('component');
        $type = $this->option('admin')
            ? 'Admin'
            : (($this->option('front')
                ? 'Front'
                : ''
            ));
        if ($component) {
            $basePath = 'DaydreamLab\\' . $component;
        } else {
            $basePath = 'App';
        }

        if($this->option('component')) {
            $stub = str_replace('DummyParentControllerClass', $component.'Controller' , $stub);
            $stub = str_replace('DummyParentControllerPath', $basePath.'\\Controllers\\'.$component.'Controller' , $stub);
            $stub = str_replace('DummyComponentBaseClass', $component.'Controller' , $stub);
            $stub = str_replace('DummyComponentBasePath', $basePath.'\\Controllers\\'.$component.'Controller' , $stub);
            if (!$this->option('componentBase')) {
                $stub = str_replace('DummyComponentServicePath', $basePath.'\\Services\\'.$component.'Service' , $stub);
                $stub = str_replace('DummyComponentServiceClass', $component.'Service' , $stub);
                $stub = str_replace('DummyParentControllerPath', $basePath.'\\Controllers\\'.$component.'Controller', $stub);
                $stub = str_replace('DummyParentControllerClass', $component.'Controller', $stub);
            }
            $stub = str_replace('{package}', $this->option('component'), $stub);
            $stub = str_replace('DummyComponentPath', $basePath, $stub);
            $stub = str_replace('DummyModelAndTypePath', $modelName.($type ? '\\' : '').$type, $stub);

        } else {
            $stub = str_replace('{package}', '', $stub);
            $stub = str_replace('DummyParentControllerPath', 'DaydreamLab\\JJAJ\\Controllers\\BaseController', $stub);
            $stub = str_replace('DummyParentControllerClass', 'BaseController', $stub);
        }

        $stub = str_replace('{modelName}',$modelName, $stub);
        $stub = str_replace('DummyModelName', $modelName , $stub);
        $stub = str_replace('DummyService', $model.'Service', $stub);
        if (!$this->option('admin') && !$this->option('front')) {
            $stub = str_replace('DummyPathService', $basePath.'\\Services\\'.$modelName.'\\'.$model.'Service', $stub);
        } else{
            $stub = str_replace('DummyPathService', $basePath.'\\Services\\'.$modelName.'\\'.$type.'\\'.$model.'Service', $stub);
        }
        $stub = str_replace('DummyPathRequest', $basePath, $stub);
        $stub = str_replace('DummyStoreRequest', $model.'StoreRequest', $stub);
        $stub = str_replace('DummyRemoveRequest', $model.'RemoveRequest', $stub);
        $stub = str_replace('DummyStateRequest', $model.'StateRequest', $stub);
        $stub = str_replace('DummySearchRequest', $model.'SearchRequest', $stub);
        $stub = str_replace('DummyOrderingRequest', $model.'OrderingRequest', $stub);
        $stub = str_replace('DummyRestoreRequest', $model.'RestoreRequest', $stub);
        $stub = str_replace('DummyFeaturedRequest', $model.'FeaturedRequest', $stub);
        $stub = str_replace('DummyFeaturedOrderingRequest', $model.'FeaturedOrderingRequest', $stub);
        $stub = str_replace('DummyGetItemRequest', $model.'GetItemRequest', $stub);

        return $this;
    }
}
