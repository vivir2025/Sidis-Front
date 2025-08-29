<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\ApiService;

class AuthMiddleware
{
    protected $authService;
    protected $apiService;

    public function __construct(AuthService $authService, ApiService $apiService)
    {
        $this->authService = $authService;
        $this->apiService = $apiService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Verificar si está autenticado
        if (!$this->authService->check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder.');
        }

        // ✅ CORREGIDO: Usar ApiService para verificar conexión
        // Solo verificar token si NO está en modo offline
        if (!$this->authService->isOffline()) {
            $expiresAt = session('token_expires_at');
            
            // Si hay token y ha expirado
            if ($expiresAt && now()->gt($expiresAt)) {
                // Solo intentar refrescar si hay conexión
                if ($this->apiService->isOnline()) {
                    $refresh = $this->authService->refreshToken();
                    if (!$refresh['success']) {
                        $this->authService->logout();
                        return redirect()->route('login')->with('error', 'Su sesión ha expirado.');
                    }
                } else {
                    // Sin conexión, permitir continuar en modo offline
                    // pero marcar que está offline
                    session(['is_offline' => true]);
                }
            }
        }

        return $next($request);
    }
}
