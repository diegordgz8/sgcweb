<?php

namespace App\Livewire\Forms;

use App\Models\Fondo;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FondoForm extends Form
{
    public ?Fondo $fondo = null;

    #[Validate('required|max:255')]
    public $descripcion = '';

    #[Validate('required')]
    public $moneda = '';

    public function setFondo(Fondo $fondo): void
    {
        $this->fondo = $fondo;
        $this->descripcion = $fondo->descripcion;
        $this->moneda = $fondo->moneda;
    }

    public function update(): void
    {
        $this->validate();

        $this->fondo->update($this->only(['descripcion', 'moneda']));
    }
}
