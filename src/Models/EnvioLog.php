<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioLog extends Model
{
    protected $table = 'epagos_envio_logs';

    protected $fillable = [
        'id_transaccion',
        'id_organismo',
        'codigo_externo',
        'token',
        'id_resp',
        'respuesta',
        'url',
        'codigo_barras',
        'request_content',
        'response_content',
    ];
}
