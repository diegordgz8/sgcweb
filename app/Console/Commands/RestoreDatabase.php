<?php

namespace App\Console\Commands;

use App\Services\RespaldoService;
use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {respaldo? : Zip file name, as listed by backup:list} {--force : Skip the confirmation}';

    protected $description = 'Restore the database from a backup made by backup:run';

    public function handle(RespaldoService $respaldos): int
    {
        $archivo = $this->argument('respaldo') ?? $this->choice('Respaldo a restaurar', $respaldos->disponibles());

        if (! $this->option('force') && ! $this->confirm("Se reemplazará la base de datos con $archivo. ¿Continuar?")) {
            return self::FAILURE;
        }

        $respaldos->restaurar($archivo);

        $this->info("Base de datos restaurada desde $archivo.");

        return self::SUCCESS;
    }
}
