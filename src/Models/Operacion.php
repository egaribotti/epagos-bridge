<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class Operacion extends Model
{
    protected $table = 'epagos_operaciones';

    protected $fillable = [
        'id_transaccion',
        'id_organismo',
        'boleta_id',
        'referencia_adicional',
        'codigo_externo',
        'monto',
        'fecha_vencimiento',
    ];

    public function boleta(): object
    {
        return $this->belongsTo(Boleta::class);
    }
}
