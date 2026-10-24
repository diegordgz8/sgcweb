<?php

namespace App\Models;

use App\Observers\ComunicadoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ComunicadoObserver::class)]
class Comunicado extends Model
{
    use HasFactory;

	protected $with = ['autor'];

    /**
     * Los atributos que permiten asignación masiva.
     *
     * @var array
     */
    protected $fillable = ['asunto', 'contenido'];

	public function autor() {
        return $this->belongsTo(Administrador::class, 'administrador_id')->withTrashed();
    }
}
