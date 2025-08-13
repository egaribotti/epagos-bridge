<?php

namespace EpagosBridge\Http\Middleware;

use Closure;
use EpagosBridge\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SecretGuard
{
    public function handle(Request $request, Closure $next)
    {
        $secretKey = Config::getValue('secret_key');

        // Para evitar solicitudes de otro origen que no es Epagos

        if ($secretKey !== $request->secret) {
            return Response::json('El secret es requerido.', 401);
        }

        return $next($request);
    }
}
