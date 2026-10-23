<?php

namespace App\Livewire\Forms;

use App\Models\Proveedor;
use Livewire\Form;

class ProveedorForm extends Form
{
    public ?Proveedor $proveedor = null;

    // Shown read-only in the edit modal; not updated
    public $letra = '';
    public $documento = '';

    public $nombre = '';
    public $contacto = '';
    public $telefono = '';
    public $email = '';
    public $direccion = '';

    protected function rules()
    {
        return [
            'nombre' => 'required',
            'contacto' => 'required',
            'telefono' => [
                'required',
                'regex:/\d{4}-\d{7}/',
            ],
            'email' => 'nullable|email|unique:proveedores,email,' . $this->proveedor?->id,
            'direccion' => 'nullable',
        ];
    }

    public function setProveedor(Proveedor $proveedor): void
    {
        $this->proveedor = $proveedor;
        $this->letra = $proveedor->letra;
        $this->documento = $proveedor->documento;
        $this->nombre = $proveedor->nombre;
        $this->contacto = $proveedor->contacto;
        $this->telefono = $proveedor->telefono;
        $this->email = $proveedor->email;
        $this->direccion = $proveedor->direccion;
    }

    public function update(): void
    {
        $this->validate();

        $this->proveedor->update($this->only(['nombre', 'contacto', 'telefono', 'email', 'direccion']));
    }
}
