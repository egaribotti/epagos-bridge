<?php

use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Models\Boleta;
use Illuminate\Support\Facades\Artisan;

Artisan::command('epagos:verificar-pagos {limite=500}', function ($limite) {
    $boletas = Boleta::whereNull('fecha_verificacion')
        ->where('boleta_estado_id', 1)
        ->limit($limite)
        ->get();
    if ($boletas->isEmpty()) {

        // Cuando ya no hay nada más que verificar, vuelve a empezar

        Boleta::where('boleta_estado_id', 1)->update([
            'fecha_verificacion' => null
        ]);
    }
    foreach ($boletas as $boleta) {
        VerificarPago::dispatchSync($boleta->id_transaccion);
    }

})->purpose('Verificar los pagos con estado pendiente');
