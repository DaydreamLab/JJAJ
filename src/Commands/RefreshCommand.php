<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh database and seeder and passport';

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
        $this->call('migrate:refresh', [
            '--seed' => true
        ]);

        $this->call('passport:install', [
            '--force' => true
        ]);
    }
}
