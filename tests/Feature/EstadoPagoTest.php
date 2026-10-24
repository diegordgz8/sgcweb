<?php

namespace Tests\Feature;

use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago;
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
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El propietario ve en qué estado está el pago de cada factura y puede corregir y
 * reenviar un pago que el condominio rechazó.
 */
class EstadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private Factura $factura;

    private Cuenta $cuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subMinute()]);
        Banco::factory()->create();

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

        $this->cuenta = Cuenta::factory()->create(['publica' => true]);
        Fondo::create(['descripcion' => 'Banco en pesos', 'saldo' => 0, 'moneda' => 'ARS', 'cuenta_id' => $this->cuenta->id]);
    }

    public function test_an_unpaid_invoice_shows_it_is_pending(): void
    {
        Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->assertSee('Pendiente de pago');
    }

    public function test_a_notified_payment_waits_for_confirmation_and_cannot_be_repeated(): void
    {
        $this->notificar(140000);

        Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->assertSee('Esperando confirmación')
            ->call('mostrarForm', $this->factura->id)
            ->assertSet('open', false);

        $this->assertSame(1, PagoPropietario::count());
    }

    public function test_a_payment_waiting_for_confirmation_is_not_duplicated_from_an_open_form(): void
    {
        $formulario = $this->formularioCompleto(150000);

        $this->notificar(140000);

        $formulario->call('save');

        $this->assertSame(1, PagoPropietario::count());
    }

    public function test_a_rejected_payment_can_be_corrected_and_sent_again(): void
    {
        $this->notificar(140000);
        $this->rechazar();

        $formulario = Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->assertSee('Pago rechazado')
            ->call('mostrarForm', $this->factura->id)
            ->assertSet('open', true)
            ->assertSet('corrigiendo', true)
            ->assertSee('El condominio rechazó el pago anterior')
            ->assertSee('Reenviar pago')
            // Los datos del pago rechazado vienen cargados
            ->assertSet('monto', 140000.0)
            ->assertSet('referencia', '52412356')
            ->assertSet('formaPago', 'Transferencia')
            ->assertSet('cuentaId', $this->cuenta->id);

        $formulario->set('monto', 150000)->call('save')->assertHasNoErrors();

        // El rechazado queda en el historial y el corregido espera confirmación
        $this->assertSame(['Rechazado', 'Por confirmar'], PagoPropietario::orderBy('id')->pluck('estado')->all());
        $this->assertEquals(150000, PagoPropietario::latest('id')->first()->monto);

        Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->assertSee('Esperando confirmación');

        Livewire::actingAs($this->administrador())->test(ConfirmarPago::class)
            ->call('confirmar', PagoPropietario::latest('id')->first()->id);

        $this->assertSame('Pagada', $this->factura->fresh()->estado);
    }

    public function test_the_payments_list_offers_to_correct_only_the_latest_rejected_payment(): void
    {
        $this->notificar(140000);
        $this->rechazar();

        Livewire::actingAs($this->propietario)->test(TablaPago::class)
            ->set('readyToLoad', true)
            ->assertSee('Rechazado')
            ->assertSee('Corregir y reenviar')
            ->assertSee('corregir=' . $this->factura->numero, false);

        $this->notificar(150000);

        Livewire::actingAs($this->propietario->fresh())->test(TablaPago::class)
            ->set('readyToLoad', true)
            ->assertSee('Esperando confirmación')
            ->assertDontSee('Corregir y reenviar');
    }

    public function test_the_correct_link_opens_the_rejected_payment(): void
    {
        $this->notificar(140000);
        $this->rechazar();

        Livewire::withQueryParams(['corregir' => $this->factura->numero])
            ->actingAs($this->propietario)
            ->test(NuevoPago::class)
            ->assertSet('open', true)
            ->assertSet('corrigiendo', true)
            ->assertSet('monto', 140000.0);
    }

    private function formularioCompleto(float $monto)
    {
        return Livewire::actingAs($this->propietario)->test(NuevoPago::class)
            ->call('mostrarForm', $this->factura->id)
            ->set('descripcion', 'Cuota de noviembre')
            ->set('fecha', now()->format('Y-m-d'))
            ->set('formaPago', 'Transferencia')
            ->set('cuentaId', $this->cuenta->id)
            ->set('referencia', '52412356')
            ->set('monto', $monto);
    }

    private function notificar(float $monto): void
    {
        $this->formularioCompleto($monto)->call('save')->assertHasNoErrors();
    }

    private function rechazar(): void
    {
        Livewire::actingAs($this->administrador())->test(ConfirmarPago::class)
            ->call('rechazar', PagoPropietario::latest('id')->first()->id);
    }

    private function administrador(): User
    {
        return User::factory()->create()->assignRole('Condominio');
    }
}
