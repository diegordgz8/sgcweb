<?php

namespace Tests\Feature;

use App\Livewire\Gasto\NuevoGasto;
use App\Livewire\Pago\NuevoPago as NuevoPagoGasto;
use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago as NuevoPagoPropietario;
use App\Models\Banco;
use App\Models\Categoria;
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
use App\Models\Servicio;
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
            ->set('formaPago', 'Transferencia')
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

    public function test_owner_payment_only_offers_accounts_whose_fund_is_in_the_payment_currency(): void
    {
        [$propietario, $factura] = $this->propietarioConFactura(150000, 'ARS');
        $cuentaPesos = $this->cuentaConFondo('ARS');
        $cuentaDolares = $this->cuentaConFondo('USD');

        $prueba = Livewire::actingAs($propietario)->test(NuevoPagoPropietario::class)
            ->call('mostrarForm', $factura->id)
            ->set('formaPago', 'Transferencia')
            ->assertSee($cuentaPesos->numeroOculto)
            ->assertDontSee($cuentaDolares->numeroOculto)
            ->set('cuentaId', $cuentaPesos->id)
            ->set('moneda', 'USD')
            ->assertSet('cuentaId', 0)
            ->assertSee($cuentaDolares->numeroOculto)
            ->assertDontSee($cuentaPesos->numeroOculto);

        // Una cuenta de otra moneda se rechaza aunque se envíe a mano
        $prueba->set('cuentaId', $cuentaPesos->id)
            ->set('descripcion', 'Cuota')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('referencia', '52412356')
            ->set('monto', 50)
            ->call('save')
            ->assertHasErrors(['cuentaId' => 'exists']);
    }

    public function test_expense_amount_is_the_sum_of_the_provider_services(): void
    {
        Categoria::factory()->create();
        $proveedor = Proveedor::factory()->create();
        [$pintura, $limpieza] = Servicio::factory()->count(2)->create();
        $proveedor->servicios()->attach([$pintura->id, $limpieza->id]);

        Livewire::actingAs($this->usuarioCondominio())->test(NuevoGasto::class)
            ->set('readyToLoad', true)
            ->set('proveedorId', $proveedor->id)
            ->assertSee($pintura->nombre)
            ->set('servicios', [(string) $pintura->id, (string) $limpieza->id])
            ->set("montos.{$pintura->id}", 1500)
            ->set("montos.{$limpieza->id}", 500)
            ->assertSet('monto', 2000.0)
            ->set('descripcion', 'Mantenimiento')
            ->set('calculo', 'Total de inmuebles')
            ->set('comienzoCobro', now()->format('Y-m'))
            ->set('moneda', 'ARS')
            ->set('factura', 'F-10')
            ->call('save')
            ->assertHasNoErrors();

        $gasto = Gasto::sole();
        $this->assertEquals(2000, $gasto->monto);
        $this->assertSame('ARS', $gasto->moneda);
        $this->assertSame($proveedor->id, $gasto->proveedor_id);
        $this->assertSame(2, $gasto->servicios()->count());
    }

    public function test_expense_payment_by_transfer_lists_bank_funds_in_the_payment_currency(): void
    {
        $gasto = Gasto::create([
            'descripcion' => 'Ascensor', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'ARS', 'monto' => 1000, 'saldo' => 1000,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-3',
        ]);
        $cuentaPesos = $this->cuentaConFondo('ARS');
        $cuentaDolares = $this->cuentaConFondo('USD');
        $cuentaPesos->fondo->update(['saldo' => 5000]);

        Livewire::actingAs($this->usuarioCondominio())->test(NuevoPagoGasto::class)
            ->call('mostrarForm', $gasto)
            ->set('formaPago', 'Transferencia')
            ->assertSee($cuentaPesos->numeroOculto)
            ->assertDontSee($cuentaDolares->numeroOculto)
            ->set('fondoId', $cuentaPesos->fondo->id)
            ->set('descripcion', 'Pago del ascensor')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('recibo', '1003')
            ->set('referencia', '1234')
            ->set('monto', 1000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(4000, $cuentaPesos->fondo->fresh()->saldo);
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
