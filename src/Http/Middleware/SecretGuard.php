<?php

namespace EpagosBridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Env;

class SecretGuard
{
    public function handle(Request $request, Closure $next)
    {
        $key = strtoupper('epagos_webhook_secret');

        // Para evitar solicitudes de otro origen que no es Epagos

        if (Env::get($key) !== $request->secret) {
            return Response::json('El secret es requerido.', 401);
        }

        return $next($request);
    }
}
