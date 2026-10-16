<?php

namespace App\Livewire\Pago;

use App\Exceptions\TasaNoDisponible;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Moneda;
use App\Models\Pago;
use App\Traits\WithCurrencies;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class NuevoPago extends Component
{
	use WithPagination;
	use WithCurrencies;

	public $descripcion;
	public $monto;
	public $fecha;
	public $recibo;
	public $referencia;
	public $formaPago;
	public $moneda;

	public Gasto $gasto;
	public $fondoId = 0;

	// Saldo del gasto expresado en la moneda del pago, con las tasas vigentes
	public $montoGastoConvertido;

	public $open = false;

	public $busqueda;
	#[Locked]
	public $orden = 'fecha';
	#[Locked]
	public $direccion = 'desc';
	public $cantidad = '10';

	protected function rules()
	{
		$monto = ['required', 'numeric', 'gt:0', 'lte:' . (float) $this->montoGastoConvertido];

		if ($fondo = Fondo::find($this->fondoId)) {
			$monto[] = 'lte:' . (float) $fondo->saldo;
		}

		return [
			'descripcion' => 'required',
			'fecha' => 'required|before_or_equal:today',
			'recibo' => 'required|numeric|unique:pagos_gastos,recibo',
			'formaPago' => 'required',
			'moneda' => ['required', Rule::in(Moneda::codigos())],
			'fondoId' => 'required|not_in:0|exists:fondos,id',
			'referencia' => 'exclude_unless:formaPago,Transferencia,Pago móvil,Cheque|min:4|max:8',
			'monto' => $monto,
		];
	}

	protected $messages = [
		'fondoId.required' => 'Debe seleccionar un fondo.',
		'fondoId.not_in' => 'Debe seleccionar un fondo.',
		'monto.lte' => 'El monto no debe ser mayor al saldo del fondo seleccionado o al total de la deuda.',
	];

	public function mount()
	{
		$this->gasto = new Gasto;
		$this->moneda = Moneda::base();
	}

	public function render()
	{
		$gastos = Gasto::where('estado_pago', 'Pendiente')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad);

		if ($this->formaPago != '') {

			if ($this->formaPago == 'Transferencia' || $this->formaPago == 'Punto de venta') {
				$fondos = Fondo::has('cuenta')->where('moneda', $this->moneda)->get();
			} else	if ($this->formaPago == 'Pago móvil') {
				$fondos = Fondo::has('cuenta')->where('moneda', $this->moneda)->get()->whereNotNull('cuenta.telefono');
			} else {
				$fondos = Fondo::doesntHave('cuenta')->where('moneda', $this->moneda)->get();
			}

			foreach ($fondos as $item) {
				$item->saldoFormateado = $this->formatearMonto($item->saldo, $item->moneda);
			}
		} else {
			$fondos = [];
		}

		return view('livewire.pago.nuevo-pago', compact('gastos', 'fondos'));
	}

	public function mostrarForm(Gasto $gasto)
	{
		$this->reset([
			'descripcion',
			'monto',
			'fecha',
			'recibo',
			'referencia',
			'formaPago',
		]);

		$this->gasto = $gasto;
		$this->moneda = $this->gasto->moneda;
		$this->convertirSaldo();

		$this->open = true;
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function updatedFormaPago($value)
	{
		$this->formaPago = $value == '----' ? null : $value;
		$this->validateOnly('formaPago');

		$this->fondoId = 0;
	}

	public function updatedMoneda()
	{
		$this->fondoId = 0;

		$this->convertirSaldo();
		$this->validarMonto();
	}

	public function updatedFondoId()
	{
		$this->validarMonto();
	}

	private function validarMonto()
	{
		if ($this->monto !== null && $this->monto !== '') {
			$this->validateOnly('monto');
		}
	}

	private function convertirSaldo()
	{
		try {
			$this->montoGastoConvertido = $this->convertirMonto($this->gasto->saldo, $this->gasto->moneda, $this->moneda);
		} catch (TasaNoDisponible $e) {
			$this->montoGastoConvertido = 0;
			$this->addError('moneda', $e->getMessage());
		}
	}

	public function pagarTotal()
	{
		$this->monto = $this->montoGastoConvertido;

		$this->validateOnly('monto');
	}

	public function save()
	{
		$this->authorize('pago-condominio.create');

		$this->validate();

		$pago = Pago::create([
			'descripcion' => $this->descripcion,
			'monto' => $this->monto,
			'fecha' => $this->fecha,
			'recibo' => $this->recibo,
			'referencia' => $this->referencia,
			'forma_pago' => $this->formaPago,
			'moneda' => $this->moneda,
		]);

		$pago->gasto()->associate($this->gasto);
		$pago->fondo()->associate(Fondo::find($this->fondoId));

		$pago->save();

		$pago->pagarGasto();

		$this->reset([
			'open',
			'descripcion',
			'monto',
			'fecha',
			'recibo',
			'referencia',
			'formaPago',
		]);

		$this->moneda = Moneda::base();
		$this->gasto = new Gasto;
		$this->fondoId = 0;

		flash()->success('El pago se ha realizado satisfactoriamente');
	}
}
