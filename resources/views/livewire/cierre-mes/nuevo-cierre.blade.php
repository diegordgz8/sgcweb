<div>

    <x-button wire:click="$set('open', true)" class="whitespace-nowrap">
        Cerrar mes
    </x-button>

	{{-- {{ Str::substr(today(), 5, 2) }} --}}
	<x-dialog-modal wire:model.live="open">

        <x-slot name="title">
            Cierre de mes
        </x-slot>

	<x-slot name="content">

		{{-- formulario --}}
		<div class="mt-10 sm:mt-0">
			<div class="shadow overflow-hidden sm:rounded-md">
				<div class="px-4 py-5 bg-white sm:p-6">
					<div class="grid grid-cols-6 gap-6">

						<div class="col-span-6">
							<label for="mes" class="block text-sm font-medium text-gray-700">Mes</label>
							<input wire:model.live="mes" type="month" name="mes" id="mes"
								class="form-control w-full">
							<x-input-error for="mes" />
						</div>

						<div class="col-span-6">
							<label for="moneda" class="block text-sm font-medium text-gray-700">
								Moneda
							</label>
							<select wire:model.live="moneda" name="moneda" id="moneda" class="form-control w-full">
								<option>Bolívar</option>
								<option>Dólar</option>
							</select>
							<x-input-error for="moneda" />
						</div>

						<div class="col-span-6">
							<label for="tasa-cambio" class="block text-sm font-medium text-gray-700">
								Tasa de cambio
							</label>
							<input wire:model.blur="tasaCambio.tasa" type="text" name="tasa-cambio" id="tasa-cambio" readonly
								class="form-control w-full">
							<x-input-error for="tasaCambio.tasa" />
						</div>

					</div>
				</div>
			</div>
		</div>
		{{-- /formulario --}}

	</x-slot>

	<x-slot name="footer">
		<x-secondary-button class="mr-2" wire:click="$set('open', false)">
			Cancelar
		</x-secondary-button>

		<x-button wire:click="cerrarMes" wire:loading.attr="disabled" class="disabled:opacity-25">
			Registrar
		</x-button>
	</x-slot>

</x-dialog-modal>

</div>
