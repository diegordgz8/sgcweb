<div>

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model.live="open">
        <x-slot name="title">
            Nueva categoría
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6">
                                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" name="nombre" id="nombre"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    wire:model.live="nombre">
                                <x-input-error for="nombre" />
                            </div>

                            <div class="col-span-6">
                                <label for="descripcion"
                                    class="block text-sm font-medium text-gray-700">Descripción</label>
                                <input type="text" name="descripcion" id="descripcion"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    wire:model.live="descripcion">
                                <x-input-error for="descripcion" />
                            </div>
                        </div>
                    </div>
                </div>
                {{-- /formulario --}}

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('open', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="save">
                Registrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

</div>
