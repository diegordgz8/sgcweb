<?php

namespace App\Livewire\Forms;

use App\Models\Administrador;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AdministradorForm extends Form
{
    public ?Administrador $administrador = null;

    #[Validate('required')]
    public $rol = '';

    public function setAdministrador(Administrador $administrador): void
    {
        $this->administrador = $administrador;
        $this->rol = $administrador->rol;
    }

    public function update(): void
    {
        $this->validate();

        $this->administrador->update($this->only('rol'));
    }
}
