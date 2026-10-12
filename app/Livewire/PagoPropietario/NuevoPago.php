<?php

namespace App\Livewire\PagoPropietario;

use App\Exceptions\TasaNoDisponible;
use App\Models\Factura;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\PagoPropietario;
use App\Traits\WithCurrencies;
use Illuminate\Support\Facades\Auth;
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
	public $montoFormateado;
	public $fecha;
	public $referencia;
	public $formaPago;
	public $moneda;

	public Factura $factura;
	public Cuenta $cuenta;

	public bool $conCambio = false;
	// Saldo de la factura expresado en la moneda del pago, con las tasas vigentes
	public $montoFacturaConvertido;
	public $montoFacturaConvertidoFormateado;

	public $open = false;

	public $busqueda;
	#[Locked]
	public $orden = 'fecha';
	#[Locked]
	public $direccion = 'desc';
	public $cantidad = '10';

	public $facturaMasVieja;

	protected function rules()
	{
		return [
			'descripcion' => 'required',
			'fecha' => 'required|before_or_equal:today',
			'formaPago' => 'required',
			'moneda' => ['required', Rule::in(Moneda::codigos())],
			'cuenta.id' => 'exclude_unless:formaPago,Transferencia,Pago móvil,Punto de venta,Depósito|not_in:0',
			'referencia' => 'exclude_unless:formaPago,Transferencia,Pago móvil|required|min:4|max:8',
			'monto' => ['required', 'numeric', 'gt:0', 'lte:' . (float) $this->montoFacturaConvertido],
		];
	}

	protected $messages = [
		'fecha.before_or_equal' => 'La fecha debe ser anterior o igual al día actual.',
		'cuenta.id.required' => 'Debe seleccionar una cuenta.',
		'cuenta.id.not_in' => 'Debe seleccionar una cuenta.',
		'monto.lte' => 'El monto no debe ser mayor al total de la deuda.',
	];

	public function mount()
	{
		$this->factura = new Factura;
		$this->cuenta = new Cuenta;
	}

	public function render()
	{
		$facturas = Auth::user()->propietario->facturas()
			->where('estado', 'Pendiente')
			->paginate($this->cantidad);

		if ($this->formaPago == 'Pago móvil') {
			$cuentas = Cuenta::where('publica', true)->whereNotNull('telefono')->get();
		} else {
			$cuentas = Cuenta::where('publica', true)->get();
		}

		$this->facturaMasVieja = $facturas->sortBy('fecha')->first();

		return view('livewire.pago-propietario.nuevo-pago', compact('facturas', 'cuentas'));
	}

	public function mostrarForm(Factura $factura)
	{
		$this->authorize('pagar', $factura);

		if ($this->facturaMasVieja->id != $factura->id) {
			flash()->error('No puede pagar una factura si existe otra más vieja.');
			return false;
		}

		$this->reset([
			'descripcion',
			'monto',
			'fecha',
			'referencia',
			'formaPago',
			'moneda',
		]);

		$this->factura = $factura;
		$this->moneda = $this->factura->moneda;
		$this->convertirSaldo();

		$this->montoFormateado = $this->factura->montoPorPagarFormateado;

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

		$this->cuenta = new Cuenta;
		$this->cuenta->id = 0;
	}

	public function updatedMoneda()
	{
		$this->cuenta = new Cuenta;

		$this->convertirSaldo();
		$this->validarMonto();
	}

	public function updatingCuenta($value)
	{
		if ($value == 0) {
			$this->cuenta = new Cuenta;
		} else {

			$this->cuenta = Cuenta::find($value);
		};
	}

	private function validarMonto()
	{
		if ($this->monto !== null && $this->monto !== '') {
			$this->validateOnly('monto');
		}
	}

	private function convertirSaldo()
	{
		$this->conCambio = $this->moneda != $this->factura->moneda;

		try {
			$this->montoFacturaConvertido = $this->convertirMonto($this->factura->monto_por_pagar, $this->factura->moneda, $this->moneda);
		} catch (TasaNoDisponible $e) {
			$this->montoFacturaConvertido = 0;
			$this->addError('moneda', $e->getMessage());
		}

		$this->montoFacturaConvertidoFormateado = $this->formatearMonto($this->montoFacturaConvertido, $this->moneda);
	}

	public function pagarTotal()
	{
		$this->monto = $this->montoFacturaConvertido;

		$this->validateOnly('monto');
	}

	public function orden($orden)
	{
		if (! in_array($orden, ['fecha'], true)) {
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

	public function save()
	{
		$this->authorize('pagar', $this->factura);

		$this->validate();

		$datos = [
			'descripcion' => $this->descripcion,
			'monto' => $this->monto,
			'fecha' => $this->fecha,
			'referencia' => $this->referencia,
			'forma_pago' => $this->formaPago,
			'moneda' => $this->moneda,
			'unidad_id' => $this->factura->unidad->id,
			'factura_id' => $this->factura->id
		];

		if ($this->formaPago == 'Transferencia' || $this->formaPago == 'Pago móvil') {
			$datos['fondo_id'] = $this->cuenta->fondo->id;
		}

		$pago = PagoPropietario::create($datos);

		// $pago->save();

		$this->reset([
			'open',
			'descripcion',
			'monto',
			'fecha',
			'referencia',
			'formaPago',
			'moneda',
		]);

		$this->factura = new Factura;
		$this->cuenta = new Cuenta;

		flash()->success('El pago ha sido registrado, debe esperar que el condominio confirme el pago para ver los cambios.');
	}
}
