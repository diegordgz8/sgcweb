<?php

namespace App\View\Components;

use App\Models\Moneda;
use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectMoneda extends Component
{
    public $monedas;
    public $seleccionado;
    public $visibles;
    public $modelo;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $visibles  Expresión de Alpine con los códigos que se deben mostrar
     * @param  string|null  $modelo  Variable de Alpine que guarda el código seleccionado
     * @return void
     */
    public function __construct($seleccionado = null, $visibles = null, $modelo = null)
    {
        $this->seleccionado = $seleccionado;
        $this->visibles = $visibles;
        $this->modelo = $modelo;

        // Las monedas vienen por país, por eso se eliminan las repetidas
        $this->monedas = World::currencies(['fields' => 'code,symbol'])->data
            ->unique('code')
            ->sortBy('code')
            ->map(function ($moneda) {
                // world solo trae los nombres en inglés; si intl no conoce la moneda se deja ese
                $nombre = Moneda::nombreDe($moneda['code']);

                return array_merge($moneda, ['name' => $nombre !== $moneda['code'] ? $nombre : $moneda['name']]);
            });
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-moneda');
    }
}
