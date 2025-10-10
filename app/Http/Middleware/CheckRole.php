<?php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;

class CheckRole
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$this->authService->check()) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Debe iniciar sesión']);
        }

        $usuario = $this->authService->usuario();
        $rolUsuario = strtolower($usuario['rol']['nombre'] ?? '');

        // Convertir roles permitidos a minúsculas
        $rolesPermitidos = array_map('strtolower', $roles);

        if (!in_array($rolUsuario, $rolesPermitidos)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tiene permisos para acceder a este recurso'
                ], 403);
            }

            return redirect()->route('dashboard')
                ->withErrors(['error' => 'No tiene permisos para acceder a esta sección']);
        }

        return $next($request);
    }
}
