<?php

namespace App\Livewire\Categoria;

use App\Models\Categoria;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaCategoria extends Component
{
	use WithPagination;
	
	public Categoria $categoria;

	public $busqueda = '';
	#[Locked]
	public $orden = 'nombre';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $rules = [
		'categoria.nombre' => 'required|max:25',
		'categoria.descripcion' => 'max:255',
	];

	protected $listeners = ['render'];

	public function render()
	{
		if ($this->readyToLoad) {
			$categorias = Categoria::where('nombre', 'like', '%' . $this->busqueda . '%')
				->orWhere('descripcion', 'like', '%' . $this->busqueda . '%')
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
		$this->categoria = $categoria;
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('categoria.edit');

		$this->validate();

		$this->categoria->save();

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
