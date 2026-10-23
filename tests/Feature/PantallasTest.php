<?php

namespace Tests\Feature;

use App\Livewire\Banco\TablaBanco;
use App\Models\Banco;
use App\Models\Condominio;
use App\Models\Interes;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\TasaCambio;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLegacyModels\CannotBindToModelDataWithoutValidationRuleException;
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
            ->assertSet('banco.nombre', 'Banco de Prueba')
            ->set('banco.nombre', 'Banco Renombrado')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Banco Renombrado', $banco->fresh()->nombre);
    }

    public function test_only_fields_with_validation_rules_can_be_bound(): void
    {
        $banco = Banco::factory()->create();

        $vista = Livewire::actingAs(User::factory()->create()->assignRole('Administrador'))->test(TablaBanco::class)
            ->call('edit', $banco->id);

        $this->expectException(CannotBindToModelDataWithoutValidationRuleException::class);
        $vista->set('banco.id', 999);
    }
}
