{{-- Selección múltiple de monedas: cada moneda elegida se muestra como etiqueta.
     Admite x-model para compartir la lista seleccionada con el formulario. --}}
<div x-data="{
        seleccionadas: @js(array_values((array) $seleccionado)),
        etiquetas: @js($monedas->mapWithKeys(fn ($moneda) => [$moneda['code'] => $moneda['code'] . ' - ' . $moneda['name']])),
        agregar(codigo) {
            if (codigo && ! this.seleccionadas.includes(codigo)) this.seleccionadas.push(codigo);
        },
        quitar(codigo) {
            this.seleccionadas = this.seleccionadas.filter(item => item !== codigo);
        },
    }" x-modelable="seleccionadas" {{ $attributes->except(['name', 'id', 'class']) }}>

    <select id="{{ $attributes->get('id') }}" class="form-control {{ $attributes->get('class') }}"
        x-on:change="agregar($event.target.value); $event.target.value = ''">
        <option value="">Seleccione una moneda para agregarla</option>
        @foreach ($monedas as $moneda)
            <option value="{{ $moneda['code'] }}" x-bind:disabled="seleccionadas.includes('{{ $moneda['code'] }}')">
                {{ $moneda['code'] }} - {{ $moneda['name'] }} ({{ $moneda['symbol'] }})
            </option>
        @endforeach
    </select>

    <div class="flex flex-wrap gap-2 mt-2">
        <template x-for="codigo in seleccionadas" :key="codigo">
            <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700">
                <span x-text="etiquetas[codigo] ?? codigo"></span>
                <button type="button" class="ml-2 font-bold text-indigo-500 hover:text-indigo-800"
                    x-on:click="quitar(codigo)" x-bind:aria-label="'Quitar ' + codigo">&times;</button>
                <input type="hidden" name="{{ $attributes->get('name') }}[]" x-bind:value="codigo">
            </span>
        </template>

        <span x-show="seleccionadas.length === 0" class="text-sm text-gray-500">
            No ha seleccionado ninguna moneda.
        </span>
    </div>
</div>
