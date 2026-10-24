<?php

namespace Tests\Feature;

use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago;
use App\Models\AjusteSaldo;
use App\Models\Condominio;
use App\Models\Factura;
use App\Models\Fondo;
use App\Models\Integrante;
use App\Models\Interes;
use App\Models\Item;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\PagoPropietario;
use App\Models\Propietario;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use App\Services\CierreMesService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lo que una unidad paga de menos o de más no se pierde: queda como faltante o saldo a
 * favor y se cobra o descuenta en su próxima factura.
 */
class SaldoUnidadTest extends TestCase
{
    use RefreshDatabase;

    private Unidad $unidad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subMinute()]);
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Prueba', 'direccion' => 'Calle 1', 'moneda_facturacion' => 'ARS']);
        Iva::create(['factor' => 21, 'fecha' => now()]);
        Mensualidad::create(['monto' => 150000, 'moneda' => 'ARS']);

        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => User::factory()->create()->assignRole('Propietario')->id,
        ]);
        $this->unidad = Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create(['area' => 50])->id,
            'propietario_id' => $propietario->id,
        ]);
    }

    public function test_an_underpayment_is_charged_in_the_next_invoice(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 140000));

        $this->assertSame('Pagada', $septiembre->fresh()->estado);

        $octubre = $this->cerrar('2026-10');

        // Mensualidad de octubre + los 10.000 que faltaron en septiembre
        $this->assertEquals(160000, $octubre->monto);
        $this->assertSame('Pendiente', $octubre->estado);
        $this->assertSame($octubre->id, AjusteSaldo::sole()->factura_aplicada_id);
        $this->assertTrue($octubre->items()->where('itemable_type', AjusteSaldo::class)->where('monto', 10000)->exists());
    }

    public function test_even_a_few_cents_missing_are_charged(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 149999.99));

        $this->assertEquals(150000.01, $this->cerrar('2026-10')->monto);
    }

    public function test_an_overpayment_is_discounted_from_the_next_invoice(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 155000));

        $ajuste = AjusteSaldo::sole();
        $this->assertSame('A favor', $ajuste->tipo);
        $this->assertEquals(5000, $ajuste->monto);

        $this->assertEquals(145000, $this->cerrar('2026-10')->monto);
    }

    public function test_a_credit_larger_than_the_next_invoice_carries_over(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 400000));

        // 250.000 a favor cubren octubre (150.000) y sobran 100.000 para noviembre
        $octubre = $this->cerrar('2026-10');
        $this->assertEquals(0, $octubre->monto);
        $this->assertSame('Pagada', $octubre->estado);

        $noviembre = $this->cerrar('2026-11');
        $this->assertEquals(50000, $noviembre->monto);
        $this->assertSame(0, AjusteSaldo::pendientes()->count());
    }

    public function test_a_shortfall_is_converted_when_the_next_invoice_is_in_another_currency(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 133800));

        Condominio::first()->update(['moneda_facturacion' => 'USD']);
        Mensualidad::query()->update(['monto' => 100, 'moneda' => 'USD']);

        // Faltan 16.200 ARS = 10 USD
        $this->assertEquals(110, $this->cerrar('2026-10')->monto);
    }

    public function test_a_cash_payment_confirmed_into_a_fund_closes_the_invoice(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $pago = $this->pago($septiembre, 150000, 'Efectivo');
        $caja = Fondo::create(['descripcion' => 'Caja chica', 'saldo' => 0, 'moneda' => 'ARS']);

        Livewire::actingAs(User::factory()->create()->assignRole('Condominio'))->test(ConfirmarPago::class)
            ->call('elegirFondo', $pago->id)
            ->call('aceptar', $caja->id);

        $this->assertSame('Pagada', $septiembre->fresh()->estado);
        $this->assertEquals(150000, $caja->fresh()->saldo);
        $this->assertSame(0, AjusteSaldo::count());
    }

    public function test_a_paid_invoice_does_not_count_as_debt_for_interest(): void
    {
        Interes::create(['factor' => 5, 'meses' => 3, 'estado' => true]);
        $vieja = $this->factura(now()->subMonths(6));
        $vieja->saldar();

        $this->assertNull($this->cerrar('2026-10')->interes_id);
    }

    public function test_an_old_unpaid_shortfall_counts_as_debt_for_interest(): void
    {
        Interes::create(['factor' => 5, 'meses' => 3, 'estado' => true]);
        $vieja = $this->factura(now()->subMonths(6));
        $this->confirmar($this->pago($vieja, 100000));

        $octubre = $this->cerrar('2026-10');

        // La deuda viene de hace 6 meses: el faltante arrastra la antigüedad de su factura
        $this->assertNotNull($octubre->interes_id);
        $this->assertSame($vieja->fecha, $octubre->deuda_desde);
        $this->assertEquals((150000 + 50000) * 1.05, $octubre->monto);
    }

    public function test_the_payment_form_warns_about_the_shortfall_or_credit(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $usuario = $this->unidad->propietario->user;

        Livewire::actingAs($usuario)->test(NuevoPago::class)
            ->call('mostrarForm', $septiembre->id)
            ->set('monto', 140000)
            ->assertSee('Quedará un faltante de')
            ->assertSee('10.000,00')
            ->set('monto', 155000)
            ->assertSee('Quedará un saldo a favor de')
            ->assertSee('5.000,00')
            ->set('monto', 150000)
            ->assertDontSee('Quedará un');
    }

    public function test_the_owner_sees_pending_shortfalls_and_credits(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 140000));

        Livewire::actingAs($this->unidad->propietario->user)->test(NuevoPago::class)
            ->assertSee("Unidad {$this->unidad->numero}:")
            ->assertSee('tiene un faltante de')
            ->assertSee('10.000,00')
            ->assertSee('faltante de la factura ' . $septiembre->numero);
    }

    public function test_a_shortfall_can_be_paid_right_away_instead_of_waiting_for_the_close(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 135000));

        Livewire::actingAs($this->unidad->propietario->user)->test(NuevoPago::class)
            ->assertSee('Pagar ahora')
            ->call('pagarFaltante', $this->unidad->numero)
            ->assertSet('open', true)
            ->assertDontSee('tiene un faltante de');

        $faltante = Factura::where('numero', 'like', 'F%')->sole();
        $this->assertEquals(15000, $faltante->monto);
        $this->assertSame('Pendiente', $faltante->estado);
        $this->assertSame(substr($septiembre->fecha, 0, 10), $faltante->deuda_desde);
        $this->assertSame($faltante->id, AjusteSaldo::sole()->factura_aplicada_id);

        $this->confirmar($this->pago($faltante, 15000));
        $this->assertSame('Pagada', $faltante->fresh()->estado);

        // Ya pagado, el faltante no se vuelve a cobrar en el cierre
        $this->assertEquals(150000, $this->cerrar('2026-10')->monto);
    }

    public function test_underpaying_a_shortfall_invoice_leaves_a_new_shortfall(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 135000));

        $faltante = $this->unidad->facturarFaltantes();
        $this->confirmar($this->pago($faltante, 10000));

        $this->assertEquals(5000, AjusteSaldo::pendientes()->sole()->monto);
        $this->assertEquals(155000, $this->cerrar('2026-10')->monto);
    }

    public function test_only_the_owner_can_pay_the_unit_shortfall(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 135000));

        $otro = User::factory()->create()->assignRole('Propietario');
        Propietario::forceCreate(['integrante_id' => Integrante::factory()->create()->id, 'user_id' => $otro->id]);

        Livewire::actingAs($otro)->test(NuevoPago::class)
            ->call('pagarFaltante', $this->unidad->numero)
            ->assertForbidden();

        $this->assertSame(0, Factura::where('numero', 'like', 'F%')->count());
    }

    public function test_an_older_pending_invoice_must_be_paid_before_the_shortfall(): void
    {
        $septiembre = $this->cerrar('2026-09');
        $this->confirmar($this->pago($septiembre, 135000));
        $vieja = $this->factura(now()->subMonths(2));

        Livewire::actingAs($this->unidad->propietario->user)->test(NuevoPago::class)
            ->call('pagarFaltante', $this->unidad->numero)
            ->assertSet('open', false);

        // La factura del faltante se genera igual y queda en la lista para pagarla después
        $this->assertTrue(Factura::where('numero', 'like', 'F%')->where('estado', 'Pendiente')->exists());
        $this->assertSame('Pendiente', $vieja->fresh()->estado);
    }

    private function cerrar(string $mes): Factura
    {
        $cierre = app(CierreMesService::class)->cerrar($mes, Moneda::facturacion());

        return Factura::where('cierre_mes_id', $cierre->id)->sole();
    }

    private function pago(Factura $factura, float $monto, string $forma = 'Transferencia'): PagoPropietario
    {
        return PagoPropietario::forceCreate([
            'descripcion' => 'Pago', 'monto' => $monto, 'fecha' => now(), 'forma_pago' => $forma,
            'moneda' => 'ARS', 'unidad_id' => $factura->unidad_id, 'factura_id' => $factura->id,
        ]);
    }

    private function confirmar(PagoPropietario $pago): void
    {
        $pago->pagarFactura();
    }

    private function factura($fecha): Factura
    {
        return Factura::forceCreate([
            'numero' => 'F-' . uniqid(), 'monto' => 150000, 'monto_por_pagar' => 150000, 'moneda' => 'ARS',
            'fecha' => $fecha->toDateString(), 'unidad_id' => $this->unidad->id, 'iva_id' => Iva::first()->id,
        ]);
    }
}
