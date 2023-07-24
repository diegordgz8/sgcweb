<div>

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Nueva cuenta
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6 sm:col-span-3">

                                <label for="documento" class="block text-sm font-medium text-gray-700">Documento:</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 flex items-center">
                                        <select id="letra" name="letra"
                                            class="focus:ring-indigo-500 focus:border-indigo-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md"
                                            wire:model="letra">
                                            <option value="V">V</option>
                                            <option value="E">E</option>
                                            <option value="J">J</option>
                                        </select>
                                    </div>
                                    <input type="text" name="documento" id="documento"
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="Cédula o RIF" wire:model="documento">
                                </div>
                                <x-input-error for="documento" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="beneficiario"
                                    class="block text-sm font-medium text-gray-700">Beneficiario:</label>
                                <input type="text" name="beneficiario" id="beneficiario"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    wire:model="beneficiario">
                                <x-input-error for="beneficiario" />
                            </div>

                            <div class="col-span-6">
                                <label for="numero" class="block text-sm font-medium text-gray-700">Número de
                                    cuenta:</label>
                                <input type="text" name="numero" id="numero" autocomplete="street-address"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    wire:model="numero">
                                <x-input-error for="numero" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="banco_id" class="block text-sm font-medium text-gray-700">Banco:</label>
                                <select id="banco_id" name="banco_id" class="form-control w-full" wire:model="banco_id">
                                    <option value="0"> -- </option>
                                    @foreach ($bancos as $item)
                                        <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="banco_id" />
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de
                                    cuenta:</label>
                                <select id="tipo" name="tipo" class="form-control w-full" wire:model="tipo">
                                    <option value="0"> -- </option>
                                    <option>Ahorro</option>
                                    <option>Corriente</option>
                                </select>
                                <x-input-error for="tipo" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono afiliado
                                    a pago móvil</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 flex items-center">
                                        <select wire:model="codigo" id="codigo" name="codigo"
                                            class="focus:ring-indigo-500 focus:border-indigo-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md">
                                            <option>0412</option>
                                            <option>0414</option>
                                            <option>0416</option>
                                            <option>0424</option>
                                            <option>0426</option>
                                        </select>
                                    </div>
                                    <input wire:model.lazy="telefono" type="text" name="telefono" id="telefono"
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-16 sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <x-input-error for="telefono" />
                            </div>

                            <div class="col-span-6">
                                <label class="block text-sm font-medium text-gray-700">
                                    ¿Permitir que los propietarios registren pagos a esta cuenta?
                                </label>
                                <div>
                                    <input wire:model="publica" type="radio" name="publica" id="si" value="1">
                                    <label for="si">Sí</label>
                                    <input wire:model="publica" type="radio" name="publica" id="no" value="0"
                                        class="ml-2">
                                    <label for="no">No</label>
                                </div>
                                <x-input-error for="publica" />
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
