<?php

namespace Tests\Feature;

use App\Livewire\Admin\Unidad\NuevaUnidad;
use App\Livewire\Admin\Unidad\ShowUnidad as ShowUnidadAdmin;
use App\Livewire\Unidad\ShowUnidad as ShowUnidadPropietario;
use App\Livewire\Visita\NuevaVisita;
use App\Models\Enfermedad;
use App\Models\Integrante;
use App\Models\Propietario;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use App\Models\Visita;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EdicionUnidadesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_creates_a_unit_with_the_selected_type(): void
    {
        $tipo = TipoUnidad::factory()->create();

        Livewire::actingAs($this->usuarioConRol('Administrador'))->test(NuevaUnidad::class)
            ->set('numero', '101')
            ->set('tipoUnidadId', $tipo->id)
            ->set('direccion', 'Torre A, piso 1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('tipoUnidadId', null);

        $this->assertDatabaseHas('unidades', ['numero' => '101', 'tipo_unidad_id' => $tipo->id]);
    }

    public function test_new_unit_requires_an_existing_type(): void
    {
        TipoUnidad::factory()->create();

        Livewire::actingAs($this->usuarioConRol('Administrador'))->test(NuevaUnidad::class)
            ->set('numero', '101')
            ->set('tipoUnidadId', 999)
            ->set('direccion', 'Torre A, piso 1')
            ->call('save')
            ->assertHasErrors(['tipoUnidadId' => 'exists']);

        $this->assertDatabaseMissing('unidades', ['numero' => '101']);
    }

    public function test_admin_assigns_a_new_owner_to_a_unit(): void
    {
        $unidad = $this->unidadSinPropietario();

        Livewire::actingAs($this->usuarioConRol('Administrador'))->test(ShowUnidadAdmin::class, ['unidad' => $unidad])
            ->set('documento', '12345678901')
            ->set('letra', 'V')
            ->set('ci', '12345678')
            ->set('form.nombre', 'Ana')
            ->set('form.apellido', 'Pérez')
            ->set('form.fecha_nacimiento', '1990-05-10')
            ->assertSet('edad', \Carbon\Carbon::parse('1990-05-10')->age)
            ->set('codigo', '0414')
            ->set('telefono', '1234567')
            ->set('form.email', 'ana@example.com')
            ->call('asignarPropietario')
            ->assertHasNoErrors();

        $integrante = Integrante::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('V', $integrante->letra);
        $this->assertSame('12345678', (string) $integrante->documento);
        $this->assertSame('0414-1234567', $integrante->telefono);
        $this->assertSame($integrante->propietario->id, $unidad->fresh()->propietario_id);
        $this->assertSame('12345678901', $unidad->fresh()->documento);
    }

    public function test_assign_owner_loads_an_existing_resident_by_id_card(): void
    {
        $unidad = $this->unidadSinPropietario();
        $existente = Integrante::factory()->create([
            'letra' => 'E',
            'documento' => '7654321',
            'nombre' => 'Luis',
            'telefono' => '0416-7654321',
        ]);

        Livewire::actingAs($this->usuarioConRol('Administrador'))->test(ShowUnidadAdmin::class, ['unidad' => $unidad])
            ->set('letra', 'E')
            ->set('ci', '7654321')
            ->assertSet('form.nombre', 'Luis')
            ->assertSet('form.email', $existente->email)
            ->assertSet('codigo', '0416')
            ->assertSet('telefono', '7654321')
            ->set('documento', '12345678901')
            ->set('form.nombre', 'Luis Alberto')
            ->call('asignarPropietario')
            ->assertHasNoErrors();

        $this->assertSame('Luis Alberto', $existente->fresh()->nombre);
        $this->assertNotNull($existente->fresh()->propietario);
        $this->assertSame($existente->fresh()->propietario->id, $unidad->fresh()->propietario_id);
    }

    public function test_assign_owner_validates_the_resident_data(): void
    {
        $unidad = $this->unidadSinPropietario();

        Livewire::actingAs($this->usuarioConRol('Administrador'))->test(ShowUnidadAdmin::class, ['unidad' => $unidad])
            ->set('documento', '12345678901')
            ->set('ci', '12345678')
            ->set('form.nombre', '')
            ->assertHasErrors(['form.nombre' => 'required'])
            ->set('form.apellido', 'Pérez')
            ->set('form.fecha_nacimiento', '1990-05-10')
            ->set('form.email', 'no-es-un-email')
            ->call('asignarPropietario')
            ->assertHasErrors(['form.nombre' => 'required', 'form.email' => 'email']);

        $this->assertNull($unidad->fresh()->propietario_id);
        $this->assertSame(0, Integrante::count());
    }

    public function test_owner_edits_a_resident_of_own_unit(): void
    {
        $usuario = $this->usuarioConRol('Propietario');
        $unidad = $this->unidadDe($usuario);
        $hijo = Integrante::factory()->create([
            'unidad_id' => $unidad->id,
            'nombre' => 'Pedro',
            'telefono' => '0412-1111111',
        ]);
        $enfermedad = Enfermedad::factory()->create();

        Livewire::actingAs($usuario)->test(ShowUnidadPropietario::class, ['unidad' => $unidad])
            ->call('edit', $hijo->id)
            ->assertSet('form.nombre', 'Pedro')
            ->assertSet('form.email', $hijo->email)
            ->assertSet('codigo', '0412')
            ->assertSet('telefono', '1111111')
            ->set('form.nombre', 'Pedro José')
            ->set('form.letra', 'E')
            ->set('codigo', '0424')
            ->set('telefono', '2222222')
            ->set('enfermedades', [$enfermedad->id])
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('openEdit', false);

        $hijo->refresh();
        $this->assertSame('Pedro José', $hijo->nombre);
        $this->assertSame('E', $hijo->letra);
        $this->assertSame('0424-2222222', $hijo->telefono);
        $this->assertSame([$enfermedad->id], $hijo->enfermedades()->pluck('enfermedades.id')->all());
    }

    public function test_owner_resident_edit_validates_and_cannot_touch_the_model_directly(): void
    {
        $usuario = $this->usuarioConRol('Propietario');
        $unidad = $this->unidadDe($usuario);
        $hijo = Integrante::factory()->create(['unidad_id' => $unidad->id, 'nombre' => 'Pedro']);

        $vista = Livewire::actingAs($usuario)->test(ShowUnidadPropietario::class, ['unidad' => $unidad])
            ->call('edit', $hijo->id)
            ->set('form.nombre', '')
            ->assertHasErrors(['form.nombre' => 'required'])
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'required']);

        $this->assertSame('Pedro', $hijo->fresh()->nombre);

        $this->expectException(\Throwable::class);
        $vista->set('form.integrante.unidad_id', 999);
    }

    public function test_owner_cannot_edit_a_resident_of_another_unit(): void
    {
        $usuario = $this->usuarioConRol('Propietario');
        $propia = $this->unidadDe($usuario);
        $ajena = $this->unidadDe($this->usuarioConRol('Propietario'));
        $vecino = Integrante::factory()->create(['unidad_id' => $ajena->id, 'nombre' => 'Vecino']);

        Livewire::actingAs($usuario)->test(ShowUnidadPropietario::class, ['unidad' => $propia])
            ->call('edit', $vecino->id)
            ->assertForbidden();

        $this->assertSame('Vecino', $vecino->fresh()->nombre);
    }

    public function test_doorman_registers_a_visit_to_an_owned_unit(): void
    {
        $unidad = $this->unidadDe($this->usuarioConRol('Propietario'));

        Livewire::actingAs($this->usuarioConRol('Portero'))->test(NuevaVisita::class)
            ->set('letra', 'V')
            ->set('ci', '1234567')
            ->set('nombre', 'Carlos')
            ->set('apellido', 'Gómez')
            ->set('unidadId', $unidad->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('unidadId', null);

        $this->assertDatabaseHas('visitas', ['ci' => '1234567', 'unidad_id' => $unidad->id]);
    }

    public function test_visit_requires_a_unit_with_an_owner(): void
    {
        $sinPropietario = $this->unidadSinPropietario();

        Livewire::actingAs($this->usuarioConRol('Portero'))->test(NuevaVisita::class)
            ->set('ci', '1234567')
            ->set('nombre', 'Carlos')
            ->set('apellido', 'Gómez')
            ->set('unidadId', $sinPropietario->id)
            ->call('save')
            ->assertHasErrors(['unidadId' => 'exists']);

        $this->assertSame(0, Visita::count());
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function unidadSinPropietario(): Unidad
    {
        return Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'documento' => null,
        ]);
    }

    private function unidadDe(User $usuario): Unidad
    {
        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => $usuario->id,
        ]);

        return Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'propietario_id' => $propietario->id,
        ]);
    }
}
