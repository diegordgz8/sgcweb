<?php

namespace App\Livewire\Forms;

use App\Models\Comunicado;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ComunicadoForm extends Form
{
    public ?Comunicado $comunicado = null;

    #[Validate('required|max:45')]
    public $asunto = '';

    #[Validate('required')]
    public $contenido = '';

    public function setComunicado(Comunicado $comunicado): void
    {
        $this->comunicado = $comunicado;
        $this->asunto = $comunicado->asunto;
        $this->contenido = $comunicado->contenido;
    }

    public function update(): void
    {
        $this->validate();

        $this->comunicado->update($this->only(['asunto', 'contenido']));
    }
}
