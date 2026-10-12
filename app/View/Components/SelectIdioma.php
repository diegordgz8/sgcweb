<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectIdioma extends Component
{
    public $idiomas;
    public $seleccionado;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($seleccionado = null)
    {
        $this->seleccionado = $seleccionado;

        $this->idiomas = World::languages(['fields' => 'code,name_native'])->data
            ->sortBy('name');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-idioma');
    }
}
