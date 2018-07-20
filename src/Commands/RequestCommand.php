<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Foundation\Console\RequestMakeCommand;

class RequestCommand extends RequestMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:request {name}, {--list} {--admin} {--front} {--remove} {--store} {--state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create request';


    protected $type = 'Request';

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return  $this->replaceNamespace($stub, $name)->replaceScaffold($stub,$name)->replaceClass($stub, $name);
    }


    public function getStub()
    {
        if ($this->option('list')) {
            return __DIR__.'/../Requests/Stubs/request.list.stub';
        }
        elseif($this->option('admin')){
            return __DIR__.'/../Requests/Stubs/request.admin.admin.stub';
        }
        elseif($this->option('front')){
            return __DIR__.'/../Requests/Stubs/request.admin.front.stub';
        }
        else if($this->option('remove')){
            return __DIR__.'/../Requests/Stubs/request.admin.remove.stub';
        }
        else if($this->option('state')){
            return __DIR__.'/../Requests/Stubs/request.admin.state.stub';
        }
        else if($this->option('store')){
            return __DIR__.'/../Requests/Stubs/request.admin.store.stub';
        }
        else {
            return __DIR__.'/../Requests/Stubs/request.admin.stub';
        }
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $model          = str_replace($this->getNamespace($name).'\\', '', $name);
        $parent_model   = CommandHelper::getParent($model);
        $type           = CommandHelper::getType($name);

        $stub  = str_replace('DummyPostRequest', $model . 'Repository' , $stub);
        $stub  = str_replace('DummyType', $type , $stub);
        $stub  = str_replace('DummyParentRequest', $parent_model , $stub);

        return  $this;
    }

}
