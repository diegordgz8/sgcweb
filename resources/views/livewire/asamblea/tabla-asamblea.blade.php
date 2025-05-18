<div wire:init="loadAsambleas">
    {{-- Do your work, then step back. --}}

    <div class="space-y-4">
        <div class="flex space-x-4 items-center">

            <x-select-cantidad />

            <x-input type="text" placeholder="Escriba para buscar..." class="w-full" wire:model.live="busqueda" />

            @livewire('asamblea.nueva-asamblea')
        </div>

        <!-- tabla -->
        <div class="flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        @if ($readyToLoad)
                            @if (count($asambleas))
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                                wire:click="orden('fecha')">
                                                fecha

                                                @if ($orden == 'fecha')

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
                                                wire:click="orden('descripcion')">
                                                Descripción

                                                @if ($orden == 'descripcion')

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
                                                wire:click="orden('descripcion')">
                                                # de asistentes
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">

                                        @foreach ($asambleas as $item)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $item->fecha }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $item->descripcion }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $item->asistentes()->count() }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-right text-xs space-x-1 font-medium">
                                                    <a class="btn btn-blue" href="{{ route('asamblea.show', $item) }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($asambleas->hasPages())
                                    <div class="px-6 py-3">
                                        {{ $asambleas->links() }}
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

