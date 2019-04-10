<?php

namespace DaydreamLab\JJAJ\Tests;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Mockery;

class BaseTest extends TestCase
{

    protected function setUp() : void
    {
        parent::setUp();
        //Artisan::call('jjaj:refresh');
    }

    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
}