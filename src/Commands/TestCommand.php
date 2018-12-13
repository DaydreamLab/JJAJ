<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TestCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:test {name} {--unit} {--feature} {--type=} {--admin} {--front} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test';


    protected $type;

    protected $unit = 'Unit';

    protected $model ;

    protected $front_or_admin = null;

    protected $namespace;


    protected function buildClass($name)
    {
        try {
            $stub = $this->files->get($this->getStub());
        }
        catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }

        return $this->replaceNamespace($stub, $name)->replaceScaffold($stub, $name)->replaceClass($stub, $name);
    }


    public function handle()
    {
        $this->getSettings();

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->error('Test already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass($name));

        $this->info('Test created successfully.');
    }


    protected function getPath($name)
    {
        return base_path(). '/'.str_replace('\\', '/', $this->namespace). $this->getNameInput().'.php';
    }


    public function getStub()
    {
        $stub  = __DIR__. '/../Tests/Stubs/test.' . $this->type . '.stub' ;

        return $stub;
    }


    protected function getSettings()
    {
        $this->model = explode('_', Str::snake($this->getNameInput()))[0];

        $this->type = Str::lower($this->option('type'));

        $this->unit = $this->option('feature') ? 'Feature' : 'Unit';

        if ($this->option('front') || $this->option('admin'))
        {
            $this->front_or_admin = $this->option('front') ? 'front': 'admin';
        }

        $this->namespace = 'Tests\\' . ucfirst($this->unit). '\\' . ucfirst($this->type) . 's\\' . ucfirst($this->model) . '\\';
        if ($this->front_or_admin != null)
        {
            $this->namespace .= ucfirst($this->front_or_admin) . '\\';
        }
    }


    protected function replaceScaffold(&$stub, $name)
    {
//        $name = str_replace($this->getNamespace($name).'\\', '', $name);

        return  $this;
    }
}
