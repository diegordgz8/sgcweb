<?php

namespace App\Livewire\Forms;

use App\Models\Medicamento;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MedicamentoForm extends Form
{
    public ?Medicamento $medicamento = null;

    #[Validate('required|max:25')]
    public $nombre = '';

    #[Validate('max:255')]
    public $descripcion = '';

    public function setMedicamento(Medicamento $medicamento): void
    {
        $this->medicamento = $medicamento;
        $this->nombre = $medicamento->nombre;
        $this->descripcion = $medicamento->descripcion;
    }

    public function update(): void
    {
        $this->validate();

        $this->medicamento->update($this->only(['nombre', 'descripcion']));
    }
}
