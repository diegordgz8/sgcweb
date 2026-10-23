<?php

namespace App\Livewire\Forms;

use App\Models\Integrante;
use Livewire\Attributes\Validate;
use Livewire\Form;

class IntegranteForm extends Form
{
    public ?Integrante $integrante = null;

    #[Validate('required|max:20')]
    public $nombre = '';

    public function setIntegrante(Integrante $integrante): void
    {
        $this->integrante = $integrante;
        $this->nombre = $integrante->nombre;
    }

    public function update(): void
    {
        $this->validate();

        $this->integrante->update($this->only('nombre'));
    }
}
