<?php

namespace App\Livewire\Pago;

use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Pago;
use App\Models\TasaCambio;
use Livewire\Component;
use Livewire\WithPagination;
use NumberFormatter;

class NuevoPago extends Component
{
	use WithPagination;

	public $descripcion;
	public $monto;
	public $montoFormateado;
	public $fecha;
	public $recibo;
	public $referencia;
	public $formaPago;
	public $moneda = 'Bolívar';
	public $tasaCambio;

	public Gasto $gasto;
	public Fondo $fondo;

	public bool $conCambio = false;
	public $montoGastoConvertido;
	public $montoGastoConvertidoFormateado;

	private NumberFormatter $formatoDinero;
	private $bolivar = 'VES';
	private $dolar = 'USD';

	public $open = false;

	public $busqueda;
	public $orden = 'fecha';
	public $direccion = 'desc';
	public $cantidad = '10';

	protected function rules()
	{
		// $this->formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);

		$rules = [
			'descripcion' => 'required',
			'fecha' => 'required|before_or_equal:today',
			'recibo' => 'required|numeric|unique:pagos_gastos,recibo',
			'formaPago' => 'required',
			'moneda' => 'required',
			'fondo.id' => 'required|not_in:0',
			'referencia' => 'exclude_unless:formaPago,Transferencia,Pago móvil,Cheque|min:4|max:8',
			'tasaCambio.tasa' => 'exclude_if:conCambio,false|required|numeric',
		];

		if ($this->conCambio == true) {
			if ($this->tasaCambio) {
				$rules['monto'] = [
					'required',
					'numeric',
					'gt:0',
					'lte:' . $this->montoGastoConvertido,
				];

				if ($this->fondo->id > 0) {
					array_push($rules['monto'], 'lte:fondo.saldo');
				}
			} else {
				$rules['monto'] = 'required|numeric';
			}
		} else {
			$rules['monto'] = [
				'required',
				'numeric',
				'lte:gasto.saldo',
			];

			if ($this->fondo->id > 0) {
				array_push($rules['monto'], 'lte:fondo.saldo');
			}
		}

		return $rules;
	}

	protected $messages = [
		'fondo.id.required' => 'Debe seleccionar un fondo.',
		'fondo.id.not_in' => 'Debe seleccionar un fondo.',
		'monto.lte' => 'El monto no debe ser mayor al saldo del fondo seleccionado o al total de la deuda.',
		'tasaCambio.tasa.required_if' => 'Debe ingresar la tasa de cambio.'
	];

	public function mount()
	{
		$this->gasto = new Gasto;
		$this->fondo = new Fondo;
		$this->tasaCambio = TasaCambio::orderBy('created_at', 'desc')->first();

		$this->conCambio = $this->moneda != $this->gasto->moneda;

		$this->formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);
	}

	public function render()
	{
		$this->formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);

		$gastos = Gasto::where('estado_pago', 'Pendiente')
			->orderBy($this->orden, $this->direccion)
			->paginate($this->cantidad);

		if ($this->formaPago != '') {

			if ($this->formaPago == 'Transferencia' || $this->formaPago == 'Punto de venta') {
				$fondos = Fondo::has('cuenta')->where('moneda', $this->moneda)->get();

				foreach ($fondos as $item) {
					$item->cuenta->ocultarNumero();
				}
			} else	if ($this->formaPago == 'Pago móvil') {
				$fondos = Fondo::has('cuenta')->where('moneda', $this->moneda)->get()->whereNotNull('cuenta.telefono');

				foreach ($fondos as $item) {
					$item->cuenta->ocultarNumero();
				}
			} else {
				$fondos = Fondo::doesntHave('cuenta')->where('moneda', $this->moneda)->get();
			}

			foreach ($fondos as $item) {
				if ($item->moneda == 'Bolívar') {
					$item->saldoFormateado = $this->formatoDinero->format($item->saldo);
				} elseif ($item->moneda == 'Dólar') {
					$item->saldoFormateado = $this->formatoDinero->formatCurrency($item->saldo, $this->dolar);
				}
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
			'moneda',
		]);

		$this->gasto = $gasto;
		$this->moneda = $this->gasto->moneda;
		$this->conCambio = $this->moneda != $this->gasto->moneda;

		$this->formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);

		if ($this->gasto->moneda == 'Bolívar') {
			$this->montoFormateado = $this->formatoDinero->format($this->gasto->saldo);
		} elseif ($this->gasto->moneda == 'Dólar') {
			$this->montoFormateado = $this->formatoDinero->formatCurrency($this->gasto->saldo, $this->dolar);
		}

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

		$this->fondo = new Fondo;
		$this->fondo->id = 0;
	}

	public function updatedMoneda()
	{
		$this->fondo = new Fondo;
		$this->fondo->id = 0;

		$this->conCambio = $this->moneda != $this->gasto->moneda;

		$this->validarMonto();
	}

	public function updatingFondo($value)
	{
		if ($value == 0) {
			$this->fondo = new Fondo;
		} else {

			$this->fondo = Fondo::find($value);
		};

		$this->validarMonto();
	}

	public function updatedTasaCambio()
	{
		$this->convertirMonto();
		$this->validarMonto();
	}

	private function validarMonto()
	{
		if ($this->conCambio) {
			if ($this->tasaCambio) {
				$this->convertirMonto();

				$rules['monto'] = [
					'exclude_if:monto,null',
					'required',
					'numeric',
					'gt:0',
					'lte:' . $this->montoGastoConvertido,
				];

				if ($this->fondo->id > 0) {
					array_push($rules['monto'], 'lte:fondo.saldo');
				}

				$this->validateOnly('monto', $rules);
			} else {
				$this->validateOnly('monto', ['monto' => '']);
			}
		} else {
			$rules['monto'] = [
				'exclude_if:monto,null',
				'required',
				'numeric',
				'gt:0',
				'lte:gasto.saldo',
			];

			if ($this->fondo->id > 0) {
				array_push($rules['monto'], 'lte:fondo.saldo');
			}

			$this->validateOnly('monto', $rules);
		}
	}

	private function convertirMonto()
	{
		$this->formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);

		if ($this->moneda == 'Bolívar') {
			$this->montoGastoConvertido = $this->gasto->saldo * $this->tasaCambio->tasa;
			$this->montoGastoConvertidoFormateado = $this->formatoDinero->formatCurrency($this->montoGastoConvertido, 'VES');
		} else if ($this->moneda == 'Dólar') {
			$this->montoGastoConvertido = $this->gasto->saldo / $this->tasaCambio->tasa;
			$this->montoGastoConvertidoFormateado = $this->formatoDinero->formatCurrency($this->montoGastoConvertido, 'USD');
		}
	}

	public function pagarTotal()
	{
		if ($this->conCambio) {
			if ($this->tasaCambio) {
				if ($this->moneda == 'Bolívar') {

					$this->monto = $this->montoGastoConvertido;
					// $this->monto = '1';
				} elseif ($this->moneda == 'Dólar') {

					$this->monto = $this->montoGastoConvertido;
					// $this->monto = '2';
				}
			} else {
				$this->validateOnly('tasaCambio.tasa');
			}
		} else {

			$this->monto = $this->gasto->saldo;
			// $this->monto = '3';
		}

		$this->validateOnly('monto');
	}

	public function save()
	{
		$this->validate();

		$pago = Pago::create([
			'descripcion' => $this->descripcion,
			'monto' => $this->monto,
			'fecha' => $this->fecha,
			'recibo' => $this->recibo,
			'referencia' => $this->referencia,
			'forma_pago' => $this->formaPago,
			'moneda' => $this->moneda,
			'tasa_cambio_id' => $this->tasaCambio->id,
		]);

		$pago->gasto()->associate($this->gasto);
		$pago->fondo()->associate($this->fondo);

		$pago->save();

		$pago->pagarGasto($this->conCambio);

		$this->reset([
			'open',
			'descripcion',
			'monto',
			'fecha',
			'recibo',
			'referencia',
			'formaPago',
			'moneda',
		]);

		$this->gasto = new Gasto;
		$this->fondo = new Fondo;

		toastr()->livewire()->addSuccess('El pago se ha realizado satisfactoriamente');
	}
}
