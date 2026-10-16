<?php

namespace App\View\Components;

use App\Models\Moneda;
use Illuminate\View\Component;

/**
 * Selector con las monedas con las que trabaja el condominio, la base primero.
 */
class SelectMonedaCondominio extends Component
{
    public $monedas;
    public $seleccionado;

    /**
     * Create a new component instance.
     *
     * @param  bool  $sinBase  Excluye la moneda base, por ejemplo para cargar tasas de cambio
     * @return void
     */
    public function __construct($seleccionado = null, $sinBase = false)
    {
        $this->seleccionado = $seleccionado;

        $this->monedas = collect(Moneda::codigos())
            ->when($sinBase, fn ($codigos) => $codigos->reject(fn ($codigo) => $codigo === Moneda::base()))
            ->mapWithKeys(fn ($codigo) => [$codigo => $codigo . ' - ' . Moneda::nombreDe($codigo)]);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-moneda-condominio');
    }
}
