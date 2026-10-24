<?php

namespace App\Livewire\PagoPropietario;

use App\Exceptions\TasaNoDisponible;
use App\Models\AjusteSaldo;
use App\Models\Factura;
use App\Models\Cuenta;
use App\Models\Fondo;
use App\Models\Moneda;
use App\Models\PagoPropietario;
use App\Models\Unidad;
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
	public $cuentaId = 0;

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

	// El formulario corrige un pago rechazado: se reenvía como un pago nuevo
	public $corrigiendo = false;
	public $motivoRechazo;

	protected function rules()
	{
		return [
			'descripcion' => 'required',
			'fecha' => 'required|before_or_equal:today',
			'formaPago' => 'required',
			'moneda' => ['required', Rule::in(Moneda::codigos())],
			// La cuenta debe ser pública y tener un fondo en la moneda del pago, que es donde
			// se acredita al confirmarlo
			'cuentaId' => [
				'exclude_unless:formaPago,Transferencia,Pago móvil,Punto de venta,Depósito',
				'not_in:0',
				Rule::exists('fondos', 'cuenta_id')
					->where('moneda', $this->moneda)
					->whereIn('cuenta_id', Cuenta::where('publica', true)->select('id')),
			],
			'referencia' => 'exclude_unless:formaPago,Transferencia,Pago móvil|required|min:4|max:8',
			// Se acepta cualquier monto: lo que falte o sobre queda en el saldo de la unidad
			'monto' => ['required', 'numeric', 'gt:0'],
		];
	}

	protected $messages = [
		'fecha.before_or_equal' => 'La fecha debe ser anterior o igual al día actual.',
		'cuentaId.not_in' => 'Debe seleccionar una cuenta.',
		'cuentaId.exists' => 'La cuenta seleccionada no recibe pagos en la moneda elegida.',
	];

	public function mount()
	{
		$this->factura = new Factura;

		// Desde la lista de pagos, "Corregir y reenviar" abre el pago rechazado de esa factura
		if ($numero = request()->query('corregir')) {
			$factura = Auth::user()->propietario->facturas()->where('facturas.numero', $numero)->first();

			if ($factura) {
				$this->mostrarForm($factura);
			}
		}
	}

	public function render()
	{
		$facturas = Auth::user()->propietario->facturas()
			->with('ultimoPago')
			->where('estado', 'Pendiente')
			->paginate($this->cantidad);

		if ($this->formaPago == 'Pago móvil') {
			$cuentas = $this->cuentasEnMoneda()->whereNotNull('telefono')->get();
		} else {
			$cuentas = $this->cuentasEnMoneda()->get();
		}

		// Faltantes y saldos a favor que se aplicarán en la próxima factura de cada unidad
		$ajustes = AjusteSaldo::pendientes()
			->with('unidad')
			->whereIn('unidad_id', Auth::user()->propietario->unidades()->select('unidades.id'))
			->get();

		return view('livewire.pago-propietario.nuevo-pago', compact('facturas', 'cuentas', 'ajustes'));
	}

	public function mostrarForm(Factura $factura)
	{
		$this->authorize('pagar', $factura);

		if ($this->facturaMasVieja()?->id != $factura->id) {
			flash()->error('No puede pagar una factura si existe otra más vieja.');
			return false;
		}

		$ultimoPago = $factura->ultimoPago;

		if ($ultimoPago?->estado === 'Por confirmar') {
			flash()->info('El pago de esta factura está esperando la confirmación del condominio.');
			return false;
		}

		$this->reset([
			'descripcion',
			'monto',
			'fecha',
			'referencia',
			'formaPago',
			'moneda',
			'cuentaId',
			'corrigiendo',
			'motivoRechazo',
		]);

		$this->factura = $factura;
		$this->moneda = $this->factura->moneda;

		// Un pago rechazado se corrige partiendo de los datos que se enviaron
		if ($ultimoPago?->estado === 'Rechazado') {
			$this->corrigiendo = true;
			$this->motivoRechazo = $ultimoPago->motivo_rechazo;
			$this->descripcion = $ultimoPago->descripcion;
			$this->monto = (float) $ultimoPago->monto;
			$this->fecha = substr($ultimoPago->fecha, 0, 10);
			$this->referencia = $ultimoPago->referencia;
			$this->formaPago = $ultimoPago->forma_pago;
			$this->moneda = $ultimoPago->moneda;
			$this->cuentaId = $ultimoPago->fondo?->cuenta_id ?? 0;
		}

		$this->convertirSaldo();

		$this->montoFormateado = $this->factura->montoPorPagarFormateado;

		$this->open = true;
	}

	/**
	 * Convierte los faltantes de la unidad en una factura y abre el pago de esa factura.
	 */
	public function pagarFaltante(Unidad $unidad)
	{
		$this->authorize('pagarFaltante', $unidad);

		$factura = $unidad->facturarFaltantes();

		if (! $factura) {
			flash()->error('La unidad no tiene faltantes pendientes.');

			return false;
		}

		if ($this->facturaMasVieja()?->id != $factura->id) {
			flash()->info("Se generó la factura {$factura->numero} con el faltante. Primero debe pagar su factura más antigua.");

			return false;
		}

		return $this->mostrarForm($factura);
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function updatedFormaPago($value)
	{
		$this->formaPago = $value == '----' ? null : $value;
		$this->validateOnly('formaPago');

		$this->cuentaId = 0;
	}

	public function updatedMoneda()
	{
		$this->cuentaId = 0;

		$this->convertirSaldo();
		$this->validarMonto();
	}

	/**
	 * Factura pendiente más antigua del propietario: hay que pagarlas en orden.
	 */
	private function facturaMasVieja(): ?Factura
	{
		return Auth::user()->propietario->facturas()
			->where('estado', 'Pendiente')
			->orderBy('fecha')
			->orderBy('facturas.id')
			->first();
	}

	/**
	 * Faltante o saldo a favor que dejaría el monto escrito, en la moneda de la factura.
	 * Usa las tasas vigentes, las mismas con que se registrará el pago.
	 */
	public function getAvisoSaldoProperty(): ?array
	{
		if (! $this->factura->exists || ! is_numeric($this->monto) || $this->monto <= 0) {
			return null;
		}

		try {
			$abono = $this->convertirMonto($this->monto, $this->moneda, $this->factura->moneda);
		} catch (TasaNoDisponible $e) {
			return null;
		}

		$diferencia = round($this->factura->monto_por_pagar - $abono, 2);

		if ($diferencia == 0) {
			return null;
		}

		return [
			'faltante' => $diferencia > 0,
			'monto' => $this->formatearMonto(abs($diferencia), $this->factura->moneda),
		];
	}

	/**
	 * Cuentas públicas cuyo fondo está en la moneda del pago.
	 */
	private function cuentasEnMoneda()
	{
		return Cuenta::where('publica', true)
			->whereHas('fondo', fn ($fondo) => $fondo->where('moneda', $this->moneda));
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

		// Un pago por confirmar no se duplica; uno rechazado sí se puede reenviar
		if ($this->factura->pagos()->where('estado', 'Por confirmar')->exists()) {
			flash()->error('El pago de esta factura ya está esperando la confirmación del condominio.');
			return false;
		}

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
			$datos['fondo_id'] = Fondo::where('cuenta_id', $this->cuentaId)->value('id');
		}

		PagoPropietario::create($datos);

		$mensaje = $this->corrigiendo
			? 'El pago corregido fue enviado. Debe esperar que el condominio lo confirme.'
			: 'El pago ha sido registrado, debe esperar que el condominio confirme el pago para ver los cambios.';

		$this->reset([
			'open',
			'descripcion',
			'monto',
			'fecha',
			'referencia',
			'formaPago',
			'moneda',
			'corrigiendo',
			'motivoRechazo',
		]);

		$this->factura = new Factura;
		$this->cuentaId = 0;

		flash()->success($mensaje);
	}
}
