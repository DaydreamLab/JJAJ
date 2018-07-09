<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;

class RepositoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate repository';


    protected $type = 'Repository';


    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    public function getStub()
    {
        return __DIR__.'/../Repositories/Stubs/repository.stub';
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $repository = str_replace($this->getNamespace($name).'\\', '', $name);

        $model = str_replace('Repository', '', $repository);

        $stub  = str_replace('DummyModel', $model , $stub);

        $stub  = str_replace('DummyType', Helper::getType($name) , $stub);

        return  $this;
    }

}
