<div class="space-y-4">
    <div class="flex space-x-4 items-center">
        <x-select-cantidad />

        <x-input type="text" placeholder="Escriba para buscar..." class="w-full" wire:model.live="busqueda" />
    </div>

    <!-- tabla -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    @if (count($facturas))
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        wire:click="orden('numero')">
                                        Código

                                        @if ($orden == 'numero')

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
                                        wire:click="orden('fecha')">
                                        Fecha

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
                                        Unidad
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        wire:click="orden('descripcion')">
                                        Monto
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        wire:click="orden('descripcion')">
                                        Monto por pagar
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($facturas as $item)
                                    <tr wire:click="mostrarForm({{ $item }})"
                                        @class([
                                            'hover:bg-gray-100',
                                            'cursor-pointer' => $item->estado_pago !== 'Esperando confirmación',
                                            'cursor-default opacity-75' => $item->estado_pago === 'Esperando confirmación',
                                        ])>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->numero }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->fecha }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->unidad->numero }} -
                                                {{ $item->unidad->propietario->integrante->nombreCompleto }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->montoFormateado }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item->montoPorPagarFormateado }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-estado-pago :estado="$item->estado_pago" />
                                            @if ($item->estado_pago === 'Pago rechazado')
                                                @if ($item->ultimoPago->motivo_rechazo)
                                                    <div class="mt-1 max-w-xs whitespace-normal text-xs text-red-800">
                                                        Motivo: {{ $item->ultimoPago->motivo_rechazo }}
                                                    </div>
                                                @endif
                                                <div class="mt-1 text-xs text-gray-500">Toque la fila para corregirlo y reenviarlo.</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($facturas->hasPages())
                            <div class="px-6 py-3">
                                {{ $facturas->links() }}
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
