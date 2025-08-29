<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class PagoAdicional extends Model
{
    protected $table = 'epagos_pagos_adicionales';

    protected $fillable = [
        'boleta_id',
        'id_transaccion',
        'id_pago',
        'id_organismo',
        'forma_pago',
        'monto',
        'fecha_pago',
        'fecha_novedad',
    ];
}
