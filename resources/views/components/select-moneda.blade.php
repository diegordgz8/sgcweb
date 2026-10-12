<select {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccione una moneda</option>
    @foreach ($monedas as $moneda)
        <option value="{{ $moneda['code'] }}" @selected($moneda['code'] == $seleccionado)>
            {{ $moneda['code'] }} - {{ $moneda['name'] }} ({{ $moneda['symbol'] }})
        </option>
    @endforeach
</select>
