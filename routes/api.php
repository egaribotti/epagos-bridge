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
                $error = 'El id de organismo es requerido cuando no se especifica id de transacción.';

                return Response::json(compact('error'), 422);
            }

            if ($idTransaccion = $request->id_transaccion) {

                $operacion = Operacion::firstWhere('id_transaccion', $idTransaccion);
                if (!$operacion) {
                    $error = sprintf('Operación inválida (%s).', $idTransaccion);

                    return Response::json(compact('error'), 422);
                }
                $idOrganismo = $operacion->id_organismo;
            }
            $epagosApi = new EpagosApi();
            $respuesta = $epagosApi->obtenerPago($idOrganismo, $request->id_transaccion, $request->criterios ?? []);
            return Response::json($respuesta);
        });

        Route::get('/boletas', function (Request $request) {
            if (!$request->ids_transaccion) {
                $error = 'Los ids de transacción son requeridos.';

                return Response::json(compact('error'), 422);
            }

            $boletas = Boleta::whereIn('id_transaccion', $request->ids_transaccion)
                ->with('operaciones')
                ->get();

            return Response::json(compact('boletas'));
        });

        Route::get('/operaciones', function (Request $request) {
            if (!$request->ids_transaccion) {
                $error = 'Los ids de transacción son requeridos.';

                return Response::json(compact('error'), 422);
            }

            $operaciones = Operacion::whereIn('id_transaccion', $request->ids_transaccion)
                ->with('boleta')
                ->get();

            return Response::json(compact('operaciones'));
        });

        Route::post('/verificacion-manual', function (Request $request) {
            if (!$request->ids_transaccion) {
                $error = 'Los ids de transacción son requeridos.';

                return Response::json(compact('error'), 422);
            }

            foreach ($request->ids_transaccion as $idTransaccion) {
                VerificarPago::dispatchSync($idTransaccion);
            }
            $boletas = Boleta::whereIn('id_transaccion', $request->ids_transaccion)
                ->get();

            return Response::json(compact('boletas'));
        });
    });
