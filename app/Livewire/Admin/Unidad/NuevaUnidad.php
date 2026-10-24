<?php

namespace App\Livewire\Admin\Unidad;

use App\Models\TipoUnidad;
use App\Models\Unidad;
use Livewire\Component;

class NuevaUnidad extends Component
{
	public $numero;
	public $direccion;
	public $tipoUnidadId;

	public $tipoUnidades;

	public $open = false;

	protected $rules = [
		'numero' => 'required|numeric|unique:unidades,numero',
		'tipoUnidadId' => 'required|exists:tipo_unidades,id',
		'direccion' => 'required|max:255',
	];

	protected $messages = [
		'tipoUnidadId.required' => 'Debe seleccionar un tipo de unidad.',
		'tipoUnidadId.exists' => 'Debe seleccionar un tipo de unidad.',
	];

	public function mount() {
		$this->tipoUnidades = TipoUnidad::all();
	}

    public function render()
    {
        return view('livewire.admin.unidad.nueva-unidad');
    }

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function save() {
		$this->authorize('admin.unidad.create');

		$this->validate();

		Unidad::create([
			'numero' => $this->numero,
			'tipo_unidad_id' => $this->tipoUnidadId,
			'direccion' => $this->direccion,
		]);

		$this->reset([
			'open',
			'numero',
			'direccion',
			'tipoUnidadId',
		]);

		$this->dispatch('render')->to('admin.unidad.tabla-unidad');
		flash()->success('La unidad se registró satisfactoriamente');
	}
}
