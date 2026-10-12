<?php

namespace App\Livewire\CierreMes;

use App\Exceptions\MesYaCerrado;
use App\Exceptions\TasaNoDisponible;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Services\CierreMesService;
use Livewire\Component;

class NuevoCierre extends Component
{
	public $mes;

	public $open = false;

	public $readyToLoad = false;

	protected $listeners = ['render'];

	protected $messages = [
		'mes.before_or_equal' => 'El mes debe ser anterior o igual a este mes.',
	];

	protected function rules()
	{
		return [
			'mes' => 'required|before_or_equal:this month',
		];
	}

	public function render()
	{
		// La moneda de las facturas se configura en la administración, no en cada cierre
		$moneda = Moneda::facturacion();
		$nombreMoneda = Moneda::nombreDe($moneda);

		// Tasas con las que se convertirán los montos de las facturas
		$tasas = collect(Moneda::codigos())
			->reject(fn ($codigo) => $codigo === Moneda::base())
			->map(fn ($codigo) => TasaCambio::vigente($codigo))
			->filter();

		return view('livewire.cierre-mes.nuevo-cierre', compact('moneda', 'nombreMoneda', 'tasas'));
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
			$cierreMes->cerrar($this->mes, Moneda::facturacion());
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

		$this->dispatch('render')->to('cierre-mes.tabla-factura');
		flash()->success('El cierre de mes fue satisfactorio. Se han generado las facturas.');
	}
}
