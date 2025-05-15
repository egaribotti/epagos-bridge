<?php

namespace EpagosBridge\Lib;

use EpagosBridge\Exceptions\EpagosException;
use EpagosBridge\Models\Credencial;
use EpagosBridge\Models\EnvioLog;
use Illuminate\Support\Env;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class EpagosApi
{
    public object $cliente;
    private string $_version = '2.0';

    public function obtenerToken(array $credenciales): array
    {
        $credenciales = count($credenciales) < 3
            ? Credencial::firstWhere($credenciales)->toArray()
            : $credenciales;

        $opciones = [
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
        ];
        $wsdl = strtoupper('epagos_wsdl');

        try {
            $this->cliente = new \SoapClient(Env::get($wsdl), $opciones);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }

        try {
            $respuesta = $this->cliente->obtener_token($this->_version, $credenciales);
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
            $respuesta = $this->cliente->obtener_entidades_pago($this->_version, $credenciales);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);
        if ($respuesta->id_resp !== 3001) {
            throw new EpagosException($respuesta->respuesta);
        }
        return $respuesta;
    }

    public function obtenerPagos(int $organismoId, int $idTransaccion, string $codigoExterno): object
    {
        $credenciales = Credencial::where('id_organismo', $organismoId)->firstOrFail();
        $criterios = [
            'CodigoUnicoTransaccion' => $idTransaccion,
            'ExternoId' => $codigoExterno,
        ];

        $credenciales = $this->obtenerToken($credenciales->toArray());
        try {
            $respuesta = $this->cliente->obtener_pagos($this->_version, $credenciales, $criterios);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);

        EnvioLog::create(array_merge($respuesta->toArray(), [
            'id_transaccion' => $idTransaccion,
            'codigo_externo' => $codigoExterno,
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

        $codigoExterno = ($payload->referencia_adicional ? $payload->referencia_adicional. chr(124) : null). Str::uuid()->toString();
        $operacion = [
            'numero_operacion' => $codigoExterno,
            'identificador_externo_2' => $payload->identificador_externo_2,
            'identificador_externo_3' => $payload->identificador_externo_3,
            'identificador_externo_4' => $payload->identificador_externo_4,
            'identificador_cliente' => null,
            'id_moneda_operacion' => 1,
            'monto_operacion' => $payload->monto_final,
            'opc_pdf' => false,
            'opc_fecha_vencimiento' => $payload->fecha_vencimiento,
            'opc_devolver_qr' => false,
            'opc_devolver_codbarras' => false,
            'opc_generar_pdf' => false,
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
            'id_fp' => $payload->id_fp ?? 4, // Pago fácil
            'monto_fp' => $payload->monto_final,
        ]];

        $credenciales = $this->obtenerToken($payload->credenciales);
        try {
            $respuesta = $this->cliente->solicitud_pago($this->_version, 'op_pago', $credenciales, $operacion, $fp, $payload->convenio);
        } catch (\SoapFault $exception) {
            throw new EpagosException($exception->getMessage());
        }
        $respuesta = new Fluent($respuesta);

        EnvioLog::create(array_merge($respuesta->toArray(), $credenciales, [
            'codigo_externo' => $codigoExterno,
            'url' => $respuesta->fp ? $respuesta->fp[0]->url_qr : null,
            'request_content' => $this->cliente->__getLastRequest(),
            'response_content' => $this->cliente->__getLastResponse(),
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
        foreach ($payload->lote as $loteItem) {
            $operacion = $this->solicitudPago($loteItem);
            $lote[] = [
                'fp' => [[
                    'id_fp' => $loteItem->id_fp ?? 4, // Pago fácil
                    'monto_fp' => $loteItem->monto_final,
                ]],
                'operacion' => $operacion,
                'convenio' => $loteItem->convenio,
            ];
        }

        try {
            $respuesta = $this->cliente->solicitud_pago_lote($this->_version, 'op_pago', $credenciales, $lote);
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
