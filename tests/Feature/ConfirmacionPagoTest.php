<?php

namespace Tests\Feature;

use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago;
use App\Livewire\PagoPropietario\ShowPago;
use App\Livewire\PagoPropietario\TablaPago;
use App\Models\Banco;
use App\Models\Cuenta;
use App\Models\Factura;
use App\Models\Fondo;
use App\Models\Integrante;
use App\Models\Iva;
use App\Models\Moneda;
use App\Models\PagoPropietario;
use App\Models\Propietario;
use App\Models\Recibo;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El condominio confirma o rechaza los pagos desde la lista o desde el detalle de cada pago,
 * y un rechazo siempre lleva un motivo que ve el propietario.
 */
class ConfirmacionPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $administrador;

    private Factura $factura;

    private Fondo $fondoCuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subMinute()]);
        Banco::factory()->create();

        $this->administrador = User::factory()->create()->assignRole('Condominio');
        $this->propietario = User::factory()->create()->assignRole('Propietario');
        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => $this->propietario->id,
        ]);
        $unidad = Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'propietario_id' => $propietario->id,
        ]);

        $this->factura = Factura::forceCreate([
            'numero' => '202611-' . $unidad->numero . '-1', 'monto' => 150000, 'monto_por_pagar' => 150000,
            'moneda' => 'ARS', 'fecha' => now()->toDateString(), 'unidad_id' => $unidad->id,
            'iva_id' => Iva::create(['factor' => 21, 'fecha' => now()])->id,
        ]);

        $cuenta = Cuenta::factory()->create(['publica' => true]);
        $this->fondoCuenta = Fondo::create(['descripcion' => 'Banco en pesos', 'saldo' => 0, 'moneda' => 'ARS', 'cuenta_id' => $cuenta->id]);
    }

    public function test_a_rejection_needs_a_reason_that_the_owner_then_sees(): void
    {
        $pago = $this->pago();

        Livewire::actingAs($this->administrador)->test(ConfirmarPago::class)
            ->call('abrirRechazar', $pago->id)
            ->assertSet('openRechazar', true)
            ->call('rechazar')
            ->assertHasErrors(['motivoRechazo' => 'required'])
            ->set('motivoRechazo', 'La referencia no coincide.')
            ->call('rechazar')
            ->assertHasNoErrors()
            ->assertSet('openRechazar', false);

        $pago->refresh();
        $this->assertSame('Rechazado', $pago->estado);
        $this->assertSame('La referencia no coincide.', $pago->motivo_rechazo);

        Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->assertSee('Motivo: La referencia no coincide.')
            ->call('mostrarForm', $this->factura->id)
            ->assertSet('motivoRechazo', 'La referencia no coincide.');

        Livewire::actingAs($this->propietario->fresh())->test(TablaPago::class)
            ->set('readyToLoad', true)
            ->assertSee('La referencia no coincide.');
    }

    public function test_the_payment_detail_offers_confirm_reject_and_back_to_the_condominium(): void
    {
        Livewire::actingAs($this->administrador)->test(ShowPago::class, ['pago' => $this->pago()])
            ->assertSee('Esperando confirmación')
            ->assertSee('wire:click="confirmar(', false)
            ->assertSee('wire:click="abrirRechazar(', false)
            ->assertSee('Atrás')
            ->assertSee(route('pago.confirmar'), false);
    }

    public function test_a_payment_is_confirmed_from_its_detail(): void
    {
        $pago = $this->pago();

        Livewire::actingAs($this->administrador)->test(ShowPago::class, ['pago' => $pago])
            ->call('confirmar', $pago->id)
            ->assertRedirect(route('pago.confirmar'));

        $this->assertSame('Confirmado', $pago->fresh()->estado);
        $this->assertSame('Pagada', $this->factura->fresh()->estado);
        $this->assertEquals(150000, $this->fondoCuenta->fresh()->saldo);
        $this->assertSame(1, Recibo::where('pago_propietario_id', $pago->id)->count());
    }

    public function test_a_cash_payment_asks_for_the_fund_from_its_detail(): void
    {
        $pago = $this->pago('Efectivo');
        $caja = Fondo::create(['descripcion' => 'Caja chica', 'saldo' => 0, 'moneda' => 'ARS']);

        Livewire::actingAs($this->administrador)->test(ShowPago::class, ['pago' => $pago])
            ->call('confirmar', $pago->id)
            ->assertSet('openConfirmar', true)
            ->assertSee('Caja chica')
            ->assertDontSee('Banco en pesos')
            ->call('aceptar', $caja->id)
            ->assertRedirect(route('pago.confirmar'));

        $this->assertSame('Confirmado', $pago->fresh()->estado);
        $this->assertEquals(150000, $caja->fresh()->saldo);
    }

    public function test_a_payment_is_rejected_from_its_detail_with_a_reason(): void
    {
        $pago = $this->pago();

        Livewire::actingAs($this->administrador)->test(ShowPago::class, ['pago' => $pago])
            ->call('abrirRechazar', $pago->id)
            ->set('motivoRechazo', 'El comprobante está ilegible.')
            ->call('rechazar')
            ->assertRedirect(route('pago.confirmar'));

        $this->assertSame('Rechazado', $pago->fresh()->estado);
        $this->assertSame('El comprobante está ilegible.', $pago->fresh()->motivo_rechazo);
        $this->assertSame('Pendiente', $this->factura->fresh()->estado);

        Livewire::actingAs($this->administrador)->test(ShowPago::class, ['pago' => $pago->fresh()])
            ->assertSee('Motivo del rechazo:')
            ->assertSee('El comprobante está ilegible.')
            ->assertDontSee('wire:click="confirmar(', false);
    }

    public function test_the_owner_only_sees_the_back_button_and_cannot_confirm(): void
    {
        $pago = $this->pago();

        Livewire::actingAs($this->propietario)->test(ShowPago::class, ['pago' => $pago])
            ->assertDontSee('wire:click="confirmar(', false)
            ->assertSee(route('pago-propietario.index'), false)
            ->call('confirmar', $pago->id)
            ->assertForbidden();

        $this->assertSame('Por confirmar', $pago->fresh()->estado);
    }

    public function test_a_resolved_payment_is_not_confirmed_twice(): void
    {
        $pago = $this->pago();

        Livewire::actingAs($this->administrador)->test(ConfirmarPago::class)
            ->call('confirmar', $pago->id)
            ->call('confirmar', $pago->id);

        $this->assertSame(1, Recibo::count());
        $this->assertEquals(150000, $this->fondoCuenta->fresh()->saldo);
    }

    private function pago(string $forma = 'Transferencia'): PagoPropietario
    {
        return PagoPropietario::forceCreate([
            'descripcion' => 'Cuota de noviembre', 'monto' => 150000, 'fecha' => now()->toDateString(),
            'referencia' => '52412356', 'forma_pago' => $forma, 'moneda' => 'ARS',
            'fondo_id' => $forma === 'Transferencia' ? $this->fondoCuenta->id : null,
            'unidad_id' => $this->factura->unidad_id, 'factura_id' => $this->factura->id,
        ])->fresh();
    }
}
