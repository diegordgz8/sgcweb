<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectZonaHoraria extends Component
{
    public $zonasHorarias;
    public $zonasPorPais;
    public $seleccionado;
    public $pais;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $pais  Expresión de Alpine con el código ISO2 del país por el que se filtra
     * @return void
     */
    public function __construct($seleccionado = null, $pais = null)
    {
        $this->seleccionado = $seleccionado;
        $this->pais = $pais;

        $zonas = World::timezones(['fields' => 'country_id'])->data;

        // Las zonas horarias vienen por país, por eso se eliminan las repetidas
        $this->zonasHorarias = $zonas->unique('name')->sortBy('name');

        // Zonas horarias agrupadas por el código ISO2 de su país
        $paises = World::countries(['fields' => 'iso2'])->data->pluck('iso2', 'id');

        $this->zonasPorPais = $zonas
            ->groupBy(fn ($zona) => $paises[$zona['country_id']] ?? '')
            ->map(fn ($grupo) => $grupo->pluck('name')->sort()->values());
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-zona-horaria');
    }
}
