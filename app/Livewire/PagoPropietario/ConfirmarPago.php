<?php

namespace App\Livewire\PagoPropietario;

use App\Models\PagoPropietario;
use App\Traits\WithConfirmacionPago;
use App\Traits\WithCurrencies;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ConfirmarPago extends Component
{
	use WithPagination;
	use WithCurrencies;
	use WithConfirmacionPago;

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
			PagoPropietario::where('estado', 'Por confirmar')
			->where('descripcion', 'LIKE', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad) :
			[];

		return view('livewire.pago-propietario.confirmar-pago', compact('pagos'));
	}

	protected function pagoResuelto(): void
	{
		$this->resetPage();
	}
}
