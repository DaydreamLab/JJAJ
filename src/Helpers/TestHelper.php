<?php
namespace DaydreamLab\JJAJ\Helpers;

use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Laravel\Passport\PersonalAccessTokenResult;


class TestHelper
{
    use WithFaker;

    public function passportActingAs($user, $scopes = [], $guard = 'api', $random_member_id = false)
    {
        $token = Mockery::mock(PersonalAccessTokenResult::class)->shouldIgnoreMissing(false);
        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $token->member_id = $random_member_id == true ? $this->faker->randomNumber(2) : 0;

        $user->withAccessToken($token);

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
    }
}