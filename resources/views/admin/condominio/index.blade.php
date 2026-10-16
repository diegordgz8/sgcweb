<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Condominio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div>

                    {{-- formulario --}}
                    <form action="{{ route('admin.condominio.store') }}" method="POST"
                        x-data="{
                            pais: @js(old('pais', 'VE')),
                            monedas: @js(old('monedas', ['VES', 'USD'])),
                            base: @js(old('moneda_base', 'USD')),
                            facturacion: @js(old('moneda_facturacion')),
                            monedaMensualidad: @js(old('moneda')),
                            tasas: @js((object) old('tasas', [])),
                            ajustar() {
                                if (! this.monedas.includes(this.base)) this.base = this.monedas[0] ?? '';
                                if (! this.monedas.includes(this.facturacion)) this.facturacion = this.base;
                                if (! this.monedas.includes(this.monedaMensualidad)) this.monedaMensualidad = this.base;
                            },
                        }"
                        x-init="ajustar(); $watch('monedas', () => ajustar())">
                        <div class="shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 bg-white sm:p-6">
                                <div class="grid grid-cols-6 gap-6">

                                    @csrf

                                    <h3 class="col-span-6 text-center">Datos del condominio</h3>

                                    <div class="col-span-6">
                                        <label for="rif" class="block text-sm font-medium text-gray-700">RIF:</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 flex items-center">
                                                <select id="letra" name="letra"
                                                    class="focus:ring-indigo-500 focus:border-indigo-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md"
                                                    disabled>
                                                    <option value="J">J</option>
                                                </select>
                                            </div>
                                            <input type="text" name="rif" id="rif" value="{{ old('rif') }}"
                                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                        <x-input-error for="rif" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="nombre"
                                            class="block text-sm font-medium text-gray-700">Nombre:</label>
                                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <x-input-error for="nombre" />
                                    </div>

                                    <h3 class="col-span-6 text-center">Configuración regional</h3>

                                    <div class="col-span-6">
                                        <label for="pais" class="block text-sm font-medium text-gray-700">
                                            País:
                                        </label>
                                        <x-select-pais name="pais" id="pais" class="w-full" x-model="pais"
                                            :seleccionado="old('pais', 'VE')" />
                                        <x-input-error for="pais" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="direccion"
                                            class="block text-sm font-medium text-gray-700">Dirección:</label>
                                        <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <x-input-error for="direccion" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="zona_horaria" class="block text-sm font-medium text-gray-700">
                                            Zona horaria:
                                        </label>
                                        <x-select-zona-horaria name="zona_horaria" id="zona_horaria" class="w-full" pais="pais"
                                            :seleccionado="old('zona_horaria', config('app.timezone'))" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Solo se muestran las zonas horarias del país seleccionado.
                                        </p>
                                        <x-input-error for="zona_horaria" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="monedas" class="block text-sm font-medium text-gray-700">
                                            Monedas con las que trabajará el sistema:
                                        </label>
                                        <x-select-monedas name="monedas" id="monedas" class="w-full" x-model="monedas" />
                                        <x-input-error for="monedas" />
                                        <x-input-error for="monedas.*" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="moneda_base" class="block text-sm font-medium text-gray-700">
                                            Moneda base:
                                        </label>
                                        <x-select-moneda name="moneda_base" id="moneda_base" class="w-full"
                                            visibles="monedas" modelo="base" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Las tasas de las demás monedas se expresan contra ella. No se podrá cambiar después.
                                        </p>
                                        <x-input-error for="moneda_base" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="moneda_facturacion" class="block text-sm font-medium text-gray-700">
                                            Moneda de facturación:
                                        </label>
                                        <x-select-moneda name="moneda_facturacion" id="moneda_facturacion" class="w-full"
                                            visibles="monedas" modelo="facturacion" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Moneda en la que se emiten las facturas del cierre de mes. Se puede cambiar desde la administración.
                                        </p>
                                        <x-input-error for="moneda_facturacion" />
                                    </div>

                                    <h3 class="col-span-6 text-center">Tasas de cambio iniciales</h3>

                                    <p class="col-span-6 text-sm text-gray-500" x-show="monedas.length < 2">
                                        Con una sola moneda no hace falta tasa de cambio.
                                    </p>

                                    <template x-for="codigo in monedas.filter(item => item !== base)" :key="codigo">
                                        <div class="col-span-6">
                                            <label x-bind:for="'tasa-' + codigo" class="block text-sm font-medium text-gray-700">
                                                Tasa de <span x-text="codigo"></span>:
                                            </label>
                                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                                <span class="whitespace-nowrap" x-text="'1 ' + base + ' ='"></span>
                                                <input type="text" x-bind:name="'tasas[' + codigo + ']'" x-bind:id="'tasa-' + codigo"
                                                    x-bind:value="tasas[codigo] ?? ''" class="form-control w-full">
                                                <span x-text="codigo"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="col-span-6 -mt-4">
                                        <x-input-error for="tasas.*" />
                                    </div>

                                    <h3 class="col-span-6 text-center">Mensualidad inicial</h3>

                                    <div class="col-span-6">
                                        <label for="monto" class="block text-sm font-medium text-gray-700">
                                            Monto:
                                        </label>
                                        <input type="text" name="monto" id="monto" value="{{ old('monto') }}" class="form-control w-full">
                                        <x-input-error for="monto" />
                                    </div>

                                    <div class="col-span-6">
                                        <label for="moneda" class="block text-sm font-medium text-gray-700">
                                            Moneda:
                                        </label>
                                        <x-select-moneda name="moneda" id="moneda" class="w-full"
                                            visibles="monedas" modelo="monedaMensualidad" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Solo se muestran las monedas seleccionadas en la configuración regional.
                                        </p>
                                        <x-input-error for="moneda" />
                                    </div>

                                </div>
                            </div>

                            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                                <x-button type="submit">
                                    Registrar
                                </x-button>
                            </div>

                        </div>
                    </form>
                    {{-- /formulario --}}

                </div>

            </div>
        </div>
    </div>

</x-app-layout>
