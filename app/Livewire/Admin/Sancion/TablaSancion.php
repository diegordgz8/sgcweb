<?php

namespace App\Livewire\Admin\Sancion;

use App\Livewire\Forms\SancionForm;
use App\Models\Sancion;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TablaSancion extends Component
{
    
	use WithPagination;

	public SancionForm $form;

	public $busqueda;
	#[Locked]
	public $orden = 'descripcion';
	#[Locked]
	public $direccion = "desc";
	public $cantidad = "10";

	public $openEdit = false;

	protected $listeners = ['render'];

	public $readyToLoad = false;

	public function render()
	{
		if ($this->readyToLoad) {

			$sanciones = Sancion::where('descripcion', 'like', '%' . $this->busqueda . '%')
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$sanciones = [];
		}

		return view('livewire.admin.sancion.tabla-sancion', compact('sanciones'));
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
		if (! in_array($orden, ['descripcion', 'monto'], true)) {
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

	public function edit(Sancion $sancion)
    {
        $this->form->setSancion($sancion);
        $this->resetValidation();
        $this->openEdit = true;
    }

    public function update()
    {
        $this->authorize('sancion.edit');

        $this->form->update();
        $this->reset('openEdit');
        flash()->success('El monto se actualizó satisfactoriamente');
    }

}
