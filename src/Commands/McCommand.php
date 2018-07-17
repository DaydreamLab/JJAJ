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
    protected $signature = 'jjaj:mc {name} {--admin} {--front}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model, controller implement service/repository design pattern';

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
        $name  = ucfirst($this->argument('name'));
        $type  = ucfirst(explode('_', Str::snake($name))[0]);
        $table = Helper::convertTableName($name);

        //$this->call('make:migration', ['name' => 'create_'.$table.'_table', '--create' => $table]);


        //$this->call('jjaj:controller', ['name' => 'API/'. $type . '/' .$name.'Controller']);
        //$this->call('jjaj:service', ['name' => 'Services/'.$type.'/'.$name.'Service']);
        //$this->call('jjaj:repository', ['name' => 'Repositories/'.$type.'/'.$name.'Repository']);
        //$this->call('jjaj:model', ['name' => 'Models/'.$type.'/'.$name]);


        if ($this->option('front')) {
            //$this->call('jjaj:controller', ['name' => 'API/'. $type . '/Front/' .$name.'FrontController']);
            //$this->call('jjaj:service', ['name' => 'Services/'.$type.'/Front/'.$name.'FrontService']);
            //$this->call('jjaj:repository', ['name' => 'Repositories/'.$type.'/Front/'.$name.'FrontRepository']);
            $this->call('jjaj:model', ['name' => 'Models/'.$type.'/Front/'.$name.'Front', '--front']);
        }

        if ($this->option('admin')) {
            //$this->call('jjaj:controller', ['name' => 'API/'. $type . '/Admin/' .$name.'AdminController']);
            //$this->call('jjaj:service', ['name' => 'Services/'.$type.'/Admin/'.$name.'AdminService']);
            //$this->call('jjaj:repository', ['name' => 'Repositories/'.$type.'/Admin/'.$name.'AdminRepository']);
            //$this->call('jjaj:model', ['name' => 'Models/'.$type.'/Admin/'.$name.'Admin']);
        }
    }


}
