<?php

namespace Tests\Feature;

use App\Exceptions\MonedaNoEliminable;
use App\Exceptions\TasaNoDisponible;
use App\Models\AjusteSaldo;
use App\Models\Factura;
use App\Models\Fondo;
use App\Models\Gasto;
use App\Models\Integrante;
use App\Models\Iva;
use App\Models\Moneda;
use App\Models\Pago;
use App\Models\PagoPropietario;
use App\Models\Propietario;
use App\Models\Proveedor;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use App\Traits\WithCurrencies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonedaTest extends TestCase
{
    use RefreshDatabase;

    private object $conversor;

    protected function setUp(): void
    {
        parent::setUp();

        // Base USD: 1 USD = 1620 ARS y 1 USD = 0.92 EUR
        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'ARS']);
        Moneda::create(['codigo' => 'EUR']);
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 1620, 'fecha' => now()->subDay()]);
        TasaCambio::create(['moneda' => 'EUR', 'tasa' => 0.92, 'fecha' => now()->subDay()]);

        $this->conversor = new class {
            use WithCurrencies;
        };
    }

    public function test_converts_from_base_to_another_currency(): void
    {
        $this->assertSame(16200.0, $this->conversor->convertirMonto(10, 'USD', 'ARS'));
    }

    public function test_converts_from_a_currency_to_the_base(): void
    {
        $this->assertSame(100.0, $this->conversor->convertirMonto(162000, 'ARS', 'USD'));
    }

    public function test_converts_between_two_non_base_currencies_through_the_base(): void
    {
        // 150000 / 1620 * 0.92
        $this->assertSame(85.19, $this->conversor->convertirMonto(150000, 'ARS', 'EUR'));
    }

    public function test_same_currency_is_not_converted(): void
    {
        $this->assertSame(123.45, $this->conversor->convertirMonto(123.45, 'ARS', 'ARS'));
    }

    public function test_uses_the_rate_in_force_at_the_given_date(): void
    {
        TasaCambio::create(['moneda' => 'ARS', 'tasa' => 2000, 'fecha' => now()]);

        $this->assertSame(16200.0, $this->conversor->convertirMonto(10, 'USD', 'ARS', now()->subHours(12)));
        $this->assertSame(20000.0, $this->conversor->convertirMonto(10, 'USD', 'ARS'));
    }

    public function test_missing_rate_raises_an_error_instead_of_converting(): void
    {
        Moneda::create(['codigo' => 'VES']);

        $this->expectException(TasaNoDisponible::class);

        $this->conversor->convertirMonto(10, 'USD', 'VES');
    }

    public function test_rate_from_before_its_first_value_is_not_available(): void
    {
        $this->expectException(TasaNoDisponible::class);

        $this->conversor->convertirMonto(10, 'USD', 'ARS', now()->subDays(2));
    }

    public function test_currencies_cannot_be_deleted(): void
    {
        $this->expectException(MonedaNoEliminable::class);

        Moneda::where('codigo', 'EUR')->first()->delete();
    }

    public function test_base_currency_and_codes(): void
    {
        $this->assertSame('USD', Moneda::base());
        $this->assertSame(['USD', 'ARS', 'EUR'], Moneda::codigos());
    }

    public function test_amounts_and_rates_are_formatted_for_the_app_locale(): void
    {
        $this->assertStringContainsString('150.000,00', $this->conversor->formatearMonto(150000, 'ARS'));
        $this->assertSame('1 USD = 1.620,00 ARS', TasaCambio::vigente('ARS')->tasa_formateada);
        $this->assertSame('Peso argentino', Moneda::where('codigo', 'ARS')->first()->nombre);
    }

    public function test_owner_payment_is_converted_to_the_invoice_and_fund_currencies(): void
    {
        $unidad = $this->unidad();
        $factura = Factura::forceCreate([
            'numero' => 'F-1', 'monto' => 100, 'monto_por_pagar' => 100, 'moneda' => 'USD',
            'fecha' => now(), 'unidad_id' => $unidad->id, 'iva_id' => Iva::create(['factor' => 16, 'fecha' => now()])->id,
        ]);
        $fondo = Fondo::create(['descripcion' => 'Fondo en euros', 'saldo' => 0, 'moneda' => 'EUR']);

        $pago = PagoPropietario::forceCreate([
            'descripcion' => 'Pago', 'monto' => 81000, 'fecha' => now(), 'forma_pago' => 'Transferencia',
            'moneda' => 'ARS', 'fondo_id' => $fondo->id, 'unidad_id' => $unidad->id, 'factura_id' => $factura->id,
        ]);

        $pago->pagarFactura();

        // 81000 ARS = 50 USD = 46 EUR
        $this->assertSame('Pagada', $factura->fresh()->estado);
        $this->assertEquals(50, AjusteSaldo::where('factura_origen_id', $factura->id)->where('tipo', 'Faltante')->value('monto'));
        $this->assertEquals(46, $fondo->fresh()->saldo);
    }

    public function test_expense_payment_is_converted_to_the_expense_currency(): void
    {
        $gasto = Gasto::create([
            'descripcion' => 'Pintura', 'tipo' => 'Ordinario', 'calculo_por' => 'Total de inmuebles',
            'mes_cobro' => now()->format('Y-m'), 'moneda' => 'USD', 'monto' => 100, 'saldo' => 100,
            'proveedor_id' => Proveedor::factory()->create()->id, 'factura' => 'F-2',
        ]);
        $fondo = Fondo::create(['descripcion' => 'Fondo en pesos', 'saldo' => 200000, 'moneda' => 'ARS']);

        $pago = Pago::create([
            'descripcion' => 'Pago pintura', 'monto' => 81000, 'fecha' => now(), 'recibo' => 'R-1', 'forma_pago' => 'Transferencia', 'moneda' => 'ARS',
        ]);
        $pago->gasto()->associate($gasto);
        $pago->fondo()->associate($fondo);
        $pago->save();

        $pago->pagarGasto();

        $this->assertEquals(50, $gasto->fresh()->saldo);
        $this->assertEquals(119000, $fondo->fresh()->saldo);
    }

    private function unidad(): Unidad
    {
        $propietario = Propietario::forceCreate([
            'integrante_id' => Integrante::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ]);

        return Unidad::factory()->create([
            'tipo_unidad_id' => TipoUnidad::factory()->create()->id,
            'propietario_id' => $propietario->id,
        ]);
    }
}
