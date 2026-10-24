<?php

namespace App\Livewire\Forms;

use App\Models\Banco;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BancoForm extends Form
{
    public ?Banco $banco = null;

    #[Validate('required|max:60')]
    public $nombre = '';

    public function setBanco(Banco $banco): void
    {
        $this->banco = $banco;
        $this->nombre = $banco->nombre;
    }

    public function update(): void
    {
        $this->validate();

        $this->banco->update($this->only('nombre'));
    }
}
