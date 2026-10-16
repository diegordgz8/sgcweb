<?php

namespace App\Livewire\Fondo;

use App\Models\Cuenta;
use App\Models\Fondo;
use App\Models\Moneda;
use Illuminate\Validation\Rule;
use Livewire\Component;

class NuevoFondo extends Component
{
	public $descripcion, $moneda, $saldoInicial = 0;
	// Cuenta bancaria afiliada; vacío para un fondo en efectivo
	public $cuentaId = '';

	public $open = false;

	protected function rules()
	{
		return [
			'descripcion' => 'required|max:255',
			'saldoInicial' => 'nullable|numeric|gt:0',
			'moneda' => ['required', Rule::in(Moneda::codigos())],
			'cuentaId' => ['nullable', Rule::exists('cuentas', 'id'), Rule::unique('fondos', 'cuenta_id')],
		];
	}

	protected $messages = [
		'cuentaId.unique' => 'La cuenta seleccionada ya tiene un fondo.',
	];

	public function mount() {
		$this->moneda = Moneda::base();
	}

	public function render()
	{
		$cuentas = Cuenta::doesntHave('fondo')->get();

		return view('livewire.fondo.nuevo-fondo', compact('cuentas'));
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function save()
	{
		$this->authorize('fondo.create');

		$this->validate();

		Fondo::create([
			'descripcion' => $this->descripcion,
			'moneda' => $this->moneda,
			'saldo' => $this->saldoInicial,
			'cuenta_id' => $this->cuentaId ?: null,
		]);

		$this->reset([
			'open',
			'descripcion',
			'saldoInicial',
			'cuentaId',
		]);

		$this->moneda = Moneda::base();

		$this->dispatch('render')->to('fondo.tabla-fondo');
		flash()->success('El fondo se registró satisfactoriamente');
	}
}
