@if ($pais)
    {{-- Solo muestra las zonas horarias del país indicado; al cambiar de país
         elige la primera zona del nuevo país si la actual no le corresponde. --}}
    <select {{ $attributes->merge(['class' => 'form-control']) }}
        x-data="{
            zonas: @js($zonasPorPais),
            zona: @js($seleccionado),
            ajustar() {
                const lista = this.zonas[{{ $pais }}] ?? [];
                if (! lista.includes(this.zona)) this.zona = lista[0] ?? '';
            },
        }"
        x-init="ajustar(); $watch('{{ $pais }}', () => ajustar())"
        x-on:change="zona = $event.target.value">
        <option value="">Seleccione una zona horaria</option>
        <template x-for="nombre in zonas[{{ $pais }}] ?? []" :key="nombre">
            <option x-bind:value="nombre" x-text="nombre" x-bind:selected="nombre === zona"></option>
        </template>
    </select>
@else
    <select {{ $attributes->merge(['class' => 'form-control']) }}>
        <option value="">Seleccione una zona horaria</option>
        @foreach ($zonasHorarias as $zonaHoraria)
            <option value="{{ $zonaHoraria['name'] }}" @selected($zonaHoraria['name'] == $seleccionado)>
                {{ $zonaHoraria['name'] }}
            </option>
        @endforeach
    </select>
@endif
