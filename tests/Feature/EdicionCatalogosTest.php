<?php

namespace Tests\Feature;

use App\Livewire\Categoria\TablaCategoria;
use App\Livewire\Enfermedad\TablaEnfermedad;
use App\Livewire\Medicamento\TablaMedicamento;
use App\Livewire\Servicio\TablaServicio;
use App\Livewire\TipoUnidad\TablaTipoUnidad;
use App\Models\Categoria;
use App\Models\Enfermedad;
use App\Models\Medicamento;
use App\Models\Servicio;
use App\Models\TipoUnidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Catalog edit modals load the record into a form object and save through it.
 */
class EdicionCatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function comoAdministrador()
    {
        return Livewire::actingAs(User::factory()->create()->assignRole('Administrador'));
    }

    public function test_categoria_edit_loads_and_saves(): void
    {
        $categoria = Categoria::factory()->create(['nombre' => 'Limpieza', 'descripcion' => 'Aseo']);

        $this->comoAdministrador()->test(TablaCategoria::class)
            ->call('edit', $categoria->id)
            ->assertSet('form.nombre', 'Limpieza')
            ->assertSet('form.descripcion', 'Aseo')
            ->set('form.nombre', 'Mantenimiento')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Mantenimiento', $categoria->fresh()->nombre);
    }

    public function test_categoria_edit_validates(): void
    {
        $categoria = Categoria::factory()->create(['nombre' => 'Limpieza']);

        $this->comoAdministrador()->test(TablaCategoria::class)
            ->call('edit', $categoria->id)
            ->set('form.nombre', '')
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'required']);

        $this->assertSame('Limpieza', $categoria->fresh()->nombre);
    }

    public function test_enfermedad_edit_loads_and_saves(): void
    {
        $enfermedad = Enfermedad::factory()->create(['nombre' => 'Asma', 'descripcion' => 'Crónica']);

        $this->comoAdministrador()->test(TablaEnfermedad::class)
            ->call('edit', $enfermedad->id)
            ->assertSet('form.nombre', 'Asma')
            ->assertSet('form.descripcion', 'Crónica')
            ->set('form.descripcion', 'Respiratoria')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Respiratoria', $enfermedad->fresh()->descripcion);
    }

    public function test_enfermedad_edit_validates(): void
    {
        $enfermedad = Enfermedad::factory()->create(['nombre' => 'Asma']);

        $this->comoAdministrador()->test(TablaEnfermedad::class)
            ->call('edit', $enfermedad->id)
            ->set('form.nombre', str_repeat('a', 26))
            ->assertHasErrors(['form.nombre' => 'max'])
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'max']);

        $this->assertSame('Asma', $enfermedad->fresh()->nombre);
    }

    public function test_medicamento_edit_loads_and_saves(): void
    {
        $medicamento = Medicamento::factory()->create(['nombre' => 'Insulina', 'descripcion' => 'Diabetes']);

        $this->comoAdministrador()->test(TablaMedicamento::class)
            ->call('edit', $medicamento->id)
            ->assertSet('form.nombre', 'Insulina')
            ->assertSet('form.descripcion', 'Diabetes')
            ->set('form.nombre', 'Metformina')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Metformina', $medicamento->fresh()->nombre);
    }

    public function test_medicamento_edit_validates(): void
    {
        $medicamento = Medicamento::factory()->create(['nombre' => 'Insulina']);

        $this->comoAdministrador()->test(TablaMedicamento::class)
            ->call('edit', $medicamento->id)
            ->set('form.nombre', '')
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'required']);

        $this->assertSame('Insulina', $medicamento->fresh()->nombre);
    }

    public function test_tipo_unidad_edit_loads_and_saves(): void
    {
        $tipoUnidad = TipoUnidad::factory()->create(['nombre' => 'Apartamento', 'area' => 80, 'descripcion' => 'Tipo A']);

        $this->comoAdministrador()->test(TablaTipoUnidad::class)
            ->call('edit', $tipoUnidad->id)
            ->assertSet('form.nombre', 'Apartamento')
            ->assertSet('form.area', $tipoUnidad->fresh()->area)
            ->assertSet('form.descripcion', 'Tipo A')
            ->set('form.area', '95.5')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(95.5, $tipoUnidad->fresh()->area);
    }

    public function test_tipo_unidad_edit_validates(): void
    {
        $tipoUnidad = TipoUnidad::factory()->create(['nombre' => 'Apartamento', 'area' => 80]);

        $this->comoAdministrador()->test(TablaTipoUnidad::class)
            ->call('edit', $tipoUnidad->id)
            ->set('form.area', 'mucho')
            ->call('update')
            ->assertHasErrors(['form.area' => 'numeric']);

        $this->assertEquals(80, $tipoUnidad->fresh()->area);
    }

    public function test_servicio_edit_loads_and_saves(): void
    {
        $categoria = Categoria::factory()->create();
        $otraCategoria = Categoria::factory()->create();
        $servicio = Servicio::factory()->create([
            'nombre' => 'Vigilancia',
            'descripcion' => 'Nocturna',
            'categoria_id' => $categoria->id,
        ]);

        $this->comoAdministrador()->test(TablaServicio::class)
            ->call('edit', $servicio->id)
            ->assertSet('form.nombre', 'Vigilancia')
            ->assertSet('form.descripcion', 'Nocturna')
            ->assertSet('form.categoria_id', $categoria->id)
            // Keeping its own name must pass the unique rule
            ->set('form.categoria_id', $otraCategoria->id)
            ->call('update')
            ->assertHasNoErrors();

        $servicio->refresh();
        $this->assertSame('Vigilancia', $servicio->nombre);
        $this->assertEquals($otraCategoria->id, $servicio->categoria_id);
    }

    public function test_servicio_edit_validates(): void
    {
        $categoria = Categoria::factory()->create();
        Servicio::factory()->create(['nombre' => 'Jardinería', 'categoria_id' => $categoria->id]);
        $servicio = Servicio::factory()->create(['nombre' => 'Vigilancia', 'categoria_id' => $categoria->id]);

        $this->comoAdministrador()->test(TablaServicio::class)
            ->call('edit', $servicio->id)
            ->set('form.nombre', 'Jardinería')
            ->set('form.categoria_id', '0')
            ->call('update')
            ->assertHasErrors(['form.nombre' => 'unique', 'form.categoria_id' => 'not_in']);

        $servicio->refresh();
        $this->assertSame('Vigilancia', $servicio->nombre);
        $this->assertEquals($categoria->id, $servicio->categoria_id);
    }
}
