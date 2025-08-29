<?php

namespace EpagosBridge\Commands;

use Carbon\Carbon;
use EpagosBridge\Enums\EstadoPago;
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
        $minutosEspera = Config::getValor('minutos_espera') ?? 5;

        // Reintento verificar las boletas cada 5 minutos o lo que esté configurado

        Boleta::where('estado', EstadoPago::PENDIENTE)
            ->where('fecha_verificacion', '<=', Carbon::now()->subMinutes($minutosEspera))
            ->update([
                'fecha_verificacion' => null
            ]);

        $limite = Config::getValor('limite') ?? 100;

        $boletas = Boleta::whereNull('fecha_verificacion')
            ->where('estado', EstadoPago::PENDIENTE)
            ->where('created_at', '<=', Carbon::now()->subMinutes($minutosEspera))
            ->latest()
            ->limit($limite)
            ->get();
        if ($boletas->isEmpty()) return;

        // Evito volver a verificar por la fecha de verificacion

        Boleta::whereIn('id_transaccion', $boletas->pluck('id_transaccion'))
            ->update([
                'fecha_verificacion' => Carbon::now()
            ]);

        // Es más eficiente si se usan las queues

        $queue = Config::getValor('on_queue');

        $pb = $this->output->createProgressBar($boletas->count());
        $pb->setOverwrite(true);

        $pb->start();
        foreach ($boletas as $boleta) {
            $idTransaccion = $boleta->id_transaccion;

            $queue ? VerificarPago::dispatch($idTransaccion)->onQueue($queue)
                : VerificarPago::dispatchSync($idTransaccion);

            $pb->advance();
        }

        $pb->finish();
        $this->info(Carbon::now());
    }
}
