<?php

namespace App\Livewire\Visita;

use App\Models\Unidad;
use App\Models\Visita;
use Livewire\Component;
use Livewire\WithPagination;

class NuevaVisita extends Component
{
	use WithPagination;

	public $letra = 'V';
	public $ci;
	public $nombre;
	public $apellido;
	public $unidadId;
	public $numeroPersonas = 1;
	public $matricula;
	public $marca;
	public $modelo;
	public $color;

	public $open = false;

	public $readyToLoad = false;

	protected $rules = [
		'letra' => 'required',
		'ci' => 'required|digits_between:6,8',
		'nombre' => 'required|max:45',
		'apellido' => 'required|max:45',
		'unidadId' => 'required|exists:unidades,id,propietario_id,NOT_NULL',
		'numeroPersonas' => 'gt:0',
		'matricula' => 'nullable|max:7',
		'marca' => 'nullable|max:25',
		'modelo' => 'nullable|max:25',
		'color' => 'required_with:matricula|max:25',
	];

	protected $messages = [
		'ci.required' => 'El campo cédula es obligatorio.',
		'unidadId.required' => 'Debe seleccionar una unidad.',
		'unidadId.exists' => 'Debe seleccionar una unidad.',
		'color.required_with' => 'El campo color es oblgatorio cuando ingresó la matrícula.',
	];

	public function render()
	{
		$unidades = Unidad::has('propietario')->get();

		return view('livewire.visita.nueva-visita', compact('unidades'));
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function save()
	{
		$this->authorize('visita.create');

		$this->validate();

		Visita::create([
			'letra' => $this->letra,
			'ci' => $this->ci,
			'nombre' => $this->nombre,
			'apellido' => $this->apellido,
			'unidad_id' => $this->unidadId,
			'numero_personas' => $this->numeroPersonas,
			'matricula' => $this->matricula,
			'marca' => $this->marca,
			'modelo' => $this->modelo,
			'color' => $this->color,
		]);

		$this->reset([
			'open',
			'letra',
			'ci',
			'nombre',
			'apellido',
			'unidadId',
			'numeroPersonas',
			'matricula',
			'marca',
			'modelo',
			'color',
		]);

		$this->dispatch('render')->to('visita.tabla-visita');
		flash()->success('La visita se registró satisfactoriamente');
	}
}
