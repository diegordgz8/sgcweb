<?php

namespace App\Livewire\CierreMes;

use App\Exceptions\MesYaCerrado;
use App\Exceptions\TasaNoDisponible;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Services\CierreMesService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class NuevoCierre extends Component
{
	public $mes;
	public $moneda;

	public $open = false;

	public $readyToLoad = false;

	protected $messages = [
		'mes.before_or_equal' => 'El mes debe ser anterior o igual a este mes.',
	];

	protected function rules()
	{
		return [
			'mes' => 'required|before_or_equal:this month',
			'moneda' => ['required', Rule::in(Moneda::codigos())],
		];
	}

	public function mount()
	{
		$this->moneda = Moneda::base();
	}

	public function render()
	{
		// Tasas con las que se convertirán los montos de las facturas
		$tasas = collect(Moneda::codigos())
			->reject(fn ($codigo) => $codigo === Moneda::base())
			->map(fn ($codigo) => TasaCambio::vigente($codigo))
			->filter();

		return view('livewire.cierre-mes.nuevo-cierre', compact('tasas'));
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
			$cierreMes->cerrar($this->mes, $this->moneda);
		} catch (MesYaCerrado $e) {
			flash()->error('El mes que ha seleccionado ya ha sido cerrado.');

			return false;
		} catch (TasaNoDisponible $e) {
			flash()->error($e->getMessage());

			return false;
		}

		$this->reset([
			'open',
			'mes',
		]);
		$this->moneda = Moneda::base();

		$this->dispatch('render')->to('cierre-mes.tabla-factura');
		flash()->success('El cierre de mes fue satisfactorio. Se han generado las facturas.');
	}
}
