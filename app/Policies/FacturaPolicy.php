<?php

namespace App\Policies;

use App\Models\Factura;
use App\Models\User;

class FacturaPolicy
{
	/**
	 * An owner can only pay pending invoices of their own units.
	 */
	public function pagar(User $usuario, Factura $factura): bool
	{
		return $usuario->can('pago-propietario.create')
			&& $usuario->propietario !== null
			&& $factura->estado == 'Pendiente'
			&& $factura->unidad->propietario_id == $usuario->propietario->id;
	}
}
