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
    protected $signature = 'jjaj:repository {name} {--admin} {--front} {--componentBase} {--component=}';

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
        if ($this->option('component')) {
            if ($this->option('componentBase')) {
                return __DIR__.'/../Repositories/Stubs/repository.component.base.stub';
            } else {
                return ($this->option('front') || $this->option('admin'))
                    ? __DIR__.'/../Repositories/Stubs/repository.child.stub'
                    : __DIR__.'/../Repositories/Stubs/repository.component.stub';
            }
        } else {
            return ($this->option('front') || $this->option('admin'))
                ? __DIR__.'/../Repositories/Stubs/repository.child.stub'
                : __DIR__.'/../Repositories/Stubs/repository.parent.stub';
        }
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $repositoryClass = str_replace($this->getNamespace($name).'\\', '', $name);
        $model      = str_replace('Repository', '', $repositoryClass);
        $modelName = str_replace('Front', '', str_replace('Admin', '', $model));
        $component  = $this->option('component');

        $namespace = $component
            ? 'DaydreamLab\\' . $component
            : 'App';

        if ($component) {
            $stub  = str_replace('DummyComponentBaseClass', $component.'Repository' , $stub);
            $stub  = str_replace('DummyComponentBasePath', $namespace.'\\Repositories\\'.$component.'Repository' , $stub);

            if (!$this->option('componentBase')) {
                $stub  = str_replace('DummyComponentModelClass', $model, $stub);
                $stub  = str_replace('DummyComponentModelPath', $namespace.'\\Models\\'.$modelName. '\\'.$model, $stub);
            }
            $stub = str_replace('{package}', $this->option('component'), $stub);
        }
        else {
            $stub = str_replace('{package}', '', $stub);
        }

        if ($this->option('front') || $this->option('admin')) {
            $type = $this->option('admin') ? 'Admin' : 'Front';
            $stub  = str_replace('DummyParentClassPath', $namespace . '\\Repositories\\' . $modelName . '\\' . $modelName . 'Repository', $stub);
            $stub  = str_replace('DummyModelPath', $namespace . '\\Models\\'. $modelName . '\\' .$type .'\\' . $modelName . $type, $stub);
            $stub  = str_replace('DummyParentClass', $modelName . 'Repository', $stub);
            $stub  = str_replace('DummyParentClass', $modelName . 'Repository', $stub);
            $stub = str_replace('DummyModel', $modelName . $type, $stub);
        }

        $stub = str_replace('DummyModelName', $modelName, $stub);


        return  $this;
    }

}
