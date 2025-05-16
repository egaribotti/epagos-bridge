<?php

namespace EpagosBridge\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoDevuelto
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $idTransaccion)
    {
    }
}
