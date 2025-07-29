<?php

use EpagosBridge\Http\Middleware\SecretGuard;
use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Lib\EpagosApi;
use EpagosBridge\Models\Boleta;
use EpagosBridge\Models\Operacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::middleware([SecretGuard::class])->prefix('epagos-bridge')
    ->group(function () {

        Route::get('/pagos', function (Request $request) {

            $idOrganismo = $request->id_organismo;
            if (!$request->id_transaccion && !$idOrganismo) {
                return Response::json('El id de organismo es requerido cuando no se especifica id de transacción.', 422);
            }

            if ($idTransaccion = $request->id_transaccion) {

                $operacion = Operacion::firstWhere('id_transaccion', $idTransaccion);
                if (!$operacion) {
                    return Response::json('Operación inválida.', 422);
                }
                $idOrganismo = $operacion->id_organismo;
            }
            $epagosApi = new EpagosApi();
            $respuesta = $epagosApi->obtenerPago($idOrganismo, $request->id_transaccion, $request->criterios ?? []);
            return Response::json($respuesta);
        });

        Route::get('/boletas', function (Request $request) {
            if (!$request->ids_transaccion) {
                return Response::json('Los ids de transacción son requeridos.', 422);
            }

            $boletas = Boleta::whereIn('id_transaccion', $request->ids_transaccion)
                ->with(['boletaEstado', 'operaciones'])
                ->get();
            return Response::json($boletas);
        });

        Route::post('/verificacion-manual', function (Request $request) {
            if (!$request->ids_transaccion) {
                return Response::json('Los ids de transacción son requeridos.', 422);
            }

            foreach ($request->ids_transaccion as $idTransaccion) {
                VerificarPago::dispatchSync($idTransaccion);
            }
            $boletas = Boleta::whereIn('id_transaccion', $request->ids_transaccion)
                ->with('boletaEstado')
                ->get();
            return Response::json($boletas);
        });
    });
