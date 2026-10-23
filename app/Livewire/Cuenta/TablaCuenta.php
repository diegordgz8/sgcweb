<?php

namespace App\Livewire\Cuenta;

use App\Livewire\Forms\CuentaForm;
use App\Models\Banco;
use App\Models\Cuenta;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCuenta extends Component
{

	use WithPagination;

	public $cuenta;

	public CuentaForm $form;

	public $busqueda;
	#[Locked]
	public $orden = 'numero';
	#[Locked]
	public $direccion = "desc";
	public $cantidad = "10";

	protected $listeners = ['render'];

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	public $bancos = [];

	public function mount()
	{
		$this->cuenta = new Cuenta;
	}

	public function render()
	{
		if ($this->readyToLoad) {

			$cuentas = Cuenta::where('numero', 'like', '%' . $this->busqueda . '%')
				->orwhere('tipo', 'like', '%' . $this->busqueda . '%')
				->orwhere('documento', 'like', '%' . $this->busqueda . '%')
				->orwhere('beneficiario', 'like', '%' . $this->busqueda . '%')
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$cuentas = [];
		}

		return view('livewire.cuenta.tabla-cuenta', compact('cuentas'));
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function loadCuentas()
	{

		$this->readyToLoad = true;
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
		if (! in_array($orden, ['documento', 'numero', 'tipo'], true)) {
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

	public function edit(Cuenta $cuenta)
	{
		$this->form->setCuenta($cuenta);
		$this->resetValidation();

		$this->bancos = Banco::all();
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('cuenta.edit');

		$this->form->update();

		$this->reset('openEdit');

		flash()->success('La cuenta se actualizó satisfactoriamente');
	}

	public function destroy(Cuenta $cuenta)
	{
		$this->cuenta = $cuenta;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('cuenta.delete');

		$this->cuenta->delete();

		$this->reset('openDestroy');

		flash()->success('La cuenta se eliminó satisfactoriamente');
	}
}
