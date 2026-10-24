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
 *
 * previsualizar() computes exactly what cerrar() would charge without writing anything,
 * so the month close can be reviewed before it is confirmed.
 */
class CierreMesService
{
	private string $mes;
	private string $moneda;
	private ?Interes $interes;
	private Iva $iva;
	private Mensualidad $mensualidad;
	private int $numeroUnidades;
	private float $areaTotal;
	private Collection $gastosOrdinarios;
	private Collection $gastosExtraordinarios;

	public function cerrar(string $mes, string $moneda): CierreMes
	{
		$this->comprobarAbierto($mes);

		return DB::transaction(function () use ($mes, $moneda) {
			$cierre = CierreMes::create([
				'mes' => $mes,
				'moneda' => $moneda,
			]);

			foreach ($this->preparar($mes, $moneda, $cierre->created_at) as $borrador) {
				$this->guardarFactura($borrador, $cierre);
			}

			$this->actualizarEstadoGastos();

			return $cierre;
		});
	}

	/**
	 * What the close would generate: one draft invoice per unit, the expenses and sanctions
	 * included, and the totals. Nothing is saved.
	 */
	public function previsualizar(string $mes, string $moneda): array
	{
		$this->comprobarAbierto($mes);

		$borradores = $this->preparar($mes, $moneda, now());

		return [
			'mes' => $mes,
			'moneda' => $moneda,
			'facturas' => $borradores->map(fn (array $b) => [
				'unidad' => $b['unidad']->numero,
				'monto' => $b['factura']->monto,
				'con_interes' => $b['factura']->interes_id !== null,
			])->values()->all(),
			'gastos' => $this->gastosOrdinarios->concat($this->gastosExtraordinarios)->map(fn (Gasto $gasto) => [
				'descripcion' => $gasto->descripcion,
				'tipo' => $gasto->tipo,
				'cuota' => $gasto->getMontoCuota(),
				'moneda' => $gasto->moneda,
				'ultima_cuota' => $gasto->tipo == 'Extraordinario' && $gasto->getUltimoMesCobro() <= $mes,
			])->values()->all(),
			'sanciones' => $borradores->sum(fn (array $b) => $b['sanciones']->count()),
			'total' => $borradores->sum(fn (array $b) => $b['factura']->monto),
		];
	}

	private function comprobarAbierto(string $mes): void
	{
		if (CierreMes::where('mes', $mes)->exists()) {
			throw MesYaCerrado::para($mes);
		}
	}

	/**
	 * Builds the unsaved invoice, items and sanctions for every unit with an owner.
	 */
	private function preparar(string $mes, string $moneda, Carbon $fecha): Collection
	{
		$this->mes = $mes;
		$this->moneda = $moneda;

		$unidades = Unidad::has('propietario')->get();

		$this->numeroUnidades = $unidades->count();
		$this->areaTotal = Unidad::getAreaTotal();
		$this->mensualidad = Mensualidad::orderBy('created_at', 'desc')->first();
		$this->interes = Interes::orderBy('created_at', 'desc')->first();
		$this->iva = Iva::orderBy('created_at', 'desc')->first();

		$this->gastosOrdinarios = $this->gastosOrdinarios();
		$this->gastosExtraordinarios = $this->gastosExtraordinarios();
		$gastos = $this->gastosOrdinarios->concat($this->gastosExtraordinarios);

		return $unidades->map(fn (Unidad $unidad) => $this->borrador($unidad, $gastos, $fecha));
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

	private function borrador(Unidad $unidad, Collection $gastos, Carbon $fecha): array
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

		$sanciones = $unidad->sanciones()->where('estado', 'Por procesar')->get();

		foreach ($sanciones as $sancion) {
			$items->push(Item::make([
				'itemable_id' => $sancion->id,
				'itemable_type' => Sancion::class,
				'monto' => $sancion->monto,
			]));
		}

		$montoFactura = 0;

		// Cada item está en la moneda de su origen; se pasa a la de la factura con las tasas del cierre
		foreach ($items as $item) {
			$item->convertirA($this->moneda, $fecha);
			$montoFactura += $item->monto;
		}

		$factura = Factura::make([
			'numero' => Str::replace('-', '', $this->mes) . '-' . $unidad->numero . '-1',
			'monto' => $montoFactura,
			'monto_por_pagar' => $montoFactura,
			'moneda' => $this->moneda,
			'fecha' => now(),
			'unidad_id' => $unidad->id,
			'iva_id' => $this->iva->id,
		]);

		$this->aplicarInteres($factura, $unidad);

		return compact('unidad', 'factura', 'items', 'sanciones');
	}

	private function guardarFactura(array $borrador, CierreMes $cierre): void
	{
		$borrador['factura']->cierre_mes_id = $cierre->id;
		$borrador['factura']->save();

		foreach ($borrador['items'] as $item) {
			$item->factura()->associate($borrador['factura']->id);
			$item->save();
		}

		foreach ($borrador['sanciones'] as $sancion) {
			$sancion->pivot->estado = 'Procesada';
			$sancion->pivot->save();
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

	private function actualizarEstadoGastos(): void
	{
		foreach ($this->gastosOrdinarios as $gasto) {
			$gasto->estado_cobro = 'Procesado';
			$gasto->save();
		}

		foreach ($this->gastosExtraordinarios as $gasto) {
			$gasto->estado_cobro = $gasto->getUltimoMesCobro() <= $this->mes ? 'Procesado' : 'En proceso';
			$gasto->save();
		}
	}
}
