<?php

namespace Tests\Feature;

use App\Livewire\Admin\ConfigurarIva;
use App\Livewire\Admin\Database\Backup;
use App\Livewire\Admin\Roles\TablaRol;
use App\Livewire\PagoPropietario\ConfirmarPago;
use App\Livewire\PagoPropietario\NuevoPago;
use App\Models\Factura;
use App\Models\Integrante;
use App\Models\Iva;
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

class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Iva::create(['factor' => 16, 'fecha' => now()]);
        TasaCambio::create(['tasa' => 40, 'fecha' => now()]);
    }

    public function test_owner_cannot_open_admin_pages(): void
    {
        $propietario = $this->usuarioConRol('Propietario');

        foreach (['/admin/backup', '/admin/roles', '/admin/permisos', '/admin/unidad', '/admin/condominio', '/admin/habitante', '/admin/administrador'] as $url) {
            $this->actingAs($propietario)->get($url)->assertForbidden();
        }

        $this->actingAs($propietario)->post('/admin/condominio')->assertForbidden();
    }

    public function test_condominium_role_cannot_manage_roles_or_backups(): void
    {
        $condominio = $this->usuarioConRol('Condominio');

        $this->actingAs($condominio)->get('/admin/roles')->assertForbidden();
        $this->actingAs($condominio)->get('/admin/backup')->assertForbidden();
    }

    public function test_owner_cannot_run_admin_actions(): void
    {
        $propietario = $this->usuarioConRol('Propietario');

        Livewire::actingAs($propietario)->test(TablaRol::class)->call('delete')->assertForbidden();
        Livewire::actingAs($propietario)->test(Backup::class)->call('restaurar')->assertForbidden();
        Livewire::actingAs($propietario)->test(ConfigurarIva::class)->call('actualizar')->assertForbidden();
    }

    public function test_owner_cannot_confirm_payments(): void
    {
        $propietario = $this->usuarioConRol('Propietario');
        [$unidad] = $this->unidadesDe($propietario);
        $pago = $this->pagoPorConfirmar($this->factura($unidad));

        Livewire::actingAs($propietario)->test(ConfirmarPago::class)
            ->call('confirmar', $pago->id)
            ->assertForbidden();

        $this->assertSame('Por confirmar', $pago->fresh()->estado);
    }

    public function test_owner_can_open_payment_form_for_own_invoice(): void
    {
        $propietario = $this->usuarioConRol('Propietario');
        [$unidad] = $this->unidadesDe($propietario);
        $factura = $this->factura($unidad);

        Livewire::actingAs($propietario)->test(NuevoPago::class)
            ->call('mostrarForm', $factura->id)
            ->assertSet('open', true);
    }

    public function test_owner_cannot_pay_another_owners_invoice(): void
    {
        $propietario = $this->usuarioConRol('Propietario');
        $this->unidadesDe($propietario);

        $otro = $this->usuarioConRol('Propietario');
        [$unidadAjena] = $this->unidadesDe($otro);
        $facturaAjena = $this->factura($unidadAjena);

        Livewire::actingAs($propietario)->test(NuevoPago::class)
            ->call('mostrarForm', $facturaAjena->id)
            ->assertForbidden();
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    /**
     * @return Unidad[]
     */
    private function unidadesDe(User $usuario): array
    {
        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => $usuario->id,
        ]);

        return [Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'propietario_id' => $propietario->id,
        ])];
    }

    private function factura(Unidad $unidad): Factura
    {
        return Factura::forceCreate([
            'numero' => 'F-'.uniqid(),
            'monto' => 100,
            'monto_por_pagar' => 100,
            'moneda' => 'Bolívar',
            'fecha' => now(),
            'unidad_id' => $unidad->id,
            'iva_id' => Iva::first()->id,
            'tasa_cambio_id' => TasaCambio::first()->id,
        ]);
    }

    private function pagoPorConfirmar(Factura $factura): PagoPropietario
    {
        return PagoPropietario::forceCreate([
            'descripcion' => 'Pago de prueba',
            'monto' => 100,
            'fecha' => now(),
            'referencia' => '123456',
            'forma_pago' => 'Transferencia',
            'moneda' => 'Bolívar',
            'tasa_cambio_id' => TasaCambio::first()->id,
            'unidad_id' => $factura->unidad_id,
            'factura_id' => $factura->id,
        ]);
    }
}
