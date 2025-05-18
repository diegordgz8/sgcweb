<?php

namespace App\Livewire\Admin\Sancion;

use App\Models\Sancion;
use Livewire\Component;

class NuevaSancion extends Component
{

    public $descripcion, $monto,/*  $tasaCambio, */ $moneda = 'Bolívar';

    public $open = false;

    public $readyToLoad = false;

    public $rules = [
        'descripcion' => 'required',
        'monto' => 'required|numeric|gt:0',
        'moneda' => 'required',
    ];


    public function render()
    {
        return view('livewire.admin.sancion.nueva-sancion');
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }  

    function save()
    {
        $this->validate();

        Sancion::create([
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
        ]);

        $this->reset([
            'open',
            'descripcion',
            'monto',
            'moneda',
        ]);

        $this->dispatch('render')->to('admin.sancion.tabla-sancion');
        toastr()->livewire()->addSuccess('La sanción se registró satisfactoriamente');
    }
}
