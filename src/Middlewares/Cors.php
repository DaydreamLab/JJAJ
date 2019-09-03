<?php

namespace DaydreamLab\JJAJ\Middlewares;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//        if($request->getMethod() == 'OPTIONS'){
//
//            header('Access-Control-Allow-Origin: *');
//            header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
//            header('Access-Control-Allow-Headers: Origin, Access-Control-Request-Headers, SERVER_NAME, Access-Control-Allow-Headers, cache-control, token, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie, X-XSRF-TOKEN');
//            header('Access-Control-Allow-Credentials: true');
//        }
//
//        return $next($request);
        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', true);
        return $response;


    }
}
