<?php

namespace App\Models;

use App\Exceptions\MonedaNoEliminable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Locale;
use ResourceBundle;

/**
 * Moneda con la que trabaja el condominio, identificada por su código ISO 4217.
 * Una sola es la moneda base y ninguna se puede eliminar.
 */
class Moneda extends Model
{
    use HasFactory;

    protected $table = 'monedas';

    protected $fillable = [
        'codigo',
        'es_base',
    ];

    protected $casts = [
        'es_base' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Moneda $moneda) {
            throw MonedaNoEliminable::para($moneda->codigo);
        });
    }

    /**
     * Código de la moneda base del condominio.
     */
    public static function base(): ?string
    {
        return static::where('es_base', true)->value('codigo');
    }

    /**
     * Moneda en la que se emiten las facturas del cierre de mes; la base si no se configuró.
     */
    public static function facturacion(): ?string
    {
        return Condominio::value('moneda_facturacion') ?? static::base();
    }

    /**
     * Códigos de las monedas del condominio, con la base primero.
     */
    public static function codigos(): array
    {
        return static::orderByDesc('es_base')->orderBy('codigo')->pluck('codigo')->all();
    }

    public function getNombreAttribute(): string
    {
        return static::nombreDe($this->codigo);
    }

    /**
     * Nombre de la moneda en el idioma de la aplicación según los datos de intl (ICU).
     * Si intl no la conoce se devuelve el código.
     */
    public static function nombreDe(string $codigo): string
    {
        $locale = app()->getLocale();

        foreach (array_unique([$locale, Locale::getPrimaryLanguage($locale)]) as $idioma) {
            $nombre = ResourceBundle::create($idioma, 'ICUDATA-curr')?->get('Currencies')?->get($codigo)?->get(1);

            if ($nombre) {
                return Str::ucfirst($nombre);
            }
        }

        return $codigo;
    }

    public function tasas()
    {
        return $this->hasMany(TasaCambio::class, 'moneda', 'codigo');
    }
}
