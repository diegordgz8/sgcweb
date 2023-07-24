<div>

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Nuevo integrante
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6 sm:col-span-3">
                                <label for="documento" class="block text-sm font-medium text-gray-700">Cédula:</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 flex items-center">
                                        <select wire:model="letra" id="letra" name="letra"
                                            class="focus:ring-indigo-500 focus:border-indigo-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md">
                                            <option>V</option>
                                            <option>E</option>
                                        </select>
                                    </div>
                                    <input wire:model="documento" type="text" name="documento" id="documento"
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <x-input-error for="documento" />
                            </div>

                            <div class="col-span-6 sm:col-span-3 sm:col-start-1">
                                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre:</label>
                                <input wire:model="nombre" type="text" name="nombre" id="nombre"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="nombre" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="s_nombre" class="block text-sm font-medium text-gray-700">Segundo
                                    nombre:</label>
                                <input wire:model="segundoNombre" type="text" name="s_nombre" id="s_nombre"
                                    autocomplete="street-address"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="segundoNombre" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido:</label>
                                <input wire:model="apellido" type="text" name="apellido" id="apellido"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="apellido" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="s_apellido" class="block text-sm font-medium text-gray-700">Segundo
                                    apellido:</label>
                                <input wire:model="segundoApellido" type="text" name="s_apellido" id="s_apellido"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="segundoApellido" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700">Fecha de
                                    nacimiento:</label>
                                <input wire:model="fecha_nacimiento" type="date" name="fecha_nacimiento"
                                    id="fecha_nacimiento"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="fecha_nacimiento" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="edad" class="block text-sm font-medium text-gray-700">Edad:</label>
                                <input wire:model="edad" type="text" name="edad"
                                    id="edad" readonly
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="edad" />
                            </div>

                            <div class="col-span-6 sm:col-span-3 sm:col-start-1">
                                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
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
                                    <input wire:model="telefono" type="text" name="telefono" id="telefono"
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-16 sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <x-input-error for="telefono" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input wire:model="email" type="text" name="email" id="email" autocomplete="email"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                <x-input-error for="email" />
                            </div>

                            <h3 class="col-span-6 text-center">Enfermedades que padece</h3>

                            <div class="col-span-6 grid grid-cols-4">
                                @foreach ($listaEnfermedades as $item)
                                    <div>
                                        <input wire:model="enfermedades" type="checkbox" name="{{ $item->nombre }}"
                                            id="{{ $item->nombre }}" value="{{ $item->id }}"
                                            class="form-control">
                                        <label for="{{ $item->nombre }}"
                                            class=" text-sm font-medium text-gray-700">{{ $item->nombre }}</label>
                                    </div>
                                @endforeach
                                <x-input-error for="enfermedades" />
                            </div>

                            <h3 class="col-span-6 text-center">Medicamentos que utiliza</h3>

                            <div class="col-span-6 grid grid-cols-4">
                                @foreach ($listaMedicamentos as $item)
                                    <div>
                                        <input wire:model="medicamentos" type="checkbox" name="{{ $item->nombre }}"
                                            id="{{ $item->nombre }}" value="{{ $item->id }}"
                                            class="form-control">
                                        <label for="{{ $item->nombre }}"
                                            class=" text-sm font-medium text-gray-700">{{ $item->nombre }}</label>
                                    </div>
                                @endforeach
                                <x-input-error for="medicamentos" />
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
