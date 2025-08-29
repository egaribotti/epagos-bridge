<?php

namespace EpagosBridge\Jobs;

use Carbon\Carbon;
use EpagosBridge\Enums\EstadoPago;
use EpagosBridge\Events\PagoAcreditado;
use EpagosBridge\Events\PagoDevuelto;
use EpagosBridge\Events\PagoRechazado;
use EpagosBridge\Lib\EpagosApi;
use EpagosBridge\Models\Boleta;
use EpagosBridge\Models\Operacion;
use EpagosBridge\Models\PagoAdicional;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerificarPago implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $idTransaccion)
    {
    }

    public function handle(): void
    {
        $idTransaccion = $this->idTransaccion;

        $operacion = Operacion::firstWhere('id_transaccion', $idTransaccion);
        if (!$operacion) return;

        $boleta = Boleta::firstWhere('id_transaccion', $idTransaccion);
        if (!$boleta) {

            // Solo las boletas pueden verificarse, si es un item de un lote no

            return;
        }
        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->obtenerPago($operacion->id_organismo, $idTransaccion);
        if ($respuesta->cantidadTotal !== 1) {
            return;
        }
        $pago = $respuesta->pago[0];
        if (in_array($pago->Estado, [EstadoPago::ACREDITADO, EstadoPago::LOTE_ACREDITADO])
            && $boleta->monto_final === $pago->Importe) {

            // Para acreditar el monto final de la boleta debe coincidir con el monto pagado

            Boleta::find($boleta->id)->update([
                'id_fp' => $pago->FormaPago[0]->Identificador,
                'url_recibo' => $pago->Recibo,
                'estado' => EstadoPago::ACREDITADO,
                'fecha_pago' => Carbon::parse($pago->FechaPago),
                'fecha_acreditacion' => Carbon::parse($pago->FechaAcreditacion),
                'fecha_novedad' => Carbon::parse($pago->FechaNovedadAcreditacion),
                'fecha_verificacion' => Carbon::now()
            ]);

            if (count($pago->PagosAdicionales) > 0) {

                // Evitar duplicar los pagos adicionales

                $adicionales = PagoAdicional::where('boleta_id', $boleta->id)
                    ->exists();

                if (! $adicionales) {
                    foreach ($pago->PagosAdicionales as $pagoAdicional) {

                        PagoAdicional::create([
                            'boleta_id' => $boleta->id,
                            'id_transaccion' => $pagoAdicional->CodigoUnicoTransaccion,
                            'id_pago' => $pagoAdicional->IdPago,
                            'id_organismo' => $respuesta->id_organismo,
                            'forma_pago' => $pagoAdicional->FormaPago,
                            'monto' => $pagoAdicional->Monto,
                            'fecha_pago' => $pagoAdicional->FechaPago,
                            'fecha_novedad' => $pagoAdicional->FechaNovedad,
                        ]);
                    }
                }
            }
            PagoAcreditado::dispatch($boleta);

        } else {
            $estado = $pago->Estado == EstadoPago::ADEUDADO
                ? EstadoPago::PENDIENTE : $pago->Estado;

            Boleta::find($boleta->id)->update([
                'estado' => $estado,
                'id_fp' => $estado == EstadoPago::PENDIENTE ? $pago->FormaPago[0]->Identificador : $boleta->id_fp,
                'fecha_verificacion' => Carbon::now()
            ]);

            if ($estado == EstadoPago::PENDIENTE) return; // No hay evento

            $estado === EstadoPago::DEVUELTO
                ? PagoDevuelto::dispatch($boleta) : PagoRechazado::dispatch($boleta);
        }
    }
}
