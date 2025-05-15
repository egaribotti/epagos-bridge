<?php

namespace EpagosBridge\Jobs;

use Carbon\Carbon;
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

    public function __construct(
        public int $idTransaccion
    )
    {
    }

    public function handle(): void
    {
        $operacion = Operacion::firstWhere('id_transaccion', $this->idTransaccion);
        $boleta = $operacion->boleta;

        $api = new EpagosApi();
        $respuesta = $api->obtenerPagos($operacion->id_organismo, $operacion->id_transaccion, $operacion->codigo_externo);
        if ($respuesta->cantidadTotal === 0) {
            return;
        }
        $pago = $respuesta->pago[0];
        if (in_array(ord($pago->Estado), [65, 76]) && $boleta->monto_final === $pago->Importe) {

            // Para acreditar el monto final de la boleta debe coincidir con el monto pagado

            Boleta::find($boleta->id)->update([
                'boleta_estado_id' => 4,
                'url_recibo' => $pago->Recibo,
                'fecha_pago' => Carbon::parse($pago->FechaPago),
                'fecha_verificacion' => Carbon::now()
            ]);

            PagoAcreditado::dispatch($boleta);
        } else {
            $estados = [79 => 1, 80 => 1, 86 => 5, 68 => 7];

            $boletaEstadoId = $estados[ord($pago->Estado)] ?? 6;
            Boleta::find($boleta->id)->update([
                'boleta_estado_id' => $boletaEstadoId,
                'fecha_verificacion' => Carbon::now()
            ]);

            if ($boletaEstadoId === 1) return;
            $boletaEstadoId === 7 ? PagoDevuelto::dispatch($boleta) : PagoRechazado::dispatch($boleta);
        }
    }
}
