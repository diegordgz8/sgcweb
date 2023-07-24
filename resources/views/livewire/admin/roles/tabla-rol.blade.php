<div>
    <x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800 leading-tight">
			{{ __('Rol') }}
		</h2>
	</x-slot>

	<div class="py-12">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">

				<div class="p-4" wire:init="$set('readyToLoad', true)">
					
                    <div class="space-y-4">
                        <div class="flex space-x-4 items-center">
                            <x-select-cantidad />

                            <x-input type="search" placeholder="Escriba para buscar..." class="w-full"
                                wire:model="busqueda" />

                            @livewire('admin.roles.nuevo-rol')
                        </div>

                        <!-- tabla -->
                        <div class="flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        @if ($readyToLoad)
                                            @if (count($roles))
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th scope="col"
                                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                                                wire:click="orden('name')">
                                                                Nombre
                                                                @if ($orden == 'name')

                                                                    @if ($direccion == 'asc')
                                                                        <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>

                                                                    @else
                                                                        <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>

                                                                    @endif

                                                                @else
                                                                    <i class="fas fa-sort float-right mt-1"></i>

                                                                @endif
                                                            </th>
                                                            <th scope="col" class="relative px-6 py-3">
                                                                <span class="sr-only">Acciones</span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach ($roles as $item)
                                                            <tr>
                                                                <td class="px-6 py-4">
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        {{ $item->name }}
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap space-x-1 text-xs text-right">
                                                                    <a class="btn btn-green" wire:click="edit({{ $item }})">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a class="btn btn-red" wire:click="destroy({{ $item }})">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                                @if ($roles->hasPages())
                                                    <div class="px-6 py-3">
                                                        {{ $roles->links() }}
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
                        {{-- /tabla --}}
                    </div>

                    <x-dialog-modal wire:model="openEdit">
                        <x-slot name="title">
                            Editar rol
                        </x-slot>

                        <x-slot name="content">
                            <div class="mt-10 sm:mt-0">

                                {{-- formulario --}}
                                <div class="shadow overflow-hidden sm:rounded-md">
                                    <div class="px-4 py-5 bg-white sm:p-6">
                                        <div class="grid grid-cols-6 gap-6">

                                            <div class="col-span-6">
                                                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                                <input type="text" name="name" id="name"
                                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                    wire:model.lazy="rol.name">
                                                <x-input-error for="rol.name" />
                                            </div>

                                            <div class="col-span-6">
                                                <h1 class="mb-2">Permisos</h1>
                
                                                <div class="grid grid-cols-2 xl:grid-cols-3 gap-2">
                
                                                    @foreach ($this->listaPermisos as $key => $item)
                                                    <div class="flex items-start">
                                                        <div class="flex items-center">
                                                            <input wire:model.defer="permisos" type="checkbox"
                                                            {{-- id="permiso_{{ $key }}"
                                                                name="permiso_{{ $key }}" --}}
                                                                value="{{ $item->id }}"
                                                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="permiso_{{ $key }}"
                                                                class="font-medium text-gray-700">{{ $item->name }}</label>
                                                        </div>
                                                    </div>
                                                    @endforeach
                
                                                </div>
                                                <x-input-error for="permisos" />
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
                            ¿Seguro que desea eliminar al rol?
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

			</div>
		</div>
	</div>
</div>
