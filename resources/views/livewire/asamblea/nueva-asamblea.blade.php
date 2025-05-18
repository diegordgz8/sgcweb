<div wire:init="$set('readyToLoad', true)">

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model.live="open" maxWidth='4xl'>
        <x-slot name="title">
            Nueva asamblea
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                {{-- formulario --}}
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6 sm:col-span-3">

                                <label for="descripcion"
                                    class="block text-sm font-medium text-gray-700">Descripción:</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="text" name="descripcion" id="descripcion"
                                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        wire:model.live="descripcion">
                                </div>
                                <x-input-error for="descripcion" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="fecha" class="block text-sm font-medium text-gray-700"> Fecha:</label>
                                <input type="date" name="fecha" id="fecha" autocomplete="street-address"
                                    class="form-control w-full" wire:model.live="fecha">
                                <x-input-error for="fecha" />
                            </div>

                            <div class="col-span-6">
                                <label for="observacion"
                                    class="block text-sm font-medium text-gray-700">Observación:</label>
                                <textarea name="observacion" class="form-control w-full" cols="30" rows="5"
                                    wire:model.live="observacion"></textarea>
                                <x-input-error for="observacion" />
                            </div>

                            <div class="col-span-6">
                                <x-input-error for="asistentes" />

                                <div class="space-y-4">
                                    <div class="flex space-x-4 items-center">

                                        <x-select-cantidad />

                                        <x-input type="text" placeholder="Escriba para buscar..."
                                            class="w-full" wire:model.live="busqueda" />

                                    </div>

                                    <!-- tabla -->
                                    <div class="flex flex-col">
                                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                                <div
                                                    class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                                    @if ($readyToLoad)
                                                        @if (count($integrantes))
                                                            <table class="min-w-full divide-y divide-gray-200">

                                                                <thead class="bg-gray-50">
                                                                    <tr>
                                                                        <th scope="col"
                                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1">
                                                                            <x-checkbox wire:model.live="selectPage"
                                                                                name="selectPage" id="selectPage" />

                                                                        </th>

                                                                        <th scope="col"
                                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                                                            wire:click="orden('documento')">
                                                                            Documento

                                                                            @if ($orden == 'documento')

                                                                                @if ($direccion == 'asc')
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                                                @else
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                                                @endif

                                                                            @else
                                                                                <i
                                                                                    class="fas fa-sort float-right mt-1"></i>

                                                                            @endif
                                                                        </th>

                                                                        <th scope="col"
                                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                                                            wire:click="orden('nombre')">
                                                                            Nombre

                                                                            @if ($orden == 'nombre')

                                                                                @if ($direccion == 'asc')
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                                                @else
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                                                @endif

                                                                            @else
                                                                                <i
                                                                                    class="fas fa-sort float-right mt-1"></i>

                                                                            @endif
                                                                        </th>
                                                                        <th scope="col"
                                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                                                            wire:click="orden('apellido')">
                                                                            Apellido

                                                                            @if ($orden == 'apellido')

                                                                                @if ($direccion == 'asc')
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                                                @else
                                                                                    <i
                                                                                        class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                                                @endif

                                                                            @else
                                                                                <i
                                                                                    class="fas fa-sort float-right mt-1"></i>

                                                                            @endif
                                                                        </th>

                                                                        <th scope="col"
                                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                            Unidad
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="bg-white divide-y divide-gray-200">
                                                                    @if (count($asistentes) > 0)
                                                                        <tr>
                                                                            <td colspan="5"
                                                                                class=" text-sm px-6 py-4 whitespace-nowrap bg-gray-200">

                                                                                @unless($selectAll)
                                                                                    <div>
                                                                                        <span>Ha seleccionado
                                                                                            <strong>{{ count($asistentes) }}</strong>
                                                                                            personas. ¿Quiere seleccionar a
                                                                                            todas las
                                                                                            <strong>{{ $integrantes->total() }}</strong>
                                                                                            personas?</span>


                                                                                        <button class="text-blue-500"
                                                                                            wire:click="$set('selectAll', true)">
                                                                                            Seleccionar todo
                                                                                        </button>
                                                                                    </div>
                                                                                @else

                                                                                    <span>Ha seleccionado
                                                                                        <strong>{{ $integrantes->total() }}</strong>
                                                                                        personas</span>

                                                                                @endunless

                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                    @foreach ($integrantes as $item)
                                                                        <tr>
                                                                            <td
                                                                                class="px-6 py-4 whitespace-nowrap text-xs space-x-1 font-medium">
                                                                                <x-checkbox wire:model.live="asistentes"
                                                                                    value="{{ $item->id }}" />
                                                                            </td>
                                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                                <div
                                                                                    class="text-sm font-medium text-gray-900">
                                                                                    @if ($item->documento)
                                                                                        {{ $item->letra }}-{{ $item->documento }}
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                                <div
                                                                                    class="text-sm font-medium text-gray-900">
                                                                                    {{ $item->nombre }}
                                                                                </div>
                                                                            </td>
                                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                                <div
                                                                                    class="text-sm font-medium text-gray-900">
                                                                                    {{ $item->apellido }}
                                                                                </div>
                                                                            </td>
                                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                                <div
                                                                                    class="text-sm font-medium text-gray-900">
                                                                                    {{ $item->unidad->numero }}
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                            @if ($integrantes->hasPages())
                                                                <div class="px-6 py-3">
                                                                    {{ $integrantes->links() }}
                                                                </div>
                                                            @endif

                                                        @else
                                                            <div class="px-6 py-4">
                                                                Su búsqueda no tuvo resultado
                                                            </div>
                                                        @endif
                                                    @else

                                                        <div class="px-6 py-4">
                                                            Cargando...
                                                        </div>

                                                    @endif


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>


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
            <x-button wire:click="save()" wire:loading.attr="disabled">
                Registrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

</div>
