<?php

namespace App\Exceptions;

use DomainException;

class MesYaCerrado extends DomainException
{
	public static function para(string $mes): self
	{
		return new self("El mes $mes ya ha sido cerrado.");
	}
}
