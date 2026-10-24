<?php

namespace Tests\Feature;

use App\Livewire\Banco\TablaBanco;
use App\Livewire\Cuenta\TablaCuenta;
use App\Livewire\Proveedor\NuevoProveedor;
use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Condominio;
use App\Models\Cuenta;
use App\Models\Interes;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\Servicio;
use App\Models\TasaCambio;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every screen opens, and edit forms load the record being edited.
 */
class PantallasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'VES']);
        TasaCambio::create(['moneda' => 'VES', 'tasa' => 4.8, 'fecha' => now()->subMinute()]);
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Residencias Prueba', 'direccion' => 'Calle 1', 'moneda_facturacion' => 'USD']);
        Mensualidad::create(['monto' => 10, 'moneda' => 'USD']);
        Iva::create(['factor' => 16, 'fecha' => now()]);
        Interes::create(['factor' => 5, 'meses' => 3, 'estado' => false]);
    }

    public function test_admin_list_screens_open_for_the_administrator(): void
    {
        $administrador = User::factory()->create()->assignRole('Administrador');

        foreach (['admin.home', 'admin.backup', 'admin.rol.index', 'admin.permiso.index', 'admin.administrador.index',
            'admin.comunicado.index', 'admin.habitante.index', 'admin.sancion.index', 'admin.unidad.index',
            'admin.usuario.index'] as $ruta) {
            $this->actingAs($administrador)->get(route($ruta))->assertOk();
        }
    }

    public function test_visit_list_is_not_taken_for_a_visit_id(): void
    {
        $condominio = User::factory()->create()->assignRole('Condominio');

        $this->actingAs($condominio)->get(route('visita.lista'))->assertOk();
    }

    public function test_edit_form_loads_the_record_being_edited(): void
    {
        $banco = Banco::factory()->create(['nombre' => 'Banco de Prueba']);

        Livewire::actingAs(User::factory()->create()->assignRole('Administrador'))->test(TablaBanco::class)
            ->call('edit', $banco->id)
            ->assertSet('form.nombre', 'Banco de Prueba')
            ->set('form.nombre', 'Banco Renombrado')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Banco Renombrado', $banco->fresh()->nombre);
    }

    public function test_edit_form_validates_and_cannot_touch_the_model_directly(): void
    {
        $banco = Banco::factory()->create(['nombre' => 'Banco de Prueba']);

        $vista = Livewire::actingAs(User::factory()->create()->assignRole('Administrador'))->test(TablaBanco::class)
            ->call('edit', $banco->id)
            ->set('form.nombre', '')
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'required']);

        $this->assertSame('Banco de Prueba', $banco->fresh()->nombre);

        $this->expectException(\Throwable::class);
        $vista->set('form.banco.id', 999);
    }

    public function test_services_of_a_deleted_category_still_show_it(): void
    {
        $categoria = Categoria::factory()->create(['nombre' => 'Mantenimiento']);
        $servicio = Servicio::factory()->create(['categoria_id' => $categoria->id]);
        $categoria->delete();
        $administrador = User::factory()->create()->assignRole('Administrador');

        $this->actingAs($administrador)->get(route('servicio.index'))->assertOk();

        Livewire::actingAs($administrador)->test(NuevoProveedor::class)
            ->set('readyToLoad', true)
            ->assertSee($servicio->nombre)
            ->assertSee('Mantenimiento');
    }

    public function test_accounts_of_a_deleted_bank_still_show_it(): void
    {
        $banco = Banco::factory()->create(['nombre' => 'Banco Cerrado']);
        Cuenta::factory()->create(['banco_id' => $banco->id]);
        $banco->delete();

        Livewire::actingAs(User::factory()->create()->assignRole('Administrador'))->test(TablaCuenta::class)
            ->set('readyToLoad', true)
            ->assertSee('Banco Cerrado');
    }
}
