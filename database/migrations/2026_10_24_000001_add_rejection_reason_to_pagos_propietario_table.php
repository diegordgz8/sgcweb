<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why the condominium rejected an owner's payment, so the owner knows what to correct.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('pagos_propietario', function (Blueprint $table) {
			$table->text('motivo_rechazo')->nullable()->after('estado');
		});
	}

	public function down(): void
	{
		Schema::table('pagos_propietario', function (Blueprint $table) {
			$table->dropColumn('motivo_rechazo');
		});
	}
};
