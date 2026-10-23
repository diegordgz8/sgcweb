<?php

namespace App\Livewire\Forms;

use App\Models\Interes;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InteresForm extends Form
{
    public ?Interes $interes = null;

    #[Validate('required|numeric|gt:0|lte:5')]
    public $factor = '';

    #[Validate('required|numeric|gt:0')]
    public $meses = '';

    #[Validate('boolean')]
    public $estado = '';

    protected $messages = [
        'factor.gt' => 'El factor debe ser mayor a :value%.',
        'factor.lte' => 'El factor no puede ser mayor a :value%.',
    ];

    public function setInteres(Interes $interes): void
    {
        $this->interes = $interes;
        $this->factor = $interes->factor;
        $this->meses = $interes->meses;
        $this->estado = $interes->estado;
    }

    /**
     * Interest settings are versioned: a new factor or number of months appends a row,
     * while toggling only the state updates the current one.
     */
    public function update(): void
    {
        $this->validate();

        $actual = $this->interes;

        if (! $actual->exists || $actual->factor != $this->factor || $actual->meses != $this->meses) {
            $this->setInteres(Interes::create($this->only(['factor', 'meses', 'estado'])));
        } elseif ($actual->estado != $this->estado) {
            $actual->update($this->only('estado'));
        }
    }
}
