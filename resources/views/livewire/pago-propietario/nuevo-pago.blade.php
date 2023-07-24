<div x-data="nuevoPagoPropietario" x-init="init">

    <div class="w-full flex justify-end gap-4">

        <a href="{{ route('pago-propietario.index') }}" class="w-full">
            <button class="btn btn-blue w-full h-20 text-2xl">Lista de pagos</button>
        </a>
    </div>

    <div class="py-6 space-y-2">
        <h1 class="text-center text-xl font-semibold">Seleccione la factura a pagar...</h1>

        @include('livewire.pago-propietario.partials.tabla-facturas')
    </div>

    <x-dialog-modal wire:model="open" maxWidth="4xl">

        <x-slot name="title">
            Nuevo pago
        </x-slot>

        <x-slot name="content">

            {{-- formulario --}}
            <div class="mt-10 sm:mt-0">
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6">
                                <label for="factura" class="block text-sm font-medium text-gray-700">
                                    Factura:
                                </label>
                                <input type="text" name="factura" id="factura" readonly value="{{ $factura->numero }}"
                                    class="form-control w-full" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="monto-por-pagar" class="block text-sm font-medium text-gray-700">
                                    Monto restante por pagar:
                                </label>
                                <input type="text" name="monto-por-pagar" id="monto-por-pagar" readonly
                                    value="{{ $montoFormateado }}" class="form-control w-full" />
                            </div>

                            @if ($conCambio)
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="monto-por-pagar-convertido"
                                        class="block text-sm font-medium text-gray-700">
                                        Monto restante por pagar (convertido):
                                    </label>
                                    <input type="text" name="monto-por-pagar-convertido" id="monto-por-pagar-convertido"
                                        value="{{ $montoFacturaConvertidoFormateado }}" readonly
                                        class="form-control w-full" />
                                </div>
                            @endif

                            <div class="col-span-6">
                                <label for="descripcion" class="block text-sm font-medium text-gray-700">
                                    Descripción:
                                </label>
                                <input wire:model.lazy="descripcion" type="text" name="descripcion" id="descripcion"
                                    class="form-control w-full" />
                                <x-input-error for="descripcion" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="fecha" class="block text-sm font-medium text-gray-700">
                                    Fecha de pago:
                                </label>
                                <input wire:model="fecha" type="date" name="fecha" id="fecha"
                                    class="form-control w-full" />
                                <x-input-error for="fecha" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="monto" class="block text-sm font-medium text-gray-700">
                                    Monto:
                                </label>
                                <input wire:model="monto" type="text" name="monto" id="monto"
                                    class="form-control w-full" />
                                <x-input-error for="monto" />
                            </div>

                            <div class="col-span-6 sm:col-span-1">
                                <input wire:click="pagarTotal" wire:loading.attr="disabled" type="button"
                                    value="Monto total" class="btn btn-blue text-xs h-14 disabled:bg-opacity-25" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="forma-pago" class="block text-sm font-medium text-gray-700">
                                    Forma de pago:
                                </label>
                                <div>
                                    <select wire:model.lazy="formaPago" x-model="formaPago" name="forma-pago" id="forma-pago"
                                        class="form-control w-full">
                                        <option>----</option>
                                        <option>Transferencia</option>
                                        <option>Pago móvil</option>
                                        <option>Punto de venta</option>
                                        <option>Efectivo</option>
                                        <option>Depósito</option>
                                        <option>Cheque</option>
                                    </select>
                                </div>
                                <x-input-error for="formaPago" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="moneda" class="block text-sm font-medium text-gray-700">
                                    Moneda:
                                </label>
                                <select wire:model="moneda" x-model="moneda" name="moneda" id="moneda" class="form-control w-full">
                                    <option>Bolívar</option>
                                    <option>Dólar</option>
                                </select>
                                <x-input-error for="moneda" />
                            </div>

                            <div x-show="mostrarCuenta" x-transition class="col-span-6 sm:col-span-3">
                                <label for="cuenta" class="block text-sm font-medium text-gray-700">
                                    Cuenta:
                                </label>
                                <select wire:model="cuenta.id" name="cuenta" id="cuenta"
                                    class="form-control w-full">
                                    <option value="0" selected>----</option>

                                    @if ($formaPago == 'Pago móvil')

                                        @foreach ($cuentas as $item)
                                            <option value="{{ $item->id }}">
                                                #{{ Str::substr($item->numero, 0, 4) }}
                                                - {{ $item->telefono }}
                                            </option>
                                        @endforeach

                                    @else

                                        @foreach ($cuentas as $item)
                                            <option value="{{ $item->id }}">{{ $item->numeroOculto }}
                                            </option>
                                        @endforeach

                                    @endif

                                </select>
                                <x-input-error for="cuenta.id" />
                            </div>

                            <div x-show="mostrarReferencia" x-transition class="col-span-6 sm:col-span-3">
                                <label for="referencia"
                                    class="block text-sm font-medium text-gray-700">
                                    Referencia:
                                </label>
                                <input wire:model.lazy="referencia" type="text" name="referencia"
                                    id="referencia" class="form-control w-full" />
                                <x-input-error for="referencia" />
                            </div>

                            @if ($factura->moneda != $moneda)
                                <div class="col-span-6 sm:col-span-3">
                                    <label for="tasa-cambio"
                                        class="block text-sm font-medium text-gray-700">
                                        Tasa de cambio:
                                    </label>
                                    <input type="text" name="tasa-cambio" id="tasa-cambio" value="{{$this->tasaCambio->tasa}}" class="form-control w-full" readonly />
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
            {{-- /formulario --}}

        </x-slot>

        <x-slot name="footer">

            <x-secondary-button wire:click="$set('open', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="save" wire:loading.attr="disabled" class="disabled:bg-opacity-25">
                Registrar
            </x-button>

        </x-slot>

    </x-dialog-modal>

</div>

@push('js')
<script>
    function nuevoPagoPropietario() {
        return {
            monedaFactura: null,
            moneda: null,
            conCambio: null,
            formaPago: false,

            init: function() {
                // console.log('Listo!');
            },

            mostrarCuenta: function() {
                switch(this.formaPago) {
                    case 'Pago móvil':
                    case 'Transferencia':
                    case 'Punto de venta':
                    case 'Depósito':
                        return true;
                        break;
                    default:
                        return false
                }
            },

            mostrarReferencia: function() {
                switch(this.formaPago) {
                    case 'Pago móvil':
                    case 'Transferencia':
                        return true;
                        break;
                    default:
                        return false
                }
            }
        }
    }
</script>
@endpush