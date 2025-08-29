<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    protected $table = 'epagos_formas_pago';

    public $timestamps = false;

    protected $fillable = [
        'id_fp',
        'descripcion',
        'modalidad',
    ];
}
