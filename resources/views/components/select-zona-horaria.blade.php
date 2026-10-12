<select {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccione una zona horaria</option>
    @foreach ($zonasHorarias as $zonaHoraria)
        <option value="{{ $zonaHoraria['name'] }}" @selected($zonaHoraria['name'] == $seleccionado)>
            {{ $zonaHoraria['name'] }}
        </option>
    @endforeach
</select>
