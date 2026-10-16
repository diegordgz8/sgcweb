<div class="flex flex-col shadow-md rounded bg-white border p-5">

    <h5 class="text-gray-900 border-b-2 font-bold text-2xl tracking-tight mb-2 text-center">
        Monedas
    </h5>

    <ul class="divide-y divide-gray-100">
        @foreach ($monedas as $item)
            <li class="flex items-center justify-between gap-3 py-2">
                <div>
                    <span class="font-semibold">{{ $item->codigo }}</span>
                    <span class="text-gray-600">- {{ $item->nombre }}</span>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if ($item->codigo === $facturacion)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                            Facturación
                        </span>
                    @endif

                    @if ($item->es_base)
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                            Moneda base
                        </span>
                    @elseif ($item->tasaVigente)
                        <span class="text-sm text-gray-600 whitespace-nowrap">{{ $item->tasaVigente->tasa_formateada }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @can('admin.home')
        <div class="mt-3 flex flex-wrap justify-end gap-2">
            <x-secondary-button wire:click="mostrarFacturacion">
                Moneda de facturación
            </x-secondary-button>

            <x-button wire:click="$set('open', true)">
                Agregar moneda
            </x-button>
        </div>
    @endcan

    <x-dialog-modal wire:model.live="openFacturacion">
        <x-slot name="title">
            Moneda de facturación
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6">
                    <label for="moneda-facturacion" class="block text-sm font-medium text-gray-700">
                        Las facturas del cierre de mes se emitirán en:
                    </label>
                    <x-select-moneda-condominio wire:model="monedaFacturacion" name="moneda_facturacion"
                        id="moneda-facturacion" class="w-full" />
                    <x-input-error for="monedaFacturacion" />
                </div>

                <p class="col-span-6 text-sm text-gray-600">
                    El cambio aplica a los próximos cierres. Las facturas ya emitidas conservan su moneda.
                </p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('openFacturacion', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="guardarFacturacion" wire:loading.attr="disabled" class="disabled:opacity-25">
                Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model.live="open">
        <x-slot name="title">
            Agregar moneda
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-6 gap-6">

                <div class="col-span-6">
                    <label for="nueva-moneda" class="block text-sm font-medium text-gray-700">
                        Moneda:
                    </label>
                    <x-select-moneda wire:model.live="moneda" name="moneda" id="nueva-moneda" class="w-full"
                        :excluir="$monedas->pluck('codigo')->all()" />
                    <x-input-error for="moneda" />
                </div>

                <div class="col-span-6">
                    <label for="tasa-nueva-moneda" class="block text-sm font-medium text-gray-700">
                        Tasa de cambio inicial:
                    </label>
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="whitespace-nowrap">1 {{ $base }} =</span>
                        <input wire:model.blur="tasa" type="text" name="tasa" id="tasa-nueva-moneda"
                            class="form-control w-full">
                        <span>{{ $moneda ?: '...' }}</span>
                    </div>
                    <x-input-error for="tasa" />
                </div>

                <p class="col-span-6 rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    Una vez agregada, la moneda no se podrá eliminar.
                </p>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click="$set('open', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="agregar" wire:loading.attr="disabled" class="disabled:opacity-25">
                Agregar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
