<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class Credencial extends Model
{
    protected $table = 'epagos_credenciales';

    protected $fillable = [
        'id_organismo',
        'id_usuario',
        'password',
        'hash',
    ];
}
