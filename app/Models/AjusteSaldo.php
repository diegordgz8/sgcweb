<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Diferencia entre lo que una unidad debía y lo que pagó. Un faltante se cobra y un saldo
 * a favor se descuenta en la próxima factura de la unidad; ninguna diferencia se perdona.
 */
class AjusteSaldo extends Model
{
    protected $table = 'ajustes_saldo';

    protected $fillable = [
        'unidad_id',
        'tipo',
        'monto',
        'moneda',
        'deuda_desde',
        'factura_origen_id',
        'pago_propietario_id',
        'factura_aplicada_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'deuda_desde' => 'date',
    ];

    /**
     * Ajustes que todavía no se aplicaron en ninguna factura.
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereNull('factura_aplicada_id');
    }

    public function esFaltante(): bool
    {
        return $this->tipo === 'Faltante';
    }

    /**
     * Monto con signo: positivo si se cobra, negativo si se descuenta.
     */
    public function getMontoConSignoAttribute(): float
    {
        return $this->esFaltante() ? (float) $this->monto : -(float) $this->monto;
    }

    public function getDescripcionAttribute(): string
    {
        $origen = $this->facturaOrigen?->numero;

        if ($this->esFaltante()) {
            return $origen ? "Faltante de la factura $origen" : 'Faltante';
        }

        return $origen ? "Saldo a favor de la factura $origen" : 'Saldo a favor';
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function facturaOrigen()
    {
        return $this->belongsTo(Factura::class, 'factura_origen_id');
    }

    public function pago()
    {
        return $this->belongsTo(PagoPropietario::class, 'pago_propietario_id');
    }

    public function facturaAplicada()
    {
        return $this->belongsTo(Factura::class, 'factura_aplicada_id');
    }
}
