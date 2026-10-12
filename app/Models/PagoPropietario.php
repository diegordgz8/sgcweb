<?php

namespace App\Models;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
	 * Abona a la factura y al fondo el monto convertido a la moneda de cada uno,
	 * con las tasas vigentes al registrar el pago.
	 */
	public function pagarFactura()
	{
		$this->factura->pagar($this->convertirMonto($this->monto, $this->moneda, $this->factura->moneda, $this->created_at));

		if ($this->fondo) {
			$this->acreditarFondo();
		}

		$this->estado = 'Confirmado';
		$this->save();
	}

	public function getMontoFormateadoAttribute() {
		return $this->formatearMonto($this->monto, $this->moneda);
	}

	public function aceptarPago()
	{
		$this->acreditarFondo();

		$this->estado = 'Confirmado';
		$this->save();
	}

	private function acreditarFondo(): void
	{
		$this->fondo->acreditar($this->convertirMonto($this->monto, $this->moneda, $this->fondo->moneda, $this->created_at));
	}

	public function rechazar() {
		$this->estado = 'Rechazado';
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
