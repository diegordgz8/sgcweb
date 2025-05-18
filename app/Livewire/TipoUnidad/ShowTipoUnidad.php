<?php

namespace App\Livewire\TipoUnidad;

use App\Models\TipoUnidad;
use Livewire\Component;

class ShowTipoUnidad extends Component
{
    public TipoUnidad $tipoUnidad;

    public function render()
    {
        return view('livewire.tipo-unidad.show-tipo-unidad');
    }
}
