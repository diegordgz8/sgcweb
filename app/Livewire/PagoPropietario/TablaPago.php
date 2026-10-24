<?php

namespace App\Livewire\PagoPropietario;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
		$pagos = new Collection;

		$unidades = Auth::user()->propietario->unidades;

		if ($this->readyToLoad) {

			foreach ($unidades as $item) {

				foreach ($item->pagos as $pago) {
					$pagos->push($pago);
				}
			}

			if ($pagos->count() > 0) {
				$pagos = $pagos->toQuery()
					->with('factura.ultimoPago', 'unidad')
					->where('descripcion', 'LIKE', '%' . $this->busqueda . '%')
					->orderBy($this->orden, $this->direccion)
					->paginate($this->cantidad);
			}
		} else {
			$pagos = new Collection;
		}

		foreach ($pagos as $pago) {
			$pago->montoFormateado = $this->formatearMonto($pago->monto, $pago->moneda);
		}

		return view('livewire.pago-propietario.tabla-pago', compact('pagos'));
	}
}
