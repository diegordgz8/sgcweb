{{-- Modales para confirmar un pago sin fondo y para rechazar un pago con su motivo.
     Los usan la lista de pagos por confirmar y el detalle de un pago (WithConfirmacionPago). --}}
<x-dialog-modal wire:model.live="openConfirmar">
    <x-slot name="title">
        Confirmar pago
    </x-slot>

    <x-slot name="content">
        <h1 class="text-center text-lg mb-4">Seleccione el fondo donde se agregará el pago</h1>

        @if (count($fondos))
            <div class="grid grid-cols-2 gap-6">
                @foreach ($fondos as $item)
                    <x-card-fondo wire:click="aceptar({{ $item->id }})" :fondo="$item" class="cursor-pointer" />
                @endforeach
            </div>
        @else
            <p class="text-center text-sm text-gray-600">
                No hay fondos en la moneda de este pago donde pueda registrarse.
            </p>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="$set('openConfirmar', false)">
            Cancelar
        </x-secondary-button>
    </x-slot>
</x-dialog-modal>

<x-dialog-modal wire:model.live="openRechazar">
    <x-slot name="title">
        Rechazar pago
    </x-slot>

    <x-slot name="content">
        <label for="motivo-rechazo" class="block text-sm font-medium text-gray-700">
            Motivo del rechazo:
        </label>
        <textarea wire:model="motivoRechazo" id="motivo-rechazo" rows="3" class="form-control w-full"
            placeholder="Por ejemplo: la referencia no coincide con ninguna transferencia recibida."></textarea>
        <x-input-error for="motivoRechazo" />
        <p class="mt-2 text-xs text-gray-500">
            El propietario verá este motivo para corregir el pago y volver a enviarlo.
        </p>
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button class="mr-2" wire:click="$set('openRechazar', false)">
            Cancelar
        </x-secondary-button>

        <x-danger-button wire:click="rechazar" wire:loading.attr="disabled" class="disabled:opacity-25">
            Rechazar pago
        </x-danger-button>
    </x-slot>
</x-dialog-modal>
