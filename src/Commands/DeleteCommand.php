<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:delete';

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
        File::deleteDirectory('app/Http/Controllers/API');
        File::deleteDirectory('app/Models');
        File::deleteDirectory('app/Repositories');
        File::deleteDirectory('app/Services');
    }
}
