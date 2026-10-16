<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Currency the month close bills in, set in the condominium administration instead of
 * being picked on every close.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('condominio', function (Blueprint $table) {
			$table->char('moneda_facturacion', 3)->nullable()->after('zona_horaria');
		});
	}

	public function down(): void
	{
		Schema::table('condominio', function (Blueprint $table) {
			$table->dropColumn('moneda_facturacion');
		});
	}
};
