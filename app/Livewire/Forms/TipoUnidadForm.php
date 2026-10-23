<?php

namespace App\Livewire\Forms;

use App\Models\TipoUnidad;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TipoUnidadForm extends Form
{
    public ?TipoUnidad $tipoUnidad = null;

    #[Validate('required')]
    public $nombre = '';

    #[Validate('required|numeric')]
    public $area = '';

    #[Validate('max:255')]
    public $descripcion = '';

    public function setTipoUnidad(TipoUnidad $tipoUnidad): void
    {
        $this->tipoUnidad = $tipoUnidad;
        $this->nombre = $tipoUnidad->nombre;
        $this->area = $tipoUnidad->area;
        $this->descripcion = $tipoUnidad->descripcion;
    }

    public function update(): void
    {
        $this->validate();

        $this->tipoUnidad->update($this->only(['nombre', 'area', 'descripcion']));
    }
}
