<?php

namespace EpagosBridge\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoAcreditado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public object $boleta
    )
    {
    }
}
