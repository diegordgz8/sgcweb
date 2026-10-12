<select {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccione una moneda</option>
    @foreach ($monedas as $moneda)
        @if ($visibles)
            <template x-if="{{ $visibles }}.includes('{{ $moneda['code'] }}')">
        @endif
        <option value="{{ $moneda['code'] }}" @selected(in_array($moneda['code'], (array) $seleccionado))>
            {{ $moneda['code'] }} - {{ $moneda['name'] }} ({{ $moneda['symbol'] }})
        </option>
        @if ($visibles)
            </template>
        @endif
    @endforeach
</select>
