<?php

namespace App\Livewire\Admin;

use App\Models\Moneda;
use App\Models\TasaCambio;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Actualiza la tasa de cada moneda respecto a la moneda base. Cada cambio crea un
 * registro nuevo para conservar el historial con el que se convirtieron los montos.
 */
class ConfigurarTasaCambio extends Component
{
    public $open = false;

    public $moneda;
    public $tasa;

    public $busqueda = '';
    #[Locked]
    public $orden = 'fecha';
    #[Locked]
    public $direccion = 'desc';
    public $cantidad = '10';

    protected $listeners = ['render'];

    protected $messages = [
        'tasa.required' => 'La tasa de cambio es obligatoria.',
        'tasa.numeric' => 'La tasa de cambio debe ser un número.',
        'tasa.gt' => 'La tasa de cambio debe ser mayor a :value.',
    ];

    protected function rules()
    {
        return [
            'moneda' => ['required', Rule::in($this->monedas())],
            'tasa' => 'required|numeric|gt:0',
        ];
    }

    public function mount()
    {
        $this->moneda = $this->monedas()[0] ?? null;
        $this->cargarTasa();
    }

    public function render()
    {
        $listaTasas = TasaCambio::where('moneda', $this->moneda)
            ->where('fecha', 'LIKE', '%' . $this->busqueda . '%')
            ->orderBy($this->orden, $this->direccion)
            ->paginate($this->cantidad);

        $base = Moneda::base();

        return view('livewire.admin.configurar-tasa-cambio', compact('listaTasas', 'base'));
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function updatedMoneda()
    {
        $this->cargarTasa();
    }

    public function actualizar()
    {
        $this->authorize('admin.home');

        $this->validate();

        if ((float) TasaCambio::vigente($this->moneda)?->tasa !== (float) $this->tasa) {
            TasaCambio::create([
                'moneda' => $this->moneda,
                'tasa' => $this->tasa,
                'fecha' => now(),
            ]);
        }

        $this->dispatch('render')->to('admin.configurar-moneda');
        flash()->success('La tasa de cambio fue actualizada con éxito');
    }

    /**
     * Monedas que tienen tasa: todas menos la base.
     */
    private function monedas(): array
    {
        return array_values(array_diff(Moneda::codigos(), [Moneda::base()]));
    }

    private function cargarTasa(): void
    {
        $this->tasa = $this->moneda ? TasaCambio::vigente($this->moneda)?->tasa : null;
    }
}
