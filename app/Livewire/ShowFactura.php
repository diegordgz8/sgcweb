<?php

namespace App\Livewire;

use App\Models\Condominio;
use App\Models\Factura;
use App\Traits\WithCurrencies;
use Livewire\Component;

class ShowFactura extends Component
{
	use WithCurrencies;

	public Factura $factura;

	public $countItems = 0;
	public $sub = 0;
	public $subConInteres = 0;
	public $montoIva = 0;
	public $montoInteres = 0;
	public $total = 0;

	public function mount()
	{
		foreach ($this->factura->items as $item) {
			$item->montoSinIva = $this->revertirIva($item->monto);

			$this->sub += $item->montoSinIva;
		}

		// Interest is charged on the pre-tax base, and VAT applies to base plus interest
		if ($this->factura->interes) {
			$this->montoInteres = $this->sub * ($this->factura->interes->factor / 100);
		}

		$this->subConInteres = $this->sub + $this->montoInteres;
		$this->montoIva = $this->subConInteres * ($this->factura->iva->factor / 100);
		$this->total = $this->subConInteres + $this->montoIva;
	}

	public function render()
	{
		$condominio = Condominio::first();

		return view('livewire.show-factura', compact('condominio'));
	}

	private function revertirIva($monto)
	{
		return $monto / (($this->factura->iva->factor / 100) + 1);
	}
}
