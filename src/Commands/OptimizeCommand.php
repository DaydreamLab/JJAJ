<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;

class OptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize config';

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
        $this->call('config:cache');
        $this->call('view:cache');
        $this->call('route:cache');
        $this->call('optimize');
    }
}
