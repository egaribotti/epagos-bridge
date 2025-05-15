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

    public function validarVencimiento(array $operaciones): object
    {
        $operaciones = Operacion::whereIn('id_transaccion', $operaciones)->get();

        $vencidas = [];
        foreach ($operaciones as $operacion) {
            if ($operacion->fecha_vencimiento < Carbon::now()) {
                $vencidas[] = $operacion->id_transaccion;
            }
        }
        return new Fluent([
            'vencidas' => $vencidas,
        ]);
    }

    public function crearPago(object $payload): object
    {
        if ($payload->operaciones_lote && count($payload->operaciones_lote) > 100) {
            throw new EpagosException('El máximo de operaciones por lote es 100.');
        }
        $this->calcularMontoFinal($payload);

        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->solicitudPago($payload);
        $montoFinal = $respuesta->fp[0]->importe_fp;

        if ($boletaId = $payload->boleta_id) {
            $boleta = Boleta::findOrFail($boletaId);

        } else {
            $boleta = Boleta::create([
                'boleta_estado_id' => 1, // Pendiente
                'id_transaccion' => $respuesta->id_transaccion,
                'id_organismo' => $respuesta->id_organismo,
                'monto_final' => $montoFinal,
            ]);
        }

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

        return new Fluent([
            'boleta_id' => $boleta->id,
            'id_transaccion' => $respuesta->id_transaccion,
            'referencia_adicional' => $refAdicional,
            'url' => $respuesta->fp[0]->url_qr,
        ]);
    }

    public function crearOperacionesLote(object $payload): object
    {
        if (!$payload->lote || count($payload->lote) > 50) {
            throw new EpagosException('El lote debe tener al menos 1 y como máximo 50.');
        }

        $lote = [];
        $montoLote = 0;
        foreach ($payload->lote as $loteItem) {
            $loteItem = new Fluent($loteItem);

            $this->calcularMontoFinal($loteItem);
            $loteItem->fecha_vencimiento = $loteItem->fecha_vencimiento
                ?? Carbon::now()->addDays(7)->toDateString();

            $lote[] = $loteItem;
            $montoLote += $loteItem->monto_final;
        }
        $payload->lote = $lote;

        $epagosApi = new EpagosApi();
        $respuesta = $epagosApi->solicitudPagoLote($payload);

        $operacionesLote = [];
        foreach ($respuesta->lote as $loteItem) {
            $idTransaccion = $loteItem->id_transaccion;
            $codigoDividido = explode(chr(124), $loteItem->numero_operacion);
            $refAdicional = count($codigoDividido) > 1 ? $codigoDividido[0] : null;

            Operacion::create([
                'referencia_adicional' => $refAdicional,
                'codigo_externo' => $loteItem->numero_operacion,
                'id_transaccion' => $idTransaccion,
                'id_organismo' => $respuesta->id_organismo,
                'monto' => $loteItem->respuesta_forma_pago_array[0]->importe_fp,
                'fecha_vencimiento' => $loteItem->respuesta_forma_pago_array[0]->fechavenc_fp,
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

            $descItem = sprintf('%s (Cantidad: %d)', $item->desc_item, $item->cantidad_item);

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
