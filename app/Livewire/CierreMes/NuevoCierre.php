<?php

namespace App\Livewire\CierreMes;

use App\Exceptions\MesYaCerrado;
use App\Exceptions\TasaNoDisponible;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Services\CierreMesService;
use App\Traits\WithCurrencies;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NuevoCierre extends Component
{
	use WithCurrencies;

	public $mes;

	public $open = false;

	public $readyToLoad = false;

	/**
	 * What the close will generate, shown for review before confirming. It is only
	 * computed on the server, so the browser cannot change it.
	 */
	#[Locked]
	public ?array $vistaPrevia = null;

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

	public function updatedMes()
	{
		// A different month needs a new review
		$this->vistaPrevia = null;
	}

	public function cancelar()
	{
		$this->reset(['open', 'mes', 'vistaPrevia']);
		$this->resetValidation();
	}

	public function volver()
	{
		$this->vistaPrevia = null;
	}

	public function revisar(CierreMesService $cierreMes)
	{
		$this->authorize('cierre-mes.create');

		$this->validate();

		try {
			$this->vistaPrevia = $cierreMes->previsualizar($this->mes, Moneda::facturacion());
		} catch (MesYaCerrado $e) {
			$this->addError('mes', 'El mes que ha seleccionado ya ha sido cerrado.');
		} catch (TasaNoDisponible $e) {
			$this->addError('mes', $e->getMessage());
		}
	}

	public function cerrarMes(CierreMesService $cierreMes)
	{
		$this->authorize('cierre-mes.create');

		$this->validate();

		// The close is only confirmed after reviewing that same month
		if (! $this->vistaPrevia || $this->vistaPrevia['mes'] !== $this->mes) {
			$this->vistaPrevia = null;
			$this->addError('mes', 'Revise el cierre antes de confirmarlo.');

			return false;
		}

		try {
			$cierreMes->cerrar($this->mes, Moneda::facturacion());
		} catch (MesYaCerrado $e) {
			flash()->error('El mes que ha seleccionado ya ha sido cerrado.');

			return false;
		} catch (TasaNoDisponible $e) {
			flash()->error($e->getMessage());

			return false;
		}

		$nombreMes = $this->nombreMes();

		$this->reset(['open', 'mes', 'vistaPrevia']);

		$this->dispatch('render')->to('cierre-mes.tabla-factura');
		flash()->success("Se cerró {$nombreMes} y se generaron las facturas.");
	}

	public function nombreMes(): string
	{
		return $this->mes ? Carbon::createFromFormat('!Y-m', $this->mes)->locale('es')->translatedFormat('F Y') : '';
	}
}
