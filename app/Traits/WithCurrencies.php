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

	/**
	 * Monto a abonar en la moneda de destino para saldar una deuda con un pago en otra moneda.
	 * Cada conversión redondea a 2 decimales, así que pagar el total deja un resto de
	 * céntimos; si el resto vale menos de un céntimo de la moneda del pago, se salda exacto.
	 */
	public function abonoConvertido($monto, string $de, string $a, $saldo, $fecha = null): float
	{
		$abono = $this->convertirMonto($monto, $de, $a, $fecha);

		if ($de === $a) {
			return $abono;
		}

		// Un céntimo de la moneda del pago, en la moneda de destino (al menos un céntimo de esta)
		$centimo = max(0.01, 0.01 / $this->tasaDe($de, $fecha) * $this->tasaDe($a, $fecha));

		return abs($saldo - $abono) < $centimo ? (float) $saldo : $abono;
	}

	public function formatearMonto($monto, string $moneda): string
	{
		$formato = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);

		return $formato->formatCurrency((float) $monto, $moneda);
	}
}
