<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;

class ClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cache view config';

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
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
    }
}
