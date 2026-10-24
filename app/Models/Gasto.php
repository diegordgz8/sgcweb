<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Gasto extends Model
{
	use HasFactory;
	use SoftDeletes;

	protected $fillable = [
		'descripcion',
		'tipo',
		'calculo_por',
		'mes_cobro',
		'moneda',
		'monto',
		'saldo',
		'observaciones',
		'factura',
		'proveedor_id',
	];

	public function pagar(float $monto)
	{
		$this->saldo -= $monto;

		if ($this->saldo <= 0) {
			$this->estado_pago = 'Pagado';
		}

		$this->save();
	}

	/**
	 * Last month (Y-m, inclusive) in which the expense is charged.
	 */
	public function getUltimoMesCobro(): string
	{
		$meses = $this->tipo == 'Extraordinario' ? $this->extraordinario->num_meses : 1;

		return Carbon::createFromFormat('!Y-m', $this->mes_cobro)
			->addMonthsNoOverflow($meses - 1)
			->format('Y-m');
	}

	/**
	 * Amount charged per close: the full amount, or one installment for extraordinary expenses.
	 */
	public function getMontoCuota(): float
	{
		if ($this->tipo == 'Extraordinario') {
			return $this->monto / $this->extraordinario->num_meses;
		}

		return $this->monto;
	}

	public function proveedor()
	{
		return $this->belongsTo(Proveedor::class)->withTrashed();
	}

	public function extraordinario()
	{
		return $this->hasOne(GastoExtraordinario::class);
	}

	public function servicios()
	{
		return $this->belongsToMany(Servicio::class)->withPivot('monto')->withTimestamps();
	}

	public function factura()
	{
		return $this->morphOne(Factura::class, 'facturable');
	}
}
