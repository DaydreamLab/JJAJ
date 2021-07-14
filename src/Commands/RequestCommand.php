<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Psy\Util\Str;

class RequestCommand extends RequestMakeCommand
{
    protected $requestType;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:request {name} {--list} {--remove} {--restore} {--store} {--state} {--search} {--ordering} {--orderingNested} {--featuredOrdering} {--featured} {--getItem}  {--admin} {--front} {--componentBase} {--component=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create request';

    protected $type = 'Request';


    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Requests';
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
            $name = str_replace('App\Requests\\', '', $name);
        }

        return  $this->replaceNamespace($stub, $name)->replaceScaffold($stub,$name)->replaceClass($stub, $name);
    }


    public function getStub()
    {
        if($this->option('remove')){
            $this->requestType = 'Remove';
        } elseif ($this->option('restore')) {
            $this->requestType = 'Restore';
        } else if($this->option('state')){
            $this->requestType = 'State';
        } else if($this->option('store')){
            $this->requestType = 'Store';
        } else if($this->option('ordering')){
            $this->requestType = 'Ordering';
        } else if($this->option('orderingNested')){
            $this->requestType = 'OrderingNested';
        } else if($this->option('search')){
            $this->requestType = 'Search';
        } else if($this->option('featured')){
            $this->requestType = 'Featured';
        } else if($this->option('featuredOrdering')){
            $this->requestType = 'FeaturedOrdering';
        } elseif ($this->option('getItem')) {
            $this->requestType = 'GetItem';
        } elseif ($this->option('list')) {
            $this->requestType = 'List';
        } elseif($this->option('admin')){
            $this->requestType = 'Admin';
        }

        if ($this->option('component')) {
            if ($this->option('componentBase')) {
                return __DIR__ . '/../Requests/Stubs/request.component.base.stub';
            } else {
                return __DIR__ . '/../Requests/Stubs/request.admin.stub';
            }
        } else {
            return  __DIR__ . '/../Requests/Stubs/request.admin.stub';
        }
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $requestClass = str_replace($this->getNamespace($name).'\\', '', $name);
        $model     = str_replace($this->requestType.'Request', '', $requestClass);
        $modelName = str_replace('Front', '', str_replace('Admin', '', $model));
        $component = $this->option('component');

        if ($this->option('component')) {
            if ($this->option('componentBase')) {
                $stub = str_replace('DummyClass', $requestClass , $stub);
                $stub = str_replace('DummyPackage', $component , $stub);
                $stub = str_replace('DummyNamespace', $this->getNamespace($name) , $stub);
                $className = 'Base' . $this->requestType . 'Request';
                $stub = str_replace('DummyParentClassPath', 'DaydreamLab\\JJAJ\\Requests\\'.$className , $stub);
                $stub = str_replace('DummyParentClass',$className , $stub);
            } else {
                if ($this->option('admin') || $this->option('front')) {
                    $className = $model .$this->requestType.'Request';
                    $parentClassName = $component.$this->requestType.'Request';
                    $stub = str_replace('DummyParentClassPath', 'DaydreamLab\\'.$component.'\\Requests\\'.$parentClassName, $stub);
                    $stub = str_replace('DummyParentClass',$parentClassName , $stub);
                    $stub = str_replace('DummyClass',$className , $stub);
                } else {
                    $className = $component .$this->requestType.'Request';
                    $stub = str_replace('DummyParentClassPath', 'DaydreamLab\\'.$component.'\\Requests\\'. $className, $stub);
                    $stub = str_replace('DummyParentClass',$className , $stub);
                }

                $stub = str_replace('DummyModelName',$modelName , $stub);
                if ($this->option('getItem')) {
                    $stub = str_replace('DummyApiMethod','get'.$modelName , $stub);
                } else {
                    $stub = str_replace('DummyApiMethod', lcfirst($this->requestType).$modelName , $stub);
                }
            }
        } else {
            if ($this->option('admin') || $this->option('front')) {

            } else {

            }
        }

        return  $this;
    }

}
