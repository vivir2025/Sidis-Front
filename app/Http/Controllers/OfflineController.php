<?php
// app/Http/Controllers/OfflineController.php (COMPLETO)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{ApiService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * ✅ Sincronizar datos maestros manualmente (TODOS los registros)
     */
    public function syncMasterData(Request $request)
    {
        try {
            set_time_limit(0);
            
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay conexión a internet'
                ], 400);
            }

            $response = $this->apiService->get('/master-data/all');
            
            if ($response['success'] && isset($response['data'])) {
                $success = $this->offlineService->syncMasterDataFromApi($response['data']);
                
                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error sincronizando datos maestros básicos'
                    ], 500);
                }

                $stats = [
                    'departamentos' => count($response['data']['departamentos'] ?? []),
                    'municipios' => count($response['data']['municipios'] ?? []),
                    'empresas' => count($response['data']['empresas'] ?? []),
                    'procesos' => count($response['data']['procesos'] ?? []),
                    'brigadas' => count($response['data']['brigadas'] ?? []),
                    'especialidades' => count($response['data']['especialidades'] ?? []),
                    'tipos_documento' => count($response['data']['tipos_documento'] ?? [])
                ];

                $medicamentosResponse = $this->apiService->get('/medicamentos', ['all' => true]);
                
                if ($medicamentosResponse['success'] && !empty($medicamentosResponse['data'])) {
                    $medicamentosData = is_array($medicamentosResponse['data']) ? $medicamentosResponse['data'] : [];
                    $this->offlineService->syncMedicamentosFromApi($medicamentosData);
                    $stats['medicamentos'] = count($medicamentosData);
                } else {
                    $stats['medicamentos'] = 0;
                }

                $diagnosticosResponse = $this->apiService->get('/diagnosticos', ['all' => true]);
                
                if ($diagnosticosResponse['success'] && !empty($diagnosticosResponse['data'])) {
                    $diagnosticosData = is_array($diagnosticosResponse['data']) ? $diagnosticosResponse['data'] : [];
                    $this->offlineService->syncDiagnosticosFromApi($diagnosticosData);
                    $stats['diagnosticos'] = count($diagnosticosData);
                } else {
                    $stats['diagnosticos'] = 0;
                }

                $remisionesResponse = $this->apiService->get('/remisiones', ['all' => true]);
                
                if ($remisionesResponse['success'] && !empty($remisionesResponse['data'])) {
                    $remisionesData = is_array($remisionesResponse['data']) ? $remisionesResponse['data'] : [];
                    $this->offlineService->syncRemisionesFromApi($remisionesData);
                    $stats['remisiones'] = count($remisionesData);
                } else {
                    $stats['remisiones'] = 0;
                }

                $cupsResponse = $this->apiService->get('/cups', ['all' => true]);
                
                if ($cupsResponse['success'] && !empty($cupsResponse['data'])) {
                    $cupsData = is_array($cupsResponse['data']) ? $cupsResponse['data'] : [];
                    $this->offlineService->syncCupsFromApi($cupsData);
                    $stats['cups'] = count($cupsData);
                } else {
                    $stats['cups'] = 0;
                }

                $cupsContratadosResponse = $this->apiService->get('/cups-contratados/disponibles', ['all' => true]);
                
                if ($cupsContratadosResponse['success'] && !empty($cupsContratadosResponse['data'])) {
                    $cupsContratadosData = is_array($cupsContratadosResponse['data']) ? $cupsContratadosResponse['data'] : [];
                    DB::connection('offline')->table('cups_contratados')->delete();
                    
                    foreach ($cupsContratadosData as $cupsContratado) {
                        $this->offlineService->storeCupsContratadoOffline($cupsContratado);
                    }
                    $stats['cups_contratados'] = count($cupsContratadosData);
                } else {
                    $stats['cups_contratados'] = 0;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Datos sincronizados exitosamente',
                    'stats' => $stats
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo datos desde la API'
                ], 500);
            }

        } catch (\Exception $e) {
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
