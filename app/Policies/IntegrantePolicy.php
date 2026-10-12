<?php

namespace App\Policies;

use App\Models\Integrante;
use App\Models\User;

class IntegrantePolicy
{
	/**
	 * Staff can see any resident; an owner only the residents of their units.
	 */
	public function view(User $usuario, Integrante $persona): bool
	{
		if ($usuario->can('integrante.index')) {
			return true;
		}

		return $usuario->can('integrante.show')
			&& $usuario->propietario !== null
			&& $persona->unidad !== null
			&& $persona->unidad->propietario_id == $usuario->propietario->id;
	}
}
