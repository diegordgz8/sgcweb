<select {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccione un idioma</option>
    @foreach ($idiomas as $idioma)
        <option value="{{ $idioma['code'] }}" @selected($idioma['code'] == $seleccionado)>
            {{ $idioma['name'] }} ({{ $idioma['name_native'] }})
        </option>
    @endforeach
</select>
