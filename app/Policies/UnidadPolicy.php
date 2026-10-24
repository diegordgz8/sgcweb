<?php

namespace App\Policies;

use App\Models\Unidad;
use App\Models\User;

class UnidadPolicy
{
	/**
	 * Staff can see any unit; an owner only their own.
	 */
	public function view(User $usuario, Unidad $unidad): bool
	{
		return $usuario->can('admin.unidad.index')
			|| ($usuario->can('unidad.show') && $this->esPropietario($usuario, $unidad));
	}

	/**
	 * Managing a unit's residents: staff, or the unit's owner.
	 */
	public function update(User $usuario, Unidad $unidad): bool
	{
		return $usuario->can('admin.unidad.edit')
			|| ($usuario->can('unidad.edit') && $this->esPropietario($usuario, $unidad));
	}

	/**
	 * Paying a unit's shortfall before the next close: only its owner.
	 */
	public function pagarFaltante(User $usuario, Unidad $unidad): bool
	{
		return $usuario->can('pago-propietario.create') && $this->esPropietario($usuario, $unidad);
	}

	private function esPropietario(User $usuario, Unidad $unidad): bool
	{
		return $usuario->propietario !== null
			&& $unidad->propietario_id == $usuario->propietario->id;
	}
}
