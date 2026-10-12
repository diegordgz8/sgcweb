<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectMoneda extends Component
{
    public $monedas;
    public $seleccionado;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($seleccionado = null)
    {
        $this->seleccionado = $seleccionado;

        // Las monedas vienen por país, por eso se eliminan las repetidas
        $this->monedas = World::currencies(['fields' => 'code,symbol'])->data
            ->unique('code')
            ->sortBy('code');
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
