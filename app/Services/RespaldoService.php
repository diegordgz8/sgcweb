<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

/**
 * Database backups made by spatie/laravel-backup (a zip with db-dumps/mysql-<db>.sql)
 * and their restore into MySQL.
 */
class RespaldoService
{
	/**
	 * Zip file names of the existing backups, newest first.
	 */
	public function disponibles(): array
	{
		return collect(Storage::disk('backups')->files($this->carpeta()))
			->filter(fn ($archivo) => str_ends_with($archivo, '.zip'))
			->map(fn ($archivo) => basename($archivo))
			->sortDesc()
			->values()
			->all();
	}

	public function crear(): void
	{
		if (Artisan::call('backup:run', ['--only-db' => true]) !== 0) {
			throw new RuntimeException('No se pudo crear el respaldo: ' . trim(Artisan::output()));
		}
	}

	/**
	 * Restores a backup over the current database. The dump drops and recreates each table it
	 * contains. A fresh backup is taken first, so the restore itself can be undone.
	 */
	public function restaurar(string $archivo): void
	{
		if (! in_array($archivo, $this->disponibles(), true)) {
			throw new InvalidArgumentException("El respaldo $archivo no existe.");
		}

		$conexion = config('database.connections.' . $this->conexion());

		if (($conexion['driver'] ?? null) !== 'mysql') {
			throw new RuntimeException('La restauración solo está disponible para MySQL.');
		}

		$temporal = storage_path('app/backup-temp/restaurar-' . Str::random(8));
		File::ensureDirectoryExists($temporal);

		try {
			$volcado = $this->extraerVolcado($archivo, $temporal);

			$this->crear();

			$entrada = fopen($volcado, 'r');

			try {
				Process::env(['MYSQL_PWD' => (string) $conexion['password']])
					->timeout(900)
					->input($entrada)
					->run([
						$this->binario($conexion, 'mysql'),
						'--host=' . $conexion['host'],
						'--port=' . $conexion['port'],
						'--user=' . $conexion['username'],
						'--default-character-set=utf8mb4',
						$conexion['database'],
					])
					->throw();
			} finally {
				fclose($entrada);
			}
		} finally {
			File::deleteDirectory($temporal);
		}
	}

	private function extraerVolcado(string $archivo, string $destino): string
	{
		$zip = new ZipArchive;

		if ($zip->open(Storage::disk('backups')->path($this->carpeta() . '/' . $archivo)) !== true) {
			throw new RuntimeException("No se pudo abrir el respaldo $archivo.");
		}

		try {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				// Zips created on Windows store paths with backslashes
				$nombre = str_replace('\\', '/', $zip->getNameIndex($i));

				if (preg_match('#^db-dumps/[^/]+\.sql$#', $nombre)) {
					$volcado = $destino . '/' . basename($nombre);
					file_put_contents($volcado, $zip->getStream($zip->getNameIndex($i)));

					if (! str_contains((string) file_get_contents($volcado), 'CREATE TABLE')) {
						throw new RuntimeException("El respaldo $archivo no contiene un volcado de MySQL válido.");
					}

					return $volcado;
				}
			}
		} finally {
			$zip->close();
		}

		throw new RuntimeException("El respaldo $archivo no contiene un volcado de la base de datos.");
	}

	private function binario(array $conexion, string $nombre): string
	{
		$ruta = rtrim($conexion['dump']['dump_binary_path'] ?? '', '/\\');

		return $ruta === '' ? $nombre : $ruta . DIRECTORY_SEPARATOR . $nombre;
	}

	private function conexion(): string
	{
		return config('backup.backup.source.databases')[0];
	}

	private function carpeta(): string
	{
		return config('backup.backup.name');
	}
}
