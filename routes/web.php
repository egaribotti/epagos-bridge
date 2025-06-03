<?php

use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Models\Operacion;
use EpagosBridge\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle'])
    ->prefix('epagos-bridge')
    ->group(function () {
        Route::post('/webhook', function (Request $request) {
            if (!$request->secret || !$request->id_transaccion || !$request->numero_operacion) {
                return Response::json();
            }
            $secret = strtoupper('epagos_webhook_secret');

            // Para evitar solicitudes de otro origen que no es Epagos

            if (Env::get($secret) === $request->secret) {

                Webhook::create(array_merge($request->all(), [
                    'id_tipo' => ord($request->tipo),
                    'codigo_externo' => $request->numero_operacion,
                    'response_content' => json_encode($request->all()),
                ]));

                $operacion = Operacion::where('id_transaccion', $request->id_transaccion)
                    ->where('codigo_externo', $request->numero_operacion)
                    ->exists();
                if ($operacion) VerificarPago::dispatchSync($request->id_transaccion);
            }
            return Response::json();
        });
    });
