<?php
// app/Http/Middleware/ProfesionalEnSaludMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;

class ProfesionalEnSaludMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$this->authService->check()) {
            return redirect()->route('login');
        }

        $usuario = $this->authService->usuario();
        $rolNombre = strtolower($usuario['rol']['nombre'] ?? '');

        // Verificar si es profesional en salud
        if (!in_array($rolNombre, ['profesional en salud', 'medico', 'doctor', 'profesional'])) {
            abort(403, 'Acceso denegado. Se requiere rol de Profesional en Salud.');
        }

        return $next($request);
    }
}
