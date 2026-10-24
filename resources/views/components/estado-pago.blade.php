@props(['estado'])

{{-- Estado de una factura o de un pago, con el mismo color en todas las listas --}}
@php
    $etiqueta = $estado === 'Por confirmar' ? 'Esperando confirmación' : $estado;

    $color = match ($etiqueta) {
        'Pagada', 'Confirmado' => 'bg-green-100 text-green-800',
        'Esperando confirmación' => 'bg-yellow-100 text-yellow-800',
        'Pago rechazado', 'Rechazado' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap $color"]) }}>
    {{ $etiqueta }}
</span>
