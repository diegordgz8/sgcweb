<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class TablaUsuario extends Component
{
	use WithPagination;

	public User $usuario;
	public $roles = [];

	public $busqueda = '';
	#[Locked]
	public $orden = 'name';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $rules = [
		'roles' => 'array',
	];

	protected $listeners = ['render'];

	public function mount()
	{
		$this->usuario = new User;
	}

	public function render()
	{
		if ($this->readyToLoad) {
			$usuarios = User::where('name', 'like', '%' . $this->busqueda . '%')
				->orWhere('email', 'like', '%' . $this->busqueda . '%')
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$usuarios = [];
		}

		$listaRoles = Role::orderBy('name', 'asc')->get();

		return view('livewire.tabla-usuario', compact('usuarios', 'listaRoles'));
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
		if (! in_array($orden, ['email', 'name'], true)) {
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

	public function edit(User $usuario)
	{
		$this->usuario = $usuario;
		$this->roles = $this->usuario->roles()->allRelatedIds()->map(fn ($id) => (string)$id);

		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('admin.usuario.edit');

		$this->validate();

		$this->usuario->roles()->sync($this->roles);

		$this->reset(['openEdit', 'roles']);

		$this->dispatch('render')->to('tabla-usuario');
		flash()->success('Los roles fueron asignados satisfactoriamente');
	}

	public function destroy(User $usuario)
	{
		$this->usuario = $usuario;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('admin.usuario.delete');

		$this->usuario->delete();

		$this->reset('openDestroy');

		flash()->success('El usuario se eliminó satisfactoriamente');
	}
}
