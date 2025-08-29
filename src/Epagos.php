<?php

namespace EpagosBridge;

use EpagosBridge\Services\EpagosService;
use Illuminate\Container\Container;
use Illuminate\Support\Fluent;

class Epagos
{
    public static function crearPago(array $payload, string $concepto = null): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->crearPago(new Fluent($payload), $concepto);
    }

    public static function obtenerFormasPago(): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->obtenerFormasPago();
    }

    public static function obtenerComprobantePdf(int $idTransaccion): ?string
    {
        return Container::getInstance()->make(EpagosService::class)
            ->obtenerComprobantePdf($idTransaccion);
    }

    public static function crearOperacionesLote(array $payload): object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->crearOperacionesLote(new Fluent($payload));
    }

    public static function obtenerPago(int $idTransaccion): ?object
    {
        return Container::getInstance()->make(EpagosService::class)
            ->obtenerPago($idTransaccion);
    }

    public static function obtenerMediosPago(array $credenciales): array
    {
        return Container::getInstance()->make(EpagosService::class)
            ->obtenerMediosPago($credenciales);
    }
}
