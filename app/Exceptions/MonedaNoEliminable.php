<?php

namespace App\Exceptions;

use DomainException;

class MonedaNoEliminable extends DomainException
{
	public static function para(string $codigo): self
	{
		return new self("La moneda $codigo no se puede eliminar.");
	}
}
