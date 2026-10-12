<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condominio;
use App\Models\Mensualidad;
use App\Models\Moneda;
use App\Models\TasaCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\Timezone;

class CtrCondominio extends Controller
{
	public function index()
	{
		if (Condominio::exists()) {
			return $this->yaConfigurado();
		}

		return view('admin.condominio.index');
	}

	public function store(Request $request)
	{
		if (Condominio::exists()) {
			return $this->yaConfigurado();
		}

		// Las monedas no se eliminan nunca, así que solo se pueden elegir en una instalación nueva
		if (Moneda::exists()) {
			return back()->withInput()->withErrors([
				'monedas' => 'Ya hay monedas registradas de una instalación anterior. La configuración inicial solo se puede hacer con la base de datos vacía.',
			]);
		}

		$monedas = (array) $request->input('monedas');

		$reglas = [
			'rif' => 'required|max:10',
			'nombre' => 'required',
			'direccion' => 'required',
			'pais' => ['required', Rule::exists(Country::class, 'iso2')],
			'zona_horaria' => [
				'required',
				Rule::exists(Timezone::class, 'name')
					->where('country_id', Country::where('iso2', $request->input('pais'))->value('id')),
			],
			'monedas' => 'required|array|min:1',
			'monedas.*' => ['distinct', Rule::exists(Currency::class, 'code')],
			'moneda_base' => ['required', Rule::in($monedas)],
			'monto' => 'required|numeric|gt:0',
			'moneda' => ['required', Rule::in($monedas)],
		];

		// Cada moneda que no es la base necesita su tasa inicial: 1 base = tasa unidades de la moneda
		foreach (array_diff($monedas, [$request->input('moneda_base')]) as $codigo) {
			$reglas["tasas.$codigo"] = 'required|numeric|gt:0';
		}

		$request->validate($reglas, [
			'zona_horaria.exists' => 'La zona horaria seleccionada no corresponde al país.',
			'moneda_base.in' => 'La moneda base debe ser una de las monedas seleccionadas.',
			'moneda.in' => 'La moneda de la mensualidad debe ser una de las monedas seleccionadas.',
			'tasas.*.required' => 'Indique la tasa de cambio de :attribute.',
		], $this->nombresDeTasas($monedas));

		DB::transaction(function () use ($request, $monedas) {
			Condominio::create([
				'rif' => 'J-' . $request->rif,
				'nombre' => $request->nombre,
				'direccion' => $request->direccion,
				'pais' => $request->pais,
				'zona_horaria' => $request->zona_horaria,
			]);

			foreach ($monedas as $codigo) {
				Moneda::create([
					'codigo' => $codigo,
					'es_base' => $codigo === $request->moneda_base,
				]);

				if ($codigo !== $request->moneda_base) {
					TasaCambio::create([
						'moneda' => $codigo,
						'tasa' => $request->input("tasas.$codigo"),
						'fecha' => now(),
					]);
				}
			}

			Mensualidad::create([
				'monto' => $request->monto,
				'moneda' => $request->moneda,
			]);
		});

		return redirect()->route('admin.home');
	}

	private function yaConfigurado()
	{
		flash()->info('El condominio ya está configurado.');

		return redirect()->route('admin.home');
	}

	/**
	 * Nombres de los campos de tasa para los mensajes de validación, por ejemplo "ARS".
	 */
	private function nombresDeTasas(array $monedas): array
	{
		return collect($monedas)->mapWithKeys(fn ($codigo) => ["tasas.$codigo" => $codigo])->all();
	}
}
