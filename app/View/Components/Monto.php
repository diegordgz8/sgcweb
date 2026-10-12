<?php

namespace App\View\Components;

use App\Traits\WithCurrencies;
use Illuminate\View\Component;

/**
 * Monto con el formato de su moneda en el idioma de la aplicación, por ejemplo "ARS 150.000,00".
 */
class Monto extends Component
{
    use WithCurrencies;

    public $texto;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($monto, $moneda)
    {
        $this->texto = $this->formatearMonto($monto ?? 0, $moneda);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return '<span {{ $attributes }}>{{ $texto }}</span>';
    }
}
