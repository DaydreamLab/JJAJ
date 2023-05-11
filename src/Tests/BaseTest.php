<?php

namespace DaydreamLab\JJAJ\Tests;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class BaseTest extends TestCase
{
    public function assertHttpResponseException($funcName, $funcParams = [], $expect)
    {
        try {
            $this->service->{$funcName}(...$funcParams);
        } catch (HttpResponseException $e) {
            $content = $this->getContent($e->getResponse());
            $this->assertEquals(Str::upper(Str::snake($expect)), $content['status']);
        }
    }

    public function getContent($response)
    {
        return json_decode($response->content(), true);
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

    public function getContentData($response)
    {
        return $this->getContent($response)['data']['items'];
    }

    public function showContentStatus($response)
    {
        Helper::show($this->getContentStatus($response));
    }

    public function getContentStatus($response)
    {
        return $this->getContent($response)['status'];
    }

    public function upperSnake($string)
    {
        return Str::upper(Str::snake($string));
    }

    protected function setUp(): void
    {
        parent::setUp();
        //Artisan::call('jjaj:refresh');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
