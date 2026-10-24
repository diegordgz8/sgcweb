<?php

namespace Tests\Feature;

use App\Livewire\Admin\Database\Backup;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class RespaldoTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->administrador = User::factory()->create()->assignRole('Administrador');

        // Restore always targets the MySQL connection; the test database is SQLite
        config(['backup.backup.source.databases' => ['mysql']]);
        Storage::fake('backups');
        Process::fake();
    }

    public function test_backup_list_shows_existing_backups_newest_first(): void
    {
        $this->crearRespaldo('2026-09-01-00-00-00.zip');
        $this->crearRespaldo('2026-10-01-00-00-00.zip');

        Livewire::actingAs($this->administrador)->test(Backup::class)
            ->assertViewHas('files', ['2026-10-01-00-00-00.zip', '2026-09-01-00-00-00.zip']);
    }

    public function test_restore_takes_a_backup_first_and_imports_the_dump_into_mysql(): void
    {
        $this->crearRespaldo('2026-10-01-00-00-00.zip');
        Artisan::shouldReceive('call')->once()->with('backup:run', ['--only-db' => true])->andReturn(0);

        Livewire::actingAs($this->administrador)->test(Backup::class)
            ->set('zippedBackup', '2026-10-01-00-00-00.zip')
            ->call('restaurar')
            ->assertHasNoErrors();

        Process::assertRan(function (PendingProcess $proceso) {
            // The binary may come from DB_DUMP_BINARY_PATH, so compare only its name
            return pathinfo($proceso->command[0], PATHINFO_FILENAME) === 'mysql'
                && end($proceso->command) === config('database.connections.mysql.database');
        });
        $this->assertEmpty(glob(storage_path('app/backup-temp/restaurar-*')));
    }

    public function test_restore_rejects_a_backup_without_a_mysql_dump(): void
    {
        $this->crearRespaldo('2026-10-01-00-00-00.zip', 'esto no es un volcado');
        Artisan::shouldReceive('call')->never();

        Livewire::actingAs($this->administrador)->test(Backup::class)
            ->set('zippedBackup', '2026-10-01-00-00-00.zip')
            ->call('restaurar');

        Process::assertNothingRan();
    }

    public function test_restore_only_accepts_existing_backup_files(): void
    {
        $this->crearRespaldo('2026-10-01-00-00-00.zip');

        Livewire::actingAs($this->administrador)->test(Backup::class)
            ->set('zippedBackup', '../../../.env')
            ->call('restaurar')
            ->assertHasErrors('zippedBackup');

        Process::assertNothingRan();
    }

    private function crearRespaldo(string $nombre, string $volcado = "DROP TABLE IF EXISTS `bancos`;\nCREATE TABLE `bancos` (`id` int);\n"): void
    {
        $ruta = Storage::disk('backups')->path(config('backup.backup.name') . '/' . $nombre);
        File::ensureDirectoryExists(dirname($ruta));

        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::CREATE);
        $zip->addFromString('db-dumps/mysql-sgcweb.sql', $volcado);
        $zip->close();
    }
}
