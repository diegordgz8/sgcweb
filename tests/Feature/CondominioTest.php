<?php

namespace Tests\Feature;

use App\Models\Condominio;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\TasaCambio;
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
        $argentina = Country::forceCreate([
            'iso2' => 'AR', 'iso3' => 'ARG', 'name' => 'Argentina', 'phone_code' => '54',
            'region' => 'Americas', 'subregion' => 'South America',
        ]);
        $estadosUnidos = Country::forceCreate([
            'iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'phone_code' => '1',
            'region' => 'Americas', 'subregion' => 'Northern America',
        ]);

        Timezone::forceCreate(['country_id' => $venezuela->id, 'name' => 'America/Caracas']);
        Timezone::forceCreate(['country_id' => $estadosUnidos->id, 'name' => 'America/New_York']);
        Timezone::forceCreate(['country_id' => $argentina->id, 'name' => 'America/Argentina/Buenos_Aires']);
        Currency::forceCreate([
            'country_id' => $argentina->id, 'name' => 'Argentine Peso', 'code' => 'ARS',
            'symbol' => 'AR$', 'symbol_native' => '$',
        ]);
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
            ->assertSee('name="moneda_base"', false)
            ->assertSee('x-if="monedas.includes(\'USD\')"', false)
            ->assertDontSee('<option>Bolívar</option>', false)
            ->assertSee('USD - Dólar estadounidense')
            ->assertSee("pais: 'VE'", false);
    }

    public function test_timezone_must_belong_to_the_selected_country(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['pais' => 'AR', 'zona_horaria' => 'America/Caracas']))
            ->assertSessionHasErrors(['zona_horaria' => 'La zona horaria seleccionada no corresponde al país.']);

        $this->assertNull(Condominio::first());
    }

    public function test_condominium_is_stored_with_its_currencies_base_and_rates(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos())
            ->assertRedirect(route('admin.home'));

        $condominio = Condominio::first();

        $this->assertSame('AR', $condominio->pais);
        $this->assertSame('America/Argentina/Buenos_Aires', $condominio->zona_horaria);
        $this->assertSame('USD', Moneda::base());
        $this->assertSame(['USD', 'ARS'], Moneda::codigos());
        $this->assertEquals(1620, TasaCambio::vigente('ARS')->tasa);
        $this->assertNull(TasaCambio::vigente('USD'));
        $this->assertSame('ARS', Mensualidad::first()->moneda);
        $this->assertEquals(150000, Mensualidad::first()->monto);
    }

    public function test_every_non_base_currency_needs_an_initial_rate(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['monedas' => ['ARS', 'USD', 'EUR'], 'tasas' => ['ARS' => 1620]]))
            ->assertSessionHasErrors(['tasas.EUR' => 'Indique la tasa de cambio de EUR.']);

        $this->assertNull(Condominio::first());
        $this->assertSame(0, Moneda::count());
    }

    public function test_a_single_currency_needs_no_rate(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['monedas' => ['ARS'], 'moneda_base' => 'ARS', 'tasas' => []]))
            ->assertRedirect(route('admin.home'));

        $this->assertSame('ARS', Moneda::base());
        $this->assertSame(0, TasaCambio::count());
    }

    public function test_base_and_monthly_fee_currencies_must_be_selected_currencies(): void
    {
        $this->actingAs($this->usuario)
            ->post('/admin/condominio', $this->datos(['moneda_base' => 'EUR', 'moneda' => 'VES']))
            ->assertSessionHasErrors(['moneda_base', 'moneda']);

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
                'monedas' => ['ARS', 'ABC', 'ARS'],
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
            'pais' => 'AR',
            'zona_horaria' => 'America/Argentina/Buenos_Aires',
            'monedas' => ['ARS', 'USD'],
            'moneda_base' => 'USD',
            'tasas' => ['ARS' => 1620],
            'monto' => 150000,
            'moneda' => 'ARS',
        ], $cambios);
    }
}
