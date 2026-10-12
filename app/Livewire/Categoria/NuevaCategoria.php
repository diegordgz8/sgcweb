<?php

namespace App\Livewire\Categoria;

use App\Models\Categoria;
use Livewire\Component;

class NuevaCategoria extends Component
{
	public $open = false;

	public $nombre, $descripcion;

	protected $rules = [
		'nombre' => 'required|max:25',
		'descripcion' => 'max:255',
	];

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function save()
	{
		$this->authorize('categoria.create');

		$this->validate();

		Categoria::create([
			'nombre' => $this->nombre,
			'descripcion' => $this->descripcion,
		]);

		$this->reset([
			'open',
			'nombre',
			'descripcion',
		]);

		$this->dispatch('render')->to('categoria.tabla-categoria');
		flash()->success('La categoría se creó satisfactoriamente');
	}

	public function render()
	{
		return view('livewire.categoria.nueva-categoria');
	}
}
