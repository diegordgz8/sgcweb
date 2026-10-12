<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Currencies stop being the fixed 'Bolívar' / 'Dólar' pair: the condominium works with
 * the ISO 4217 currencies chosen at setup, one of them fixed as the base currency.
 * Each other currency has its own exchange rate history against the base, so a single
 * tasa_cambio reference per invoice, close or payment is no longer enough.
 *
 * Meant for fresh installs: existing 'Bolívar' / 'Dólar' values do not fit in char(3).
 */
return new class extends Migration
{
	private array $columnasMoneda = [
		'mensualidades',
		'fondos',
		'gastos',
		'sanciones',
		'facturas',
		'pagos_gastos',
		'pagos_propietario',
		'cierres_mes',
	];

	private array $montos = [
		'mensualidades' => 'monto',
		'sanciones' => 'monto',
		'items' => 'monto',
		'sancion_unidad' => 'monto_pagar',
	];

	public function up(): void
	{
		Schema::create('monedas', function (Blueprint $table) {
			$table->id();
			$table->char('codigo', 3)->unique();
			$table->boolean('es_base')->default(false);
			$table->timestamps();
		});

		// 1 unit of the base currency = tasa units of moneda
		Schema::table('tasas_cambio', function (Blueprint $table) {
			$table->char('moneda', 3)->after('id')->index();
			$table->decimal('tasa', 18, 6)->change();
		});

		foreach ($this->columnasMoneda as $tabla) {
			Schema::table($tabla, function (Blueprint $table) {
				$table->char('moneda', 3)->change();
			});
		}

		foreach ($this->montos as $tabla => $columna) {
			Schema::table($tabla, function (Blueprint $table) use ($columna) {
				$table->decimal($columna, 15, 2)->change();
			});
		}

		foreach (['facturas', 'cierres_mes', 'pagos_propietario'] as $tabla) {
			Schema::table($tabla, function (Blueprint $table) {
				$table->dropConstrainedForeignId('tasa_cambio_id');
			});
		}

		Schema::table('pagos_gastos', function (Blueprint $table) {
			$table->dropColumn('tasa_cambio');
		});
	}

	public function down(): void
	{
		Schema::table('pagos_gastos', function (Blueprint $table) {
			$table->decimal('tasa_cambio', 12)->nullable();
		});

		// Nullable so rollback works on tables that already have rows
		foreach (['facturas', 'cierres_mes', 'pagos_propietario'] as $tabla) {
			Schema::table($tabla, function (Blueprint $table) {
				$table->foreignId('tasa_cambio_id')->nullable()->constrained('tasas_cambio');
			});
		}

		foreach ($this->montos as $tabla => $columna) {
			Schema::table($tabla, function (Blueprint $table) use ($columna) {
				$table->decimal($columna)->change();
			});
		}

		foreach ($this->columnasMoneda as $tabla) {
			Schema::table($tabla, function (Blueprint $table) use ($tabla) {
				$tabla == 'cierres_mes'
					? $table->string('moneda')->change()
					: $table->enum('moneda', ['Bolívar', 'Dólar'])->change();
			});
		}

		Schema::table('tasas_cambio', function (Blueprint $table) {
			$table->dropIndex(['moneda']);
			$table->dropColumn('moneda');
			$table->decimal('tasa')->change();
		});

		Schema::dropIfExists('monedas');
	}
};
