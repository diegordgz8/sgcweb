<div>

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Nuevo comunicado
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6">
                                <label for="asunto" class="block text-sm font-medium text-gray-700">Asunto</label>
                                <input wire:model.lazy="asunto" type="text" name="asunto" id="asunto"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="asunto" />
                            </div>

                            <div class="col-span-6">
                                <label for="contenido"
                                    class="block text-sm font-medium text-gray-700">Contenido</label>
                                <textarea wire:model.lazy="contenido" name="contenido" id="contenido" rows="15" class="mt-1 w-full form-control"></textarea>
                                <x-input-error for="contenido" />
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
