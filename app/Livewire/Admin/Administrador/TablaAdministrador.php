<?php

namespace App\Livewire\Admin\Administrador;

use App\Livewire\Forms\AdministradorForm;
use App\Models\Administrador;
use App\Models\Integrante;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaAdministrador extends Component
{
	use WithPagination;

	public Administrador $administrador;

	public AdministradorForm $form;

	public $busqueda;
	#[Locked]
	public $orden = 'id';
	#[Locked]
	public $direccion = 'desc';
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

	public function render()
	{
		$administradores = $this->readyToLoad ? Administrador::paginate($this->cantidad) : [];

		return view('livewire.admin.administrador.tabla-administrador', compact('administradores'));
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
		if (! in_array($orden, ['id'], true)) {
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

	public function edit(Administrador $administrador)
	{
		$this->form->setAdministrador($administrador);
		$this->resetValidation();

		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('admin.administrador.edit');

		$this->form->update();

		$this->reset('openEdit');

		flash()->success('El administrador se actualizó satisfactoriamente');
	}

	public function destroy(Administrador $administrador)
	{
		$this->administrador = $administrador;
		$this->openDestroy = true;
	}

	public function delete()
	{
		$this->authorize('admin.administrador.delete');

		$this->administrador->delete();

		$this->reset('openDestroy');

		flash()->success('El administrador se eliminó satisfactoriamente');
	}
}
