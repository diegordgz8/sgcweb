<?php

namespace App\Livewire\Forms;

use App\Models\Integrante;
use Livewire\Form;

/**
 * Resident data of the owner being assigned to a unit: an existing
 * resident found by its ID card, or a new one.
 */
class PropietarioForm extends Form
{
    public ?Integrante $integrante = null;

    public $nombre = '';

    public $s_nombre = '';

    public $apellido = '';

    public $s_apellido = '';

    public $fecha_nacimiento = '';

    public $email = '';

    protected function rules()
    {
        return [
            'nombre' => 'required|max:20',
            's_nombre' => 'nullable|max:20',
            'apellido' => 'required|max:20',
            's_apellido' => 'nullable|max:20',
            'fecha_nacimiento' => 'required|before_or_equal:today',
            'email' => 'required|email|max:45|unique:integrantes,email'.($this->integrante ? ','.$this->integrante->id : ''),
        ];
    }

    public function setIntegrante(Integrante $integrante): void
    {
        $this->integrante = $integrante;
        $this->nombre = $integrante->nombre;
        $this->s_nombre = $integrante->s_nombre;
        $this->apellido = $integrante->apellido;
        $this->s_apellido = $integrante->s_apellido;
        $this->fecha_nacimiento = $integrante->fecha_nacimiento;
        $this->email = $integrante->email;
    }

    /**
     * @param  array{letra: string, documento: string, telefono: string}  $identificacion
     */
    public function store(array $identificacion): Integrante
    {
        $this->validate();

        $integrante = $this->integrante ?? new Integrante;
        $integrante->fill($this->only([
            'nombre',
            's_nombre',
            'apellido',
            's_apellido',
            'fecha_nacimiento',
            'email',
        ]) + $identificacion);
        $integrante->save();

        return $integrante;
    }
}
