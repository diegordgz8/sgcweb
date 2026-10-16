<?php

namespace App\Models;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
	use HasFactory;
	use WithCurrencies;

	protected $table = 'pagos_gastos';

	protected $fillable = [
		'descripcion',
		'monto',
		'fecha',
		'recibo',
		'referencia',
		'forma_pago',
		'moneda',
	];

	public function gasto()
	{
		return $this->belongsTo(Gasto::class);
	}

	public function fondo()
	{
		return $this->belongsTo(Fondo::class);
	}

	/**
	 * Monto abonado al gasto, en la moneda del gasto.
	 */
	public function getMontoAbonadoAttribute(): float
	{
		return $this->convertirMonto($this->monto, $this->moneda, $this->gasto->moneda, $this->created_at);
	}

	/**
	 * Debita el fondo en la moneda del pago y abona al gasto el monto convertido a la moneda
	 * del gasto, con las tasas vigentes al registrar el pago.
	 */
	public function pagarGasto(): void
	{
		$this->fondo->debitar($this->convertirMonto($this->monto, $this->moneda, $this->fondo->moneda, $this->created_at));

		$this->gasto->pagar($this->convertirMonto($this->monto, $this->moneda, $this->gasto->moneda, $this->created_at));
	}
}
