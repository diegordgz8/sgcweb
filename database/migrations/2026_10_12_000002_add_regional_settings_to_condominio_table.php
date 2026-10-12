<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regional settings of the condominium, chosen from the nnjeim/world data:
 * country (ISO2), timezone (IANA) and working currencies (ISO 4217 codes).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('condominio', function (Blueprint $table) {
			$table->char('pais', 2)->nullable()->after('direccion');
			$table->string('zona_horaria')->nullable()->after('pais');
			$table->json('monedas')->nullable()->after('zona_horaria');
		});
	}

	public function down(): void
	{
		Schema::table('condominio', function (Blueprint $table) {
			$table->dropColumn(['pais', 'zona_horaria', 'monedas']);
		});
	}
};
