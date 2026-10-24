<?php

namespace App\Livewire\Forms;

use App\Models\Categoria;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CategoriaForm extends Form
{
    public ?Categoria $categoria = null;

    #[Validate('required|max:25')]
    public $nombre = '';

    #[Validate('max:255')]
    public $descripcion = '';

    public function setCategoria(Categoria $categoria): void
    {
        $this->categoria = $categoria;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion;
    }

    public function update(): void
    {
        $this->validate();

        $this->categoria->update($this->only(['nombre', 'descripcion']));
    }
}
