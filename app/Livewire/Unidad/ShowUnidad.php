<?php

namespace App\Livewire\Unidad;

use App\Livewire\Forms\IntegranteUnidadForm;
use App\Models\Enfermedad;
use App\Models\Integrante;
use App\Models\Medicamento;
use App\Models\Unidad;
use Livewire\Component;
use Illuminate\Support\Str;

class ShowUnidad extends Component
{
	public Unidad $unidad;

	public Integrante $integrante;

	public IntegranteUnidadForm $form;

	public $enfermedades = [];
	public $medicamentos = [];

	public $codigo = '0412';
	public $telefono;

	public $openEdit = false;
	public $openDestroy = false;

	protected $rules = [
		'codigo' => 'nullable',
		'telefono' => 'nullable|digits:7',
	];

	protected $listeners = ['render'];

	public function render()
	{
		$listaEnfermedades = Enfermedad::orderBy('nombre')->get();
		$listaMedicamentos = Medicamento::orderBy('nombre')->get();

		return view('livewire.unidad.show-unidad', compact('listaEnfermedades', 'listaMedicamentos'));
	}

	public function edit(Integrante $integrante)
	{
		$this->autorizarIntegrante($integrante);

		$this->form->setIntegrante($integrante);
		$this->resetValidation();

		if($integrante->documento == '') {
			$this->form->letra = 'V';
		}

		$this->codigo = Str::substr($integrante->telefono, 0, 4);
		$this->telefono = Str::substr($integrante->telefono, 5, 7);
		$this->enfermedades = $integrante->enfermedades()->pluck('enfermedades.id')->toArray();
		$this->medicamentos = $integrante->medicamentos()->pluck('medicamentos.id')->toArray();
		
		$this->openEdit = true;
	}

	public function update()
	{
		$integrante = $this->form->integrante;
		$this->autorizarIntegrante($integrante);

		$this->validate();

		$integrante->enfermedades()->sync($this->enfermedades);
		$integrante->medicamentos()->sync($this->medicamentos);
		$this->form->update($this->codigo . '-' . $this->telefono);

		$this->reset('openEdit');

		$this->dispatch('render')->to('unidad.show-unidad');
		flash()->success('El integrante se actualizó satisfactoriamente');
	}

	public function removerIntegrante(Integrante $integrante)
	{
		$this->autorizarIntegrante($integrante);

		$this->integrante = $integrante;
		$this->openDestroy = true;
	}

	public function remove()
	{
		$this->autorizarIntegrante($this->integrante);

		$this->integrante->unidad()->dissociate($this->unidad);
		$this->integrante->save();

		$this->reset('openDestroy');

		$this->dispatch('render')->to('unidad.show-unidad');
		flash()->success('El integrante fue removido satisfactoriamente');
	}

	/**
	 * The resident must belong to this unit, and the user must be able to manage it.
	 */
	private function autorizarIntegrante(Integrante $integrante): void
	{
		$this->authorize('update', $this->unidad);

		abort_unless($integrante->unidad_id == $this->unidad->id, 403);
	}
}
