<?php

namespace DaydreamLab\JJAJ\Middlewares;

use Closure;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;

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
        $ips = [$request->ip()];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_merge($ips, [$_SERVER['HTTP_X_FORWARDED_FOR']]);
        }

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ips = array_merge($ips, [$_SERVER['HTTP_CF_CONNECTING_IP']]);
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ips = array_merge($ips, [$_SERVER['REMOTE_ADDR']]);
        }

        $whitelist = config('app.ip.' . $category . '.whitelist') ?: [];
        if (in_array(config('app.env'), ['staging', 'production']) && !collect($ips)->intersect($whitelist)->count()) {
            return $route == 'api'
                ? ResponseHelper::genResponse('IP_REJECTED', [], 'User', 'User', [])
                : redirect('/');
        }

        $blacklist = config('app.ip.' . $category . '.blacklist') ?: [];
        if (in_array(config('app.env'), ['staging', 'production']) && collect($ips)->intersect($blacklist)->count()) {
            return $route == 'api'
                ? ResponseHelper::genResponse('IP_REJECTED', [], 'User', 'User', [])
                : redirect('/');
        }

        return $next($request);
    }
}
