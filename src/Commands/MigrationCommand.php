<?php

namespace DaydreamLab\JJAJ\Commands;


use DaydreamLab\JJAJ\Migration\BaseMigrationCreator;
use Faker\Provider\Base;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;

class MigrationCommand extends MigrateMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:migration {name : The name of the migration.}
        {--create= : The table to be created.}
        {--table= : The table to migrate.}
        {--path= : The location where the migration file should be created.}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create migration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BaseMigrationCreator $creator, Composer $composer)
    {
        parent::__construct($creator, $composer);
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();
    }
}
