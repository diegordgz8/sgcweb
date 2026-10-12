<?php

namespace App\Livewire\Fondo;

use App\Models\Fondo;
use App\Models\Moneda;
use App\Traits\WithCurrencies;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaFondo extends Component
{
	use WithPagination;
	use WithCurrencies;

	public Fondo $fondo;

	public $busqueda = '';
	#[Locked]
	public $orden = 'descripcion';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	// Código de la moneda por la que se filtra; vacío muestra todas
	public $moneda = '';
	public $minimo = '';
	public $maximo = '';

	protected $rules = [
		'fondo.descripcion' => 'required|max:255',
		'fondo.moneda' => 'required'
	];

	protected $listeners = ['render'];

	public function render()
	{
		$fondos = $this->filtrar();

		if ($this->readyToLoad) {

			if ($fondos->count()) {

				$fondos = $fondos->toQuery()
					->where(function ($query) {
						$query->where('descripcion', 'like', '%' . $this->busqueda . '%');
					})
					->orderBy($this->orden, $this->direccion)
					->paginate($this->cantidad);

			} else {
				$fondos = [];
			}

			foreach ($fondos as $fondo) {
				$fondo->saldoFormateado = $this->formatearMonto($fondo->saldo, $fondo->moneda);
			}
		} else {
			$fondos = [];
		}

		$monedas = Moneda::codigos();

		return view('livewire.fondo.tabla-fondo', compact('fondos', 'monedas'));
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function updatingBusqueda()
	{
		$this->resetPage();
	}

	public function updatingCantidad()
	{
		$this->resetPage();
	}

	public function orden($orden)
	{
		if (! in_array($orden, ['descripcion'], true)) {
			return;
		}

		if ($this->orden == $orden) {
			if ($this->direccion == 'desc') {
				$this->direccion = 'asc';
			} else {
				$this->direccion = 'desc';
			}
		} else {
			$this->orden = $orden;
			$this->direccion = 'asc';
		}
	}

	private function filtrar()
	{
		$fondos = Fondo::all();

		if ($this->moneda != '') {
			$fondos = $fondos->intersect(Fondo::where('moneda', $this->moneda)->get());
		}

		if ($this->minimo != '') {
			$fondos = $fondos->intersect(Fondo::where('saldo', '>=', $this->minimo)->get());
		}

		if ($this->maximo != '') {
			$fondos = $fondos->intersect(Fondo::where('saldo', '<=', $this->maximo)->get());
		}

		return $fondos;
	}
}
