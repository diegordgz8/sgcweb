<div wire:init="$set('readyToLoad', true)" x-data="nuevoGasto">

    <x-button wire:click="$set('open', true)">
        Nuevo
    </x-button>

    <x-dialog-modal wire:model="open" maxWidth="4xl">

        <x-slot name="title">
            Nuevo gasto
        </x-slot>

        <x-slot name="content">

            {{-- formulario --}}
            <div class="mt-10 sm:mt-0">
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">

                            <div class="col-span-6 sm:col-span-4">
                                <label for="descripcion" class="block text-sm font-medium text-gray-700">
                                    Descripción:
                                </label>
                                <input wire:model.lazy="descripcion" type="text" name="descripcion" id="descripcion"
                                    class="form-control w-full">
                                <x-input-error for="descripcion" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="tipo" class="block text-sm font-medium text-gray-700">
                                    Tipo de gasto:
                                </label>
                                <div>
                                    <input x-model="tipo" type="radio" name="tipo" id="ordinario" value="Ordinario">
                                    <label for="ordinario">Ordinario</label>
                                </div>
                                <div>
                                    <input x-model="tipo" type="radio" name="tipo" id="extraordinario"
                                        value="Extraordinario">
                                    <label for="extraordinario">Extraordinario</label>
                                </div>
                                <x-input-error for="tipo" />
                            </div>

                            {{-- @if ($tipo == 'Extraordinario') --}}
                                <div x-show="tipo == 'Extraordinario'" x-transition class="col-span-6 sm:col-span-2">
                                    <label for="numero-meses" class="block text-sm font-medium text-gray-700">
                                        Número de meses:
                                    </label>
                                    <input wire:model.lazy="numeroMeses" type="number" name="numero-meses"
                                        id="numero-meses" class="form-control w-full">
                                    <x-input-error for="numeroMeses" />
                                </div>

                                <div x-show="tipo == 'Extraordinario'" x-transition class="col-span-6 sm:col-span-2">
                                    <label for="elegido-asamblea" class="block text-sm font-medium text-gray-700">
                                        ¿El gasto fue decidido en una asamblea?
                                    </label>
                                    <div>
                                        <input x-model="elegidoAsamblea" type="radio" name="elegido-asamblea" id="si"
                                            value="si">
                                        <label for="si">Sí</label>
                                        <input x-model="elegidoAsamblea" type="radio" name="elegido-asamblea" id="no"
                                            value="no" class="ml-2">
                                        <label for="no">No</label>
                                    </div>
                                    <x-input-error for="elegidoAsamblea" />
                                </div>

                                {{-- @if ($elegidoAsamblea == 'si') --}}
                                    <div x-show="elegidoAsamblea == 'si'" x-transition class="col-span-2">
                                        <label for="asamblea" class="block text-sm font-medium text-gray-700">
                                            Asamblea:
                                        </label>
                                        <select wire:model="asamblea.id" name="asamblea" id="asamblea"
                                            class="form-control w-full">
                                            <option value="0"> -- </option>
                                            @foreach ($asambleas as $item)
                                                <option value="{{ $item->id }}">{{ $item->fecha }} -
                                                    {{ $item->descripcion }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="asamblea.id" />
                                    </div>
                                {{-- @endif --}}
                            {{-- @endif --}}

                            <div class="col-span-6 sm:col-span-2 sm:col-start-1">
                                <label for="calculo" class="block text-sm font-medium text-gray-700">
                                    Calcular por:
                                </label>
                                <div>
                                    <select wire:model="calculo" name="calculo" id="calculo"
                                        class="form-control w-full">
                                        <option>----</option>
                                        <option>Alícuota</option>
                                        <option>Total de inmuebles</option>
                                    </select>
                                </div>
                                <x-input-error for="calculo" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="comienzo-cobro" class="block text-sm font-medium text-gray-700">
                                    Comienzo de cobro:
                                </label>
                                <input wire:model="comienzoCobro" type="month" name="comienzo-cobro" id="comienzo-cobro"
                                    class="form-control w-full">
                                <x-input-error for="comienzoCobro" />
                            </div>

                            <div class="col-span-6 sm:col-span-2">
                                <label for="moneda" class="block text-sm font-medium text-gray-700">
                                    Moneda:
                                </label>
                                <select wire:model="moneda" name="moneda" id="moneda" class="form-control w-full">
                                    <option>Bolívar</option>
                                    <option>Dólar</option>
                                </select>
                                <x-input-error for="moneda" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="monto" class="block text-sm font-medium text-gray-700">
                                    Monto total:
                                </label>
                                <input wire:model="monto" type="text" name="monto" id="monto" readonly
                                    class="form-control w-full">
                                <x-input-error for="monto" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="factura" class="block text-sm font-medium text-gray-700">
                                    Factura:
                                </label>
                                <input wire:model="factura" type="text" name="factura" id="factura"
                                    class="form-control w-full">
                                <x-input-error for="factura" />
                            </div>

                            <div class="col-span-6">
                                <label for="observaciones" class="block text-sm font-medium text-gray-700">
                                    Observaciones:
                                </label>
                                <textarea wire:model.lazy="observaciones" name="observaciones" id="observaciones"
                                    rows="5" class="form-control w-full"></textarea>
                                <x-input-error for="observaciones" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="proveedor" class="block text-sm font-medium text-gray-700">
                                    Proveedor:
                                </label>
                                <select wire:model="proveedor.id" name="proveedor" id="proveedor"
                                    class="form-control w-full">
                                    <option>----</option>
                                    @foreach ($proveedores as $item)
                                        <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="proveedor.id" />
								<x-input-error for="servicios" />
                            </div>

                        </div>

                        @if ($this->proveedor->id)
                            <div class="py-4">
                                @include('livewire.gasto.partials.tabla-servicios')
                            </div>
                        @endif

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
    function nuevoGasto() {
        return {
            tipo: @entangle('tipo').defer,
            elegidoAsamblea: @entangle('elegidoAsamblea').defer,

            init: function() {
                // console.log('Listo!');
            },
        }
    }
</script>
@endpush