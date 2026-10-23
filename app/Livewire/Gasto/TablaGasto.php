<?php

namespace App\Livewire\Gasto;

use App\Livewire\Forms\GastoForm;
use App\Models\Gasto;
use App\Models\Proveedor;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaGasto extends Component
{
	use WithPagination;

	public $busqueda;
	#[Locked]
	public $orden = 'created_at';
	#[Locked]
	public $direccion = 'asc';
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $readyToLoadEdit = false;

	public $openCancelar = false;

	public Gasto $gasto;

	public GastoForm $form;

	protected $listeners = ['render'];

	public function mount()
	{
		$this->gasto = new Gasto;
		$this->gasto->proveedor = new Proveedor;
	}

	public function render()
	{
		$gastos = $this->readyToLoad ?
			Gasto::where('descripcion', 'LIKE', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad) :
			[];

		return view('livewire.gasto.tabla-gasto', compact('gastos'));
	}

	public function updatedForm($value, $key)
	{
		if (str_starts_with($key, 'montos')) {
			$this->form->sumarMontos();
		}
	}

	public function edit(Gasto $gasto)
	{
		$this->form->setGasto($gasto);
		$this->resetValidation();

		$this->gasto = $gasto;
		$this->readyToLoadEdit = true;
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('gasto.edit');

		$this->form->update();

		$this->reset('openEdit');

		flash()->success('El gasto fue actualizado satisfactoriamente');
	}

	public function cancelar(Gasto $gasto)
	{
		$this->gasto = $gasto;
		$this->openCancelar = true;
	}

	public function delete()
	{
		$this->authorize('gasto.delete');

		if ($this->gasto->delete()) {
			$this->reset('openCancelar');

			flash()->success('El gasto fue detenido satisfactoriamente');
		}
	}
}
