<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;

class CustomGuestMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        Log::info('🔍 CustomGuestMiddleware - Verificando que NO esté autenticado', [
            'url' => $request->url(),
            'method' => $request->method(),
            'has_session' => session()->has('usuario'),
            'is_authenticated' => $this->authService->check()
        ]);

        // ✅ Si YA está autenticado, redirigir al dashboard
        if ($this->authService->check()) {
            Log::info('✅ Usuario ya autenticado, redirigiendo al dashboard', [
                'user_id' => $this->authService->id(),
                'redirect_to' => route('dashboard')
            ]);

            return redirect()->route('dashboard');
        }

        Log::info('✅ Usuario no autenticado, permitir acceso a página de login');
        
        return $next($request);
    }
}
