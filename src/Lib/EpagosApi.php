<?php

namespace EpagosBridge\Lib;

use EpagosBridge\Exceptions\EpagosException;
use EpagosBridge\Models\Config;
use EpagosBridge\Models\Credencial;
use EpagosBridge\Models\EnvioLog;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class EpagosApi
{
    public object $cliente;
    public string $version = '2.0';

    public function obtenerToken(array $credenciales): array
    {
        $fueraServicio = Config::getValue('fuera_servicio');
        if ($fueraServicio) {
            throw new EpagosException('La integración con Epagos está temporalmente fuera de servicio. Por favor, inténtelo de nuevo más tarde.');
        }

        $credenciales = count($credenciales) < 3
            ? Credencial::firstWhere($credenciales)->toArray()
            : $credenciales;

        $opciones = [
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
        ];
        $wsdl = Config::getValue('wsdl');

        try {
            $this->cliente = new \SoapClient($wsdl, $opciones);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }

        try {
            $respuesta = $this->cliente->obtener_token($this->version, $credenciales);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent(array_merge($respuesta, $credenciales));
        if (!$token = $respuesta->token) {
            throw new EpagosException($respuesta->respuesta);
        }
        $idOrganismo = $respuesta->id_organismo;

        // Guardo las credenciales como caché para verificar los pagos

        Credencial::updateOrCreate(['id_organismo' => $idOrganismo], $credenciales);

        return [
            'id_organismo' => $idOrganismo,
            'token' => $token
        ];
    }

    public function obtenerEntidadesPago(array $credenciales): object
    {
        $credenciales = $this->obtenerToken($credenciales);
        try {
            $respuesta = $this->cliente->obtener_entidades_pago($this->version, $credenciales);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);
        if ($respuesta->id_resp !== 3001) {
            throw new EpagosException($respuesta->respuesta);
        }
        return $respuesta;
    }

    public function obtenerPago(int $organismoId, ?int $idTransaccion, array $criterios = []): object
    {
        $credenciales = Credencial::where('id_organismo', $organismoId)->firstOrFail();

        $criterios = array_merge($criterios, $idTransaccion ? ['CodigoUnicoTransaccion' => $idTransaccion] : []);

        $credenciales = $this->obtenerToken($credenciales->toArray());
        try {
            $respuesta = $this->cliente->obtener_pagos($this->version, $credenciales, $criterios);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);
        list($codigoExterno, $codigoBarras, $url) = array(null, null, null);

        if ($respuesta->cantidadTotal === 1) {
            $pago = $respuesta->pago[0];

            $codigoExterno = $pago->Externa;
            $codigoBarras = $pago->FormaPago[0]->CodigoBarras ?? null;
            $url = $pago->Url_QR;
        }

        EnvioLog::create(array_merge($respuesta->toArray(), [
            'url' => $url,
            'id_transaccion' => $idTransaccion,
            'codigo_externo' => $codigoExterno,
            'codigo_barras' => $codigoBarras,
            'request_content' => $this->cliente->__getLastRequest(),
            'response_content' => $this->cliente->__getLastResponse(),
        ]));
        return $respuesta;
    }

    public function solicitudPago(object $payload): object|array
    {
        if ($operacionesLote = $payload->operaciones_lote) {
            $operacionesLote = array_map(fn (int $idOperacion) => [
                'id_operacion' => $idOperacion], $operacionesLote);
        }
        $opcionPdf = $payload->pdf ?? false;

        $codigoExterno = ($payload->referencia_adicional ? $payload->referencia_adicional. chr(124) : null). Str::uuid()->toString();
        $operacion = [
            'numero_operacion' => $codigoExterno,
            'identificador_externo_2' => $payload->identificador_externo_2,
            'identificador_externo_3' => $payload->identificador_externo_3,
            'identificador_externo_4' => $payload->identificador_externo_4,
            'identificador_cliente' => null,
            'id_moneda_operacion' => 1,
            'monto_operacion' => $payload->monto_final,
            'opc_pdf' => $opcionPdf,
            'opc_fecha_vencimiento' => $payload->fecha_vencimiento,
            'opc_devolver_qr' => false,
            'opc_devolver_codbarras' => false,
            'opc_generar_pdf' => $opcionPdf,
            'opc_fp_excluidas' => $payload->fp_excluidas ? implode(chr(44), $payload->fp_excluidas) : null,
            'opc_tp_excluidos' => $payload->tp_excluidos ? implode(chr(44), $payload->tp_excluidos) : null,
            'opc_fp_permitidas' => $payload->fp_permitidas ? implode(chr(44), $payload->fp_permitidas) : null,
            'opc_operaciones_lote' => $operacionesLote,
            'detalle_operacion' => $payload->items,
            'pagador' => [
                'nombre_pagador' => $payload->nombre_pagador,
                'apellido_pagador' => $payload->apellido_pagador,
                'fechanac_pagador' => null,
                'email_pagador' => $payload->email_pagador,
                'identificacion_pagador' => $payload->cuit_pagador ? [
                    'tipo_doc_pagador' => 9,
                    'numero_doc_pagador' => $payload->cuit_pagador,
                    'cuit_doc_pagador' => $payload->cuit_pagador,
                ] : null,
                'domicilio_pagador' => null,
                'telefono_pagador' => null,
                'cbu_pagador' => null,
            ],
            'tipo_operacion' => null,
            'codigo_publicacion' => null,
            'url_boleta' => null,
        ];
        if (!$payload->credenciales) {
            return $operacion;
        }

        $fp = [[
            'id_fp' => $payload->id_fp ?? 34,
            'monto_fp' => $payload->monto_final,
        ]];

        $credenciales = $this->obtenerToken($payload->credenciales);
        try {
            $respuesta = $this->cliente->solicitud_pago($this->version, 'op_pago', $credenciales, $operacion, $fp, $payload->convenio);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);

        $pdf = Config::getValue('pdf');

        $pattern = json_decode($pdf)->pattern;
        $lastResponse = $this->cliente->__getLastResponse();
        $pdf = null;

        if ($opcionPdf && preg_match($pattern, $lastResponse, $matches)) {
            $pdf = $matches[1];
            $lastResponse = preg_replace($pattern, null, $lastResponse);
        }

        EnvioLog::create(array_merge($respuesta->toArray(), $credenciales, !is_array($respuesta->fp) ? [] : [
            'url' => $respuesta->fp[0]->url_qr,
            'codigo_barras' => $respuesta->fp[0]->codigo_barras_fp
        ], [
            'codigo_externo' => $codigoExterno,
            'pdf' => $pdf,
            'request_content' => $this->cliente->__getLastRequest(),
            'response_content' => $lastResponse,
        ]));

        if (intval($respuesta->id_resp) !== 2002) {
            throw new EpagosException($respuesta->respuesta);
        }
        return $respuesta;
    }

    public function solicitudPagoLote(object $payload): object
    {
        $credenciales = $this->obtenerToken($payload->credenciales);
        $lote = [];
        foreach ($payload->lote as $itemLote) {
            $operacion = $this->solicitudPago($itemLote);
            $lote[] = [
                'fp' => [[
                    'id_fp' => $itemLote->id_fp ?? 4,
                    'monto_fp' => $itemLote->monto_final,
                ]],
                'operacion' => $operacion,
                'convenio' => $itemLote->convenio,
            ];
        }

        try {
            $respuesta = $this->cliente->solicitud_pago_lote($this->version, 'op_pago', $credenciales, $lote);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);

        EnvioLog::create(array_merge($respuesta->toArray(), [
            'request_content' => $this->cliente->__getLastRequest(),
            'response_content' => $this->cliente->__getLastResponse(),
        ]));

        if ($respuesta->id_resp !== 8001) {
            throw new EpagosException($respuesta->respuesta);
        }
        return $respuesta;
    }
}
