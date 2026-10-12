<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for Livewire actions that had no permission of their own.
 * RoleSeeder creates them too (with findOrCreate) for fresh installs.
 */
return new class extends Migration
{
	private array $permisos = [
		'cierre-mes.create' => ['Administrador', 'Condominio'],
		'cuenta.delete' => ['Administrador', 'Condominio'],
		'gasto.edit' => ['Administrador', 'Condominio'],
		'gasto.delete' => ['Administrador', 'Condominio'],
		'proveedor.delete' => ['Administrador', 'Condominio'],
		'servicio.delete' => ['Administrador'],
	];

	public function up(): void
	{
		foreach ($this->permisos as $nombre => $roles) {
			$permiso = Permission::findOrCreate($nombre);

			foreach (Role::whereIn('name', $roles)->get() as $rol) {
				$rol->givePermissionTo($permiso);
			}
		}

		app(PermissionRegistrar::class)->forgetCachedPermissions();
	}

	public function down(): void
	{
		Permission::whereIn('name', array_keys($this->permisos))->delete();

		app(PermissionRegistrar::class)->forgetCachedPermissions();
	}
};
