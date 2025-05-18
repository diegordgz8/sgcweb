<div x-data="initConfirmarPago()" x-init="init" wire:init="$set('readyToLoad', true)">
    <div class="space-y-4">
        <div class="flex space-x-4 items-center">
            <x-select-cantidad />

            <x-input wire:model.live="busqueda" type="text" placeholder="Escriba para buscar..." class="w-full" />
        </div>

        {{-- tabla --}}
        <div class="flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        @if ($readyToLoad)
                            @if (count($pagos))
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Fecha
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Monto
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Forma de pago
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Factura
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
                                        @foreach ($pagos as $pago)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ $pago->fecha }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ $pago->montoFormateado }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ $pago->forma_pago }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ $pago->factura->numero }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ $pago->unidad->numero }}
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-1">
                                                    <a href="{{ route('pago-propietario.show', $pago) }}"
                                                        class="btn btn-blue">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @if ($pago->forma_pago == 'Efectivo' || $pago->forma_pago == 'Cheque')
                                                        <button class="btn btn-green"
                                                            wire:click="elegirFondo({{ $pago }})">
                                                            <i class="fas fa-check"></i>
                                                            Aceptar
                                                        </button>
                                                    @else
                                                        <a class="btn btn-green"
                                                            wire:click="confirmar({{ $pago }})">
                                                            <i class="fas fa-check"></i>
                                                            Aceptar
                                                        </a>
                                                    @endif

                                                    <button class="btn btn-red" @click="abrirRechazar(@js($pago))">
                                                        <i class="fas fa-times"></i>
                                                        Rechazar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($pagos->hasPages())
                                    <div class="px-6 py-3">
                                        {{ $pagos->links() }}
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
        {{-- /table --}}

    </div>

    <x-dialog-modal wire:model.live="openConfirmar">
        <x-slot name="title">
            Confirmar pago
        </x-slot>

        <x-slot name="content">
            <div class="mt-10 sm:mt-0">

                <h1 class="text-center text-lg mb-4">Seleccione el fondo donde se agregará el pago</h1>

                <div class="grid grid-cols-2 gap-6">

                    @foreach ($fondos as $item)
                        <x-card-fondo wire:click="aceptar({{ $item }})" :fondo="$item" class="cursor-pointer" />
                    @endforeach

                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('openConfirmar', false)">
                Cancelar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.live="openRechazar">

        <x-slot name="title">
            Rechazar pago
        </x-slot>

        <x-slot name="content">
            ¿Seguro que desea rechazar el pago?
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('openRechazar', false)">
                Cancelar
            </x-secondary-button>

            <x-danger-button @click="rechazar" wire:loading.attr="disabled"
                class="disabled:opacity-25">
                Rechazar
            </x-danger-button>
        </x-slot>

    </x-confirmation-modal>
</div>

@push('js')
    <script>
        function initConfirmarPago() {
            return {
                pago: null,

                init: function() {
                },

                abrirRechazar: function(pago) {
                    this.$wire.openRechazar = true;
                    this.pago = pago.id;
                },

                rechazar: function() {
                    console.log(this.pago);
                    this.$wire.rechazar(this.pago);
                }
            }
        }
    </script>
@endpush
