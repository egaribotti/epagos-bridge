<?php

namespace EpagosBridge;

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

    public static function operacionPendiente(int $idTransaccion): bool
    {
        return Container::getInstance()->make(EpagosService::class)
            ->operacionPendiente($idTransaccion);
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
