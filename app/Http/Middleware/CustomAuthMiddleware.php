<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;

class CustomAuthMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        Log::info('🔍 CustomAuthMiddleware - Verificando autenticación', [
            'url' => $request->url(),
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'has_session' => session()->has('usuario'),
            'session_id' => session()->getId()
        ]);

        // ✅ VERIFICAR AUTENTICACIÓN
        if (!$this->authService->check()) {
            Log::warning('❌ Usuario no autenticado en middleware', [
                'url' => $request->url(),
                'redirect_to' => route('login')
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sesión expirada',
                    'redirect' => route('login')
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Debe iniciar sesión para acceder');
        }

        Log::info('✅ Usuario autenticado en middleware', [
            'user_id' => $this->authService->id(),
            'is_offline' => $this->authService->isOffline()
        ]);

        return $next($request);
    }
}
