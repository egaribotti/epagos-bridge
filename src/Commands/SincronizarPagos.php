<?php

namespace EpagosBridge\Commands;

use EpagosBridge\Jobs\VerificarPago;
use EpagosBridge\Models\Boleta;
use Illuminate\Console\Command;

class SincronizarPagos extends Command
{
    protected $signature = 'epagos:sincronizar-pagos';

    protected $description = 'Sincronizar pagos.';

    public function handle(): void
    {
        $boletas = Boleta::whereNull('fecha_verificacion')
            ->where('boleta_estado_id', 1)
            ->get();
        if ($boletas->isEmpty()) {

            // Cuando ya no hay nada más que verificar, vuelve a empezar

            Boleta::where('boleta_estado_id', 1)->update([
                'fecha_verificacion' => null
            ]);
        }
        $barraProgreso = $this->output->createProgressBar(count($boletas));
        $barraProgreso->setOverwrite(true);

        $barraProgreso->start();
        foreach ($boletas as $boleta) {
            VerificarPago::dispatchSync($boleta->id_transaccion);
            $barraProgreso->advance();
        }

        $barraProgreso->finish();
    }
}
