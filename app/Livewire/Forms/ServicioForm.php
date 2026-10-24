<?php

namespace App\Livewire\Forms;

use App\Models\Servicio;
use Livewire\Form;

class ServicioForm extends Form
{
    public ?Servicio $servicio = null;

    public $nombre = '';

    public $descripcion = '';

    public $categoria_id = '';

    protected function rules()
    {
        return [
            'nombre' => [
                'required',
                'max:60',
                'unique:servicios,nombre,' . $this->servicio?->id . ',id,deleted_at,NULL',
            ],
            'descripcion' => 'nullable',
            'categoria_id' => 'required|not_in:0',
        ];
    }

    public function setServicio(Servicio $servicio): void
    {
        $this->servicio = $servicio;
        $this->nombre = $servicio->nombre;
        $this->descripcion = $servicio->descripcion;
        $this->categoria_id = $servicio->categoria_id;
    }

    public function update(): void
    {
        $this->validate();

        $this->servicio->update($this->only(['nombre', 'descripcion', 'categoria_id']));
    }
}
