<?php

namespace App\Models;

use App\Traits\WithCurrencies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
	use HasFactory;
	use WithCurrencies;

	protected $fillable = [
		'itemable_id',
		'itemable_type',
		'monto',
		'fecha',
	];

	public function factura()
	{
		return $this->belongsTo(Factura::class);
	}

	public function itemable() {
		return $this->morphTo();
	}

	/**
	 * Pasa el monto de la moneda de su origen (mensualidad, gasto o sanción) a la moneda indicada.
	 */
	public function convertirA(string $moneda, $fecha = null): void
	{
		$this->monto = $this->convertirMonto($this->monto, $this->itemable->moneda, $moneda, $fecha);
	}
}
