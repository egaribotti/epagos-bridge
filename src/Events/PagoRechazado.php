<?php

namespace EpagosBridge\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoRechazado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $idTransaccion;
    public ?string $concepto;

    public function __construct(public object $boleta)
    {
        $this->idTransaccion = $boleta->id_transaccion;
        $this->concepto = $boleta->concepto;
    }
}
