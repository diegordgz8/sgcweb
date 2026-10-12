<?php

namespace App\Livewire\Admin\Database;

use App\Services\RespaldoService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

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
            'zippedBackup' => ['required', Rule::in(app(RespaldoService::class)->disponibles())],
        ];
    }

    public function render(RespaldoService $respaldos)
    {
        $files = $respaldos->disponibles();

        return view('livewire.admin.database.backup', compact('files'));
    }

    public function updatedZippedBackup()
    {
        $this->validateOnly('zippedBackup');
    }

    public function respaldar(RespaldoService $respaldos)
    {
        $this->authorize('admin.database.backup');

        try {
            $respaldos->crear();
        } catch (Throwable $e) {
            report($e);
            flash()->error('No se pudo crear el respaldo de la base de datos.');

            return;
        }

        flash()->success('Se ha creado el respaldo de la base de datos satisfactoriamente');
    }

    public function restaurar(RespaldoService $respaldos)
    {
        $this->authorize('admin.database.restore');

        $this->validate();

        try {
            $respaldos->restaurar($this->zippedBackup);
        } catch (Throwable $e) {
            report($e);
            flash()->error('No se pudo restaurar la base de datos: ' . $e->getMessage());

            return;
        }

        $this->reset('openRestaurar');
        flash()->success('Se ha restaurado la base de datos satisfactoriamente');
    }
}
