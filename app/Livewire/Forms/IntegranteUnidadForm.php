<?php

namespace App\Livewire\Forms;

use App\Models\Integrante;
use Livewire\Attributes\Validate;
use Livewire\Form;

/**
 * A resident of a unit, edited by the unit's owner.
 */
class IntegranteUnidadForm extends Form
{
    public ?Integrante $integrante = null;

    #[Validate('nullable')]
    public $letra = 'V';

    #[Validate('nullable|digits_between:6,8')]
    public $documento = '';

    #[Validate('required|max:20')]
    public $nombre = '';

    #[Validate('nullable|max:20')]
    public $s_nombre = '';

    #[Validate('required|max:20')]
    public $apellido = '';

    #[Validate('nullable|max:20')]
    public $s_apellido = '';

    #[Validate('required|before_or_equal:today')]
    public $fecha_nacimiento = '';

    #[Validate('nullable|email|max:45')]
    public $email = '';

    public function setIntegrante(Integrante $integrante): void
    {
        $this->integrante = $integrante;
        $this->letra = $integrante->letra;
        $this->documento = $integrante->documento;
        $this->nombre = $integrante->nombre;
        $this->s_nombre = $integrante->s_nombre;
        $this->apellido = $integrante->apellido;
        $this->s_apellido = $integrante->s_apellido;
        $this->fecha_nacimiento = $integrante->fecha_nacimiento;
        $this->email = $integrante->email;
    }

    public function update(string $telefono): void
    {
        $this->validate();

        $this->integrante->update($this->only([
            'letra',
            'documento',
            'nombre',
            's_nombre',
            'apellido',
            's_apellido',
            'fecha_nacimiento',
            'email',
        ]) + ['telefono' => $telefono]);
    }
}
