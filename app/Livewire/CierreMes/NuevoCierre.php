<?php

namespace App\Livewire\CierreMes;

use App\Exceptions\MesYaCerrado;
use App\Models\TasaCambio;
use App\Services\CierreMesService;
use Livewire\Component;

class NuevoCierre extends Component
{
	public $mes;
	public $moneda = 'Bolívar';
	public TasaCambio $tasaCambio;

	public $open = false;

	public $readyToLoad = false;

	protected $rules = [
		'mes' => 'required|before_or_equal:this month',
		'moneda' => 'required',
		'tasaCambio.tasa' => 'required|numeric|gt:0',
	];

	protected $messages = [
		'mes.before_or_equal' => 'El mes debe ser anterior o igual a este mes.',
	];

	public function mount()
	{
		$this->tasaCambio = TasaCambio::orderBy('created_at', 'desc')->first();
	}

	public function render()
	{
		return view('livewire.cierre-mes.nuevo-cierre');
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function cerrarMes(CierreMesService $cierreMes)
	{
		$this->authorize('cierre-mes.create');

		$this->validate();

		try {
			$cierreMes->cerrar($this->mes, $this->moneda, $this->tasaCambio);
		} catch (MesYaCerrado $e) {
			flash()->error('El mes que ha seleccionado ya ha sido cerrado.');

			return false;
		}

		$this->reset([
			'open',
			'mes',
			'moneda',
		]);

		$this->dispatch('render')->to('cierre-mes.tabla-factura');
		flash()->success('El cierre de mes fue satisfactorio. Se han generado las facturas.');
	}
}
