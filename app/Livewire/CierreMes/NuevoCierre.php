<?php

namespace App\Livewire\CierreMes;

use App\Models\CierreMes;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Interes;
use App\Models\Item;
use App\Models\Iva;
use App\Models\Mensualidad;
use App\Models\Sancion;
use App\Models\TasaCambio;
use App\Models\Unidad;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class NuevoCierre extends Component
{
	public $mes;
	public $moneda = 'Bolívar';
	public TasaCambio $tasaCambio;

	public $open = false;

	public $readyToLoad = false;

	protected $rules = [
		'mes' => 'required|before_or_equal:this month',
		'moneda' => 'required',
		'tasaCambio.tasa' => 'required|numeric|gt:0',
	];

	protected $messages = [
		'mes.before_or_equal' => 'El mes debe ser anterior o igual a este mes.',
	];

	public function mount()
	{
		$this->tasaCambio = TasaCambio::orderBy('created_at', 'desc')->first();
	}

	public function render()
	{
		return view('livewire.cierre-mes.nuevo-cierre');
	}

	public function updated($propertyName)
	{
		$this->validateOnly($propertyName);
	}

	public function cerrarMes()
	{
		$this->validate();

		if(CierreMes::where('mes', $this->mes)->first()) {
			flash()->error('El mes que ha seleccionado ya ha sido cerrado.');
			return false;
		}

		DB::beginTransaction();

		try {
			$cierre = CierreMes::create([
				'mes' => $this->mes,
				'moneda' => $this->moneda,
				'tasa_cambio_id' => $this->tasaCambio->id,
			]);

			// Se obtienen todas las unidades que pertenezcan a un propietario
			$unidades = Unidad::has('propietario')->get();

			// Se calcula la suma del área de todas las unidades
			$areaTotal = Unidad::getAreaTotal();

			// Se obtienen los datos de la mensualidad del condominio
			$mensualidad = Mensualidad::orderBy('created_at', 'desc')->first();

			// Ordinary expenses are charged once, in the first close on or after mes_cobro
			$gastosOrdinarios = Gasto::where('tipo', 'Ordinario')
				->where('mes_cobro', '<=', $this->mes)
				->where('estado_cobro', 'Pendiente')
				->get();

			// Extraordinary expenses are charged in installments until their last month
			$gastosExtraordinarios = Gasto::with('extraordinario')
				->where('tipo', 'Extraordinario')
				->where('mes_cobro', '<=', $this->mes)
				->whereIn('estado_cobro', ['Pendiente', 'En proceso'])
				->get()
				->filter(fn (Gasto $gasto) => $gasto->getUltimoMesCobro() >= $this->mes);

			$gastos = $gastosOrdinarios->concat($gastosExtraordinarios);

			// Se obtienen los datos del interés
			$interes = Interes::orderBy('created_at', 'desc')->first();

			// Se obtienen los datos del IVA
			$iva = Iva::orderBy('created_at', 'desc')->first();

			foreach ($unidades as $unidad) {

				// Se genera una colección para los elementos de la factura
				$itemsFactura = new Collection;

				// Se crea el item de mensualidad de la factura, ya que será igual para todo
				$itemMensualidad = Item::make([
					'itemable_id' => $mensualidad->id,
					'itemable_type' => Mensualidad::class,
					'monto' => $mensualidad->monto,
				]);

				// Se agrega a la colección el item de la mensualidad creado anteriormente
				$itemsFactura->push($itemMensualidad);

				foreach ($gastos as $gasto) {
					$itemsFactura->push(Item::make([
						'itemable_id' => $gasto->id,
						'itemable_type' => Gasto::class,
						'monto' => $this->montoPorUnidad($gasto, $unidad, $unidades->count(), $areaTotal),
					]));
				}

				foreach ($unidad->sanciones()->where('estado', 'Por procesar')->get() as $sancion) {

					// Se crea el item para la factura
					$item = Item::make([
						'itemable_id' => $sancion->id,
						'itemable_type' => Sancion::class,
						'monto' => $sancion->monto,
					]);

					// Se agrega el item a la colección
					$itemsFactura->push($item);

					// Se cambia el estado de la sanción para indicar que ya fue procesada en una factura
					$sancion->pivot->estado = 'Procesada';
					$sancion->pivot->save();
				}

				// Se crea una variable para almacenar el monto total de la factura
				$montoFactura = 0;

				// Se suma el monto de todos los items
				foreach ($itemsFactura as $item) {
					$item->convertirMonto($this->moneda, $this->tasaCambio->tasa);

					$montoFactura += $item->monto;
				}

				$count = 0;
				$numero = Str::substr($this->mes, 0, 4) . Str::substr($this->mes, 5, 2) . '-' . $unidad->numero . '-' . ++$count;

				// Se crea la factura
				$factura = Factura::make([
					'numero' => $numero,
					'monto' => $montoFactura,
					'monto_por_pagar' => $montoFactura,
					'moneda' => $this->moneda,
					'fecha' => now(),
					'cierre_mes_id' => $cierre->id,
					'unidad_id' => $unidad->id,
					'iva_id' => $iva->id,
					'tasa_cambio_id' => $this->tasaCambio->id,
				]);

				if ($interes && $interes->estado) {

					$facturaMasVieja = $unidad->facturas()->orderBy('fecha', 'asc')->first();

					// Carbon 3 returns a signed diff, so measure from the old invoice forward
					if ($facturaMasVieja && Carbon::parse($facturaMasVieja->fecha)->diffInMonths(Carbon::today()) >= $interes->meses) {
						$factura->interes()->associate($interes);

						$factura->revertirIva();
						$factura->monto += $factura->monto * ($interes->factor / 100);
					}
				}

				$factura->save();

				// Se guardan los items en la base de datos
				foreach ($itemsFactura as $item) {
					$item->factura()->associate($factura->id);
					$item->save();
				}
			}

			foreach ($gastosOrdinarios as $gasto) {
				$gasto->estado_cobro = 'Procesado';
				$gasto->save();
			}

			foreach ($gastosExtraordinarios as $gasto) {
				$gasto->estado_cobro = $gasto->getUltimoMesCobro() <= $this->mes ? 'Procesado' : 'En proceso';
				$gasto->save();
			}

			DB::commit();

			$this->reset([
				'open',
				'mes',
				'moneda',
			]);

			$this->dispatch('render')->to('cierre-mes.tabla-factura');
			flash()->success('El cierre de mes fue satisfactorio. Se han generado las facturas.');

		} catch(Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	private function montoPorUnidad(Gasto $gasto, Unidad $unidad, int $numeroUnidades, $areaTotal): float
	{
		$monto = $gasto->getMontoCuota();

		if ($gasto->calculo_por == 'Alícuota') {
			return $monto * ($unidad->tipoUnidad->area / $areaTotal);
		}

		return $monto / $numeroUnidades;
	}
}
