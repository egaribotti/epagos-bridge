<?php

namespace EpagosBridge\Services;

use Carbon\Carbon;
use EpagosBridge\Exceptions\EpagosException;
use EpagosBridge\Lib\EpagosApi;
use EpagosBridge\Models\Boleta;
use EpagosBridge\Models\Operacion;
use Illuminate\Support\Fluent;

class EpagosService
{
    public function obtenerMediosPago(array $credenciales): array
    {
        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->obtenerEntidadesPago($credenciales);
        return $respuesta->fp;
    }

    public function operacionPendiente(int $idTransaccion): bool
    {
        $operacion = Operacion::where('id_transaccion', $idTransaccion)->first();

        if (!$operacion || $operacion->boleta_id && $operacion->boleta->boleta_estado_id !== 1) {

            // La boleta que contiene la operación no está pendiente

            return false;
        }
        return $operacion->fecha_vencimiento >= Carbon::now();
    }

    public function obtenerPago(int $idTransaccion): ?object
    {
        $operacion = Operacion::firstWhere('id_transaccion', $idTransaccion);
        if (!$operacion) {
            return null;
        }

        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->obtenerPago($operacion->id_organismo, $idTransaccion, $operacion->codigo_externo);
        return $respuesta->cantidadTotal === 1 ? $respuesta->pago[0] : null;
    }

    public function crearPago(object $payload): object
    {
        if (!$payload->credenciales) {
            throw new EpagosException('Las credenciales son requeridas.');
        }

        if ($payload->operaciones_lote && count($payload->operaciones_lote) > 100) {
            throw new EpagosException('El máximo de operaciones por lote es 100.');
        }
        $this->calcularMontoFinal($payload);

        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->solicitudPago($payload);
        $montoFinal = $respuesta->fp[0]->importe_fp;

        $boleta = Boleta::create([
            'boleta_estado_id' => 1, // Pendiente
            'id_transaccion' => $respuesta->id_transaccion,
            'id_organismo' => $respuesta->id_organismo,
            'monto_final' => $montoFinal,
        ]);

        if ($operacionesLote = $payload->operaciones_lote) {
            Operacion::whereIn('id_transaccion', $operacionesLote)
                ->update([
                    'boleta_id' => $boleta->id
                ]);
        }
        $codigoDividido = explode(chr(124), $respuesta->numero_operacion);
        $refAdicional = count($codigoDividido) > 1 ? $codigoDividido[0] : null;

        Operacion::create([
            'boleta_id' => $boleta->id,
            'referencia_adicional' => $refAdicional,
            'codigo_externo' => $respuesta->numero_operacion,
            'id_transaccion' => $respuesta->id_transaccion,
            'id_organismo' => $respuesta->id_organismo,
            'monto' => $montoFinal,
            'fecha_vencimiento' => $respuesta->fp[0]->fechavenc_fp,
        ]);
        $pdf = $respuesta->fp[0]->pdf; // Esta en binario

        return new Fluent([
            'boleta_id' => $boleta->id,
            'referencia_adicional' => $refAdicional,
            'id_transaccion' => $respuesta->id_transaccion,
            'monto_final' => $montoFinal,
            'url' => $respuesta->fp[0]->url_qr,
            'pdf' => strlen($pdf) === 19 ? null : base64_encode($pdf),
        ]);
    }

    public function crearOperacionesLote(object $payload): object
    {
        if (!$payload->lote || count($payload->lote) > 50) {
            throw new EpagosException('El lote debe tener al menos 1 y como máximo 50.');
        }

        $lote = [];
        $montoLote = 0;
        foreach ($payload->lote as $itemLote) {
            $itemLote = new Fluent($itemLote);

            $itemLote->pdf = false; // Para acelerar la creación desactivo los PDF

            $this->calcularMontoFinal($itemLote);
            $itemLote->fecha_vencimiento = $itemLote->fecha_vencimiento
                ?? Carbon::now()->addDays(7)->toDateString();

            $lote[] = $itemLote;
            $montoLote += $itemLote->monto_final;
        }
        $payload->lote = $lote;

        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->solicitudPagoLote($payload);

        $operacionesLote = [];
        foreach ($respuesta->lote as $itemLote) {
            $idTransaccion = $itemLote->id_transaccion;
            $codigoDividido = explode(chr(124), $itemLote->numero_operacion);
            $refAdicional = count($codigoDividido) > 1 ? $codigoDividido[0] : null;

            Operacion::create([
                'referencia_adicional' => $refAdicional,
                'codigo_externo' => $itemLote->numero_operacion,
                'id_transaccion' => $idTransaccion,
                'id_organismo' => $respuesta->id_organismo,
                'monto' => $itemLote->respuesta_forma_pago_array[0]->importe_fp,
                'fecha_vencimiento' => $itemLote->respuesta_forma_pago_array[0]->fechavenc_fp,
            ]);

            $refAdicional
                ? $operacionesLote[$refAdicional] = $idTransaccion
                : $operacionesLote[] = $idTransaccion;
        }

        return new Fluent([
            'operaciones_lote' => $operacionesLote,
            'monto_lote' => $montoLote,
        ]);
    }

    private function calcularMontoFinal(object $payload): void
    {
        $montoFinal = 0;
        $items = [];
        foreach ($payload->items as $item) {
            $item = new Fluent($item);

            $montoItem = $item->monto_item * $item->cantidad_item;
            if ($montoItem <= 0) {
                throw new EpagosException('El monto item no puede ser menor o igual a 0.');
            }

            $descItem = $item->cantidad_item > 1 ? sprintf('%s (Cant: %d)', $item->desc_item, $item->cantidad_item) : $item->desc_item;

            $montoFinal += $montoItem;
            $items[] = [
                'id_item' => $item->id_item,
                'desc_item' => $descItem,
                'monto_item' => $montoItem,
                'cantidad_item' => 1 // Para evitar conflictos, se deja 1 como valor fijo
            ];
        }
        if ($montoFinal <= 0) {
            throw new EpagosException('La suma de los monto item no puede ser menor o igual a 0.');
        }
        $payload->items = $items;
        $payload->monto_final = $montoFinal;
    }
}
