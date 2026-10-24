<?php

namespace Tests\Feature;

use App\Livewire\Admin\Administrador\TablaAdministrador;
use App\Livewire\Admin\ConfigurarInteres;
use App\Livewire\Admin\Habitante\TablaHabitante;
use App\Livewire\Admin\Permisos\TablaPermiso;
use App\Livewire\Admin\Roles\TablaRol;
use App\Livewire\Admin\Sancion\TablaSancion;
use App\Models\Administrador;
use App\Models\Integrante;
use App\Models\Interes;
use App\Models\Moneda;
use App\Models\Sancion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin edit forms load the record being edited and save it through their form object.
 */
class EdicionAdministracionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'VES']);

        $this->usuario = User::factory()->create()->assignRole('Administrador');
    }

    public function test_administrador_edit_form_loads_and_saves(): void
    {
        $administrador = $this->administrador('Presidente');

        Livewire::actingAs($this->usuario)->test(TablaAdministrador::class)
            ->call('edit', $administrador->id)
            ->assertSet('form.rol', 'Presidente')
            ->set('form.rol', 'Tesorero')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Tesorero', $administrador->fresh()->rol);
    }

    public function test_administrador_edit_form_requires_rol(): void
    {
        $administrador = $this->administrador('Presidente');

        Livewire::actingAs($this->usuario)->test(TablaAdministrador::class)
            ->call('edit', $administrador->id)
            ->set('form.rol', '')
            ->call('update')
            ->assertHasErrors(['form.rol' => 'required']);

        $this->assertSame('Presidente', $administrador->fresh()->rol);
    }

    public function test_habitante_edit_form_loads_and_saves(): void
    {
        $integrante = Integrante::factory()->create(['nombre' => 'Pedro']);

        Livewire::actingAs($this->usuario)->test(TablaHabitante::class)
            ->call('edit', $integrante->id)
            ->assertSet('form.nombre', 'Pedro')
            ->set('form.nombre', 'Pablo')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Pablo', $integrante->fresh()->nombre);
    }

    public function test_habitante_edit_form_requires_nombre(): void
    {
        $integrante = Integrante::factory()->create(['nombre' => 'Pedro']);

        Livewire::actingAs($this->usuario)->test(TablaHabitante::class)
            ->call('edit', $integrante->id)
            ->set('form.nombre', '')
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'required']);

        $this->assertSame('Pedro', $integrante->fresh()->nombre);
    }

    public function test_permiso_edit_form_loads_and_saves(): void
    {
        $permiso = Permission::create(['name' => 'prueba.index']);

        Livewire::actingAs($this->usuario)->test(TablaPermiso::class)
            ->call('edit', $permiso->id)
            ->assertSet('form.name', 'prueba.index')
            ->set('form.name', 'prueba.show')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('prueba.show', $permiso->fresh()->name);
    }

    public function test_permiso_edit_form_requires_a_unique_name(): void
    {
        $permiso = Permission::create(['name' => 'prueba.index']);

        Livewire::actingAs($this->usuario)->test(TablaPermiso::class)
            ->call('edit', $permiso->id)
            ->set('form.name', '')
            ->call('update')
            ->assertHasErrors(['form.name' => 'required'])
            ->set('form.name', 'admin.home')
            ->call('update')
            ->assertHasErrors(['form.name' => 'unique'])
            ->set('form.name', 'prueba.index')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('prueba.index', $permiso->fresh()->name);
    }

    public function test_rol_edit_form_loads_and_saves_name_and_permissions(): void
    {
        $rol = Role::create(['name' => 'Junta']);
        $rol->givePermissionTo('admin.home');
        $nuevo = Permission::findByName('sancion.index');

        Livewire::actingAs($this->usuario)->test(TablaRol::class)
            ->call('edit', $rol->id)
            ->assertSet('form.name', 'Junta')
            ->set('form.name', 'Junta directiva')
            ->set('permisos', [(string) $nuevo->id])
            ->call('update')
            ->assertHasNoErrors();

        $rol = $rol->fresh();
        $this->assertSame('Junta directiva', $rol->name);
        $this->assertSame([$nuevo->id], $rol->permissions()->pluck('id')->all());
    }

    public function test_rol_edit_form_requires_a_unique_name(): void
    {
        $rol = Role::create(['name' => 'Junta']);

        Livewire::actingAs($this->usuario)->test(TablaRol::class)
            ->call('edit', $rol->id)
            ->set('form.name', '')
            ->call('update')
            ->assertHasErrors(['form.name' => 'required'])
            ->set('form.name', 'Propietario')
            ->call('update')
            ->assertHasErrors(['form.name' => 'unique']);

        $this->assertSame('Junta', $rol->fresh()->name);
    }

    public function test_sancion_edit_form_loads_and_saves(): void
    {
        $sancion = Sancion::factory()->create(['monto' => 5, 'moneda' => 'USD']);

        Livewire::actingAs($this->usuario)->test(TablaSancion::class)
            ->call('edit', $sancion->id)
            ->assertSet('form.monto', 5)
            ->assertSet('form.moneda', 'USD')
            ->set('form.monto', '7.5')
            ->set('form.moneda', 'VES')
            ->call('update')
            ->assertHasNoErrors();

        $sancion = $sancion->fresh();
        $this->assertEquals(7.5, $sancion->monto);
        $this->assertSame('VES', $sancion->moneda);
    }

    public function test_sancion_edit_form_validates_monto_and_moneda(): void
    {
        $sancion = Sancion::factory()->create(['monto' => 5, 'moneda' => 'USD']);

        Livewire::actingAs($this->usuario)->test(TablaSancion::class)
            ->call('edit', $sancion->id)
            ->set('form.monto', '')
            ->set('form.moneda', 'EUR')
            ->call('update')
            ->assertHasErrors(['form.monto' => 'required', 'form.moneda' => 'in']);

        $sancion = $sancion->fresh();
        $this->assertEquals(5, $sancion->monto);
        $this->assertSame('USD', $sancion->moneda);
    }

    public function test_configurar_interes_appends_a_row_when_factor_changes(): void
    {
        $interes = Interes::create(['factor' => 2, 'meses' => 3, 'estado' => false]);

        Livewire::actingAs($this->usuario)->test(ConfigurarInteres::class)
            ->assertSet('form.factor', 2)
            ->assertSet('form.meses', 3)
            ->set('form.factor', '4')
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertSame(2, Interes::count());
        $this->assertEquals(2, $interes->fresh()->factor);
        $this->assertEquals(4, Interes::latest('id')->first()->factor);
    }

    public function test_configurar_interes_only_toggles_the_state_in_place(): void
    {
        $interes = Interes::create(['factor' => 2, 'meses' => 3, 'estado' => false]);

        Livewire::actingAs($this->usuario)->test(ConfigurarInteres::class)
            ->set('form.estado', '1')
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertSame(1, Interes::count());
        $this->assertEquals(1, $interes->fresh()->estado);
    }

    public function test_configurar_interes_validates_the_factor(): void
    {
        Interes::create(['factor' => 2, 'meses' => 3, 'estado' => false]);

        Livewire::actingAs($this->usuario)->test(ConfigurarInteres::class)
            ->set('form.factor', '')
            ->call('actualizar')
            ->assertHasErrors(['form.factor' => 'required'])
            ->set('form.factor', '6')
            ->call('actualizar')
            ->assertHasErrors(['form.factor' => 'lte'])
            ->assertSee('El factor no puede ser mayor a 5%.');

        $this->assertSame(1, Interes::count());
    }

    private function administrador(string $rol): Administrador
    {
        return Administrador::create([
            'rol' => $rol,
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ]);
    }
}
