<?php

namespace App\Livewire\Admin\Sancion;

use App\Models\Moneda;
use App\Models\Sancion;
use Illuminate\Validation\Rule;
use Livewire\Component;

class NuevaSancion extends Component
{

    public $descripcion, $monto, $moneda;

    public $open = false;

    public $readyToLoad = false;

    protected function rules()
    {
        return [
            'descripcion' => 'required',
            'monto' => 'required|numeric|gt:0',
            'moneda' => ['required', Rule::in(Moneda::codigos())],
        ];
    }

    public function mount()
    {
        $this->moneda = Moneda::base();
    }

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
        ]);

        $this->moneda = Moneda::base();

        $this->dispatch('render')->to('admin.sancion.tabla-sancion');
        flash()->success('La sanción se registró satisfactoriamente');
    }
}
