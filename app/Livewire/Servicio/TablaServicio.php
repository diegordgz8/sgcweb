<?php

namespace App\Livewire\Servicio;

use App\Livewire\Forms\ServicioForm;
use App\Models\Categoria;
use App\Models\Servicio;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaServicio extends Component
{
	use WithPagination;

	public Servicio $servicio;

	public ServicioForm $form;

	public $busqueda;
	#[Locked]
	public $orden = 'nombre';
	#[Locked]
	public $direccion = 'asc';
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

	public function mount(Servicio $servicio)
	{
		$this->servicio = $servicio;
	}

	public function render()
	{
		if ($this->readyToLoad) {
			$servicios = Servicio::where(function ($query) {
					$query->where('nombre', 'like', '%' . $this->busqueda . '%')
						->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
				})
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$servicios = [];
		}

		$categorias = Categoria::all();

		return view('livewire.servicio.tabla-servicio', compact('servicios', 'categorias'));
	}

	public function loadServicios()
	{
		$this->readyToLoad = true;
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
		if (! in_array($orden, ['descripcion', 'nombre'], true)) {
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

	public function edit(Servicio $servicio)
	{
		$this->form->setServicio($servicio);
		$this->resetValidation();
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('servicio.edit');

		$this->form->update();

		$this->reset('openEdit');

		$this->dispatch('render')->to('servicio.tabla-servicio');
		flash()->success('El servicio se actualizó satisfactoriamente');
	}

	public function destroy(Servicio $servicio)
	{
		$this->servicio = $servicio;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('servicio.delete');

		$this->servicio->delete();

		$this->reset('openDestroy');

		$this->dispatch('render')->to('servicio.tabla-servicio');
		flash()->success('El servicio se eliminó satisfactoriamente');
	}
}
