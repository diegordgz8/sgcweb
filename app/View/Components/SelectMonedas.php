<?php

namespace App\View\Components;

class SelectMonedas extends SelectMoneda
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-monedas');
    }
}
