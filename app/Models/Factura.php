<?php

namespace App\Models;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;
	use WithCurrencies;

	protected $fillable = [
		'numero',
		'monto',
		'monto_por_pagar',
		'moneda',
		'fecha',
		'deuda_desde',
		'unidad_id',
		'iva_id',
		'interes_id',
		'cierre_mes_id',
	];

	protected $casts = [
		'monto' => 'decimal:2',
		'monto_por_pagar' => 'decimal:2',
	];

	/**
	 * Cierra la factura con un pago. Lo que falte o sobre lo registra quien paga como
	 * ajuste de saldo de la unidad.
	 */
	public function saldar(): void
	{
		$this->monto_por_pagar = 0;
		$this->estado = 'Pagada';
		$this->save();
	}

	/**
	 * Fecha desde la que la unidad arrastra la deuda de esta factura.
	 */
	public function getDeudaDesdeEfectivaAttribute()
	{
		return $this->deuda_desde ?? $this->fecha;
	}

	public function getMontoFormateadoAttribute() {
		return $this->formatearMonto($this->monto, $this->moneda);
	}

	public function getMontoPorPagarFormateadoAttribute() {
		return $this->formatearMonto($this->monto_por_pagar, $this->moneda);
	}

	public function items() {
		return $this->hasMany(Item::class);
	}

	public function unidad() {
		return $this->belongsTo(Unidad::class);
	}

	public function iva() {
		return $this->belongsTo(Iva::class);
	}

	public function interes() {
		return $this->belongsTo(Interes::class);
	}
}
