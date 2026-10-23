<?php

namespace App\Livewire\Forms;

use App\Models\Cuenta;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CuentaForm extends Form
{
    public ?Cuenta $cuenta = null;

    #[Validate('required')]
    public $letra = 'V';

    #[Validate('required|min:6|max:10')]
    public $documento = '';

    #[Validate('required|string|max:45')]
    public $beneficiario = '';

    #[Validate('required|numeric|digits:20')]
    public $numero = '';

    #[Validate('required|not_in:0')]
    public $banco_id = '';

    #[Validate('required|not_in:0')]
    public $tipo = '';

    #[Validate('nullable')]
    public $codigo = '0412';

    #[Validate('nullable|digits:7')]
    public $telefono = '';

    #[Validate('boolean')]
    public $publica = '0';

    public function setCuenta(Cuenta $cuenta): void
    {
        $this->cuenta = $cuenta;

        // documento is stored as "V-12345678" and telefono as "0412-1234567"
        $this->letra = Str::substr($cuenta->documento, 0, 1);
        $this->documento = Str::substr($cuenta->documento, 2);
        $this->codigo = $cuenta->telefono ? Str::substr($cuenta->telefono, 0, 4) : '0412';
        $this->telefono = Str::substr((string) $cuenta->telefono, 5, 7);

        $this->beneficiario = $cuenta->beneficiario;
        $this->numero = $cuenta->numero;
        $this->banco_id = $cuenta->banco_id;
        $this->tipo = $cuenta->tipo;
        $this->publica = $cuenta->publica ? '1' : '0';
    }

    public function update(): void
    {
        $this->validate();

        $this->cuenta->update([
            'documento' => $this->letra . '-' . $this->documento,
            'beneficiario' => $this->beneficiario,
            'numero' => $this->numero,
            'banco_id' => $this->banco_id,
            'tipo' => $this->tipo,
            'telefono' => $this->telefono ? $this->codigo . '-' . $this->telefono : null,
            'publica' => (bool) $this->publica,
        ]);
    }
}
