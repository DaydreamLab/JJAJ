<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;

class RepositoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:repository {name} {--admin} {--front} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate repository';


    protected $type = 'Repository';


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


    public function getStub()
    {
        if($this->option('front')) {
            return __DIR__.'/../Repositories/Stubs/repository.front.stub';
        }
        elseif ($this->option('admin')) {
            return __DIR__.'/../Repositories/Stubs/repository.admin.stub';
        }
        else {
            return __DIR__.'/../Repositories/Stubs/repository.stub';
        }
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $repository = str_replace($this->getNamespace($name).'\\', '', $name);
        $model      = str_replace('Repository', '', $repository);
        $type       = CommandHelper::getType($name);
        $component  = $this->option('component');

        if ($this->option('front')) {
            $site = 'Front';
        }
        elseif ($this->option('admin')) {
            $site = 'Admin';
        }

        if ($component) {
            $model_path = 'DaydreamLab\\'.$component;
        }
        else {
            $model_path = 'App';
        }


        if ($this->option('front') || $this->option('admin')) {
            $parent_repo        = CommandHelper::getParent($repository);
            $parent_namespace   = CommandHelper::getParentNameSpace($this->getNamespace($name));

            $stub  = str_replace('DummyParentRepositoryNamespace', $parent_namespace.$parent_repo, $stub);
            $stub  = str_replace('DummyFrontRepository', $parent_repo, $stub);
            $stub  = str_replace('DummyAdminRepository', $parent_repo, $stub);
            $stub  = str_replace('DummySite', $site, $stub);
        }

        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyModel', $model, $stub);
        $stub  = str_replace('DummyPathModel', $model_path, $stub);


        return  $this;
    }

}
