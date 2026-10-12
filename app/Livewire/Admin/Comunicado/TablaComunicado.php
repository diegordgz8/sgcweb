<?php

namespace App\Livewire\Admin\Comunicado;

use App\Models\Comunicado;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaComunicado extends Component
{
	use WithPagination;

	public Comunicado $comunicado;

	public $busqueda = '';
	#[Locked]
	public $orden = 'created_at';
	#[Locked]
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openDestroy = false;

	protected $listeners = ['render'];

	public function render()
	{
		$comunicados = $this->readyToLoad ?
			Comunicado::where('asunto', 'like', '%' . $this->busqueda . '%')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad)
			: [];

		return view('livewire.admin.comunicado.tabla-comunicado', compact('comunicados'));
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

	public function destroy(Comunicado $comunicado) {
		$this->comunicado = $comunicado;
		$this->openDestroy = true;
	}

	public function delete() {
		$this->authorize('admin.comunicado.delete');

		$this->comunicado->delete();

		$this->reset('openDestroy');

		flash()->success('El comunicado se eliminó satisfactoriamente');
	}
}
