<?php

use EpagosBridge\Http\Middleware\SecretGuard;
use EpagosBridge\Lib\EpagosApi;
use EpagosBridge\Models\Operacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::middleware([SecretGuard::class])->prefix('epagos-bridge')
    ->group(function () {
        Route::get('/pagos', function (Request $request) {
            if (!$request->id_transaccion) {
                return Response::json(null, 422);
            }

            $operacion = Operacion::firstWhere('id_transaccion', $request->id_transaccion);

            if (!$operacion && !$request->id_organismo) {
                return Response::json(null, 422);
            }

            $epagosApi = new EpagosApi();
            $respuesta = $epagosApi->obtenerPago($request->id_organismo ?? $operacion->id_organismo, $request->id_transaccion);
            return Response::json($respuesta);
        });
    });
