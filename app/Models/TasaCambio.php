<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

/**
 * Tasa de una moneda respecto a la moneda base: 1 unidad de la base = tasa unidades de moneda.
 * Las tasas no se editan: cada cambio crea un registro nuevo, así se conserva el historial.
 */
class TasaCambio extends Model
{
    use HasFactory;

    protected $table = 'tasas_cambio';

    protected $fillable = [
        'moneda',
        'tasa',
        'fecha',
    ];

    /**
     * Última tasa de la moneda con fecha igual o anterior a la indicada (ahora, si no se indica).
     */
    public static function vigente(string $moneda, $fecha = null): ?self
    {
        return static::where('moneda', $moneda)
            ->where('fecha', '<=', $fecha ?? now())
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * La tasa como texto, por ejemplo "1 USD = 1.620,00 ARS".
     */
    public function getTasaFormateadaAttribute(): string
    {
        $formato = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);
        $formato->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formato->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 6);

        return '1 ' . Moneda::base() . ' = ' . $formato->format((float) $this->tasa) . ' ' . $this->moneda;
    }
}
