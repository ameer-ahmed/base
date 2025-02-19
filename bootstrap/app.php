<?php

use App\Http\Helpers\Http;
use App\Http\Helpers\Response;
use App\Http\Middleware\Localize;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            Route::group(['prefix' => 'api', 'middleware' => ['api', 'localize']], function () {
                Route::prefix('v1')->group(function () {
                    Route::prefix('website')->group(base_path('routes/api/v1/website.php'));
                    Route::prefix('mobile')->group(base_path('routes/api/v1/mobile.php'));
                    Route::prefix('dashboard')->group(base_path('routes/api/v1/dashboard.php'));
                });
            });

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->prefix('dashboard')
                ->group(base_path('routes/dashboard.php'));
        },
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'localize' => Localize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return Response::fail(status: $e->getStatusCode(), message: __('messages.No data found'));
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof TokenExpiredException) {
                return Response::fail(status: Http::UNAUTHORIZED, message: 'Token expired');
            }

            if ($e instanceof TokenBlacklistedException) {
                return Response::fail(status: Http::UNAUTHORIZED, message: 'Token blacklisted');
            }

            if ($e instanceof TokenInvalidException) {
                return Response::fail(status: Http::UNAUTHORIZED, message: 'Token invalid');
            }

            if ($e instanceof JWTException) {
                return Response::fail(status: Http::UNAUTHORIZED, message: 'JWT error');
            }

            if ($e instanceof AuthenticationException) {
                if ($request->expectsJson()) {
                    return Response::fail(status: Http::UNAUTHORIZED, message: 'Unauthenticated');
                } else {
                    return redirect()->route('auth.login');
                }
            }

            if ($e instanceof ValidationException) {
                $errors = $e->validator->errors()->messages();
                if ($request->acceptsHtml() && collect($request->route()->middleware())->contains('web')) {
                    return $request->ajax()
                        ? Response::fail(message: 'Validation error', data: $errors)
                        : redirect()->back()->withInput($request->validated())->withErrors($errors);
                }

                return Response::fail(message: 'Validation error', data: $errors);
            }
        });
    })->create();
