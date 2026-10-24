<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Unidad extends Model
{
	use HasFactory;

	protected $fillable = ['numero', 'direccion', 'tipo_unidad_id', 'propietario_id'];
	protected $table = 'unidades';

	public static function getAreaTotal(): float
	{
		return DB::table('unidades')
			->join('tipo_unidades', 'unidades.tipo_unidad_id', '=', 'tipo_unidades.id')
			->sum('area');
	}

	public function getRouteKeyName()
	{
		return 'numero';
	}

	public function integrantes()
	{
		return $this->hasMany(Integrante::class);
	}

	public function visitas()
	{
		return $this->hasMany(Visita::class);
	}

	public function tipoUnidad()
	{
		return $this->belongsTo(TipoUnidad::class);
	}

	public function propietario()
	{
		return $this->belongsTo(Propietario::class);
	}

	public function sanciones()
	{
		return $this->belongsToMany(Sancion::class);
	}

	public function facturas()
	{
		return $this->hasMany(Factura::class);
	}

	public function ajustesSaldo()
	{
		return $this->hasMany(AjusteSaldo::class);
	}

	/**
	 * Convierte los faltantes pendientes en una factura que se puede pagar ya, en la moneda
	 * de facturación, en lugar de esperar al próximo cierre. Devuelve null si no hay faltantes.
	 */
	public function facturarFaltantes(): ?Factura
	{
		return DB::transaction(function () {
			$faltantes = $this->ajustesSaldo()->pendientes()->where('tipo', 'Faltante')->lockForUpdate()->get();

			if ($faltantes->isEmpty()) {
				return null;
			}

			$moneda = Moneda::facturacion();

			$items = $faltantes->map(function (AjusteSaldo $faltante) use ($moneda) {
				$item = Item::make([
					'itemable_id' => $faltante->id,
					'itemable_type' => AjusteSaldo::class,
					'monto' => $faltante->monto,
				]);
				$item->convertirA($moneda);

				return $item;
			});

			$prefijo = 'F' . now()->format('Ym') . '-' . $this->numero . '-';
			$monto = $items->sum('monto');

			$factura = Factura::create([
				'numero' => $prefijo . (Factura::where('numero', 'like', "$prefijo%")->count() + 1),
				'monto' => $monto,
				'monto_por_pagar' => $monto,
				'moneda' => $moneda,
				'fecha' => now()->toDateString(),
				'deuda_desde' => $faltantes->min(fn (AjusteSaldo $faltante) => $faltante->deuda_desde->toDateString()),
				'unidad_id' => $this->id,
				'iva_id' => Iva::orderBy('created_at', 'desc')->firstOrFail()->id,
			]);

			$factura->items()->saveMany($items);

			AjusteSaldo::whereIn('id', $faltantes->pluck('id'))->update(['factura_aplicada_id' => $factura->id]);

			// Recargada para tener los valores por defecto de la base, como estado = Pendiente
			return $factura->fresh();
		});
	}

	public function pagos()
	{
		return $this->hasMany(PagoPropietario::class);
	}
}
