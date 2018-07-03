<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;

class McCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daydreamlab:mc {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $input = ucfirst($this->argument('name'));

        $this->call('make:migration', ['name' => 'create_'.strtolower($input).'s_table', '--create' => strtolower($input).'s']);
        $this->call('daydreamlab:controller', ['name' => 'API/'.$input.'Controller']);
        $this->call('daydreamlab:service', ['name' => 'Services/'.$input.'Service']);
        $this->call('daydreamlab:repository', ['name' => 'Repositories/'.$input.'Repository']);
        $this->call('daydreamlab:model', ['name' => 'Models/'.$input]);
    }
}
