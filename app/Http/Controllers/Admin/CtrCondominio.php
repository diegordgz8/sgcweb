<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condominio;
use App\Models\Mensualidad;
use App\Models\TasaCambio;
use App\Traits\WithCurrencies;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\Timezone;

class CtrCondominio extends Controller
{
	use WithCurrencies;

	public function index()
	{
		return view('admin.condominio.index');
	}

	public function store(Request $request)
	{
		$request->validate([
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
			'tasa' => 'required|numeric|gt:0',
			'monto' => 'required|numeric|gt:0',
			'moneda' => [
				'required',
				Rule::in((array) $request->input('monedas')),
				Rule::in(array_keys($this->monedasSoportadas())),
			],
		], [
			'zona_horaria.exists' => 'La zona horaria seleccionada no corresponde al país.',
			'moneda.in' => 'La moneda de la mensualidad debe ser una de las monedas seleccionadas. Por ahora el sistema solo puede facturar en ' . implode(' o ', array_keys($this->monedasSoportadas())) . '.',
		]);

		Condominio::create([
			'rif' => 'J-' . $request->rif,
			'nombre' => $request->nombre,
			'direccion' => $request->direccion,
			'pais' => $request->pais,
			'zona_horaria' => $request->zona_horaria,
			'monedas' => $request->monedas,
		]);

		TasaCambio::create([
			'tasa' => $request->tasa,
		]);

		Mensualidad::create([
			'monto' => $request->monto,
			'moneda' => $this->monedasSoportadas()[$request->moneda],
		]);

		return redirect()->route('admin.home');
	}
}
