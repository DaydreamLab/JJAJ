<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
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
        $table = CommandHelper::convertTableName($name);

        $this->call('make:migration', ['name' => 'create_'.$table.'_table', '--create' => $table]);
        $this->call('jjaj:controller', ['name' => 'API/'. $type . '/' .$name.'Controller']);
        $this->call('jjaj:service', ['name' => 'Services/'.$type.'/'.$name.'Service']);
        $this->call('jjaj:repository', ['name' => 'Repositories/'.$type.'/'.$name.'Repository']);
        $this->call('jjaj:model', ['name' => 'Models/'.$type.'/'.$name]);
        $this->call('jjaj:request', ['name' => $type.'/'.$name.'StorePost']);
        $this->call('jjaj:request', ['name' => $type.'/'.$name.'DeletePost']);
        $this->call('jjaj:request', ['name' => $type.'/'.$name.'TrashPost']);
        $this->call('jjaj:constant', ['name' => 'constants/'.$type]);

        if ($this->option('front')) {
            $this->call('jjaj:controller', [
                'name'      => 'API/'. $type . '/Front/' .$name.'FrontController',
                '--front'     => true
            ]);
            $this->call('jjaj:service', [
                'name'      => 'Services/'.$type.'/Front/'.$name.'FrontService',
                '--front'   => true,
            ]);
            $this->call('jjaj:repository', [
                'name'      => 'Repositories/'.$type.'/Front/'.$name.'FrontRepository',
                '--front'   => true,
            ]);
            $this->call('jjaj:model', [
                'name' => 'Models/'.$type.'/Front/'.$name.'Front',
                '--front'   => true,
                '--table'   => $name
            ]);

            $this->call('jjaj:request', [
                'name' => $type.'/Front/'.$name.'FrontStorePost',
                '--front'   => true
            ]);
            $this->call('jjaj:request', [
                'name' => $type.'/Front/'.$name.'FrontDeletePost',
                '--front'   => true
            ]);
            $this->call('jjaj:request', [
                'name' => $type.'/Front/'.$name.'FrontTrashPost',
                '--front'   => true
            ]);
        }

        if ($this->option('admin')) {
            $this->call('jjaj:controller', [
                'name'      => 'API/'. $type . '/Admin/' .$name.'AdminController',
                '--admin'   => true
            ]);
            $this->call('jjaj:service', [
                'name'      => 'Services/'.$type.'/Admin/'.$name.'AdminService',
                 '--admin'  => true,
            ]);
            $this->call('jjaj:repository', [
                'name'      => 'Repositories/'.$type.'/Admin/'.$name.'AdminRepository',
                '--admin'   => true,
            ]);
            $this->call('jjaj:model', [
                'name' => 'Models/'.$type.'/Admin/'.$name.'Admin',
                '--admin'   => true,
                '--table'   => $name
            ]);

            $this->call('jjaj:request', [
                'name' => $type.'/Admin/'.$name.'AdminStorePost',
                '--admin'   => true
            ]);
            $this->call('jjaj:request', [
                'name' => $type.'/Admin/'.$name.'AdminDeletePost',
                '--admin'   => true
            ]);
            $this->call('jjaj:request', [
                'name' => $type.'/Admin/'.$name.'AdminTrashPost',
                '--admin'   => true
            ]);
        }
    }


}
