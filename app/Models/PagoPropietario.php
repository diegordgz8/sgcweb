<?php

namespace App\Models;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagoPropietario extends Model
{
	use HasFactory;
	use WithCurrencies;

	protected $table = 'pagos_propietario';

	protected $fillable = [
		'descripcion',
		'monto',
		'fecha',
		'referencia',
		'forma_pago',
		'moneda',
		'fondo_id',
		'unidad_id',
		'factura_id',
	];

	/**
	 * Cierra la factura con este pago y acredita el fondo, con las tasas vigentes al registrar
	 * el pago. Lo que falte se cobra y lo que sobre se descuenta en la próxima factura de la
	 * unidad, por pequeño que sea.
	 */
	public function pagarFactura()
	{
		DB::transaction(function () {
			$factura = $this->factura;
			$diferencia = round($factura->monto_por_pagar - $this->montoEnMonedaFactura(), 2);

			$factura->saldar();

			if ($diferencia != 0) {
				AjusteSaldo::create([
					'unidad_id' => $factura->unidad_id,
					'tipo' => $diferencia > 0 ? 'Faltante' : 'A favor',
					'monto' => abs($diferencia),
					'moneda' => $factura->moneda,
					'deuda_desde' => $factura->deuda_desde_efectiva,
					'factura_origen_id' => $factura->id,
					'pago_propietario_id' => $this->id,
				]);
			}

			if ($this->fondo) {
				$this->acreditarFondo();
			}

			$this->estado = 'Confirmado';
			$this->save();
		});
	}

	/**
	 * El pago expresado en la moneda de la factura, con las tasas vigentes al registrarlo.
	 */
	public function montoEnMonedaFactura(): float
	{
		return $this->convertirMonto($this->monto, $this->moneda, $this->factura->moneda, $this->created_at);
	}

	/**
	 * Monto abonado a la factura, en la moneda de la factura.
	 */
	public function getMontoAbonadoAttribute(): float
	{
		return $this->montoEnMonedaFactura();
	}

	public function getMontoFormateadoAttribute() {
		return $this->formatearMonto($this->monto, $this->moneda);
	}

	/**
	 * Confirma el pago: cierra la factura, acredita el fondo y emite el recibo. Un pago sin
	 * fondo (efectivo, cheque) necesita que se indique el fondo donde entra.
	 */
	public function confirmar(?Fondo $fondo = null): void
	{
		DB::transaction(function () use ($fondo) {
			if ($fondo) {
				$this->fondo()->associate($fondo);
			}

			$this->pagarFactura();
			$this->emitirRecibo();
		});
	}

	public function necesitaFondo(): bool
	{
		return $this->fondo_id === null;
	}

	private function emitirRecibo(): void
	{
		$ultimoRecibo = Recibo::orderBy('created_at', 'desc')->first();

		$count = $ultimoRecibo ? Str::substr($ultimoRecibo->numero, 12) : 0;

		$numero = 'R' . Str::substr(today(), 0, 4) . Str::substr(today(), 5, 2) . '-' . $this->unidad->numero . '-' . ++$count;

		Recibo::create([
			'numero' => $numero,
			'pago_propietario_id' => $this->id,
		]);
	}

	private function acreditarFondo(): void
	{
		$this->fondo->acreditar($this->convertirMonto($this->monto, $this->moneda, $this->fondo->moneda, $this->created_at));
	}

	public function rechazar(string $motivo): void
	{
		$this->estado = 'Rechazado';
		$this->motivo_rechazo = $motivo;
		$this->save();
	}

	public function factura()
	{
		return $this->belongsTo(Factura::class);
	}

	public function fondo()
	{
		return $this->belongsTo(Fondo::class);
	}

	public function unidad()
	{
		return $this->belongsTo(Unidad::class);
	}

	public function recibo()
	{
		return $this->hasOne(Recibo::class);
	}
}
