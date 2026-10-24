<?php

namespace App\Livewire\Gasto;

use App\Models\Asamblea;
use App\Models\Gasto;
use App\Models\GastoExtraordinario;
use App\Models\Moneda;
use App\Models\Proveedor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class NuevoGasto extends Component
{
	use WithPagination;

	public $descripcion;
	public $tipo = 'Ordinario';
	public $numeroMeses;
	public $asambleaId = 0;
	public $calculo;
	public $comienzoCobro;
	public $moneda;
	public float $monto = 0.00;
	public $observaciones;
	public $proveedorId = 0;
	public $factura;

	public $servicios = [];
	public $montos = [];

	public $elegidoAsamblea = 'no';

	public $open = false;

	public $readyToLoad = false;

	public $busqueda = '';
	#[Locked]
	public $orden = 'nombre';
	#[Locked]
	public $direccion = 'asc';
	public $cantidad = '10';

	public $selectAll = false;
	public $selectPage = false;

	protected function rules()
	{
		return [
			'descripcion' => [
				'required',
				'max:255',
			],
			'tipo' => 'required',
			'numeroMeses' => 'exclude_unless:tipo,Extraordinario|required|numeric|gt:0',
			'asambleaId' => [
				'exclude_if:tipo,Ordinario',
				'exclude_unless:elegidoAsamblea,si',
				'not_in:0',
				'exists:asambleas,id',
			],
			'calculo' => 'required',
			'comienzoCobro' => [
				'required',
				'date_format:Y-m',
				Rule::in(array_keys($this->mesesCobro())),
			],
			'moneda' => ['required', Rule::in(Moneda::codigos())],
			'monto' => [
				'required',
				'numeric',
			],
			'observaciones' => 'nullable',
			'proveedorId' => [
				'required',
				'not_in:0',
				'exists:proveedores,id',
			],
			'factura' => 'required',
			'servicios' => [
				'exclude_if:proveedorId,0',
				'array',
				'min:1',
			],
			'montos.*' => [
				'required_with:servicios.*',
				'numeric',
				'gt:0',
			],
		];
	}

	/**
	 * Months in which charging can start: this one and the next eleven. Offered as a list
	 * because type="month" falls back to free text in Firefox.
	 */
	public function mesesCobro(): array
	{
		return collect(range(0, 11))
			->map(fn ($adelante) => now()->startOfMonth()->addMonths($adelante))
			->mapWithKeys(fn ($mes) => [$mes->format('Y-m') => $mes->locale('es')->translatedFormat('F Y')])
			->all();
	}

	protected $messages = [
		'comienzoCobro.date_format' => 'Seleccione el mes de la lista.',
		'comienzoCobro.in' => 'El comienzo de cobro debe ser este mes o uno de los próximos once.',
		'proveedorId.required' => 'Debe seleccionar un proveedor.',
		'proveedorId.not_in' => 'Debe seleccionar un proveedor.',
		'asambleaId.not_in' => 'Debe seleccionar una asamblea.',
		'servicios.min' => 'Debe seleccionar al menos un servicio.',
		'montos.*.required_with' => 'El monto es requerido.',
		'montos.*.gt' => 'El monto debe ser mayor que 0.',
	];

	public function mount()
	{
		$this->moneda = Moneda::base();
	}

	public function render()
	{
		$asambleas = Asamblea::all();
		$proveedores = Proveedor::all();

		if ($this->selectAll) {
			$this->servicios = $this->consultaServicios->pluck('id')->map(fn ($id) => (string)$id);
		}

		$listaServicios = $this->readyToLoad ? $this->listaServicios : [];

		return view('livewire.gasto.nuevo-gasto', compact('listaServicios', 'asambleas', 'proveedores'));
	}

	public function getConsultaServiciosProperty()
	{
		return Proveedor::findOrNew($this->proveedorId)->servicios()
			->where(function ($query) {
				$query->where('nombre', 'LIKE', '%' . $this->busqueda . '%')
					->orWhere('descripcion', 'LIKE', '%' . $this->busqueda . '%');
			})
			->orderBy($this->orden, $this->direccion);
	}

	public function getListaServiciosProperty()
	{
		return $this->consultaServicios->paginate($this->cantidad);
	}

	public function updated($propertyName)
	{
		if (strpos($propertyName, 'montos.') === false) {
			$this->validateOnly($propertyName);
		}
	}

	public function updatingBusqueda()
	{
		$this->resetPage();
	}

	public function updatingCantidad()
	{
		$this->resetPage();
	}

	public function updatingCalculo($value)
	{
		$this->calculo = $value == '----' ? '' : $value;
		$this->validateOnly('calculo');
	}

	public function updatedProveedorId()
	{
		$this->selectAll = false;
		$this->selectPage = false;

		$this->servicios = [];
		$this->montos = [];
		$this->monto = 0;
	}

	public function updatedServicios($value, $key)
	{
		if ($value == false) {
			unset($this->servicios[$key]);
			unset($this->montos[$key]);
		} else {
			// $this->montos[$key] = null;
		}

		$this->selectAll = false;
		$this->selectPage = false;

		$this->sumarMontos();
	}

	public function updatedMontos($value, $key)
	{
		if ($value == '') {
			unset($this->montos[$key]);
		}

		$this->sumarMontos();
	}

	public function updatedSelectPage($value)
	{
		$this->servicios = $value ? $this->listaServicios->pluck('id')->toArray() : [];
	}

	public function orden($orden)
	{
		if (! in_array($orden, ['nombre'], true)) {
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

	/**
	 * El monto del gasto es la suma de los montos de los servicios seleccionados.
	 */
	private function sumarMontos()
	{
		$this->monto = 0;

		foreach ($this->montos as $key => $monto) {
			if (in_array($key, $this->servicios) && is_numeric($monto)) {
				$this->monto += $monto;
			}
		}
	}

	public function save()
	{
		$this->authorize('gasto.create');

		$this->validate();

		DB::beginTransaction();

		try {
			$gasto = Gasto::create([
				'descripcion' => $this->descripcion,
				'tipo' => $this->tipo,
				'calculo_por' => $this->calculo,
				'mes_cobro' => $this->comienzoCobro,
				'moneda' => $this->moneda,
				'monto' => $this->monto,
				'saldo' => $this->monto,
				'observaciones' => $this->observaciones,
				'proveedor_id' => $this->proveedorId,
				'factura' => $this->factura,
			]);

			if ($this->tipo == 'Extraordinario') {
				$gastoExtraordinario = GastoExtraordinario::create([
					'gasto_id' => $gasto->id,
					'num_meses' => $this->numeroMeses,
				]);

				if ($this->elegidoAsamblea == 'si') {
					$gastoExtraordinario->asamblea()->associate($this->asambleaId);
					$gastoExtraordinario->save();
				}
			}

			foreach ($this->servicios as $item) {
				$gasto->servicios()->attach($item, ['monto' => $this->montos[$item]]);
			}

			DB::commit();

			$this->reset([
				'open',
				'descripcion',
				'tipo',
				'numeroMeses',
				'elegidoAsamblea',
				'calculo',
				'comienzoCobro',
				'monto',
				'observaciones',
				'factura',
				'servicios',
				'montos',
				'selectPage',
				'selectAll',
			]);

			$this->moneda = Moneda::base();

			$this->asambleaId = 0;
			$this->proveedorId = 0;

			$this->dispatch('render')->to('gasto.tabla-gasto');
			flash()->success('El gasto se registró satisfactoriamente');

		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}
}
