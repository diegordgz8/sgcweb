<?php

namespace App\Traits;

use App\Models\Fondo;
use App\Models\PagoPropietario;
use Livewire\Attributes\Locked;

/**
 * Confirmar o rechazar el pago de un propietario. Lo usan la lista de pagos por confirmar
 * y el detalle de un pago; cada componente decide qué hacer después en pagoResuelto().
 */
trait WithConfirmacionPago
{
	#[Locked]
	public $pagoId;

	public $openConfirmar = false;
	public $fondos = [];

	public $openRechazar = false;
	public $motivoRechazo = '';

	/**
	 * Confirma el pago, o pide antes el fondo si el pago no trae uno (efectivo, cheque…).
	 */
	public function confirmar(PagoPropietario $pago)
	{
		$this->authorize('pago-propietario.confirmar');

		if (! $this->porConfirmar($pago)) {
			return false;
		}

		if ($pago->necesitaFondo()) {
			$this->pagoId = $pago->id;
			$this->fondos = Fondo::where('moneda', $pago->moneda)
				->when(
					in_array($pago->forma_pago, ['Efectivo', 'Cheque']),
					fn ($query) => $query->doesntHave('cuenta'),
					fn ($query) => $query->has('cuenta'),
				)
				->get();
			$this->openConfirmar = true;

			return false;
		}

		$pago->confirmar();

		flash()->success('El pago ha sido confirmado satisfactoriamente.');
		$this->pagoResuelto();
	}

	public function aceptar(Fondo $fondo)
	{
		$this->authorize('pago-propietario.confirmar');

		$pago = PagoPropietario::findOrFail($this->pagoId);

		if (! $this->porConfirmar($pago) || $fondo->moneda !== $pago->moneda) {
			return false;
		}

		$pago->confirmar($fondo);

		$this->reset('openConfirmar', 'pagoId', 'fondos');

		flash()->success('El pago ha sido confirmado satisfactoriamente.');
		$this->pagoResuelto();
	}

	public function abrirRechazar(PagoPropietario $pago)
	{
		$this->authorize('pago-propietario.confirmar');

		$this->pagoId = $pago->id;
		$this->reset('motivoRechazo');
		$this->resetValidation('motivoRechazo');
		$this->openRechazar = true;
	}

	public function rechazar()
	{
		$this->authorize('pago-propietario.confirmar');

		$this->validate(
			['motivoRechazo' => 'required|string|min:5|max:500'],
			[
				'motivoRechazo.required' => 'Indique el motivo del rechazo para que el propietario sepa qué corregir.',
				'motivoRechazo.min' => 'El motivo debe tener al menos :min caracteres.',
			],
		);

		$pago = PagoPropietario::findOrFail($this->pagoId);

		if (! $this->porConfirmar($pago)) {
			return false;
		}

		$pago->rechazar(trim($this->motivoRechazo));

		$this->reset('openRechazar', 'pagoId', 'motivoRechazo');

		flash()->success('El pago ha sido rechazado.');
		$this->pagoResuelto();
	}

	/**
	 * Un pago ya confirmado o rechazado no se vuelve a resolver.
	 */
	private function porConfirmar(PagoPropietario $pago): bool
	{
		if ($pago->estado !== 'Por confirmar') {
			flash()->error('Este pago ya fue ' . strtolower($pago->estado) . '.');

			return false;
		}

		return true;
	}

	/**
	 * Lo que hace el componente después de confirmar o rechazar un pago.
	 */
	abstract protected function pagoResuelto(): void;
}
