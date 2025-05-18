<?php

namespace App\Livewire\Admin\Usuario;

use App\Models\User;
use Livewire\Component;

class ShowUsuario extends Component
{
    public User $usuario;

    public function render()
    {
        return view('livewire.admin.usuario.show-usuario');
    }
}
