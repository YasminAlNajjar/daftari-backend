<?php

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        | Authentication Errors
        */
        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    'جلسة الدخول غير صالحة. يرجى تسجيل الدخول مجددًا.',
                    'UNAUTHENTICATED',
                    401
                );
            }

            return null;
        });

        /*
        | Validation Errors
        */
        $exceptions->render(function (
            ValidationException $exception,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    'البيانات المدخلة غير صالحة.',
                    'VALIDATION_ERROR',
                    422,
                    $exception->errors()
                );
            }

            return null;
        });

        $exceptions->render(function (
            NotFoundHttpException $exception,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    'المسار المطلوب غير موجود أو المعرّف غير صالح.',
                    'ROUTE_NOT_FOUND',
                    404
                );
            }

            return null;
        });

    })
    ->create();