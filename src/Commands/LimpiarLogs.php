<?php

namespace EpagosBridge\Commands;

use Carbon\Carbon;
use EpagosBridge\Models\EnvioLog;
use Illuminate\Console\Command;

class LimpiarLogs extends Command
{
    protected $signature = 'epagos:limpiar-logs';

    protected $description = 'Limpiar logs.';

    public function handle(): void
    {
        EnvioLog::where('created_at', '<', Carbon::now()->subMonths(3))->delete();
    }
}
