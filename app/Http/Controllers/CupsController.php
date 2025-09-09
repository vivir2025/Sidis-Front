<?php
// app/Http/Controllers/CupsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{CupsService, AuthService};
use Illuminate\Support\Facades\Log;

class CupsController extends Controller
{
    protected $cupsService;
    protected $authService;

    public function __construct(CupsService $cupsService, AuthService $authService)
    {
        $this->middleware('custom.auth');
        $this->cupsService = $cupsService;
        $this->authService = $authService;
    }

    /**
     * ✅ BUSCAR CUPS VIA AJAX
     */
    public function buscar(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100'
            ]);

            $termino = $request->get('q');
            $result = $this->cupsService->buscarCups($termino);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en CupsController@buscar', [
                'error' => $e->getMessage(),
                'termino' => $request->get('q')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'data' => []
            ], 500);
        }
    }

    /**
     * ✅ OBTENER CUPS POR CÓDIGO EXACTO
     */
    public function obtenerPorCodigo(Request $request)
    {
        try {
            $request->validate([
                'codigo' => 'required|string|max:20'
            ]);

            $codigo = $request->get('codigo');
            $result = $this->cupsService->obtenerPorCodigo($codigo);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en CupsController@obtenerPorCodigo', [
                'error' => $e->getMessage(),
                'codigo' => $request->get('codigo')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ SINCRONIZAR CUPS DESDE API
     */
    public function sincronizar()
    {
        try {
            $result = $this->cupsService->sincronizarCups();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en CupsController@sincronizar', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ OBTENER CUPS ACTIVOS
     */
    public function activos()
    {
        try {
            $result = $this->cupsService->obtenerCupsActivos();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en CupsController@activos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'data' => []
            ], 500);
        }
    }
}
