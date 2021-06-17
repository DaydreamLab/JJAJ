<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\JJAJ\Helpers\CommandHelper;
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


    protected $commandTypes = [
        'controller',
        'service',
        'repository',
        'model',
        'request'
    ];


    protected $requestTypes = [
        'featured',
        'featuredOrdering',
        'getItem',
        'ordering',
        'orderingNested',
        'restore',
        'remove',
        'search',
        'state',
        'store'
    ];


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->commandTypes = collect($this->commandTypes);
        $this->requestTypes = collect($this->requestTypes);
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

        $migrateParams = [
            'name'      => 'create_'.$table.'_table',
            '--create'  => $table
        ];

        # 產生component base
        if ($component) {
            $this->produceContent();
            $migrateParams['--path'] = 'database/migrations/'.$component;
        }

        if (!File::exists('database/migrations/'.$component)) {
            File::makeDirectory('database/migrations/'.$component);
        }
        # 產生 migration table
        $this->call('jjaj:migration', $migrateParams);

        # 產生 component

        if ($component) {
            $this->movePackage($component);
        } else {
            $this->moveLang();
        }
    }


    public function movePackage($component)
    {
        if (!File::exists('packages')) {
            File::makeDirectory('packages');
        }

        File::copyDirectory('app/Controllers/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Requests/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/Daydreamlab/'.$component, 'packages/'.$component);
        File::copyDirectory('app/constants', 'packages/'.$component.'/constants');
        File::copyDirectory('app/Resources/', 'packages/'.$component.'/Resources');
        File::copyDirectory('database/migrations/'.$component, 'packages/'.$component.'/database/migrations');

        File::deleteDirectory('app/Controllers/Daydreamlab/');
        File::deleteDirectory('app/Requests/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/Daydreamlab/');
        File::deleteDirectory('app/constants');
        File::deleteDirectory('app/Resources');
        File::deleteDirectory('database/migrations/'.$component);
    }


    public function moveLang()
    {
        File::copyDirectory('app/Resources/', 'Resources');
    }


    public function produceContent()
    {
        $modelName = $this->argument('name');
        $component = $this->option('component');
        $commandTypes = $this->commandTypes;
        $requestTypes = $this->requestTypes;
        foreach ($commandTypes as $commandType) {
            $commandPostfix = Str::ucfirst($commandType);
            if ($commandType == 'request') {
                foreach ($requestTypes as $requestType) {
                    $namespace ='DaydreamLab\\'.$component . '\\Requests\\';

                    # 產生 base class
                    $baseParams = [
                        'name' => $namespace . $component.Str::ucfirst($requestType).'Request',
                        '--component' => $component,
                        '--componentBase' => 1,
                        '--'.$requestType => 1,
                    ];
                    $this->call('jjaj:'.$commandType, $baseParams);


                    if (!$this->option('admin') && !$this->option('front')) {
                        # 產生 parent class
                        $parentParams = [
                            'name' => $namespace . $modelName . '\\' . $modelName.Str::ucfirst($requestType).'Request',
                            '--component' => $component,
                            '--'.$requestType => 1,
                        ];
                        $this->call('jjaj:'.$commandType, $parentParams);
                    } else {
                        # 產生 child class
                        if ($this->option('admin')) {
                            $childParams = [
                                'name' => $namespace . $modelName . '\\Admin\\' . $modelName . 'Admin' . Str::ucfirst($requestType) . 'Request',
                                '--component' => $component,
                                '--' . $requestType => 1,
                                '--admin' => 1
                            ];
                            $this->call('jjaj:' . $commandType, $childParams);
                        }

                        if ($this->option('front')) {
                            $childParams = [
                                'name' => $namespace . $modelName . '\\Front\\' . $modelName . 'Front' . Str::ucfirst($requestType) . 'Request',
                                '--component' => $component,
                                '--' . $requestType => 1,
                                '--front' => 1
                            ];
                            $this->call('jjaj:' . $commandType, $childParams);
                        }
                    }
                }
            } else {
                $namespace = 'DaydreamLab\\'.$component.'\\' . Str::ucfirst(Str::plural($commandType)).'\\';

                # 產生 base class
                $baseParams = [
                    'name' => $namespace . $component .$commandPostfix,
                    '--component' => $component,
                    '--componentBase' => 1
                ];
                $this->call('jjaj:'.$commandType, $baseParams);

                # 產生 parent class
                $parentParams =  [
                    'name' => $namespace . $modelName . '\\' . $modelName . ($commandType == 'model' ? '' : $commandPostfix),
                    '--component' => $component,
                ];
                $this->call('jjaj:'.$commandType, $parentParams);

                # 產生 child class
                if ($this->option('admin')) {
                    $adminParams = [
                        'name' => $namespace . $modelName . '\\Admin\\' . $modelName . 'Admin' . ($commandType == 'model' ? '' : $commandPostfix),
                        '--component' => $component,
                        '--admin' => 1
                    ];
                    $this->call('jjaj:'.$commandType, $adminParams);
                }

                if ($this->option('front')) {
                    $frontParams = [
                        'name' => $namespace . $modelName . '\\Front\\' . $modelName . 'Front' . ($commandType == 'model' ? '' : $commandPostfix),
                        '--component' => $component,
                        '--front' => 1
                    ];
                    $this->call('jjaj:'.$commandType, $frontParams);
                }
            }
        }
    }
}
