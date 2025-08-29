<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo verificar si hay usuario en sesión
        if (session()->has('usuario')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
