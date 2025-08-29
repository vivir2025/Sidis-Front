<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;

class RoleMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$this->authService->check()) {
            return redirect()->route('login');
        }

        // ✅ CORREGIDO: Usar usuario() en lugar de user()
        $user = $this->authService->usuario();
        $userRole = strtolower($user['rol']['nombre'] ?? '');
        
        // Convertir roles permitidos a minúsculas
        $allowedRoles = array_map('strtolower', $roles);
        
        if (!in_array($userRole, $allowedRoles)) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
