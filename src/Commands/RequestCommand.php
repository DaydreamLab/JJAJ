<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Foundation\Console\RequestMakeCommand;

class RequestCommand extends RequestMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daydreamlab:request {name}, {--list} {--admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    protected $type = 'Request';

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceClass($stub, $name);
    }


    public function getStub()
    {
        $option = $this->option('list');

        if ($option) {
            return __DIR__.'/../Requests/Stubs/request.list.stub';
        }
        else {
            return __DIR__.'/../Requests/Stubs/request.admin.stub';
        }
    }

}
