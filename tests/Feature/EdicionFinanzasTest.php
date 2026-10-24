<?php

namespace Tests\Feature;

use App\Livewire\Comunicado\TablaComunicado;
use App\Livewire\Cuenta\TablaCuenta;
use App\Livewire\Fondo\TablaFondo;
use App\Livewire\Gasto\TablaGasto;
use App\Livewire\Proveedor\TablaProveedor;
use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Comunicado;
use App\Models\Cuenta;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Moneda;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Edit modals of the finance screens, bound to Livewire form objects.
 */
class EdicionFinanzasTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        Banco::factory()->create();

        $this->administrador = User::factory()->create()->assignRole('Administrador');
    }

    public function test_cuenta_edit_loads_and_saves_the_record(): void
    {
        $cuenta = Cuenta::factory()->create([
            'documento' => 'J-12345678', 'telefono' => '0414-1234567', 'tipo' => 'Ahorro', 'publica' => false,
        ]);
        $banco = Banco::factory()->create();

        Livewire::actingAs($this->administrador)->test(TablaCuenta::class)
            ->call('edit', $cuenta->id)
            ->assertSet('form.letra', 'J')
            ->assertSet('form.documento', '12345678')
            ->assertSet('form.codigo', '0414')
            ->assertSet('form.telefono', '1234567')
            ->assertSet('form.numero', $cuenta->numero)
            ->assertSet('form.tipo', 'Ahorro')
            ->set('form.beneficiario', 'Junta de condominio')
            ->set('form.banco_id', $banco->id)
            ->set('form.tipo', 'Corriente')
            ->set('form.codigo', '0424')
            ->set('form.telefono', '7654321')
            ->set('form.publica', '1')
            ->call('update')
            ->assertHasNoErrors();

        $cuenta->refresh();
        $this->assertSame('Junta de condominio', $cuenta->beneficiario);
        $this->assertEquals($banco->id, $cuenta->banco_id);
        $this->assertSame('Corriente', $cuenta->tipo);
        $this->assertSame('J-12345678', $cuenta->documento);
        $this->assertSame('0424-7654321', $cuenta->telefono);
        $this->assertTrue((bool) $cuenta->publica);
    }

    public function test_cuenta_edit_validates_the_account_number(): void
    {
        $cuenta = Cuenta::factory()->create();

        Livewire::actingAs($this->administrador)->test(TablaCuenta::class)
            ->call('edit', $cuenta->id)
            ->set('form.numero', '123')
            ->assertHasErrors(['form.numero' => 'digits'])
            ->call('update')
            ->assertHasErrors(['form.numero' => 'digits']);

        $this->assertSame($cuenta->numero, $cuenta->fresh()->numero);
    }

    public function test_fondo_edit_loads_and_saves_the_record(): void
    {
        $fondo = Fondo::create(['descripcion' => 'Caja chica', 'saldo' => 0, 'moneda' => 'USD']);

        Livewire::actingAs($this->administrador)->test(TablaFondo::class)
            ->call('edit', $fondo->id)
            ->assertSet('form.descripcion', 'Caja chica')
            ->assertSet('form.moneda', 'USD')
            ->set('form.descripcion', 'Caja en pesos')
            ->set('form.moneda', 'ARS')
            ->call('update')
            ->assertHasNoErrors();

        $fondo->refresh();
        $this->assertSame('Caja en pesos', $fondo->descripcion);
        $this->assertSame('ARS', $fondo->moneda);
    }

    public function test_fondo_edit_requires_a_description(): void
    {
        $fondo = Fondo::create(['descripcion' => 'Caja chica', 'saldo' => 0, 'moneda' => 'USD']);

        Livewire::actingAs($this->administrador)->test(TablaFondo::class)
            ->call('edit', $fondo->id)
            ->set('form.descripcion', '')
            ->call('update')
            ->assertHasErrors(['form.descripcion' => 'required']);

        $this->assertSame('Caja chica', $fondo->fresh()->descripcion);
    }

    public function test_gasto_edit_loads_the_service_amounts_and_recomputes_the_total(): void
    {
        [$gasto, $limpieza, $jardineria] = $this->gastoConServicios();

        Livewire::actingAs($this->administrador)->test(TablaGasto::class)
            ->call('edit', $gasto->id)
            ->assertSet('form.monto', '100.00')
            ->assertSet("form.montos.{$limpieza->id}", '60.00')
            ->assertSet("form.montos.{$jardineria->id}", '40.00')
            ->set("form.montos.{$limpieza->id}", '80')
            ->assertSet('form.monto', 120)
            ->call('update')
            ->assertHasNoErrors();

        $gasto->refresh();
        $this->assertEquals(120, $gasto->monto);
        $this->assertEquals(80, $gasto->servicios->firstWhere('id', $limpieza->id)->pivot->monto);
        $this->assertEquals(40, $gasto->servicios->firstWhere('id', $jardineria->id)->pivot->monto);
    }

    public function test_gasto_edit_rejects_a_non_numeric_amount(): void
    {
        [$gasto, $limpieza] = $this->gastoConServicios();

        Livewire::actingAs($this->administrador)->test(TablaGasto::class)
            ->call('edit', $gasto->id)
            ->set("form.montos.{$limpieza->id}", 'abc')
            ->call('update')
            ->assertHasErrors(["form.montos.{$limpieza->id}" => 'numeric']);

        $gasto->refresh();
        $this->assertEquals(100, $gasto->monto);
        $this->assertEquals(60, $gasto->servicios->firstWhere('id', $limpieza->id)->pivot->monto);
    }

    public function test_proveedor_edit_loads_and_saves_the_record(): void
    {
        $proveedor = Proveedor::factory()->create(['letra' => 'J', 'documento' => '30123456']);

        Livewire::actingAs($this->administrador)->test(TablaProveedor::class)
            ->call('edit', $proveedor->id)
            ->assertSet('form.letra', 'J')
            ->assertSet('form.documento', '30123456')
            ->assertSet('form.nombre', $proveedor->nombre)
            ->assertSet('form.email', $proveedor->email)
            ->set('form.nombre', 'Servicios Generales C.A.')
            ->set('form.telefono', '0412-7654321')
            ->call('update')
            ->assertHasNoErrors();

        $proveedor->refresh();
        $this->assertSame('Servicios Generales C.A.', $proveedor->nombre);
        $this->assertSame('0412-7654321', $proveedor->telefono);
    }

    public function test_proveedor_edit_rejects_an_email_taken_by_another_provider(): void
    {
        $proveedor = Proveedor::factory()->create();
        $otro = Proveedor::factory()->create();

        Livewire::actingAs($this->administrador)->test(TablaProveedor::class)
            ->call('edit', $proveedor->id)
            ->set('form.nombre', '')
            ->assertHasErrors(['form.nombre' => 'required'])
            ->set('form.nombre', 'Otro nombre')
            ->set('form.email', $otro->email)
            ->call('update')
            ->assertHasErrors(['form.email' => 'unique']);

        $this->assertNotSame('Otro nombre', $proveedor->fresh()->nombre);
    }

    public function test_comunicado_edit_loads_and_saves_the_record(): void
    {
        $comunicado = Comunicado::forceCreate(['asunto' => 'Corte de agua', 'contenido' => 'El lunes no habrá agua.']);

        Livewire::actingAs($this->administrador)->test(TablaComunicado::class)
            ->call('edit', $comunicado->id)
            ->assertSet('form.asunto', 'Corte de agua')
            ->assertSet('form.contenido', 'El lunes no habrá agua.')
            ->set('form.asunto', 'Corte de agua programado')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Corte de agua programado', $comunicado->fresh()->asunto);
    }

    public function test_comunicado_edit_limits_the_subject_length(): void
    {
        $comunicado = Comunicado::forceCreate(['asunto' => 'Corte de agua', 'contenido' => 'El lunes no habrá agua.']);

        Livewire::actingAs($this->administrador)->test(TablaComunicado::class)
            ->call('edit', $comunicado->id)
            ->set('form.asunto', str_repeat('a', 46))
            ->call('update')
            ->assertHasErrors(['form.asunto' => 'max']);

        $this->assertSame('Corte de agua', $comunicado->fresh()->asunto);
    }

    private function gastoConServicios(): array
    {
        $categoria = Categoria::create(['nombre' => 'Mantenimiento']);
        $limpieza = Servicio::create(['nombre' => 'Limpieza', 'categoria_id' => $categoria->id]);
        $jardineria = Servicio::create(['nombre' => 'Jardinería', 'categoria_id' => $categoria->id]);

        $gasto = Gasto::create([
            'descripcion' => 'Mantenimiento mensual', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'USD', 'monto' => 100, 'saldo' => 100,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-1',
        ]);
        $gasto->servicios()->attach([$limpieza->id => ['monto' => 60], $jardineria->id => ['monto' => 40]]);

        return [$gasto, $limpieza, $jardineria];
    }
}
