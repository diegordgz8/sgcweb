<?php

namespace App\Livewire\TipoUnidad;

use App\Livewire\Forms\TipoUnidadForm;
use App\Models\TipoUnidad;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaTipoUnidad extends Component
{

	use WithPagination;

	public $tipoUnidad;

	public TipoUnidadForm $form;

	public $busqueda;
	#[Locked]
	public $orden = 'nombre';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

	public function mount()
	{
		$this->tipoUnidad = new TipoUnidad;
	}

	public function render()
	{
		if ($this->readyToLoad) {
			$tipoUnidades = TipoUnidad::where(function ($query) {
					$query->where('nombre', 'like', '%' . $this->busqueda . '%')
						->orWhere('area', 'like', '%' . $this->busqueda . '%')
						->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
				})
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$tipoUnidades = [];
		}

		return view('livewire.tipo-unidad.tabla-tipo-unidad', compact('tipoUnidades'));
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

	public function loadTipoUnidades()
	{
		$this->readyToLoad = true;
	}

	public function orden($orden)
	{
		if (! in_array($orden, ['area', 'descripcion', 'nombre'], true)) {
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

	public function edit(TipoUnidad $tipoUnidad)
	{
		$this->form->setTipoUnidad($tipoUnidad);
		$this->resetValidation();
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('tipo-unidad.edit');

		$this->form->update();
		$this->reset('openEdit');
		flash()->success('El tipo de unidad se actualizó satisfactoriamente');
	}

	public function destroy(TipoUnidad $tipoUnidad)
	{
		$this->tipoUnidad = $tipoUnidad;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('tipo-unidad.delete');

		$this->tipoUnidad->delete();
		$this->reset('openDestroy');
		flash()->success('El tipo de unidad se eliminó satisfactoriamente');
	}
}
