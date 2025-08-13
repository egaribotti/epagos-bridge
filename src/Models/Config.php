<?php

namespace EpagosBridge\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = 'epagos_config';

    public $timestamps = false;

    public static function getValue(string $key): ?string
    {
        return static::where('key', $key)->value('value');
    }
}
