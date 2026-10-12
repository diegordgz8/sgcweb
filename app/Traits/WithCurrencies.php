<?php

namespace App\Traits;

use App\Exceptions\TasaNoDisponible;
use App\Models\Moneda;
use App\Models\TasaCambio;
use NumberFormatter;

/**
 * Conversión y formato de montos entre las monedas del condominio.
 * Toda conversión pasa por la moneda base con las tasas vigentes a la fecha indicada.
 */
trait WithCurrencies
{
	/**
	 * Unidades de la moneda que equivalen a 1 unidad de la moneda base.
	 */
	public function tasaDe(string $moneda, $fecha = null): float
	{
		if ($moneda === Moneda::base()) {
			return 1.0;
		}

		$tasa = TasaCambio::vigente($moneda, $fecha);

		if (! $tasa) {
			throw TasaNoDisponible::para($moneda, $fecha ?? now());
		}

		return (float) $tasa->tasa;
	}

	public function convertirMonto($monto, string $de, string $a, $fecha = null): float
	{
		if ($de === $a) {
			return (float) $monto;
		}

		return round($monto / $this->tasaDe($de, $fecha) * $this->tasaDe($a, $fecha), 2);
	}

	public function formatearMonto($monto, string $moneda): string
	{
		$formato = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);

		return $formato->formatCurrency((float) $monto, $moneda);
	}
}
