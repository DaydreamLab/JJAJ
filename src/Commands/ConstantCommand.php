<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class ConstantCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:constant {name} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create constants';

    protected $type = 'Constant';

    protected function getStub()
    {
        return __DIR__.'/../Constant/Stubs/constant.stub';
    }


    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    protected function replaceScaffold(&$stub, $name)
    {
        $model = str_replace($this->getNamespace($name).'\\', '', $name);

        $stub = str_replace('DummyModel', Str::upper(Str::snake($this->option('model'))), $stub);
        $stub = str_replace('UcModel', str_replace('_', ' ',ucfirst(Str::snake($this->option('model')))), $stub);

        return $this;
    }
}
