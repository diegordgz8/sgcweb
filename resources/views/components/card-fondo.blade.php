<div
    {{ $attributes->merge(['class' => 'shadow-lg group border hover:border-blue-500 transition-colors duration-1000 transform rounded-xl p-4 bg-white relative overflow-hidden']) }}>

    <div
        class="flex items-center border-b-2 group-hover:border-blue-500 transition-colors duration-1000 transform mb-2 pb-2">
        <div class="w-12 h-12 shrink-0 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center"
            title="{{ \App\Models\Moneda::nombreDe($fondo->moneda) }}">
            {{ $fondo->moneda }}
        </div>

        <div class="pl-3">
            <div class="text-lg text-gray-700">
                {{ $fondo->descripcion }}
            </div>
        </div>
    </div>
    <div class="w-full">
        <p class="text-gray-700 text-sm mb-2">
            Saldo:
        </p>
        <p class="text-gray-700 font-black text-xl text-center mb-2">
            <x-monto :monto="$fondo->saldo" :moneda="$fondo->moneda" />
        </p>
    </div>
</div>
