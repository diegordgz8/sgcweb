<select {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccione un país</option>
    @foreach ($paises as $pais)
        <option value="{{ $pais['iso2'] }}" @selected($pais['iso2'] == $seleccionado)>
            {{ $pais['flag'] }} {{ $pais['name'] }}
        </option>
    @endforeach
</select>
