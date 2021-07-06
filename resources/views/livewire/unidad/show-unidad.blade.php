<div class="bg-white overflow-hidden  shadow-xl rounded-xl">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Unidad
        </h3>
    </div>
    <div class="border-t border-gray-200">
        <dl>

            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">
                    Unidad:
                </dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $unidad->numero }}
                </dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">
                    Descripción:
                </dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $unidad->direccion }}
                </dd>
            </div>


        </dl>



    </div>

    <div class="border rounded-md shadow-md my-2 mx-4">

        @if (count($unidad->integrantes))
            {{-- <div class="border rounded-md shadow-md my-2"> --}}
            <div class="flex items-center px-4 py-2">
                <h2 class="px-4 py-2 text-lg inline w-full">Habitantes de la unidad</h2>

                @livewire('nuevo-integrante', ['unidad' => $unidad])
            </div>

            <!-- tabla -->
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Propietario</span>
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cédula
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nombre
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Apellido
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($unidad->integrantes as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    @if ($unidad->propietario->integrante->id == $item->id)
                                                        <i class="fas fa-home text-xl text-blue-400"></i>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->documento }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->nombre }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->apellido }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-1">
                                                <a href="{{ route('integrante.show', $item) }}" class="btn btn-blue">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a class="btn btn-red" wire:click="destroy({{ $item }})">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /tabla --}}

            {{-- </div> --}}
        @else
            <div class="px-4 py-2">
                Sin habitantes
            </div>
        @endif

        <x-jet-confirmation-modal wire:model="openDestroy">

            <x-slot name="title">
                Eliminar
            </x-slot>

            <x-slot name="content">
                ¿Seguro que desea eliminar al integrante de la unidad?
            </x-slot>

            <x-slot name="footer">
                <x-jet-secondary-button wire:click="$set('openDestroy', false)">
                    Cancelar
                </x-jet-secondary-button>

                <x-jet-danger-button wire:click="remove" wire:loading.attr="disabled" class="disabled:opacity-25">
                    Eliminar
                </x-jet-danger-button>
            </x-slot>

        </x-jet-confirmation-modal>

    </div>

</div>