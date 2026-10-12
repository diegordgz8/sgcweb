<?php

namespace Tests\Feature;

use App\Livewire\CierreMes\NuevoCierre;
use App\Models\CierreMes;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\GastoExtraordinario;
use App\Models\Integrante;
use App\Models\Interes;
use App\Models\Item;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Propietario;
use App\Models\Proveedor;
use App\Models\Sancion;
use App\Models\TasaCambio;
use App\Models\TipoUnidad;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CierreMesTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        TasaCambio::create(['tasa' => 40, 'fecha' => now()]);
        Iva::create(['factor' => 16, 'fecha' => now()]);
        Mensualidad::create(['monto' => 10, 'moneda' => 'Bolívar']);

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
        $gasto = $this->crearGasto('Ordinario', 5, $this->mes(0), moneda: 'Dólar');

        $this->cerrar($this->mes(0), 'Bolívar');

        $this->assertEquals([200.0], $this->montosCobrados($gasto, $unidades[0], $this->mes(0)));
    }

    public function test_pending_sanction_is_charged_once(): void
    {
        [$unidad] = $this->crearUnidades([50]);
        $sancion = Sancion::create(['descripcion' => 'Ruido', 'monto' => 25, 'moneda' => 'Bolívar']);
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

    private function cerrar(string $mes, string $moneda = 'Bolívar'): void
    {
        Livewire::test(NuevoCierre::class)
            ->set('moneda', $moneda)
            ->set('mes', $mes)
            ->call('cerrarMes');
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
        string $moneda = 'Bolívar',
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
            'moneda' => 'Bolívar',
            'fecha' => $fecha,
            'unidad_id' => $unidad->id,
            'iva_id' => Iva::first()->id,
            'tasa_cambio_id' => TasaCambio::first()->id,
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
