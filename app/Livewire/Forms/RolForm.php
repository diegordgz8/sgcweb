<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Spatie\Permission\Models\Role;

class RolForm extends Form
{
    public ?Role $rol = null;

    public $name = '';

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'name')->ignore($this->rol),
            ],
        ];
    }

    protected $validationAttributes = [
        'name' => 'nombre',
    ];

    public function setRol(Role $rol): void
    {
        $this->rol = $rol;
        $this->name = $rol->name;
    }

    public function update(): void
    {
        $this->validate();

        $this->rol->update($this->only('name'));
    }
}
