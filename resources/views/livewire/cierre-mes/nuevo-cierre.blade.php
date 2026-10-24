<div>

    <x-button wire:click="$set('open', true)" class="whitespace-nowrap">
        Cerrar mes
    </x-button>

	<x-dialog-modal wire:model.live="open" maxWidth="3xl">

        <x-slot name="title">
            {{ $vistaPrevia ? 'Revisar el cierre de ' . $this->nombreMes() : 'Cierre de mes' }}
        </x-slot>

	<x-slot name="content">

		@if (! $vistaPrevia)
			{{-- Paso 1: elegir el mes --}}
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
		@else
			{{-- Paso 2: revisar lo que se va a cobrar --}}
			<div class="space-y-6">

				<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
					<div class="rounded-md border border-gray-200 p-4">
						<p class="text-xs uppercase tracking-wide text-gray-500">Facturas</p>
						<p class="mt-1 text-2xl font-semibold text-gray-900">{{ count($vistaPrevia['facturas']) }}</p>
						<p class="text-xs text-gray-500">una por unidad con propietario</p>
					</div>
					<div class="rounded-md border border-gray-200 p-4">
						<p class="text-xs uppercase tracking-wide text-gray-500">Total a facturar</p>
						<p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $this->formatearMonto($vistaPrevia['total'], $vistaPrevia['moneda']) }}</p>
						<p class="text-xs text-gray-500">en {{ $vistaPrevia['moneda'] }}</p>
					</div>
					<div class="rounded-md border border-gray-200 p-4">
						<p class="text-xs uppercase tracking-wide text-gray-500">Sanciones</p>
						<p class="mt-1 text-2xl font-semibold text-gray-900">{{ $vistaPrevia['sanciones'] }}</p>
						<p class="text-xs text-gray-500">pendientes que se cobrarán</p>
					</div>
				</div>

				<div>
					<h3 class="text-sm font-medium text-gray-700">Gastos incluidos</h3>
					@if (count($vistaPrevia['gastos']))
						<table class="mt-2 min-w-full divide-y divide-gray-200 text-sm">
							<thead>
								<tr class="text-left text-xs uppercase tracking-wide text-gray-500">
									<th class="py-2 pr-4 font-medium">Gasto</th>
									<th class="py-2 pr-4 font-medium">Tipo</th>
									<th class="py-2 text-right font-medium">Se cobra este mes</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								@foreach ($vistaPrevia['gastos'] as $gasto)
									<tr>
										<td class="py-2 pr-4 text-gray-900">{{ $gasto['descripcion'] }}</td>
										<td class="py-2 pr-4 text-gray-600">
											{{ $gasto['tipo'] }}@if ($gasto['ultima_cuota']) · última cuota @endif
										</td>
										<td class="py-2 text-right tabular-nums text-gray-900">{{ $this->formatearMonto($gasto['cuota'], $gasto['moneda']) }}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					@else
						<p class="mt-1 text-sm text-gray-500">No hay gastos que cobrar este mes; las facturas solo llevarán la mensualidad y las sanciones.</p>
					@endif
				</div>

				<div>
					<h3 class="text-sm font-medium text-gray-700">Facturas por unidad</h3>
					<div class="mt-2 max-h-56 overflow-y-auto rounded-md border border-gray-200">
						<table class="min-w-full divide-y divide-gray-200 text-sm">
							<tbody class="divide-y divide-gray-100">
								@foreach ($vistaPrevia['facturas'] as $factura)
									<tr>
										<td class="px-3 py-2 text-gray-900">Unidad {{ $factura['unidad'] }}</td>
										<td class="px-3 py-2 text-gray-500">@if ($factura['con_interes']) con interés por mora @endif</td>
										<td class="px-3 py-2 text-right tabular-nums text-gray-900">{{ $this->formatearMonto($factura['monto'], $vistaPrevia['moneda']) }}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
					Al confirmar se crearán las facturas y los gastos y sanciones incluidos quedarán marcados como cobrados. El cierre no se puede deshacer.
				</div>

				<x-input-error for="mes" />
			</div>
		@endif

	</x-slot>

	<x-slot name="footer">
		@if (! $vistaPrevia)
			<x-secondary-button class="mr-2" wire:click="cancelar">
				Cancelar
			</x-secondary-button>

			<x-button wire:click="revisar" wire:loading.attr="disabled" class="disabled:opacity-25">
				Revisar el cierre
			</x-button>
		@else
			<x-secondary-button class="mr-2" wire:click="volver">
				Volver
			</x-secondary-button>

			<x-button wire:click="cerrarMes" wire:loading.attr="disabled" class="disabled:opacity-25">
				Cerrar {{ $this->nombreMes() }}
			</x-button>
		@endif
	</x-slot>

</x-dialog-modal>

</div>
