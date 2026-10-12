<?php

namespace App\Livewire\CierreMes;

use App\Models\Factura;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaFactura extends Component
{
	use WithPagination;

	public $busqueda = '';
	#[Locked]
	public $orden = 'created_at';
	#[Locked]
	public $direccion = "desc";
	public $cantidad = '10';

	public $readyToLoad = false;

	protected $listeners = ['render'];

	public function render()
	{
		if ($this->readyToLoad) {

			$facturas = Factura::orderBy($this->orden, $this->direccion)->paginate($this->cantidad);
		} else {
			$facturas = [];
		}

		return view('livewire.cierre-mes.tabla-factura', compact('facturas'));
	}
}
