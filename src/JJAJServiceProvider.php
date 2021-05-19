<?php

namespace DaydreamLab\JJAJ;

use Illuminate\Contracts\Debug\ExceptionHandler;
use DaydreamLab\JJAJ\Exceptions\BaseExceptionHandler;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class JJAJServiceProvider extends ServiceProvider
{
    protected $commands = [
        'DaydreamLab\JJAJ\Commands\ClearCommand',

        'DaydreamLab\JJAJ\Commands\ControllerCommand',
        'DaydreamLab\JJAJ\Commands\ConstantCommand',
        'DaydreamLab\JJAJ\Commands\DeleteCommand',
        'DaydreamLab\JJAJ\Commands\McCommand',
        'DaydreamLab\JJAJ\Commands\MigrationCommand',
        'DaydreamLab\JJAJ\Commands\ModelCommand',
        'DaydreamLab\JJAJ\Commands\RefreshCommand',
        'DaydreamLab\JJAJ\Commands\RepositoryCommand',
        'DaydreamLab\JJAJ\Commands\RequestCommand',
        'DaydreamLab\JJAJ\Commands\OptimizeCommand',
        'DaydreamLab\JJAJ\Commands\ServiceCommand',
        'DaydreamLab\JJAJ\Commands\TestCommand',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (File::exists(__DIR__ .'/helpers.php')) {
            require_once __DIR__ .'/helpers.php';
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'jjaj');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);

        $this->app->bind(
            ExceptionHandler::class,
            BaseExceptionHandler::class
        );
    }
}
