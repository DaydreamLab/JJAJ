<?php

namespace DaydreamLab\JJAJ;

use Illuminate\Support\ServiceProvider;

class JJAJServiceProvider extends ServiceProvider
{
    protected $commands = [
        'DaydreamLab\JJAJ\Commands\ControllerCommand',
        'DaydreamLab\JJAJ\Commands\ModelCommand',
        'DaydreamLab\JJAJ\Commands\RepositoryCommand',
        'DaydreamLab\JJAJ\Commands\ServiceCommand',
        'DaydreamLab\JJAJ\Commands\McCommand',
        'DaydreamLab\JJAJ\Commands\RequestCommand',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
