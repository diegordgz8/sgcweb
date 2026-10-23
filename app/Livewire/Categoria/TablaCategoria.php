<?php

namespace App\Livewire\Categoria;

use App\Livewire\Forms\CategoriaForm;
use App\Models\Categoria;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCategoria extends Component
{
	use WithPagination;
	
	public Categoria $categoria;

	public CategoriaForm $form;

	public $busqueda = '';
	#[Locked]
	public $orden = 'nombre';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

	public function render()
	{
		if ($this->readyToLoad) {
			$categorias = Categoria::where(function ($query) {
					$query->where('nombre', 'like', '%' . $this->busqueda . '%')
						->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
				})
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$categorias = [];
		}

		return view('livewire.categoria.tabla-categoria', compact('categorias'));
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function updatingBusqueda()
	{
		$this->resetPage();
	}

	public function updatingCantidad() {
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

	public function edit(Categoria $categoria)
	{
		$this->form->setCategoria($categoria);
		$this->resetValidation();
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('categoria.edit');

		$this->form->update();

		$this->reset('openEdit');

		$this->dispatch('render')->to('categoria.tabla-categoria');
		flash()->success('La categoría se actualizó satisfactoriamente');
	}

	public function destroy(Categoria $categoria) {
		$this->categoria = $categoria;
		$this->openDestroy = true;
	}

	public function delete() {
		$this->authorize('categoria.delete');

		$this->categoria->delete();

		$this->reset('openDestroy');

		$this->dispatch('render')->to('categoria.tabla-categoria');
		flash()->success('La categoría se eliminó satisfactoriamente');
	}
}
