<?php

namespace DaydreamLab\JJAJ\Commands;

use Illuminate\Console\Command;

class MysqlDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:mysql-dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mysql dump';


    public function handle()
    {
        $ds = DIRECTORY_SEPARATOR;

        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        $ts = time();

        $path = database_path() . $ds . 'backups' . $ds . date('Y', $ts) . $ds . date('m', $ts) . $ds . date('d', $ts) . $ds;
        $file = date('Y-m-d-His', $ts) . '-dump-' . $database . '.sql';
        $command = sprintf('mysqldump -h %s -u %s -p\'%s\' %s > %s', $host, $username, $password, $database, $path . $file);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        exec($command);
    }
}
