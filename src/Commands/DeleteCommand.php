<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:delete {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all files about service/repository design pattern(only for dev)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $controller_path    = 'app/Http/Controllers/API/';
        $model_path         = 'app/Models/';
        $repository_path    = 'app/Repositories/';
        $service_path       = 'app/Services/';
        $request_path       = 'app/Http/Requests/';
        $constants_path     = 'app/constants/';

        $name = $this->option('name');
        if ($name) {
            $controller_path    .= $name;
            $model_path         .= $name;
            $repository_path    .= $name;
            $service_path       .= $name;
            $request_path       .= $name;
            $constants_path     .= $name.'.php';
        }

        File::deleteDirectory($controller_path);
        File::deleteDirectory($model_path);
        File::deleteDirectory($repository_path);
        File::deleteDirectory($service_path);
        File::deleteDirectory($request_path);
        File::delete($constants_path);
    }
}
