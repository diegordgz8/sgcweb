<?php

namespace App\Services;

use App\Exceptions\MesYaCerrado;
use App\Models\CierreMes;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Interes;
use App\Models\Item;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Sancion;
use App\Models\Unidad;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Closes a month: creates one invoice per unit with an owner, charging the monthly fee,
 * the expenses due that month and the unit's pending sanctions.
 */
class CierreMesService
{
	private string $mes;
	private string $moneda;
	private CierreMes $cierre;
	private ?Interes $interes;
	private Iva $iva;
	private Mensualidad $mensualidad;
	private int $numeroUnidades;
	private float $areaTotal;

	public function cerrar(string $mes, string $moneda): CierreMes
	{
		if (CierreMes::where('mes', $mes)->exists()) {
			throw MesYaCerrado::para($mes);
		}

		$this->mes = $mes;
		$this->moneda = $moneda;

		return DB::transaction(function () {
			$this->cierre = CierreMes::create([
				'mes' => $this->mes,
				'moneda' => $this->moneda,
			]);

			$unidades = Unidad::has('propietario')->get();

			$this->numeroUnidades = $unidades->count();
			$this->areaTotal = Unidad::getAreaTotal();
			$this->mensualidad = Mensualidad::orderBy('created_at', 'desc')->first();
			$this->interes = Interes::orderBy('created_at', 'desc')->first();
			$this->iva = Iva::orderBy('created_at', 'desc')->first();

			$gastosOrdinarios = $this->gastosOrdinarios();
			$gastosExtraordinarios = $this->gastosExtraordinarios();
			$gastos = $gastosOrdinarios->concat($gastosExtraordinarios);

			foreach ($unidades as $unidad) {
				$this->facturar($unidad, $gastos);
			}

			$this->actualizarEstadoGastos($gastosOrdinarios, $gastosExtraordinarios);

			return $this->cierre;
		});
	}

	/**
	 * Ordinary expenses are charged once, in the first close on or after mes_cobro.
	 */
	private function gastosOrdinarios(): Collection
	{
		return Gasto::where('tipo', 'Ordinario')
			->where('mes_cobro', '<=', $this->mes)
			->where('estado_cobro', 'Pendiente')
			->get();
	}

	/**
	 * Extraordinary expenses are charged in installments until their last month.
	 */
	private function gastosExtraordinarios(): Collection
	{
		return Gasto::with('extraordinario')
			->where('tipo', 'Extraordinario')
			->where('mes_cobro', '<=', $this->mes)
			->whereIn('estado_cobro', ['Pendiente', 'En proceso'])
			->get()
			->filter(fn (Gasto $gasto) => $gasto->getUltimoMesCobro() >= $this->mes);
	}

	private function facturar(Unidad $unidad, Collection $gastos): void
	{
		$items = collect([
			Item::make([
				'itemable_id' => $this->mensualidad->id,
				'itemable_type' => Mensualidad::class,
				'monto' => $this->mensualidad->monto,
			]),
		]);

		foreach ($gastos as $gasto) {
			$items->push(Item::make([
				'itemable_id' => $gasto->id,
				'itemable_type' => Gasto::class,
				'monto' => $this->montoPorUnidad($gasto, $unidad),
			]));
		}

		foreach ($unidad->sanciones()->where('estado', 'Por procesar')->get() as $sancion) {
			$items->push(Item::make([
				'itemable_id' => $sancion->id,
				'itemable_type' => Sancion::class,
				'monto' => $sancion->monto,
			]));

			$sancion->pivot->estado = 'Procesada';
			$sancion->pivot->save();
		}

		$montoFactura = 0;

		// Cada item está en la moneda de su origen; se pasa a la de la factura con las tasas del cierre
		foreach ($items as $item) {
			$item->convertirA($this->moneda, $this->cierre->created_at);
			$montoFactura += $item->monto;
		}

		$factura = Factura::make([
			'numero' => Str::replace('-', '', $this->mes) . '-' . $unidad->numero . '-1',
			'monto' => $montoFactura,
			'monto_por_pagar' => $montoFactura,
			'moneda' => $this->moneda,
			'fecha' => now(),
			'cierre_mes_id' => $this->cierre->id,
			'unidad_id' => $unidad->id,
			'iva_id' => $this->iva->id,
		]);

		$this->aplicarInteres($factura, $unidad);

		$factura->save();

		foreach ($items as $item) {
			$item->factura()->associate($factura->id);
			$item->save();
		}
	}

	private function aplicarInteres(Factura $factura, Unidad $unidad): void
	{
		if (! $this->interes || ! $this->interes->estado) {
			return;
		}

		$facturaMasVieja = $unidad->facturas()->orderBy('fecha', 'asc')->first();

		// Carbon 3 returns a signed diff, so measure from the old invoice forward
		if ($facturaMasVieja && Carbon::parse($facturaMasVieja->fecha)->diffInMonths(Carbon::today()) >= $this->interes->meses) {
			$factura->interes()->associate($this->interes);

			// Items already include VAT and VAT also applies to the interest, so scaling the
			// total is the same as charging interest on the pre-tax base and adding VAT after.
			$factura->monto *= 1 + ($this->interes->factor / 100);
			$factura->monto_por_pagar = $factura->monto;
		}
	}

	private function montoPorUnidad(Gasto $gasto, Unidad $unidad): float
	{
		$monto = $gasto->getMontoCuota();

		if ($gasto->calculo_por == 'Alícuota') {
			return $monto * ($unidad->tipoUnidad->area / $this->areaTotal);
		}

		return $monto / $this->numeroUnidades;
	}

	private function actualizarEstadoGastos(Collection $gastosOrdinarios, Collection $gastosExtraordinarios): void
	{
		foreach ($gastosOrdinarios as $gasto) {
			$gasto->estado_cobro = 'Procesado';
			$gasto->save();
		}

		foreach ($gastosExtraordinarios as $gasto) {
			$gasto->estado_cobro = $gasto->getUltimoMesCobro() <= $this->mes ? 'Procesado' : 'En proceso';
			$gasto->save();
		}
	}
}
