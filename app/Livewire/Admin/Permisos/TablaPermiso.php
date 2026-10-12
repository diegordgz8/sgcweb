<?php

namespace App\Livewire\Admin\Permisos;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class TablaPermiso extends Component
{
    use WithPagination;

    public Permission $permiso;

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

    protected function rules() {
        return [
            'permiso.name' => [
                'required',
                'string',
                Rule::unique('permissions', 'name')->ignore($this->permiso),
            ]
        ];
    }

    protected $validationAttributes = [
        'permiso.name' => 'nombre',
    ];

    public function mount() {
        $this->permiso = new Permission();
    }

    public function render()
    {
        $permisos = Permission::where('name', 'LIKE', "%{$this->busqueda}%")->orderBy($this->orden, $this->direccion)->paginate($this->cantidad);

        return view('livewire.admin.permisos.tabla-permiso', compact('permisos'));
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

	public function edit(Permission $permiso)
	{
		$this->permiso = $permiso;

		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('admin.permiso.edit');

		$this->validate();

		$this->permiso->save();

		$this->reset('openEdit');

		flash()->success('El permiso se actualizó satisfactoriamente');
	}

	public function destroy(Permission $permiso)
	{
		$this->permiso = $permiso;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('admin.permiso.delete');

		$this->permiso->delete();

		$this->reset('openDestroy');

		flash()->success('El permiso se eliminó satisfactoriamente');
	}
}
