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
    protected $signature = 'jjaj:service {name} {--admin} {--front} {--componentBase} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate service';


    protected $type = 'Service';


    protected function buildClass($name)
    {
        try {
            $stub = $this->files->get($this->getStub());
        }
        catch (\Exception $e) {
            //echo $e->getMessage();
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
                return __DIR__.'/../Services/Stubs/service.component.base.stub';
            } else {
                return ($this->option('front') || $this->option('admin'))
                    ? __DIR__.'/../Services/Stubs/service.child.stub'
                    : __DIR__.'/../Services/Stubs/service.component.stub';
            }
        } else {
            return ($this->option('front') || $this->option('admin'))
                ? __DIR__.'/../Services/Stubs/service.child.stub'
                : __DIR__.'/../Services/Stubs/service.parent.stub';
        }
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $serviceClass   = str_replace($this->getNamespace($name).'\\', '', $name);
        $model          = str_replace('Service', '', $serviceClass);
        $modelName      = str_replace('Front', '', str_replace('Admin', '', $model));
        $component  = $this->option('component');

        $namespace = $component
            ? 'DaydreamLab\\'.$component
            : 'App';

        if ($component) {
            $stub  = str_replace('DummyComponentBaseClass', $component.'Service' , $stub);
            $stub  = str_replace('DummyComponentBasePath', $namespace.'\\Services\\'.$component.'Service' , $stub);
            if (!$this->option('componentBase')) {
                $stub  = str_replace('DummyComponentRepositoryClass', $component.'Repository' , $stub);
                $stub  = str_replace('DummyComponentRepositoryPath', $namespace.'\\Repositories\\'. $modelName . '\\' .$model.'Repository' , $stub);
            }
        } else {
            $stub = str_replace('{package}', '', $stub);
        }

        if ($this->option('front') || $this->option('admin')) {
            $type = $this->option('admin') ? 'Admin' : 'Front';
            $stub  = str_replace('DummyParentClassPath', $namespace . '\\Services\\' . $modelName . '\\' . $modelName . 'Service', $stub);
            $stub  = str_replace('DummyRepositoryPath', $namespace . '\\Repositories\\'. $modelName . '\\' .$type .'\\' . $modelName . $type .'Repository', $stub);
            $stub  = str_replace('DummyParentClass', $modelName . 'Service', $stub);
        }

        $stub  = str_replace('DummyModelName', $modelName, $stub);
        $stub  = str_replace('DummyRepository', $model . 'Repository' , $stub);
        $stub  = str_replace('DummyPathRepository', $namespace , $stub);
        $stub = str_replace('{package}', $component ? $component : '', $stub);

        return  $this;
    }
}
