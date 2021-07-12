<?php

namespace DaydreamLab\JJAJ\Commands;

use DaydreamLab\Cms\Models\Tag\Tag;
use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\User\Models\Asset\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Csv2JsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jjaj:csv2json {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install DaydreamLab cms component';



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
        if (($handle = fopen(__DIR__.'/' . $this->argument('data.csv'), "r")) !== FALSE) {
            $csvs = [];
            while(! feof($handle)) {
                $csvs[] = fgetcsv($handle);
            }
            $datas = [];
            $column_names = [];
            foreach ($csvs[0] as $single_csv) {
                $column_names[] = $single_csv;
            }
            foreach ($csvs as $key => $csv) {
                if ($key === 0) {
                    continue;
                }
                foreach ($column_names as $column_key => $column_name) {
                    $datas[$key-1][$column_name] = $csv[$column_key];
                }
            }

            fclose($handle);
            Storage::disk('public')->put('data.json', response()->json($datas));
        }
    }


    public function deleteResources()
    {
    }
}
