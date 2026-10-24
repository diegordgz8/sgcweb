<?php

namespace App\Livewire\Admin\Unidad;

use App\Livewire\Forms\PropietarioForm;
use App\Models\Integrante;
use App\Models\Propietario;
use App\Models\Unidad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class ShowUnidad extends Component
{
	public Unidad $unidad;
	public PropietarioForm $form;

	public $documento;
	public $letra = 'V';
	public $ci;
	public $codigo = '0412';
	public $telefono;
	public $edad;

	public $openAsignar = false;
	public $openCambiar = false;
	public $openRetirar = false;
	// public $openDestroy = false;

	protected $listeners = ['render'];

	protected $rules = [
		'documento' => 'required|digits_between:8,20|unique:unidades,documento',
		'letra' => 'required',
		'ci' => 'required|digits_between:6,8',
		'codigo' => 'nullable',
		'telefono' => 'nullable|digits:7',
	];

	public function render()
	{
		return view('livewire.admin.unidad.show-unidad');
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function updatedLetra()
	{
		$this->buscarIntegrante();
	}

	public function updatedCi()
	{
		$this->buscarIntegrante();
	}

	public function updatedFormFechaNacimiento()
	{
		$this->edad = Carbon::parse($this->form->fecha_nacimiento)->age;
	}

	private function buscarIntegrante()
	{
		$integrante = Integrante::where('letra', $this->letra)
			->where('documento', $this->ci)->first();

		if ($integrante) {
			$this->form->setIntegrante($integrante);
			$this->codigo = Str::substr($integrante->telefono, 0, 4);
			$this->telefono = Str::substr($integrante->telefono, 5, 7);
		} else {
			$this->form->reset();
			$this->reset(['codigo', 'telefono']);
		}

		$this->edad = Carbon::parse($this->form->fecha_nacimiento)->age;
	}

	public function asignarPropietario()
	{
		$this->authorize('admin.unidad.edit');

		$this->validate();

		$integrante = $this->form->store([
			'letra' => $this->letra,
			'documento' => $this->ci,
			'telefono' => $this->codigo . '-' . $this->telefono,
		]);

		if ($integrante->propietario) {
			$this->unidad->propietario()->associate($integrante->propietario);
		} else {
			$propietario = new Propietario;
			$propietario->integrante()->associate($integrante);

			$propietario->user()->associate(User::create([
				'name' => $integrante->nombre . ' ' . $integrante->apellido,
				'email' => $integrante->email,
				'password' => bcrypt('password'),
			]));

			$propietario->save();
			$this->unidad->propietario()->associate($propietario);
		}

		$this->unidad->documento = $this->documento;
		$this->unidad->save();

		$this->reset([
			'openAsignar',
			'documento',
			'letra',
			'ci',
			'codigo',
			'telefono',
		]);

		$this->form->reset();

		flash()->success('El propietario fue asignado satisfactoriamente');
	}

	public function cambiarPropietario()
	{
		$this->authorize('admin.unidad.edit');

		$this->validate();

		$this->retirar();
		$this->asignarPropietario();

		$this->reset('openCambiar');
		flash()->success('El propietario fue cambiado satisfactoriamente');
	}

	public function retirar()
	{
		$this->authorize('admin.unidad.edit');

		$integrantes = $this->unidad->integrantes;

		foreach ($integrantes as $item) {
			$item->unidad()->dissociate();
			$item->save();
		}

		$this->unidad->propietario()->dissociate();
		$this->unidad->documento = null;
		$this->unidad->save();

		$this->reset('openRetirar');
	}
}
