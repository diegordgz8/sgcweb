<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Differences between what a unit owed and what it paid. A shortfall (Faltante) or a
 * credit (A favor) is carried to the unit's next invoice, so no amount is ever forgiven.
 * Invoices remember since when their oldest unpaid debt dates, for late interest.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('ajustes_saldo', function (Blueprint $table) {
			$table->id();
			$table->foreignId('unidad_id')->constrained('unidades');
			$table->enum('tipo', ['Faltante', 'A favor']);
			$table->decimal('monto', 15, 2);
			$table->char('moneda', 3);
			$table->date('deuda_desde');
			$table->foreignId('factura_origen_id')->nullable()->constrained('facturas');
			$table->foreignId('pago_propietario_id')->nullable()->constrained('pagos_propietario');
			$table->foreignId('factura_aplicada_id')->nullable()->constrained('facturas');
			$table->timestamps();
		});

		Schema::table('facturas', function (Blueprint $table) {
			$table->date('deuda_desde')->nullable()->after('fecha');
		});
	}

	public function down(): void
	{
		Schema::table('facturas', function (Blueprint $table) {
			$table->dropColumn('deuda_desde');
		});

		Schema::dropIfExists('ajustes_saldo');
	}
};
