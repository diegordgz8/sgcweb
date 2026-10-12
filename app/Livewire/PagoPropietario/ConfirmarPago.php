<?php

namespace App\Livewire\PagoPropietario;

use App\Models\Fondo;
use App\Models\PagoPropietario;
use App\Models\Recibo;
use App\Traits\WithCurrencies;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ConfirmarPago extends Component
{
	use WithPagination;
	use WithCurrencies;

	public $busqueda;
	#[Locked]
	public $orden = 'fecha';
	#[Locked]
	public $direccion = 'asc';
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openConfirmar = false;
	public $pago;
	public $fondos = [];

	public $openRechazar = false;

	public function render()
	{
		$pagos = $this->readyToLoad ?
			PagoPropietario::where('estado', 'Por confirmar')
			->where('descripcion', 'LIKE', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad) :
			[];

		return view('livewire.pago-propietario.confirmar-pago', compact('pagos'));
	}

	public function confirmar(PagoPropietario $pago)
	{
		$this->authorize('pago-propietario.confirmar');

		$pago->pagarFactura();

		$ultimoRecibo = Recibo::orderBy('created_at', 'desc')->first();

		$count = $ultimoRecibo ? Str::substr($ultimoRecibo->numero, 12) : 0;

		$numero = 'R' . Str::substr(today(), 0, 4) . Str::substr(today(), 5, 2) . '-' . $pago->unidad->numero . '-' . ++$count;

		Recibo::create([
			'numero' => $numero,
			'pago_propietario_id' => $pago->id,
		]);

		flash()->success('El pago ha sido confirmado satisfactoriamente.');
	}

	public function elegirFondo(PagoPropietario $pago)
	{
		$this->authorize('pago-propietario.confirmar');

		$this->pago = $pago;
		$this->fondos = Fondo::doesntHave('cuenta')->where('moneda', $this->pago->moneda)->get();
		$this->openConfirmar = true;
	}

	public function aceptar(Fondo $fondo)
	{
		$this->authorize('pago-propietario.confirmar');

		$this->pago->fondo()->associate($fondo);
		$this->pago->aceptarPago();

		$ultimoRecibo = Recibo::orderBy('created_at', 'desc')->first();

		$count = $ultimoRecibo ? Str::substr($ultimoRecibo->numero, 12) : 0;

		$numero = 'R' . Str::substr(today(), 0, 4) . Str::substr(today(), 5, 2) . '-' . $this->pago->unidad->numero . '-' . ++$count;

		Recibo::create([
			'numero' => $numero,
			'pago_propietario_id' => $this->pago->id,
		]);

		$this->reset('openConfirmar');

		flash()->success('El pago ha sido confirmado satisfactoriamente.');
	}

	public function rechazar(PagoPropietario $pago) {
		$this->authorize('pago-propietario.confirmar');

		$pago->rechazar();

		$this->reset('openRechazar');

		flash()->success('El pago ha sido rechazado');
	}
}
