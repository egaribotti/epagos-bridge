<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class Boleta extends Model
{
    protected $table = 'epagos_boletas';

    protected $fillable = [
        'id_transaccion',
        'id_organismo',
        'boleta_estado_id',
        'id_fp',
        'monto_final',
        'url_recibo',
        'fecha_pago',
        'fecha_acreditacion',
        'fecha_novedad',
        'fecha_verificacion',
    ];

    public function boletaEstado(): object
    {
        return $this->belongsTo(BoletaEstado::class);
    }

    public function operaciones(): object
    {
        return $this->hasMany(Operacion::class);
    }

}
