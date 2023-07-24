<div>

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Nueva unidad
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6 sm:col-span-3">
                                <label for="numero" class="block text-sm font-medium text-gray-700">Número:</label>
                                <input type="text" name="numero" id="numero" class="form-control mt-1 w-full"
                                    wire:model="numero">
                                <x-input-error for="numero" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de unidad:</label>
                                <select wire:model="tipo.id" name="tipo" id="tipo" class="form-control mt-1 w-full">
                                    <option value="0"> -- </option>
                                    @foreach ($tipoUnidades as $item)
                                        <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="tipo.id" />
                            </div>

                            <div class="col-span-6">
                                <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" name="direccion" id="direccion" class="form-control mt-1 w-full"
                                    wire:model="direccion">
                                <x-input-error for="direccion" />
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

            <x-button wire:click="save()">
                Registrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

</div>
