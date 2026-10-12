<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Tests refresh the database, so refuse to run against anything but in-memory SQLite
        // (e.g. when a cached config ignores the overrides in phpunit.xml).
        $conexion = $app['config']->get('database.default');
        $base = $app['config']->get("database.connections.$conexion.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException("Tests must run on in-memory SQLite, got [$conexion: $base]. Run php artisan config:clear.");
        }

        return $app;
    }
}
