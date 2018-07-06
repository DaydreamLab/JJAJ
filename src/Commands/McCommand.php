<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class McCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:mc {name}';

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
        $this->call('jjaj:controller', ['name' => 'API/'.$input.'Controller']);
        $this->call('jjaj:service', ['name' => 'Services/'.$input.'Service']);
        $this->call('jjaj:repository', ['name' => 'Repositories/'.$input.'Repository']);
        $this->call('jjaj:model', ['name' => 'Models/'.$input]);
    }

    public function convertTableName($input)
    {
        $input_snake = Str::snake($input);
        $items = explode('_', $input_snake);
        $snake = '';
        foreach ($items as $item) {
            $snake .=ucfirst($item . 's');
        }
        return Str::snake($snake);
    }
}
