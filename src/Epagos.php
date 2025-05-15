<?php

namespace EpagosBridge;

use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Services\EpagosService;
use Illuminate\Container\Container;
use Illuminate\Support\Fluent;

class Epagos
{
    public static function crearPago(array $payload): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->crearPago(new Fluent($payload));
    }

    public static function validarVencimiento(array $operaciones): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->validarVencimiento($operaciones);
    }

    public static function crearOperacionesLote(array $payload): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->crearOperacionesLote(new Fluent($payload));
    }

    public static function obtenerMediosPago(array $credenciales): array
    {
        return Container::getInstance()->make(EpagosService::class)
            ->obtenerMediosPago($credenciales);
    }

    public static function verificarPagoManualmente(int $idTransaccion): void
    {
        VerificarPago::dispatchSync($idTransaccion);
    }
}
