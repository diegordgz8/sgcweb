<?php

namespace Tests\Feature;

use App\Livewire\CierreMes\NuevoCierre;
use App\Livewire\ShowFactura;
use App\Models\CierreMes;
use App\Models\Condominio;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\GastoExtraordinario;
use App\Models\Integrante;
use App\Models\Interes;
use App\Models\Item;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\Propietario;
use App\Models\Proveedor;
use App\Models\Sancion;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CierreMesTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->administrador = User::factory()->create()->assignRole('Condominio');

        Moneda::create(['codigo' => 'USD', 'es_base' => true]);
        Moneda::create(['codigo' => 'VES']);
        TasaCambio::create(['moneda' => 'VES', 'tasa' => 40, 'fecha' => now()]);
        Iva::create(['factor' => 16, 'fecha' => now()]);
        Mensualidad::create(['monto' => 10, 'moneda' => 'VES']);

        $this->proveedor = Proveedor::factory()->create();
    }

    public function test_ordinary_expense_is_charged_once(): void
    {
        $unidades = $this->crearUnidades([50, 50, 50, 50]);
        $gasto = $this->crearGasto('Ordinario', 400, $this->mes(0));

        $this->cerrar($this->mes(0));
        $this->cerrar($this->mes(1));

        $this->assertEquals([100.0], $this->montosCobrados($gasto, $unidades[0], $this->mes(0)));
        $this->assertEquals([], $this->montosCobrados($gasto, $unidades[0], $this->mes(1)));
        $this->assertSame('Procesado', $gasto->fresh()->estado_cobro);
    }

    public function test_extraordinary_expense_is_charged_in_installments(): void
    {
        $unidades = $this->crearUnidades([50, 50]);
        $gasto = $this->crearGasto('Extraordinario', 600, $this->mes(0), numeroMeses: 3);

        foreach (range(0, 3) as $i) {
            $this->cerrar($this->mes($i));
        }

        foreach (range(0, 2) as $i) {
            $this->assertEquals([100.0], $this->montosCobrados($gasto, $unidades[0], $this->mes($i)), "Cuota del mes $i");
        }
        $this->assertEquals([], $this->montosCobrados($gasto, $unidades[0], $this->mes(3)));
        $this->assertEquals(600, Item::where('itemable_type', Gasto::class)->where('itemable_id', $gasto->id)->sum('monto'));
        $this->assertSame('Procesado', $gasto->fresh()->estado_cobro);
    }

    public function test_extraordinary_expense_stays_in_progress_until_last_installment(): void
    {
        $this->crearUnidades([50]);
        $gasto = $this->crearGasto('Extraordinario', 300, $this->mes(0), numeroMeses: 3);

        $this->cerrar($this->mes(0));
        $this->assertSame('En proceso', $gasto->fresh()->estado_cobro);

        $this->cerrar($this->mes(1));
        $this->assertSame('En proceso', $gasto->fresh()->estado_cobro);
    }

    public function test_expense_is_not_charged_before_its_month(): void
    {
        $unidades = $this->crearUnidades([50]);
        $gasto = $this->crearGasto('Ordinario', 100, $this->mes(2));

        $this->cerrar($this->mes(0));

        $this->assertEquals([], $this->montosCobrados($gasto, $unidades[0], $this->mes(0)));
        $this->assertSame('Pendiente', $gasto->fresh()->estado_cobro);
    }

    public function test_expense_split_by_alicuota_follows_unit_area(): void
    {
        $unidades = $this->crearUnidades([20, 30, 50]);
        $gasto = $this->crearGasto('Ordinario', 1000, $this->mes(0), calculo: 'Alícuota');

        $this->cerrar($this->mes(0));

        $this->assertEquals([200.0], $this->montosCobrados($gasto, $unidades[0], $this->mes(0)));
        $this->assertEquals([300.0], $this->montosCobrados($gasto, $unidades[1], $this->mes(0)));
        $this->assertEquals([500.0], $this->montosCobrados($gasto, $unidades[2], $this->mes(0)));
    }

    public function test_dollar_expense_is_converted_with_exchange_rate(): void
    {
        $unidades = $this->crearUnidades([50]);
        $gasto = $this->crearGasto('Ordinario', 5, $this->mes(0), moneda: 'USD');

        $this->cerrar($this->mes(0), 'VES');

        $this->assertEquals([200.0], $this->montosCobrados($gasto, $unidades[0], $this->mes(0)));
    }

    public function test_pending_sanction_is_charged_once(): void
    {
        [$unidad] = $this->crearUnidades([50]);
        $sancion = Sancion::create(['descripcion' => 'Ruido', 'monto' => 25, 'moneda' => 'VES']);
        $unidad->sanciones()->attach($sancion, ['monto_pagar' => 25]);

        $this->cerrar($this->mes(0));
        $this->cerrar($this->mes(1));

        $this->assertSame(1, Item::where('itemable_type', Sancion::class)->where('itemable_id', $sancion->id)->count());
        $this->assertDatabaseHas('sancion_unidad', [
            'unidad_id' => $unidad->id,
            'sancion_id' => $sancion->id,
            'estado' => 'Procesada',
        ]);
    }

    public function test_invoice_total_includes_fee_and_expenses(): void
    {
        [$unidad] = $this->crearUnidades([50, 50]);
        $this->crearGasto('Ordinario', 100, $this->mes(0));

        $this->cerrar($this->mes(0));

        $factura = Factura::where('unidad_id', $unidad->id)->sole();
        $this->assertEquals(60, $factura->monto);
        $this->assertEquals(60, $factura->monto_por_pagar);
    }

    public function test_month_close_bills_in_the_configured_currency(): void
    {
        $this->crearUnidades([50]);

        // Mensualidad de 10 VES = 0,25 USD con 1 USD = 40 VES
        $this->cerrar($this->mes(0), 'USD');

        $factura = Factura::sole();
        $this->assertSame('USD', $factura->moneda);
        $this->assertEquals(0.25, $factura->monto);
        $this->assertSame('USD', CierreMes::sole()->moneda);

        Livewire::actingAs($this->administrador)->test(NuevoCierre::class)
            ->assertSee('USD - Dólar estadounidense')
            ->assertDontSee('wire:model.live="moneda"', false);
    }

    public function test_month_cannot_be_closed_twice(): void
    {
        $this->crearUnidades([50, 50]);

        $this->cerrar($this->mes(0));
        $this->cerrar($this->mes(0));

        $this->assertSame(1, CierreMes::count());
        $this->assertSame(2, Factura::count());
    }

    public function test_invoice_is_saved_when_interest_is_disabled(): void
    {
        $this->crearUnidades([50]);
        Interes::create(['factor' => 5, 'meses' => 1, 'estado' => false]);

        $this->cerrar($this->mes(0));

        $this->assertSame(1, Factura::count());
        $this->assertSame(0, Item::whereNull('factura_id')->count());
    }

    public function test_interest_applies_when_oldest_invoice_is_old_enough(): void
    {
        [$unidad] = $this->crearUnidades([50]);
        $interes = Interes::create(['factor' => 5, 'meses' => 3, 'estado' => true]);
        $this->crearFactura($unidad, now()->subMonths(4));

        $this->cerrar($this->mes(0));

        $nueva = Factura::where('unidad_id', $unidad->id)->whereNotNull('cierre_mes_id')->sole();
        $this->assertSame($interes->id, $nueva->interes_id);
    }

    public function test_interest_is_charged_on_base_with_vat_on_top(): void
    {
        // Monthly fee of 10 includes 16% VAT: base 8.62, interest 5% on base, VAT on base + interest
        [$unidad] = $this->crearUnidades([50]);
        Interes::create(['factor' => 5, 'meses' => 3, 'estado' => true]);
        $this->crearFactura($unidad, now()->subMonths(4));

        $this->cerrar($this->mes(0));

        $factura = Factura::whereNotNull('cierre_mes_id')->sole();
        $this->assertEquals(10.5, $factura->monto);
        $this->assertEquals(10.5, $factura->monto_por_pagar);

        $vista = Livewire::test(ShowFactura::class, ['factura' => $factura]);
        $this->assertEqualsWithDelta(10 / 1.16, $vista->get('sub'), 0.001);
        $this->assertEqualsWithDelta(10 / 1.16 * 0.05, $vista->get('montoInteres'), 0.001);
        $this->assertEqualsWithDelta(10 / 1.16 * 1.05 * 0.16, $vista->get('montoIva'), 0.001);
        $this->assertEqualsWithDelta(10.5, $vista->get('total'), 0.001);
    }

    public function test_invoice_view_without_interest_adds_vat_to_base(): void
    {
        $this->crearUnidades([50]);

        $this->cerrar($this->mes(0));

        $vista = Livewire::test(ShowFactura::class, ['factura' => Factura::sole()]);
        $this->assertEquals(0, $vista->get('montoInteres'));
        $this->assertEqualsWithDelta(10, $vista->get('total'), 0.001);
    }

    public function test_invoice_is_marked_paid_after_partial_payments(): void
    {
        [$unidad] = $this->crearUnidades([50]);
        $factura = $this->crearFactura($unidad, now(), monto: 0.3);

        $factura->pagar(0.1);
        $factura->pagar(0.1);
        $factura->pagar(0.1);

        $this->assertSame('Pagada', $factura->fresh()->estado);
    }

    private function mes(int $desplazamiento): string
    {
        return now()->startOfMonth()->subMonths(6)->addMonths($desplazamiento)->format('Y-m');
    }

    private function cerrar(string $mes, string $moneda = 'VES'): void
    {
        $condominio = Condominio::first()
            ?? Condominio::create(['rif' => 'J-12345678-9', 'nombre' => 'Residencias Prueba', 'direccion' => 'Caracas']);
        $condominio->update(['moneda_facturacion' => $moneda]);

        Livewire::actingAs($this->administrador)->test(NuevoCierre::class)
            ->set('mes', $mes)
            ->call('revisar')
            ->call('cerrarMes');
    }

    public function test_review_shows_what_will_be_charged_without_saving_anything(): void
    {
        $this->crearUnidades([50, 50]);
        $gasto = $this->crearGasto('Extraordinario', 600, $this->mes(0), numeroMeses: 3);
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Residencias Prueba', 'direccion' => 'Caracas', 'moneda_facturacion' => 'VES']);

        $vista = Livewire::actingAs($this->administrador)->test(NuevoCierre::class)
            ->set('mes', $this->mes(0))
            ->call('revisar')
            ->assertHasNoErrors();

        $previa = $vista->get('vistaPrevia');
        $this->assertCount(2, $previa['facturas']);
        $this->assertEquals(200, $previa['gastos'][0]['cuota']);
        $this->assertEquals(2 * 10 + 200, $previa['total']);
        $vista->assertSee('Cerrar '.Carbon::createFromFormat('!Y-m', $this->mes(0))->locale('es')->translatedFormat('F Y'));

        $this->assertSame(0, CierreMes::count());
        $this->assertSame(0, Factura::count());
        $this->assertSame('Pendiente', $gasto->fresh()->estado_cobro);
    }

    public function test_month_cannot_be_closed_without_reviewing_it(): void
    {
        $this->crearUnidades([50]);
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Residencias Prueba', 'direccion' => 'Caracas', 'moneda_facturacion' => 'VES']);

        Livewire::actingAs($this->administrador)->test(NuevoCierre::class)
            ->set('mes', $this->mes(0))
            ->call('cerrarMes')
            ->assertHasErrors('mes');

        $this->assertSame(0, CierreMes::count());
    }

    public function test_changing_the_month_discards_the_review(): void
    {
        $this->crearUnidades([50]);
        Condominio::create(['rif' => 'J-1', 'nombre' => 'Residencias Prueba', 'direccion' => 'Caracas', 'moneda_facturacion' => 'VES']);

        Livewire::actingAs($this->administrador)->test(NuevoCierre::class)
            ->set('mes', $this->mes(0))
            ->call('revisar')
            ->set('mes', $this->mes(1))
            ->assertSet('vistaPrevia', null)
            ->call('cerrarMes')
            ->assertHasErrors('mes');

        $this->assertSame(0, CierreMes::count());
    }

    /**
     * @return Unidad[]
     */
    private function crearUnidades(array $areas): array
    {
        return array_map(function ($area) {
            $tipo = TipoUnidad::factory()->create(['area' => $area]);

            $propietario = Propietario::forceCreate([
                'integrante_id' => Integrante::factory()->create()->id,
                'user_id' => User::factory()->create()->id,
            ]);

            return Unidad::factory()->create([
                'tipo_unidad_id' => $tipo->id,
                'propietario_id' => $propietario->id,
            ]);
        }, $areas);
    }

    private function crearGasto(
        string $tipo,
        float $monto,
        string $mesCobro,
        string $calculo = 'Total de inmuebles',
        string $moneda = 'VES',
        ?int $numeroMeses = null,
    ): Gasto {
        $gasto = Gasto::create([
            'descripcion' => "Gasto $tipo",
            'tipo' => $tipo,
            'calculo_por' => $calculo,
            'mes_cobro' => $mesCobro,
            'moneda' => $moneda,
            'monto' => $monto,
            'saldo' => $monto,
            'proveedor_id' => $this->proveedor->id,
            'factura' => 'F-'.uniqid(),
        ]);

        if ($tipo == 'Extraordinario') {
            GastoExtraordinario::create(['gasto_id' => $gasto->id, 'num_meses' => $numeroMeses]);
        }

        return $gasto;
    }

    private function crearFactura(Unidad $unidad, $fecha, float $monto = 100): Factura
    {
        return Factura::forceCreate([
            'numero' => 'F-'.uniqid(),
            'monto' => $monto,
            'monto_por_pagar' => $monto,
            'moneda' => 'VES',
            'fecha' => $fecha,
            'unidad_id' => $unidad->id,
            'iva_id' => Iva::first()->id,
        ]);
    }

    /**
     * Amounts charged for an expense on a unit's invoice for the given month.
     */
    private function montosCobrados(Gasto $gasto, Unidad $unidad, string $mes): array
    {
        $cierre = CierreMes::where('mes', $mes)->firstOrFail();

        return Item::where('itemable_type', Gasto::class)
            ->where('itemable_id', $gasto->id)
            ->whereIn('factura_id', Factura::where('cierre_mes_id', $cierre->id)->where('unidad_id', $unidad->id)->select('id'))
            ->pluck('monto')
            ->map(fn ($monto) => round((float) $monto, 2))
            ->all();
    }
}
