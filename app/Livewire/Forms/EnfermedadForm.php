<?php

namespace App\Livewire\Forms;

use App\Models\Enfermedad;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EnfermedadForm extends Form
{
    public ?Enfermedad $enfermedad = null;

    #[Validate('required|max:25')]
    public $nombre = '';

    #[Validate('max:255')]
    public $descripcion = '';

    public function setEnfermedad(Enfermedad $enfermedad): void
    {
        $this->enfermedad = $enfermedad;
        $this->nombre = $enfermedad->nombre;
        $this->descripcion = $enfermedad->descripcion;
    }

    public function update(): void
    {
        $this->validate();

        $this->enfermedad->update($this->only(['nombre', 'descripcion']));
    }
}
