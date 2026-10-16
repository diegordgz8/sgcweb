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
							<span class="block text-sm font-medium text-gray-700">Moneda de facturación</span>
							<p class="mt-1 text-sm text-gray-900">
								{{ $moneda }} - {{ $nombreMoneda }}
							</p>
							<p class="mt-1 text-xs text-gray-500">
								Se configura en Administración, en la tarjeta "Monedas".
							</p>
						</div>

						<div class="col-span-6">
							<span class="block text-sm font-medium text-gray-700">Tasas de cambio vigentes</span>
							<ul class="mt-1 text-sm text-gray-600">
								@forelse ($tasas as $tasa)
									<li>{{ $tasa->tasa_formateada }}</li>
								@empty
									<li>El condominio trabaja con una sola moneda.</li>
								@endforelse
							</ul>
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
