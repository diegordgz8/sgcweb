<?php

namespace App\Livewire\Forms;

use App\Models\Gasto;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GastoForm extends Form
{
    public ?Gasto $gasto = null;

    // Total of the service amounts, recomputed as they change
    public $monto = 0;

    // Amount per service id
    #[Validate(['montos.*' => 'required|numeric|min:0'])]
    public $montos = [];

    public function setGasto(Gasto $gasto): void
    {
        $this->gasto = $gasto;
        $this->monto = $gasto->monto;
        $this->montos = [];

        foreach ($gasto->servicios as $servicio) {
            $this->montos[$servicio->id] = $servicio->pivot->monto;
        }
    }

    public function sumarMontos(): void
    {
        $servicios = $this->gasto->servicios->pluck('id')->toArray();

        $this->monto = 0;

        foreach ($this->montos as $id => $monto) {
            if (is_numeric($monto) && in_array($id, $servicios)) {
                $this->monto += $monto;
            }
        }
    }

    public function update(): void
    {
        $this->validate();

        $this->sumarMontos();

        foreach ($this->gasto->servicios as $servicio) {
            $servicio->pivot->monto = $this->montos[$servicio->id];
            $servicio->pivot->save();
        }

        $this->gasto->monto = $this->monto;
        $this->gasto->save();
    }
}
