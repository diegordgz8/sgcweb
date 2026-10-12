<?php

namespace Tests\Feature;

use App\Livewire\Fondo\NuevoFondo;
use App\Livewire\Fondo\TablaFondo;
use App\Livewire\Pago\NuevoPago;
use App\Models\Condominio;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Moneda;
use App\Models\Proveedor;
use App\Models\TasaCambio;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pantallas que trabajan con las monedas configuradas del condominio.
 */
class MonedasEnPantallasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->usuario = User::factory()->create()->assignRole('Condominio');

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subMinute()]);
    }

    public function test_fund_can_be_created_in_any_condominium_currency(): void
    {
        Livewire::actingAs($this->usuario)->test(NuevoFondo::class)
            ->assertSet('moneda', 'USD')
            ->assertSee('ARS - Peso argentino')
            ->set('descripcion', 'Caja chica')
            ->set('moneda', 'ARS')
            ->set('saldoInicial', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fondos', ['descripcion' => 'Caja chica', 'moneda' => 'ARS']);
    }

    public function test_fund_rejects_a_currency_outside_the_condominium(): void
    {
        Livewire::actingAs($this->usuario)->test(NuevoFondo::class)
            ->set('descripcion', 'Fondo en euros')
            ->set('moneda', 'EUR')
            ->call('save')
            ->assertHasErrors('moneda');
    }

    public function test_expense_payment_shows_the_balance_converted_to_the_payment_currency(): void
    {
        $gasto = Gasto::create([
            'descripcion' => 'Pintura', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'USD', 'monto' => 100, 'saldo' => 100,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-1',
        ]);

        Livewire::actingAs($this->usuario)->test(NuevoPago::class)
            ->call('mostrarForm', $gasto)
            ->assertSet('moneda', 'USD')
            ->assertSet('montoGastoConvertido', 100.0)
            ->set('moneda', 'ARS')
            ->assertSet('montoGastoConvertido', 162000.0)
            ->assertSee('El monto se convierte de ARS a USD');
    }

    public function test_fund_table_filters_by_currency_and_formats_balances(): void
    {
        Fondo::create(['descripcion' => 'Fondo pesos', 'saldo' => 150000, 'moneda' => 'ARS']);
        Fondo::create(['descripcion' => 'Fondo dolares', 'saldo' => 250, 'moneda' => 'USD']);

        Livewire::actingAs($this->usuario)->test(TablaFondo::class)
            ->set('readyToLoad', true)
            ->assertSee('Fondo pesos')
            ->assertSee('Fondo dolares')
            ->set('moneda', 'ARS')
            ->assertSee('Fondo pesos')
            ->assertDontSee('Fondo dolares');
    }

    public function test_total_balance_report_has_one_subtotal_per_currency(): void
    {
        Fondo::create(['descripcion' => 'Fondo pesos', 'saldo' => 150000, 'moneda' => 'ARS']);
        Fondo::create(['descripcion' => 'Fondo dolares', 'saldo' => 250, 'moneda' => 'USD']);

        $condominio = new Condominio(['rif' => 'J-1', 'nombre' => 'Prueba', 'direccion' => 'Calle 1']);
        $html = view('reporte.saldoTotal', ['fondos' => Fondo::all(), 'condominio' => $condominio])->render();

        $this->assertStringContainsString('Total (ARS)', $html);
        $this->assertStringContainsString('Total (USD)', $html);
        $this->assertStringContainsString('150.000,00', $html);
    }
}
