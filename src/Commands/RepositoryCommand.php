<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\GeneratorCommand;

class RepositoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daydreamlab:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate repository';



    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    public function getStub()
    {
        return base_path().'/DaydreamLab/Repositories/Stubs/repository.stub';
    }

    protected function replaceScaffold(&$stub, $name)
    {
        $repository = str_replace($this->getNamespace($name).'\\', '', $name);

        $model = str_replace('Repository', '', $repository);

        $stub  = str_replace('DummyModel', $model , $stub);

        return  $this;
    }

}
