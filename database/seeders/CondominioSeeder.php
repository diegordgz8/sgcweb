<?php

namespace Database\Seeders;

use App\Models\Condominio;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class CondominioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Condominio::create([
            'rif' => 'J-12345678',
            'nombre' => 'Condominio Bloque 13',
            'direccion' => 'Bloque 13, Urbanización "Las Acequias", Municipio Cocorote',
            'pais' => 'VE',
            'zona_horaria' => 'America/Caracas',
        ]);

        // Base USD y bolívares con su tasa: 1 USD = 40 VES
        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'VES']);

        TasaCambio::create([
            'moneda' => 'VES',
            'tasa' => 40,
            'fecha' => now(),
        ]);

        Mensualidad::create([
            'monto' => 20,
            'moneda' => 'USD',
        ]);
    }
}
