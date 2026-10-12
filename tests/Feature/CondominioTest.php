<?php

namespace Tests\Feature;

use App\Models\Condominio;
use App\Models\Mensualidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\Timezone;
use Tests\TestCase;

class CondominioTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->usuario = User::factory()->create()->assignRole('Condominio');

        $venezuela = Country::forceCreate([
            'iso2' => 'VE', 'iso3' => 'VEN', 'name' => 'Venezuela', 'phone_code' => '58',
            'region' => 'Americas', 'subregion' => 'South America',
        ]);
        $estadosUnidos = Country::forceCreate([
            'iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'phone_code' => '1',
            'region' => 'Americas', 'subregion' => 'Northern America',
        ]);

        Timezone::forceCreate(['country_id' => $venezuela->id, 'name' => 'America/Caracas']);
        Timezone::forceCreate(['country_id' => $estadosUnidos->id, 'name' => 'America/New_York']);
        Currency::forceCreate([
            'country_id' => $venezuela->id, 'name' => 'Bolívar Soberano', 'code' => 'VES',
            'symbol' => 'Bs', 'symbol_native' => 'Bs',
        ]);
        Currency::forceCreate([
            'country_id' => $estadosUnidos->id, 'name' => 'US Dollar', 'code' => 'USD',
            'symbol' => '$', 'symbol_native' => '$',
        ]);
        Currency::forceCreate([
            'country_id' => $venezuela->id, 'name' => 'Euro', 'code' => 'EUR',
            'symbol' => '€', 'symbol_native' => '€',
        ]);
    }

    public function test_setup_form_shows_country_timezone_and_currency_selectors(): void
    {
        $this->actingAs($this->usuario)
            ->get('/admin/condominio')
            ->assertOk()
            ->assertSee('name="pais"', false)
            ->assertSee('name="zona_horaria"', false)
            ->assertSee('name="monedas[]"', false)
            ->assertSee('x-model="monedas"', false)
            ->assertSee('x-if="monedas.includes(\'USD\')"', false)
            ->assertDontSee('<option>Bolívar</option>', false)
            ->assertSee('USD - Dólar estadounidense')
            ->assertSee("pais: 'VE'", false);
    }

    public function test_timezone_must_belong_to_the_selected_country(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['pais' => 'VE', 'zona_horaria' => 'America/New_York']))
            ->assertSessionHasErrors(['zona_horaria' => 'La zona horaria seleccionada no corresponde al país.']);

        $this->assertNull(Condominio::first());
    }

    public function test_condominium_is_stored_with_regional_settings(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos())
            ->assertRedirect(route('admin.home'));

        $condominio = Condominio::first();

        $this->assertSame('VE', $condominio->pais);
        $this->assertSame('America/Caracas', $condominio->zona_horaria);
        $this->assertSame(['VES', 'USD'], $condominio->monedas);
        $this->assertSame('Dólar', Mensualidad::first()->moneda);
    }

    public function test_monthly_fee_currency_must_be_one_of_the_selected_currencies(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['monedas' => ['VES'], 'moneda' => 'USD']))
            ->assertSessionHasErrors('moneda');

        $this->assertNull(Condominio::first());
    }

    public function test_monthly_fee_currency_must_be_supported_by_billing(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['monedas' => ['VES', 'EUR'], 'moneda' => 'EUR']))
            ->assertSessionHasErrors('moneda');

        $this->assertNull(Condominio::first());
    }

    public function test_at_least_one_currency_is_required(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['monedas' => []]))
            ->assertSessionHasErrors('monedas');

        $this->assertNull(Condominio::first());
    }

    public function test_values_outside_the_world_data_are_rejected(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos([
                'pais' => 'XX',
                'zona_horaria' => 'Mars/Olympus',
                'monedas' => ['VES', 'ABC', 'VES'],
            ]))
            ->assertSessionHasErrors(['pais', 'zona_horaria', 'monedas.0', 'monedas.1']);

        $this->assertNull(Condominio::first());
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'rif' => '12345678',
            'nombre' => 'Condominio de prueba',
            'direccion' => 'Calle 1',
            'pais' => 'VE',
            'zona_horaria' => 'America/Caracas',
            'monedas' => ['VES', 'USD'],
            'tasa' => 40,
            'monto' => 20,
            'moneda' => 'USD',
        ], $cambios);
    }
}
