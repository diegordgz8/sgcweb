<?php

namespace App\Livewire\Comunicado;

use App\Livewire\Forms\ComunicadoForm;
use App\Models\Comunicado;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaComunicado extends Component
{
	use WithPagination;

	public ComunicadoForm $form;

	public $busqueda = '';
	#[Locked]
	public $orden = 'created_at';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $listeners = ['render'];

	public function render()
	{
		$comunicados = $this->readyToLoad ?
			Auth::user()->administrador->comunicados()->where('asunto', 'like', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad)
			: [];

		return view('livewire.comunicado.tabla-comunicado', compact('comunicados'));
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
		if (! in_array($orden, ['created_at', 'nombre'], true)) {
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

	public function edit(Comunicado $comunicado)
	{
		$this->form->setComunicado($comunicado);
		$this->resetValidation();
		$this->openEdit = true;
	}

	public function update()
	{
		$this->authorize('comunicado.edit');

		$this->form->update();

		$this->reset('openEdit');

		$this->dispatch('render')->to('comunicado.tabla-comunicado');
		flash()->success('El comunicado se actualizó satisfactoriamente');
	}
}
