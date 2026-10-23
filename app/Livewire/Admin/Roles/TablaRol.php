<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Forms\RolForm;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TablaRol extends Component
{
    use WithPagination;

    public Role $rol;

    public RolForm $form;
    public $permisos = [];

    public $busqueda;
    #[Locked]
    public $orden = 'name';
    #[Locked]
    public $direccion = 'asc';
    public $cantidad = '10';

    public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

    public function mount() {
        $this->rol = new Role();
    }

    public function render()
    {
        $roles = Role::where('name', 'LIKE', "%{$this->busqueda}%")->orderBy($this->orden, $this->direccion)->paginate($this->cantidad);

        return view('livewire.admin.roles.tabla-rol', compact('roles'));
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
		if (! in_array($orden, ['name'], true)) {
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

	public function edit(Role $rol)
	{
		$this->form->setRol($rol);
		$this->resetValidation();
        $this->permisos = $rol->permissions()->allRelatedIds()->map(fn ($id) => (string)$id);

		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('admin.rol.edit');

		$this->form->update();

        $this->form->rol->permissions()->sync($this->permisos);

		$this->reset('openEdit');

		flash()->success('El rol se actualizó satisfactoriamente');
	}

	public function destroy(Role $rol)
	{
		$this->rol = $rol;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('admin.rol.delete');

		$this->rol->delete();

		$this->reset('openDestroy');

		flash()->success('El rol se eliminó satisfactoriamente');
	}

    public function getListaPermisosProperty() {
        return Permission::orderBy('name', 'asc')->get();
    }
}
