<?php

namespace App\Livewire\Unidad;

use App\Models\Categoria;
use App\Models\Unidad;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TablaUnidad extends Component
{
	public $unidad;

	public $busqueda;
	public $orden = 'numero';
	public $direccion = "asc";
	public $cantidad = '10';

	public $readyToLoad = false;

	public $openEdit = false;
	public $openDestroy = false;

	protected $rules = [
		'categoria.nombre' => 'required|max:25',
		'categoria.descripcion' => 'max:255',
	];

	protected $listeners = ['render'];

	public function mount()
	{
		$this->unidad = new Unidad;
	}

	public function render()
	{
		if ($this->readyToLoad) {
			$unidades = Auth::user()->propietario->unidades()
				->where(function ($query) {
					$query->where('numero', 'LIKE', '%' . $this->busqueda . '%')
						->orWhere('direccion', 'LIKE', '%' . $this->busqueda . '%');
				})
				->orderBy($this->orden, $this->direccion)
				->paginate($this->cantidad);
		} else {
			$unidades = [];
		}

		return view('livewire.unidad.tabla-unidad', compact('unidades'));
	}

	public function loadUnidades()
	{
		$this->readyToLoad = true;
	}

	public function orden($orden)
	{
		if ($this->orden == $orden) {
			if ($this->direccion == 'desc') {
				$this->direccion = 'asc';
			} else {
				$this->direccion = 'desc';
			}
		} else {
			$this->orden = $orden;
			$this->direccion = 'asc';
		}
	}

	// public function enviarMensaje()
	// {
	// 	$basic  = new \Vonage\Client\Credentials\Basic("d356dff6", "oifVKJxfyOeVPM08");
	// 	$client = new \Vonage\Client($basic);

	// 	$mensaje = 'Esta es una notificación de SGC Web. Por favor, no olvide mantenerse al día con el pago de las facturas.';

	// 	$response = $client->sms()->send(
	// 		new \Vonage\SMS\Message\SMS("584121512128", 'Brand?', $mensaje)
	// 	);

	// 	$message = $response->current();

	// 	if ($message->getStatus() == 0) {
	// 		$this->dispatch('alert', message: 'El mensaje se ha realizado satisfactoriamente');
	// 		$this->error = "The message was sent successfully\n";
	// 	} else {
	// 		$this->dispatch('alert', message: 'El mensaje NO se ha realizado satisfactoriamente');
	// 		$this->error = "The message failed with status: " . $message->getStatus() . "\n";
	// 	}
	// }
}
