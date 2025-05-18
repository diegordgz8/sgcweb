<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Respaldo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4">

                <div class="space-y-2">
                    <x-btn-admin wire:click="respaldar" nombre="Respaldar" imagen="img/iconos/respaldo.png" class="cursor-pointer" wire:loading.attr="hidden" />

                    <div wire:loading class="bg-white border rounded-lg shadow w-full p-4">
                        <span class="text-red-500 text-xl font-semibold">Procesando, por favor espere...</span>
                    </div>

                    <x-btn-admin wire:click="$set('openRestaurar', true)" nombre="Restaurar" imagen="img/iconos/recuperacion.png"
                        class="cursor-pointer" />
                </div>

            </div>
        </div>
    </div>

    <x-dialog-modal wire:model.live="openRestaurar">
        <x-slot name="title">
            Restaurar
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                <h1 class="text-center text-lg mb-4">Seleccione el archivo que usará para la restauración</h1>

                <select wire:model.live="zippedBackup" name="backup" id="backup" class="form-control">
                    <option value="0">----</option>

                    @foreach ($files as $item)
                        <option>{{ $item }}</option>
                    @endforeach

                </select>
				<x-input-error for="backup" />
            </div>

                <div wire:loading class="p-2">
                    <span class="text-red-500">Restaurando...</span>
                </div>

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('openRestaurar', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="restaurar">
                Restaurar
            </x-button>
        </x-slot>
    </x-dialog-modal>

</div>
