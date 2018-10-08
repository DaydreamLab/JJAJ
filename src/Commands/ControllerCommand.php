<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ControllerCommand extends ControllerMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:controller {name} {--admin} {--front} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Controller';


    protected $type = 'Controller';

    protected function getStub()
    {
        if($this->option('front')) {
            return __DIR__.'/../Controllers/Stubs/controller.front.stub';
        }
        elseif ($this->option('admin')) {
            return __DIR__.'/../Controllers/Stubs/controller.admin.stub';
        }
        else {
            return __DIR__.'/../Controllers/Stubs/controller.stub';
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
            $name = str_replace('App\Http\Controllers\\', '', $name);
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
        }
        else {
            $service_path = 'App';
            $request_path = 'App\Http';
        }


        if ($this->option('front')) {
            $site = 'Front';
        }
        elseif ($this->option('admin')) {
            $site = 'Admin';
        }


        if ($this->option('front') || $this->option('admin')) {
            $stub  = str_replace('DummySite', $site, $stub);
        }

        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyService', $model.'Service', $stub);
        $stub  = str_replace('DummyPathService', $service_path, $stub);
        $stub  = str_replace('DummyPathRequest', $request_path, $stub);
        $stub  = str_replace('DummyStorePostRequest', $model.'StorePost', $stub);
        $stub  = str_replace('DummyRemovePostRequest', $model.'RemovePost', $stub);
        $stub  = str_replace('DummyStatePostRequest', $model.'StatePost', $stub);
        $stub  = str_replace('DummySearchPostRequest', $model.'SearchPost', $stub);
        $stub  = str_replace('DummyOrderingPostRequest', $model.'OrderingPost', $stub);

        return $this;
    }
}
