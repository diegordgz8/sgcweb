{{-- Con "visibles" solo se muestran los códigos de esa lista de Alpine, y con "modelo" la
     opción seleccionada sigue a esa variable aunque las opciones aparezcan o desaparezcan. --}}
<select {{ $attributes->merge(['class' => 'form-control']) }}
    @if ($modelo) x-on:change="{{ $modelo }} = $event.target.value" @endif>
    <option value="">Seleccione una moneda</option>
    @foreach ($monedas as $moneda)
        @if ($visibles)
            <template x-if="{{ $visibles }}.includes('{{ $moneda['code'] }}')">
        @endif
        <option value="{{ $moneda['code'] }}" @selected(in_array($moneda['code'], (array) $seleccionado))
            @if ($modelo) x-bind:selected="{{ $modelo }} === '{{ $moneda['code'] }}'" @endif>
            {{ $moneda['code'] }} - {{ $moneda['name'] }} ({{ $moneda['symbol'] }})
        </option>
        @if ($visibles)
            </template>
        @endif
    @endforeach
</select>
