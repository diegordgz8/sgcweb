<?php

namespace App\Livewire\PagoPropietario;

use App\Models\PagoPropietario;
use App\Traits\WithConfirmacionPago;
use Livewire\Component;

class ShowPago extends Component
{
	use WithConfirmacionPago;

	public PagoPropietario $pago;

    public function render()
    {
        return view('livewire.pago-propietario.show-pago');
    }

	/**
	 * Resuelto el pago, se vuelve a la lista de pagos por confirmar.
	 */
	protected function pagoResuelto(): void
	{
		$this->redirectRoute('pago.confirmar');
	}
}
