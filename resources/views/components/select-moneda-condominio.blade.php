<select {{ $attributes->merge(['class' => 'form-control']) }}>
    @foreach ($monedas as $codigo => $nombre)
        <option value="{{ $codigo }}" @selected($codigo == $seleccionado)>{{ $nombre }}</option>
    @endforeach
</select>
