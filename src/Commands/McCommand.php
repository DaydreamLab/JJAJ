<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class McCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:mc {name} {--admin} {--front} {--component=}';

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
        $name       = ucfirst($this->argument('name'));
        $type       = ucfirst(explode('_', Str::snake($name))[0]);
        $table      = CommandHelper::convertTableName($name);
       
        $component  = $this->option('component');
        if ($component) {
            $component_namespace = 'DaydreamLab/'. $component;
            $controller_namespace       = $component_namespace.'/Controllers/'.$type.'/'.$name.'Controller';
            $controller_front_namespace = $component_namespace.'/Controllers/'.$type.'/Front/'.$name.'FrontController';
            $controller_admin_namespace = $component_namespace.'/Controllers/'.$type.'/Admin/'.$name.'AdminController';

            $service_namespace          = $component_namespace.'/Services/'.$type.'/'.$name.'Service';
            $service_front_namespace    = $component_namespace.'/Services/'.$type.'/Front/'.$name.'FrontService';
            $service_admin_namespace    = $component_namespace.'/Services/'.$type.'/Admin/'.$name.'AdminService';

            $repository_namespace       = $component_namespace.'/Repositories/'.$type.'/'.$name.'Repository';
            $repository_front_namespace = $component_namespace.'/Repositories/'.$type.'/Front/'.$name.'FrontRepository';
            $repository_admin_namespace = $component_namespace.'/Repositories/'.$type.'/Admin/'.$name.'AdminRepository';

            $model_namespace            = $component_namespace.'/Models/'.$type.'/'.$name;
            $model_front_namespace      = $component_namespace.'/Models/'.$type.'/Front/'.$name.'Front';
            $model_admin_namespace      = $component_namespace.'/Models/'.$type.'/Admin/'.$name.'Admin';

            $request_namespace          = $component_namespace.'/Requests/'.$type.'/'.$name;
            $request_front_namespace    = $component_namespace.'/Requests/'.$type.'/Front/'.$name;
            $request_admin_namespace    = $component_namespace.'/Requests/'.$type.'/Admin/'.$name;
        }
        else {
            $controller_namespace       = 'API/'. $type . '/' .$name.'Controller';
            $controller_front_namespace = 'API/'. $type . '/Front/' .$name.'FrontController';
            $controller_admin_namespace = 'API/'. $type . '/Admin/' .$name.'AdminController';

            $service_namespace          = 'Services/'.$type . '/' .$name.'Service';
            $service_front_namespace    = 'Services/'.$type . '/Front/' .$name.'FrontService';
            $service_admin_namespace    = 'Services/'.$type . '/Admin/' .$name.'AdminService';

            $repository_namespace       = 'Repositories/'.$type . '/' .$name.'Repository';
            $repository_front_namespace = 'Repositories/'.$type . '/Front/' .$name.'FrontRepository';
            $repository_admin_namespace = 'Repositories/'.$type . '/Admin/' .$name.'AdminRepository';

            $model_namespace            = 'Models/'.$type . '/' .$name;
            $model_front_namespace      = 'Models/'.$type . '/Front/' .$name.'Front';
            $model_admin_namespace      = 'Models/'.$type . '/Admin/' .$name.'Admin';

            $request_namespace = $type.'/'.$name;
            $request_front_namespace = $type.'/Front/'.$name;
            $request_admin_namespace = $type.'/Admin/'.$name;
        }

        if ($component) {
            if (!File::exists('database/migrations/'.$component)) {
                File::makeDirectory('database/migrations/'.$component);
            }
            $this->call('jjaj:migration', [
                'name'          => 'create_'.$table.'_table',
                '--path'        => 'database/migrations/'.$component,
                '--create'      => $table
            ]);
        }
        else {
            $this->call('jjaj:migration', [
                'name'          => 'create_'.$table.'_table',
                '--create'      => $table
            ]);
        }

        $this->call('jjaj:controller', [
            'name'          => $controller_namespace,
            '--component'   => $component
        ]);

        $this->call('jjaj:service', [
            'name'          => $service_namespace,
            '--component'   => $component,
        ]);
        $this->call('jjaj:repository', [
            'name'          => $repository_namespace,
            '--component'   => $component,
        ]);
        $this->call('jjaj:model', [
            'name'          => $model_namespace,
            '--component'   => $component,
        ]);

        $this->call('jjaj:request', [
            'name'      => $request_namespace.'StorePost',
            '--component'   => $component,
            '--store'   => true
        ]);
        $this->call('jjaj:request', [
            'name'      => $request_namespace.'RemovePost',
            '--component'   => $component,
            '--remove'  => true
        ]);
        $this->call('jjaj:request', [
            'name'      => $request_namespace.'StatePost',
            '--component'   => $component,
            '--state'   => true
        ]);
        $this->call('jjaj:request', [
            'name'      => $request_namespace.'SearchPost',
            '--component'   => $component,
            '--search'    => true,
        ]);
        $this->call('jjaj:request', [
            'name'      => $request_namespace.'OrderingPost',
            '--component'   => $component,
            '--ordering'    => true,
        ]);

        $this->call('jjaj:constant', ['name' => 'constants/'.Str::lower($type), '--model' => $name]);

        if ($this->option('front')) {
            $this->call('jjaj:controller', [
                'name'          => $controller_front_namespace,
                '--component'   => $component,
                '--front'       => true
            ]);
            $this->call('jjaj:service', [
                'name'          => $service_front_namespace,
                '--component'   => $component,
                '--front'       => true,
            ]);
            $this->call('jjaj:repository', [
                'name'          => $repository_front_namespace,
                '--component'   => $component,
                '--front'       => true,
            ]);
            $this->call('jjaj:model', [
                'name'          => $model_front_namespace,
                '--component'   => $component,
                '--front'       => true,
                '--table'       => $name
            ]);

            $this->call('jjaj:request', [
                'name'          => $request_front_namespace.'FrontStorePost',
                '--component'   => $component,
                '--store'       => true,
                '--front'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_front_namespace.'FrontRemovePost',
                '--component'   => $component,
                '--remove'      => true,
                '--front'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_front_namespace.'FrontStatePost',
                '--component'   => $component,
                '--state'       => true,
                '--front'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_front_namespace.'FrontSearchPost',
                '--component'   => $component,
                '--search'       => true,
                '--front'       => true,
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_front_namespace.'FrontOrderingPost',
                '--component'   => $component,
                '--ordering'    => true,
                '--front'       => true,
            ]);
        }

        if ($this->option('admin')) {
            $this->call('jjaj:controller', [
                'name'          => $controller_admin_namespace,
                '--component'   => $component,
                '--admin'       => true
            ]);
            $this->call('jjaj:service', [
                'name'          => $service_admin_namespace,
                '--component'   => $component,
                 '--admin'      => true,
            ]);
            $this->call('jjaj:repository', [
                'name'          => $repository_admin_namespace,
                '--component'   => $component,
                '--admin'       => true,
            ]);
            $this->call('jjaj:model', [
                'name'          => $model_admin_namespace,
                '--component'   => $component,
                '--admin'       => true,
                '--table'       => $name
            ]);

            $this->call('jjaj:request', [
                'name'          => $request_admin_namespace.'AdminStorePost',
                '--store'       => true,
                '--component'   => $component,
                '--admin'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_admin_namespace.'AdminRemovePost',
                '--remove'      => true,
                '--component'   => $component,
                '--admin'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_admin_namespace.'AdminStatePost',
                '--state'       => true,
                '--component'   => $component,
                '--admin'       => true
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_admin_namespace.'AdminSearchPost',
                '--search'       => true,
                '--component'   => $component,
                '--admin'       => true,
            ]);
            $this->call('jjaj:request', [
                'name'          => $request_admin_namespace.'AdminOrderingPost',
                '--ordering'       => true,
                '--component'   => $component,
                '--admin'       => true,
            ]);
        }

        if ($component) {
            $this->movePackage($component);
        }
    }


    public function movePackage($component)
    {
        if (!File::exists('packages')) {
            File::makeDirectory('packages');
        }

        File::copyDirectory('app/Http/Controllers/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Http/Requests/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/constants', 'packages/'.$component.'/constants');
        File::copyDirectory('database/migrations/'.$component, 'packages/'.$component.'/database/migrations');

        File::deleteDirectory('app/Http/Controllers/Daydreamlab/');
        File::deleteDirectory('app/Http/Requests/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/constants');
        File::deleteDirectory('database/migrations/'.$component);
    }

}
