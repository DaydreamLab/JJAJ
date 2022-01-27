<?php

namespace DaydreamLab\JJAJ\Middlewares;

use Closure;
use DaydreamLab\JJAJ\Exceptions\ForbiddenException;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;

class RestrictIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $category, $route = 'api')
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $request->ip();
        }

        $whitelist = config('app.ip.' . $category . '.whitelist') ?: [];
        if (in_array(config('app.env'), ['staging', 'production']) && !in_array($ip, $whitelist)) {
            return $route == 'api'
                ? ResponseHelper::genResponse('IP_REJECTED', [], 'User', 'User', [])
                : redirect('/');
        }

        $blacklist = config('app.ip.' . $category . '.blacklist') ?: [];
        if (in_array(config('app.env'), ['staging', 'production']) && in_array($ip, $blacklist)) {
            return $route == 'api'
                ? ResponseHelper::genResponse('IP_REJECTED', [], 'User', 'User', [])
                : redirect('/');
        }

        return $next($request);
    }
}
