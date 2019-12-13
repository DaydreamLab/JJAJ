<?php

namespace DaydreamLab\JJAJ\Tests;

use DaydreamLab\JJAJ\Helpers\Helper;
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


    public function getContent($response)
    {
        return json_decode($response->content(), true);
    }

    public function getContentStatus($response)
    {
        return $this->getContent($response)['status'];
    }

    public function getContentData($response)
    {
        return $this->getContent($response)['data']['items'];
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

}