<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // `then` instead of `using`: Laravel ignores `health` when `using` is given
        then: function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware(['web', 'auth:sanctum', 'verified'])
                ->group(base_path('routes/web.php'));

            Route::prefix('admin')
                ->middleware(['web', 'auth'])
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::prefix('reportes')
                ->middleware(['web', 'auth'])
                ->group(base_path('routes/reportes.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [AuthenticateSession::class]);
        $middleware->api(prepend: ['throttle:api']);

        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
