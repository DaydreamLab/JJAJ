<?php

namespace DaydreamLab\JJAJ\Tests;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Mockery;

class BaseTest extends TestCase
{

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

    protected function getContentData($response)
    {
        $content = $response->getContent();
        return json_decode($content, true)['data']['items'];
    }

    protected function getStatusCode($response)
    {
        $content = $response->getContent();
        return json_decode($content, true)['code'];
    }

    protected function getToken($response)
    {
        $items = $this->getContentData($response);
        return $items['token'];
    }
}
