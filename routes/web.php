<?php

use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Models\Operacion;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::prefix('epagos-bridge')
    ->name('epagos.')
    ->group(function () {
        Route::post('/webhook', function (Request $request) {
            if (!$request->token || !$request->id_transaccion || !$request->numero_operacion) {
                return Response::json();
            }
            $token = strtoupper('epagos_webhook_token');

            // Para evitar solicitudes de otro origen que no es Epagos

            if (Env::get($token) === $request->token) {
                $operacion = Operacion::where('id_transaccion', $request->id_transaccion)
                    ->where('codigo_externo', $request->numero_operacion)
                    ->exists();

                if ($operacion) {
                    VerificarPago::dispatchSync($request->id_transaccion);
                }
            }
            return Response::json();
        })->middleware('throttle:100,1');
    });
