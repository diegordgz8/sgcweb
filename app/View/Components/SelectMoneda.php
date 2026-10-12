<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Locale;
use Nnjeim\World\World;
use ResourceBundle;

class SelectMoneda extends Component
{
    public $monedas;
    public $seleccionado;
    public $visibles;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $visibles  Expresión de Alpine con los códigos que se deben mostrar
     * @return void
     */
    public function __construct($seleccionado = null, $visibles = null)
    {
        $this->seleccionado = $seleccionado;
        $this->visibles = $visibles;

        // Las monedas vienen por país, por eso se eliminan las repetidas
        $this->monedas = World::currencies(['fields' => 'code,symbol'])->data
            ->unique('code')
            ->sortBy('code')
            ->map(fn ($moneda) => array_merge($moneda, [
                'name' => $this->nombreTraducido($moneda['code']) ?? $moneda['name'],
            ]));
    }

    /**
     * Nombre de la moneda en el idioma de la aplicación, según los datos de intl (ICU).
     * La librería world solo trae los nombres en inglés.
     */
    private function nombreTraducido(string $codigo): ?string
    {
        $locale = app()->getLocale();

        foreach (array_unique([$locale, Locale::getPrimaryLanguage($locale)]) as $idioma) {
            $nombre = ResourceBundle::create($idioma, 'ICUDATA-curr')?->get('Currencies')?->get($codigo)?->get(1);

            if ($nombre) {
                return Str::ucfirst($nombre);
            }
        }

        return null;
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
