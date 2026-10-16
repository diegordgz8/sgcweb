<?php

namespace Tests\Feature;

use App\Livewire\Admin\ConfigurarMoneda;
use App\Livewire\Admin\ConfigurarTasaCambio;
use App\Livewire\Fondo\NuevoFondo;
use App\Livewire\Fondo\TablaFondo;
use App\Livewire\Pago\NuevoPago;
use App\Models\Banco;
use App\Models\Condominio;
use App\Models\Cuenta;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\Proveedor;
use App\Models\TasaCambio;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Nnjeim\World\Models\Currency;
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
    public function test_a_currency_is_added_with_its_initial_rate(): void
    {
        Currency::forceCreate(['country_id' => 1, 'name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'symbol_native' => '€']);

        Livewire::actingAs($this->usuario)->test(ConfigurarMoneda::class)
            ->assertSee('Moneda base')
            ->assertSee('1 USD = 1.620,00 ARS')
            ->set('moneda', 'EUR')
            ->call('agregar')
            ->assertHasErrors(['tasa' => 'required'])
            ->set('tasa', 0.92)
            ->call('agregar')
            ->assertHasNoErrors();

        $this->assertSame(['USD', 'ARS', 'EUR'], Moneda::codigos());
        $this->assertEquals(0.92, TasaCambio::vigente('EUR')->tasa);
    }

    public function test_an_existing_currency_cannot_be_added_again(): void
    {
        Currency::forceCreate(['country_id' => 1, 'name' => 'Argentine Peso', 'code' => 'ARS', 'symbol' => 'AR$', 'symbol_native' => '$']);

        Livewire::actingAs($this->usuario)->test(ConfigurarMoneda::class)
            ->set('moneda', 'ARS')
            ->set('tasa', 1500)
            ->call('agregar')
            ->assertHasErrors(['moneda' => 'not_in']);

        $this->assertSame(1, Moneda::where('codigo', 'ARS')->count());
    }

    public function test_currencies_card_has_no_way_to_delete_a_currency(): void
    {
        $metodos = get_class_methods(ConfigurarMoneda::class);

        $this->assertEmpty(array_filter($metodos, fn ($metodo) => preg_match('/elimin|borr|delete|destroy|quitar/i', $metodo)));
    }

    public function test_rate_update_adds_a_new_record_for_the_chosen_currency(): void
    {
        Livewire::actingAs($this->usuario)->test(ConfigurarTasaCambio::class)
            ->assertSet('moneda', 'ARS')
            ->assertSet('tasa', '1620.000000')
            ->set('tasa', 1700)
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertSame(2, TasaCambio::where('moneda', 'ARS')->count());
        $this->assertEquals(1700, TasaCambio::vigente('ARS')->tasa);
    }

    public function test_rate_screen_explains_when_there_is_a_single_currency(): void
    {
        // El modelo no permite eliminar monedas; se arma el escenario directo en la tabla
        DB::table('tasas_cambio')->delete();
        DB::table('monedas')->where('codigo', 'ARS')->delete();

        Livewire::actingAs($this->usuario)->test(ConfigurarTasaCambio::class)
            ->assertSet('moneda', null)
            ->assertSee('El condominio trabaja solo con USD');
    }
    public function test_admin_home_shows_the_currencies_card(): void
    {
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Consor Gurruchaga', 'direccion' => 'Calle 1', 'pais' => 'AR', 'zona_horaria' => 'America/Argentina/Buenos_Aires']);
        Mensualidad::create(['monto' => 150000, 'moneda' => 'ARS']);
        Iva::create(['factor' => 21, 'fecha' => now()]);

        $this->actingAs($this->usuario)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Monedas')
            ->assertSee('Agregar moneda')
            ->assertSee('1 USD = 1.620,00 ARS');
    }
    public function test_fund_is_linked_to_the_chosen_bank_account(): void
    {
        Banco::factory()->create();
        $cuenta = Cuenta::factory()->create();

        Livewire::actingAs($this->usuario)->test(NuevoFondo::class)
            ->set('descripcion', 'Banco en pesos')
            ->set('moneda', 'ARS')
            ->set('saldoInicial', 1000)
            ->set('cuentaId', $cuenta->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($cuenta->id, Fondo::where('descripcion', 'Banco en pesos')->value('cuenta_id'));

        Livewire::actingAs($this->usuario)->test(NuevoFondo::class)
            ->set('descripcion', 'Otro fondo')
            ->set('saldoInicial', 1000)
            ->set('cuentaId', $cuenta->id)
            ->call('save')
            ->assertHasErrors(['cuentaId' => 'unique']);
    }
    public function test_billing_currency_is_changed_from_the_currencies_card(): void
    {
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Prueba', 'direccion' => 'Calle 1', 'moneda_facturacion' => 'USD']);

        Livewire::actingAs($this->usuario)->test(ConfigurarMoneda::class)
            ->assertSee('Facturación')
            ->call('mostrarFacturacion')
            ->assertSet('monedaFacturacion', 'USD')
            ->set('monedaFacturacion', 'EUR')
            ->call('guardarFacturacion')
            ->assertHasErrors('monedaFacturacion')
            ->set('monedaFacturacion', 'ARS')
            ->call('guardarFacturacion')
            ->assertHasNoErrors();

        $this->assertSame('ARS', Moneda::facturacion());
    }
}
