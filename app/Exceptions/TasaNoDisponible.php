<?php

namespace App\Exceptions;

use DomainException;

class TasaNoDisponible extends DomainException
{
	public static function para(string $moneda, $fecha): self
	{
		return new self("No hay una tasa de cambio para $moneda vigente al $fecha.");
	}
}
