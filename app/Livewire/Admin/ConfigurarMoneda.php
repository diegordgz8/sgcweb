<?php

namespace App\Livewire\Admin;

use App\Models\Condominio;
use App\Models\Moneda;
use App\Models\TasaCambio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Nnjeim\World\Models\Currency;

/**
 * Tarjeta con las monedas del condominio. Permite agregar monedas, siempre con su
 * tasa inicial, pero nunca eliminarlas, y elegir en cuál se emiten las facturas.
 */
class ConfigurarMoneda extends Component
{
	public $open = false;

	public $moneda;
	public $tasa;

	public $openFacturacion = false;
	public $monedaFacturacion;

	protected $listeners = ['render'];

	protected $messages = [
		'moneda.required' => 'Debe seleccionar una moneda.',
		'moneda.not_in' => 'El condominio ya trabaja con esa moneda.',
		'tasa.required' => 'Indique la tasa de cambio de la nueva moneda.',
	];

	protected function rules()
	{
		return [
			'moneda' => ['required', Rule::exists(Currency::class, 'code'), Rule::notIn(Moneda::codigos())],
			'tasa' => 'required|numeric|gt:0',
		];
	}

	public function render()
	{
		$monedas = Moneda::orderByDesc('es_base')->orderBy('codigo')->get();

		foreach ($monedas as $item) {
			$item->tasaVigente = $item->es_base ? null : TasaCambio::vigente($item->codigo);
		}

		$base = Moneda::base();
		$facturacion = Moneda::facturacion();

		return view('livewire.admin.configurar-moneda', compact('monedas', 'base', 'facturacion'));
	}

	public function mostrarFacturacion()
	{
		$this->monedaFacturacion = Moneda::facturacion();
		$this->resetValidation();
		$this->openFacturacion = true;
	}

	public function guardarFacturacion()
	{
		$this->authorize('admin.home');

		$this->validateOnly('monedaFacturacion', [
			'monedaFacturacion' => ['required', Rule::in(Moneda::codigos())],
		]);

		Condominio::firstOrFail()->update(['moneda_facturacion' => $this->monedaFacturacion]);

		$this->reset('openFacturacion');

		$this->dispatch('render')->to('cierre-mes.nuevo-cierre');
		flash()->success('La moneda de facturación fue actualizada con éxito');
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function agregar()
	{
		$this->authorize('admin.home');

		$this->validate();

		DB::transaction(function () {
			Moneda::create(['codigo' => $this->moneda]);

			TasaCambio::create([
				'moneda' => $this->moneda,
				'tasa' => $this->tasa,
				'fecha' => now(),
			]);
		});

		$this->reset(['open', 'moneda', 'tasa']);

		$this->dispatch('render')->to('admin.configurar-tasa-cambio');
		flash()->success('La moneda fue agregada con éxito');
	}
}
