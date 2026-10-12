<?php

namespace App\Livewire\Admin\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use ZipArchive;

class Backup extends Component
{
    public $zippedBackup = '0';
    public bool $creandoBackup = false;

    public $openRestaurar = false;

    protected $messages = [
        'zippedBackup.required' => 'Debe seleccionar un archivo.',
        'zippedBackup.in' => 'Debe seleccionar un archivo.',
    ];

    protected function rules()
    {
        // Only backups that exist in the backups folder, never an arbitrary path
        return [
            'zippedBackup' => ['required', Rule::in($this->respaldosDisponibles())],
        ];
    }

    public function render()
    {
        $files = $this->respaldosDisponibles();

        return view('livewire.admin.database.backup', compact('files'));
    }

    public function updatedZippedBackup()
    {
        $this->validateOnly('zippedBackup');
    }

    public function respaldar()
    {
        $this->authorize('admin.database.backup');

        $this->creandoBackup = true;
        Artisan::call('backup:run --only-db');

        flash()->success('Se ha creado el respaldo de la base de datos satisfactoriamente');
        $this->creandoBackup = false;
    }

    public function restaurar()
    {
        $this->authorize('admin.database.restore');

        $this->validate();

        $carpeta = Storage::disk('backups')->path($this->carpetaRespaldos());

        $zip = new ZipArchive;
        $zip->open($carpeta . DIRECTORY_SEPARATOR . $this->zippedBackup);
        $zip->extractTo($carpeta);
        $zip->close();

        Artisan::call('db:restore', ['--path' => $carpeta . '/db-dumps/postgresql-sgcweb.backup']);

        Storage::disk('backups')->deleteDirectory($this->carpetaRespaldos() . '/db-dumps');

        $this->reset('openRestaurar');
        flash()->success('Se ha restaurado la base de datos satisfactoriamente');
    }

    /**
     * Zip file names (without folder) of the backups created by spatie/laravel-backup.
     */
    private function respaldosDisponibles(): array
    {
        return collect(Storage::disk('backups')->files($this->carpetaRespaldos()))
            ->filter(fn ($archivo) => str_ends_with($archivo, '.zip'))
            ->map(fn ($archivo) => basename($archivo))
            ->values()
            ->all();
    }

    private function carpetaRespaldos(): string
    {
        return config('backup.backup.name');
    }
}
