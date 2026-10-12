<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectZonaHoraria extends Component
{
    public $zonasHorarias;
    public $seleccionado;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($seleccionado = null)
    {
        $this->seleccionado = $seleccionado;

        // Las zonas horarias vienen por país, por eso se eliminan las repetidas
        $this->zonasHorarias = World::timezones()->data
            ->unique('name')
            ->sortBy('name');
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
