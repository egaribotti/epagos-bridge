<?php

namespace EpagosBridge\Models;

use EpagosBridge\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Model;

class Boleta extends Model
{
    protected $table = 'epagos_boletas';

    protected $fillable = [
        'id_transaccion',
        'id_organismo',
        'estado',
        'id_fp',
        'monto_final',
        'url_recibo',
        'concepto',
        'fecha_pago',
        'fecha_acreditacion',
        'fecha_novedad',
        'fecha_verificacion',
    ];

    protected $appends = ['estado_descripcion'];

    public function getEstadoDescripcionAttribute(): string
    {
        return strtolower(array_flip((new \ReflectionClass(EstadoPago::class))
            ->getConstants())[$this->estado]);
    }

    public function operacion(): object
    {
        return $this->hasOne(Operacion::class, 'id_transaccion', 'id_transaccion');
    }

    public function pagosAdicionales(): object
    {
        return $this->hasMany(PagoAdicional::class);
    }

    public function operaciones(): object
    {
        return $this->hasMany(Operacion::class);
    }

}
