<?php

namespace App\View\Components;

use Collator;
use Illuminate\View\Component;
use Nnjeim\World\World;

class SelectPais extends Component
{
    public $paises;
    public $seleccionado;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($seleccionado = null)
    {
        $this->seleccionado = $seleccionado;

        $collator = new Collator(config('app.locale'));

        $this->paises = World::countries(['fields' => 'iso2,flag'])->data
            ->sort(fn ($a, $b) => $collator->compare($a['name'], $b['name']));
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-pais');
    }
}
