<div>
	{{-- A good traveler has no fixed plans and is not intent upon arriving. --}}

	<div class="space-y-4">
		<div class="flex space-x-4 items-center">
			<x-jet-input type="text" placeholder="Escriba para buscar..." class="w-full" wire:model="busqueda" />

			@livewire('nueva-unidad')
		</div>

		<!-- tabla -->
		<div class="flex flex-col">
			<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
				<div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
					<div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
						@if($unidades->count())
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th scope="col"
										class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
										wire:click="orden('numero')">
										Número

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
										class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Dirección
									</th>
									<th scope="col"
										class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Tipo de unidad
									</th>
									<th scope="col"
										class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
										Propietario
									</th>
									<th scope="col" class="relative px-6 py-3">
										<span class="sr-only">Acciones</span>
									</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@foreach ($unidades as $item)
								<tr>
									<td class="px-6 py-4 whitespace-nowrap">
										<div class="text-sm font-medium text-gray-900">
											{{ $item->numero }}
										</div>
									</td>
									<td class="px-6 py-4">
										<div class="text-sm font-medium text-gray-900">
											{{ $item->direccion }}
										</div>
									</td>
									<td class="px-6 py-4">
										<div class="text-sm font-medium text-gray-900">
											{{ $item->tipoUnidad->nombre }}
										</div>
									</td>
									<td class="px-6 py-4">
										<div class="text-sm font-medium text-gray-900">
											{{ $item->propietario->integrante->nombre.' '.$item->propietario->integrante->apellido }}
										</div>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
										<a href="#" class="text-indigo-600 hover:text-indigo-900"><i
												class="fas fa-zoom"></i>Ver</a>
										<a href="#" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-edit"></i></a>
										<a href="#" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-trash"></i></a>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
						@else
						Su búsqueda no tuvo resultado
						@endif
					</div>
				</div>
			</div>
		</div>
		{{-- /tabla --}}
	</div>

</div>