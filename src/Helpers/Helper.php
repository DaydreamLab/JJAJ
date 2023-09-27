<?php

namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Helper
{
    public static function show($data)
    {
        $args = func_get_args();
        // Print Multiple values
        if (count($args) > 1) {
            $prints = array();
            $i = 1;
            foreach ($args as $arg) {
                $prints[] = "[Value " . $i . "]\n" . print_r($arg, 1);
                $i++;
            }
            echo '<pre>' . implode("\n\n", $prints) . '</pre>';
        } else {
            // Print one value.
            echo '<pre>' . print_r($data, 1) . '</pre>';
        }
    }


    public static function collect($data)
    {
        $collect = new Collection($data);
        $collect->each(function ($item, $key) use ($collect) {
            $collect->{$key} = $item;
        });
        return $collect;
    }


    public static function setEnv($data = [])
    {
        if (!count($data)) {
            return;
        }

        $pattern = '/([^\=]*)\=[^\n]*/';

        $envFile = base_path() . '/.env';
        $lines = file($envFile);
        $newLines = [];
        $line_counter = 0;
        foreach ($lines as $line) {
            preg_match($pattern, $line, $matches);

            if (!count($matches)) {
                $newLines[] = $line;
                $line_counter++;
                continue;
            }

            if (!key_exists(trim($matches[1]), $data)) {
                $newLines[] = $line;
                $line_counter++;
                continue;
            }

            $line = trim($matches[1]) . "={$data[trim($matches[1])]}\n";
            unset($data[trim($matches[1])]);
            $newLines[] = $line;
        }

        // 處理新增 key
        foreach ($data as $key => $value) {
            $newLines[] = trim($key) . "={$value}\n";
        }

        $newContent = implode('', $newLines);
        file_put_contents($envFile, $newContent);
    }


    public static function flushLog()
    {
        DB::connection()->flushQueryLog();
    }


    public static function getJson($path, $assoc = true)
    {
        return json_decode(file_get_contents($path), $assoc);
    }


    public static function generateRandomIntegetString($length = 6)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public static function getUserToken($guard = 'api')
    {
        $user = auth()->guard($guard)->user();

        return $user ? $user->token() : null;
    }


    public static function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $queryString = Paginator::resolveQueryString();

        # 取出幾筆成一分頁
        if (isset($queryString['limit'])) {
            $perPage = $queryString['limit'] ?: $perPage;
        }

        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginate = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            $options
        );

        if (count($options)) {
            $url = url()->current() . '?';
            $counter = 0;
            foreach ($options as $key => $option) {
                $url .= $key . '=' . $option;
                $counter++;
                $counter != count($options) ? $url .= '&' : true;
            }
            $paginate = $paginate->setPath($url);
        } else {
            $paginate = $paginate->setPath(url()->current());
        }

        return $paginate;
    }


    public static function startLog()
    {
        DB::connection()->enableQueryLog();
    }


    public static function showLog()
    {
        self::show(DB::getQueryLog());
    }


    public static function showLogCount()
    {
        self::show(count(DB::getQueryLog()));
    }


    public static function stopLog()
    {
        DB::connection()->disableQueryLog();
    }



    public static function exportXlsx($headers, $data, $filename)
    {
        $spreedsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreedsheet->getActiveSheet();
        $sizeOfHeader = count($headers);
        $startColumn = 'A';
        for ($i = 0; $i < $sizeOfHeader; $i++) {
            $spreedsheet->getActiveSheet()->getColumnDimension($startColumn++)->setAutoSize(true);
        }

        $h = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($h, 1, $header);
            $h += 1;
        }

        $r = 2;
        foreach ($data as $item) {
            for ($i = 0; $i < count($headers); $i++) {
                $sheet->setCellValueExplicitByColumnAndRow(($i + 1), $r, $item[$i], 's');
            }
            $r++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreedsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        $writer->save(public_path($filename));
    }


    public static function recursiveMap($collection, $callback)
    {
        return $collection->map(function ($item) use ($callback) {
            if ($item->children->count()) {
                $item = $callback($item);
                $item['children'] = self::recursiveMap($item['children'], $callback);
                return $item;
            } else {
                return $callback($item);
            }
        });
    }
}
