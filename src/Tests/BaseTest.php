<?php

namespace DaydreamLab\JJAJ\Tests;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class BaseTest extends TestCase
{
    use WithFaker;

    protected $package;

    protected $modelName;

    protected $modelType = 'Base';

    protected function setUp() :void
    {
        parent::setUp();
        //Artisan::call('jjaj:refresh');
    }

    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }


    /**
     * @param string $funcName
     * @param mixed $input
     * @param string $expect
     */
    public function assertException($funcName, $input, $expect)
    {
        $status  = null;
        try {
            $this->service->{$funcName}($input);
        } catch (\Throwable $t) {
            $status = $t->status;
        }

        $this->assertEquals($expect, $status);
    }


    public function getContent($response)
    {
        return json_decode($response->content(), true);
    }


    public function getContentData($response)
    {
        return $this->getContent($response)['data']['items'];
    }


    public function getContentStatus($response)
    {
        return $this->getContent($response)['status'];
    }


    public function getContentCode($response)
    {
        return json_decode($response->content(), true)['code'];
    }

    public function showContent($response)
    {
        Helper::show($this->getContent($response));
    }


    public function showContentData($response)
    {
        Helper::show($this->getContentData($response));
    }

    public function showContentStatus($response)
    {
        Helper::show($this->getContentStatus($response));
    }

    public function upperSnake($string)
    {
        return Str::upper(Str::snake($string));
    }

}
