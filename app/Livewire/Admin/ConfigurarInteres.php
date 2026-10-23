<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\InteresForm;
use App\Models\Interes;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConfigurarInteres extends Component
{
    public $open = false;

    public InteresForm $form;

    public $busqueda = '';
    #[Locked]
    public $orden = 'created_at';
    #[Locked]
    public $direccion = 'desc';
    public $cantidad = '10';

    public function mount()
    {
        $this->form->setInteres(Interes::orderBy('created_at', 'desc')->firstOrNew());
    }

    public function render()
    {
        $intereses = Interes::where('fecha', 'LIKE', '%' . $this->busqueda . '%')
            ->orderBy($this->orden, $this->direccion)
            ->paginate($this->cantidad);

        return view('livewire.admin.configurar-interes', compact('intereses'));
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function actualizar()
    {
        $this->authorize('admin.home');

        $this->form->update();

        $this->reset('open');

        flash()->success('El interés fue actualizado con éxito');
    }
}
