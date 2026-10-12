<?php

namespace Tests\Feature;

use App\Livewire\Pago\NuevoPago as NuevoPagoGasto;
use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago as NuevoPagoPropietario;
use App\Models\Banco;
use App\Models\Cuenta;
use App\Models\Factura;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Integrante;
use App\Models\Iva;
use App\Models\Moneda;
use App\Models\PagoPropietario;
use App\Models\Propietario;
use App\Models\Proveedor;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Registro de pagos de punta a punta a través de los componentes, con varias monedas.
 */
class PagosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subMinute()]);
        Banco::factory()->create();
    }

    public function test_owner_payment_by_transfer_is_registered_and_confirmed_into_the_account_fund(): void
    {
        [$propietario, $factura] = $this->propietarioConFactura(150000, 'ARS');
        $cuenta = $this->cuentaConFondo('ARS');

        Livewire::actingAs($propietario)->test(NuevoPagoPropietario::class)
            ->call('mostrarForm', $factura->id)
            ->set('descripcion', 'Cuota de octubre')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('formaPago', 'Transferencia')
            ->set('cuentaId', $cuenta->id)
            ->set('referencia', '52412356')
            ->set('monto', 150000)
            ->call('save')
            ->assertHasNoErrors();

        $pago = PagoPropietario::sole();
        $this->assertSame($cuenta->fondo->id, $pago->fondo_id);
        $this->assertSame('ARS', $pago->moneda);

        Livewire::actingAs($this->usuarioCondominio())->test(ConfirmarPago::class)
            ->call('confirmar', $pago->id);

        $this->assertSame('Pagada', $factura->fresh()->estado);
        $this->assertEquals(150000, $cuenta->fondo->fresh()->saldo);
    }

    public function test_owner_payment_in_another_currency_is_converted_when_confirmed(): void
    {
        [$propietario, $factura] = $this->propietarioConFactura(150000, 'ARS');
        $cuenta = $this->cuentaConFondo('USD');

        Livewire::actingAs($propietario)->test(NuevoPagoPropietario::class)
            ->call('mostrarForm', $factura->id)
            ->set('moneda', 'USD')
            ->assertSet('montoFacturaConvertido', 92.59)
            ->set('descripcion', 'Cuota en dólares')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('formaPago', 'Transferencia')
            ->set('cuentaId', $cuenta->id)
            ->set('referencia', '11112222')
            ->set('monto', 50)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($this->usuarioCondominio())->test(ConfirmarPago::class)
            ->call('confirmar', PagoPropietario::sole()->id);

        // 50 USD = 81.000 ARS abonados a la factura; el fondo en USD recibe los 50
        $this->assertEquals(69000, $factura->fresh()->monto_por_pagar);
        $this->assertEquals(50, $cuenta->fondo->fresh()->saldo);
    }

    public function test_owner_payment_rejects_an_account_without_fund(): void
    {
        [$propietario, $factura] = $this->propietarioConFactura(150000, 'ARS');
        $cuenta = Cuenta::factory()->create(['publica' => true]);

        Livewire::actingAs($propietario)->test(NuevoPagoPropietario::class)
            ->call('mostrarForm', $factura->id)
            ->assertDontSee($cuenta->numeroOculto)
            ->set('descripcion', 'Cuota')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('formaPago', 'Transferencia')
            ->set('cuentaId', $cuenta->id)
            ->set('referencia', '52412356')
            ->set('monto', 150000)
            ->call('save')
            ->assertHasErrors(['cuentaId' => 'exists']);

        $this->assertSame(0, PagoPropietario::count());
    }

    public function test_expense_payment_from_a_fund_in_another_currency(): void
    {
        $gasto = Gasto::create([
            'descripcion' => 'Pintura', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'USD', 'monto' => 100, 'saldo' => 100,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-1',
        ]);
        $fondo = Fondo::create(['descripcion' => 'Caja en pesos', 'saldo' => 200000, 'moneda' => 'ARS']);

        Livewire::actingAs($this->usuarioCondominio())->test(NuevoPagoGasto::class)
            ->call('mostrarForm', $gasto)
            ->set('moneda', 'ARS')
            ->set('formaPago', 'Efectivo')
            ->set('fondoId', $fondo->id)
            ->set('descripcion', 'Pago de la pintura')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('recibo', '1001')
            ->set('monto', 162000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(0, $gasto->fresh()->saldo);
        $this->assertEquals(38000, $fondo->fresh()->saldo);
    }

    public function test_expense_payment_cannot_exceed_the_fund_balance(): void
    {
        $gasto = Gasto::create([
            'descripcion' => 'Pintura', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'ARS', 'monto' => 100000, 'saldo' => 100000,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-2',
        ]);
        $fondo = Fondo::create(['descripcion' => 'Caja chica', 'saldo' => 5000, 'moneda' => 'ARS']);

        Livewire::actingAs($this->usuarioCondominio())->test(NuevoPagoGasto::class)
            ->call('mostrarForm', $gasto)
            ->set('formaPago', 'Efectivo')
            ->set('fondoId', $fondo->id)
            ->set('descripcion', 'Pago parcial')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('recibo', '1002')
            ->set('monto', 6000)
            ->call('save')
            ->assertHasErrors(['monto' => 'lte']);
    }

    private function usuarioCondominio(): User
    {
        return User::factory()->create()->assignRole('Condominio');
    }

    private function cuentaConFondo(string $moneda): Cuenta
    {
        $cuenta = Cuenta::factory()->create(['publica' => true]);
        Fondo::create(['descripcion' => "Banco en $moneda", 'saldo' => 0, 'moneda' => $moneda, 'cuenta_id' => $cuenta->id]);

        return $cuenta;
    }

    private function propietarioConFactura(float $monto, string $moneda): array
    {
        $usuario = User::factory()->create()->assignRole('Propietario');
        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => $usuario->id,
        ]);
        $unidad = Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'propietario_id' => $propietario->id,
        ]);

        $factura = Factura::forceCreate([
            'numero' => 'F-' . uniqid(), 'monto' => $monto, 'monto_por_pagar' => $monto, 'moneda' => $moneda,
            'fecha' => now(), 'unidad_id' => $unidad->id, 'iva_id' => Iva::create(['factor' => 21, 'fecha' => now()])->id,
        ]);

        return [$usuario, $factura];
    }
}
