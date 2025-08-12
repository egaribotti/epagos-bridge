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

            // Si es un item de un lote busco la boleta acreditadora del lote

            $boleta = $operacion->boleta;
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
                'boleta_estado_id' => 2,
                'id_fp' => $pago->FormaPago[0]->Identificador,
                'url_recibo' => $pago->Recibo,
                'fecha_pago' => Carbon::parse($pago->FechaPago),
                'fecha_acreditacion' => Carbon::parse($pago->FechaAcreditacion),
                'fecha_novedad' => Carbon::parse($pago->FechaNovedadAcreditacion),
                'fecha_verificacion' => Carbon::now()
            ]);

            PagoAcreditado::dispatch($idTransaccion);
        } else {
            $estados = [EstadoPago::ADEUDADO => 1, EstadoPago::PENDIENTE => 1,
                EstadoPago::VENCIDO => 4, EstadoPago::DEVUELTO => 5];

            $boletaEstadoId = $estados[$pago->Estado] ?? 3;
            Boleta::find($boleta->id)->update([
                'boleta_estado_id' => $boletaEstadoId,
                'id_fp' => $pago->Estado === EstadoPago::PENDIENTE ? $pago->FormaPago[0]->Identificador : $boleta->id_fp,
                'fecha_verificacion' => Carbon::now()
            ]);

            if ($boletaEstadoId === 1) return;
            $boletaEstadoId === 5 ?
                PagoDevuelto::dispatch($idTransaccion) : PagoRechazado::dispatch($idTransaccion);
        }
    }
}
