<?php

namespace App\Livewire\Pago;

use App\Models\Pago;
use App\Traits\WithCurrencies;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaPago extends Component
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

	public function render()
	{
		$pagos = $this->readyToLoad ?
			Pago::where('descripcion', 'LIKE', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad) :
			[];

		foreach ($pagos as $pago) {
			$pago->montoFormateado = $this->formatearMonto($pago->monto, $pago->moneda);
		}

		return view('livewire.pago.tabla-pago', compact('pagos'));
	}
}
