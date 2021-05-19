<?php

namespace DaydreamLab\JJAJ\Exceptions;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\ResponseHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Str;
use Throwable;

class BaseExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        return parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
//        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
//            return ResponseHelper::response('Unauthorized', null);
//        } else if($exception instanceof \League\Flysystem\FileNotFoundException) {
//            return ResponseHelper::response('FILE_NOT_FOUND', null);
//        } else if($exception instanceof \Intervention\Image\Exception\NotWritableException) {
//            return ResponseHelper::response('FILE_PATH_CANT_BE_WRITE', null);
//        } else if($exception instanceof \League\Flysystem\RootViolationException) {
//            return ResponseHelper::response('FILE_ROOT_CANT_BE_DELETE', null);
//        } elseif ($exception instanceof AuthorizationException) {
//            return ResponseHelper::genResponse(
//                Str::upper(Str::snake('ApiAccessDeny')),
//                null,
//                '',
//                ''
//            );
//        }

        return parent::render($request, $exception);
    }
}
