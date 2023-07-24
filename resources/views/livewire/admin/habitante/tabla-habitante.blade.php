<div wire:init="loadIntegrantes">

    <div class="space-y-4">
        <div class="flex space-x-4 items-center">
            <x-select-cantidad />

            <x-input type="text" placeholder="Escriba para buscar..." class="w-full" wire:model="busqueda" />

            <a href="{{ route('habitante.exportar') }}" class="btn btn-blue whitespace-nowrap">
                <i class="fas fa-file-export"></i> Exportar
            </a>

        </div>

        <!-- tabla -->
        <div class="flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        @if (count($integrantes))
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                            wire:click="orden('documento')">
                                            Cédula
                                            @if ($orden == 'documento')

                                                @if ($direccion == 'asc')
                                                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                @else
                                                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                @endif

                                            @else
                                                <i class="fas fa-sort float-right mt-1"></i>

                                            @endif
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                            wire:click="orden('nombre')">
                                            Nombre

                                            @if ($orden == 'nombre')

                                                @if ($direccion == 'asc')
                                                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                @else
                                                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                @endif

                                            @else
                                                <i class="fas fa-sort float-right mt-1"></i>

                                            @endif
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                            wire:click="orden('apellido')">
                                            Apellido

                                            @if ($orden == 'apellido')

                                                @if ($direccion == 'asc')
                                                    <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                @else
                                                    <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                @endif

                                            @else
                                                <i class="fas fa-sort float-right mt-1"></i>

                                            @endif
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Unidad
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($integrantes as $item)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->letra }}-{{ $item->documento }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->nombre }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->apellido }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    @if ($item->unidad)
                                                        {{ $item->unidad->numero }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium flex gap-1">
                                                <a href="{{route('admin.habitante.show', $item)}}" class="btn btn-blue">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                {{-- <a class="btn btn-green" wire:click="edit({{ $item }})">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a class="btn btn-red" wire:click="destroy({{ $item }})">
                                                    <i class="fas fa-trash"></i>
                                                </a> --}}
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
                    </div>
                </div>
            </div>
        </div>
        {{-- /tabla --}}
    </div>

    <x-dialog-modal wire:model="openEdit">
        <x-slot name="title">
            Editar la categoría
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
                                    wire:model="categoria.nombre">
                                <x-input-error for="nombre" />
                            </div>

                            <div class="col-span-6">
                                <label for="descripcion"
                                    class="block text-sm font-medium text-gray-700">Descripción</label>
                                <input type="text" name="descripcion" id="descripcion"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    wire:model="categoria.descripcion">
                                <x-input-error for="descripcion" />
                            </div>

                        </div>
                    </div>
                </div>
                {{-- /formulario --}}

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('openEdit', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="update" wire:loading.attr="disabled" class="disabled:opacity-25">
                Actualizar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model="openDestroy">

        <x-slot name="title">
            Eliminar
        </x-slot>

        <x-slot name="content">
            ¿Seguro que desea eliminar la categoría?
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('openDestroy', false)">
                Cancelar
            </x-secondary-button>

            <x-danger-button wire:click="delete" wire:loading.attr="disabled" class="disabled:opacity-25">
                Eliminar
            </x-danger-button>
        </x-slot>

    </x-confirmation-modal>

</div>
