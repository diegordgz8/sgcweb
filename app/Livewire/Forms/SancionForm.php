<?php

namespace App\Livewire\Forms;

use App\Models\Moneda;
use App\Models\Sancion;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SancionForm extends Form
{
    public ?Sancion $sancion = null;

    public $monto = '';

    public $moneda = '';

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|gt:0',
            'moneda' => ['required', Rule::in(Moneda::codigos())],
        ];
    }

    public function setSancion(Sancion $sancion): void
    {
        $this->sancion = $sancion;
        $this->monto = $sancion->monto;
        $this->moneda = $sancion->moneda;
    }

    public function update(): void
    {
        $this->validate();

        $this->sancion->update($this->only(['monto', 'moneda']));
    }
}
