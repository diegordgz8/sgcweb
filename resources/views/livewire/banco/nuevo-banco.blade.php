<div>
	<x-jet-button wire:click="$set('abierto', true)">
		Nuevo
	</x-jet-button>

	<x-jet-dialog-modal wire:model="abierto">
		<x-slot name="title">
			Nuevo Banco
		</x-slot>

		<x-slot name="content">
			<div class="mt-10 sm:mt-0">

				{{-- formulario --}}
				<div class="shadow overflow-hidden sm:rounded-md">
					<div class="px-4 py-5 bg-white sm:p-6">
						<div class="grid grid-cols-6 gap-6">
							<div class="col-span-6">
								<label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
								<input type="text" name="nombre" id="nombre"
									class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
									wire:model="nombre">
								<x-jet-input-error for="nombre" />
							</div>
						</div>
					</div>
				</div>
				{{-- /formulario --}}

			</div>
		</x-slot>

		<x-slot name="footer">
			<x-jet-secondary-button class="mr-2" wire:click="$set('abierto', false)">
				Cancelar
			</x-jet-secondary-button>

			<x-jet-button wire:click="save()">
				Registrar
			</x-jet-button>
		</x-slot>
	</x-jet-dialog-modal>

</div>