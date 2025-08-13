<?php

namespace EpagosBridge\Commands;

use Carbon\Carbon;
use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Models\Boleta;
use EpagosBridge\Models\Config;
use Illuminate\Console\Command;

class SincronizarPagos extends Command
{
    protected $signature = 'epagos:sincronizar-pagos';

    protected $description = 'Sincronizar pagos con Epagos.';

    public function handle(): void
    {
        // Reintento verificar las boletas de hace 5 minutos

        Boleta::where('boleta_estado_id', 1)
            ->where('fecha_verificacion', '<=', Carbon::now()->subMinutes(5))
            ->update([
                'fecha_verificacion' => null
            ]);

        $boletas = Boleta::whereNull('fecha_verificacion')
            ->where('boleta_estado_id', 1)
            ->where('created_at', '<=', Carbon::now()->subMinutes(3))
            ->latest()
            ->get();
        if ($boletas->isEmpty()) return;

        // Evito volver a verificar por la fecha de verificacion

        Boleta::whereIn('id_transaccion', $boletas->pluck('id_transaccion'))
            ->update([
                'fecha_verificacion' => Carbon::now()
            ]);

        // Es más eficiente si se usan las queues

        $queue = Config::getValue('on_queue');

        foreach ($boletas as $boleta) {
            $idTransaccion = $boleta->id_transaccion;

            $queue ? VerificarPago::dispatch($idTransaccion)->onQueue($queue)
                : VerificarPago::dispatchSync($idTransaccion);
        }
        $this->info(Carbon::now());
    }
}
