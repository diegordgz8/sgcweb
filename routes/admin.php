<?php

use App\Http\Controllers\Admin\CtrAdministrador;
use App\Http\Controllers\Admin\CtrComunicado;
use App\Http\Controllers\Admin\CtrCondominio;
use App\Http\Controllers\Admin\CtrHabitante;
use App\Http\Controllers\Admin\CtrSancion;
use App\Http\Controllers\Admin\CtrUnidad;
use App\Http\Controllers\Admin\CtrUser;
use App\Livewire\Admin\Database\Backup;
use App\Livewire\Admin\Permisos\TablaPermiso;
use App\Livewire\Admin\Roles\TablaRol;
use Illuminate\Support\Facades\Route;

Route::view('/', 'admin.index')->middleware('can:admin.home')->name('home');

Route::get('backup', Backup::class)->middleware('can:admin.database')->name('backup');

Route::resource('administrador', CtrAdministrador::class)->only(['index', 'show'])->middleware('can:admin.administrador.index')->names('administrador');

Route::get('condominio', [CtrCondominio::class, 'index'])->middleware('can:admin.home')->name('condominio');

Route::post('condominio', [CtrCondominio::class, 'store'])->middleware('can:admin.home')->name('condominio.store');

Route::resource('comunicado', CtrComunicado::class)->only('index', 'show')->middleware('can:admin.comunicado.index')->names('comunicado');

Route::resource('habitante', CtrHabitante::class)->only(['index', 'show'])->middleware('can:integrante.index')->names('habitante');

Route::get('permisos', TablaPermiso::class)->middleware('can:admin.permiso.index')->name('permiso.index');

Route::get('roles', TablaRol::class)->middleware('can:admin.rol.index')->name('rol.index');

// Route::get('roles', CtrProveedor::class)->name('rol.show');

Route::resource('sancion', CtrSancion::class)->only(['index', 'show'])->middleware('can:sancion.index')->names('sancion');

Route::resource('unidad', CtrUnidad::class)->only(['index', 'show'])->middleware('can:admin.unidad.index')->names('unidad');

Route::get('usuario', [CtrUser::class, 'index'])->middleware('can:admin.usuario.index')->name('usuario.index');

Route::get('usuario/{usuario}', [CtrUser::class, 'show'])->middleware('can:admin.usuario.show')->name('usuario.show');
