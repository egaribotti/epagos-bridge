<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'epagos_webhook';

    protected $fillable = [
        'id_transaccion',
        'id_organismo',
        'id_tipo',
        'codigo_externo',
        'response_content',
    ];
}
