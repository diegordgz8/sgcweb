<?php

namespace App\Livewire\TipoUnidad;

use App\Models\TipoUnidad;
use Livewire\Component;

class NuevoTipoUnidad extends Component
{
    public $abierto = false;

    public $nombre, $descripcion, $area;

    protected $rules = [
        'nombre' => 'required',
        'area' => 'required|numeric',
        'descripcion' => 'max:255',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        TipoUnidad::create([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'area' => $this->area,
        ]);

        $this->reset([
            'abierto',
            'nombre',
            'descripcion',
            'area',
        ]);

        $this->dispatch('render')->to('tipo-unidad.tabla-tipo-unidad');
        toastr()->livewire()->addSuccess('El registro se creó satisfactoriamente');
    }

    public function render()
    {
        return view('livewire.tipo-unidad.nuevo-tipo-unidad');
    }
}
