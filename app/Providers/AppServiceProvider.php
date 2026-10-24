<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Under `php artisan serve` on Windows, Symfony Process builds child environments from
        // the keys present in $_SERVER, which lacks the system variables: mysqldump/mysql then
        // start without SystemRoot and fail with "Can't create TCP/IP socket (10106)".
        if (PHP_OS_FAMILY === 'Windows' && PHP_SAPI === 'cli-server') {
            foreach (['SYSTEMROOT', 'WINDIR', 'TEMP', 'TMP', 'PATH'] as $variable) {
                if (! isset($_SERVER[$variable]) && getenv($variable) !== false) {
                    $_SERVER[$variable] = getenv($variable);
                }
            }
        }

        Route::resourceVerbs([
            'create' => 'crear',
            'edit' => 'modificar',
        ]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
