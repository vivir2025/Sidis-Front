<?php
// app/Http/Controllers/OfflineController.php (COMPLETO)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{ApiService, OfflineService};
use Illuminate\Support\Facades\Log;

class OfflineController extends Controller
{
    protected $apiService;
    protected $offlineService;

    public function __construct(ApiService $apiService, OfflineService $offlineService)
    {
        $this->middleware('custom.auth');
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    /**
     * ✅ Sincronizar datos maestros manualmente
     */
    public function syncMasterData(Request $request)
    {
        try {
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay conexión a internet'
                ], 400);
            }

            $response = $this->apiService->get('/master-data/all');
            
            if ($response['success'] && isset($response['data'])) {
                $success = $this->offlineService->syncMasterDataFromApi($response['data']);
                
                if ($success) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Datos maestros sincronizados exitosamente'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error sincronizando datos maestros'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo datos desde la API'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error sincronizando datos maestros', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Obtener estado de sincronización
     */
    public function getSyncStatus(Request $request)
    {
        try {
            $status = [
                'has_offline_data' => $this->offlineService->hasMasterDataOffline(),
                'api_online' => $this->apiService->isOnline(),
                'last_sync' => null,
                'offline_stats' => $this->offlineService->getOfflineStats()
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado de sincronización', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Limpiar datos offline
     */
    public function clearOfflineData(Request $request)
    {
        try {
            $success = $this->offlineService->clearAllOfflineData();
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Datos offline eliminados exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error eliminando datos offline'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error limpiando datos offline', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Forzar sincronización completa
     */
    public function forceSyncAll(Request $request)
    {
        try {
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay conexión a internet'
                ], 400);
            }

            // Limpiar datos existentes
            $this->offlineService->clearAllOfflineData();

            // Sincronizar datos maestros
            $response = $this->apiService->get('/master-data/all');
            
            if ($response['success'] && isset($response['data'])) {
                $success = $this->offlineService->syncMasterDataFromApi($response['data']);
                
                if ($success) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Sincronización completa realizada exitosamente'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error en sincronización completa'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo datos desde la API'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en sincronización completa', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Obtener estadísticas detalladas
     */
    public function getDetailedStats(Request $request)
    {
        try {
            $stats = $this->offlineService->getOfflineStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas detalladas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }
}
