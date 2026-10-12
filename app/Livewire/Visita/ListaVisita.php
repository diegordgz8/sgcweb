<?php

namespace App\Livewire\Visita;

use App\Models\Visita;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ListaVisita extends Component
{
    public $busqueda;
    #[Locked]
    public $orden = 'fecha_hora_entrada';
    #[Locked]
    public $direccion = 'asc';
    public $cantidad = '10';

    public $fechaDesde;
    public $fechaHasta;

    public $readyToLoad = false;

    protected $listeners = ['render'];

    public function render()
    {
        $visitas = $this->filtrar();

        if ($visitas->count()) {
            $visitas = $visitas->toQuery()
                ->where(function ($query) {
                    $query->where('nombre', 'LIKE', '%' . $this->busqueda . '%')
                        ->orWhere('apellido', 'LIKE', '%' . $this->busqueda . '%');
                })
                ->orderBy($this->orden, $this->direccion)
                ->paginate($this->cantidad);
        } else {
            $visitas = [];
        }

        return view('livewire.visita.lista-visita', compact('visitas'));
    }

    public function orden($orden)
    {
        if (! in_array($orden, ['apellido', 'ci', 'fecha_hora_entrada', 'fecha_hora_salida', 'nombre'], true)) {
            return;
        }

        if ($this->orden == $orden) {
            if ($this->direccion == 'desc') {
                $this->direccion = 'asc';
            } else {
                $this->direccion = 'desc';
            }
        } else {
            $this->orden = $orden;
            $this->direccion = 'asc';
        }
    }

    private function filtrar()
    {
        $visitas = Visita::all();

        if ($this->fechaDesde) {
            $visitas = $visitas->intersect(Visita::where('fecha_hora_entrada', '>', $this->fechaDesde)->get());
        }

        if ($this->fechaHasta) {
            $visitas = $visitas->intersect(Visita::where('fecha_hora_salida', '<', $this->fechaHasta)->get());
        }

        return $visitas;
    }
}
