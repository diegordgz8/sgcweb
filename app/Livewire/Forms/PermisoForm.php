<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Spatie\Permission\Models\Permission;

class PermisoForm extends Form
{
    public ?Permission $permiso = null;

    public $name = '';

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('permissions', 'name')->ignore($this->permiso),
            ],
        ];
    }

    protected $validationAttributes = [
        'name' => 'nombre',
    ];

    public function setPermiso(Permission $permiso): void
    {
        $this->permiso = $permiso;
        $this->name = $permiso->name;
    }

    public function update(): void
    {
        $this->validate();

        $this->permiso->update($this->only('name'));
    }
}
