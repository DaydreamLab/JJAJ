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
    protected $signature = 'jjaj:controller {name} {--admin} {--front}';

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
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $controller = str_replace($this->getNamespace($name).'\\', '', $name);
        $model      = str_replace('Controller', '', $controller);
        $type       = CommandHelper::getType($name);

        if ($this->option('front')) {
            $site = 'Front';
        }
        elseif ($this->option('admin')) {
            $site = 'Admin';
        }

        if ($this->option('front') || $this->option('admin')) {
            $parent_controller  = CommandHelper::getParent($controller);
            $parent_namespace   = CommandHelper::getParentNameSpace($this->getNamespace($name));
            $parent_model       =  CommandHelper::getParent($model);

            //$stub  = str_replace('DummyParentControllerNamespace', $parent_namespace.$parent_controller, $stub);
            $stub  = str_replace('DummyFrontController', $parent_controller, $stub);
            $stub  = str_replace('DummyAdminController', $parent_controller, $stub);
            $stub  = str_replace('DummySite', $site, $stub);
            $stub  = str_replace('DummyStorePostParentRequest', $parent_model.'StorePost', $stub);
            $stub  = str_replace('DummyDeletePostParentRequest', $parent_model.'DeletePost', $stub);
            $stub  = str_replace('DummyTrashPostParentRequest', $parent_model.'TrashPost', $stub);
        }


        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyService', $model.'Service', $stub);
        $stub  = str_replace('DummyStorePostRequest', $model.'StorePost', $stub);
        $stub  = str_replace('DummyDeletePostRequest', $model.'DeletePost', $stub);
        $stub  = str_replace('DummyTrashPostRequest', $model.'TrashPost', $stub);



        return $this;
    }
}
