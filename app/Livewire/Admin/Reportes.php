<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Reportes extends Component
{
    public $open = false;

    public function render()
    {
        return view('livewire.admin.reportes');
    }
}
