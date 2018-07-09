<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\Helper;
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
        $name = ucfirst($this->argument('name'));
        $type =  ucfirst(explode('_', Str::snake($name))[0]);
        $table = Helper::convertTableName($name);

        $this->call('make:migration', ['name' => 'create_'.$table.'_table', '--create' => $table]);
        $this->call('jjaj:controller', ['name' => 'API/'. $type . '/' .$name.'Controller']);
        $this->call('jjaj:service', ['name' => 'Services/'.$type.'/'.$name.'Service']);
        $this->call('jjaj:repository', ['name' => 'Repositories/'.$type.'/'.$name.'Repository']);
        $this->call('jjaj:model', ['name' => 'Models/'.$type.'/'.$name]);
    }


}
