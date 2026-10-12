<?php

namespace App\Traits;

use NumberFormatter;

trait WithCurrencies
{
	/**
	 * Monedas que la lógica de facturación sabe manejar, por código ISO 4217,
	 * con el nombre con el que se guardan en la base de datos.
	 */
	public function monedasSoportadas(): array
	{
		return [
			'VES' => 'Bolívar',
			'USD' => 'Dólar',
		];
	}

	public function convertirMonto($monto, $moneda, $tasa)
	{
		if ($moneda == 'Bolívar') {
			$montoConvertido = $monto * $tasa;
		} else if ($moneda == 'Dólar') {
			$montoConvertido = $monto / $tasa;
		}

        return $montoConvertido;
	}

    public function formatearMonto($monto, $moneda)
    {
        $formatoDinero = new NumberFormatter('es_VE', NumberFormatter::CURRENCY);
        $bolivar = 'VES';
        $dolar = 'USD';

        if ($moneda == 'Bolívar') {
            $montoFormateado = $formatoDinero->formatCurrency($monto, $bolivar);
        } else if ($moneda == 'Dólar') {
            $montoFormateado = $formatoDinero->formatCurrency($monto, $dolar);
        }

        return $montoFormateado;
    }
}
