<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{ApiService, OfflineService, PacienteService};
use Illuminate\Support\Facades\Log;

/**
 * Controlador para manejar la sincronización unificada de todos los módulos
 */
class SyncController extends Controller
{
    protected $apiService;
    protected $offlineService;
    protected $pacienteService;

    public function __construct(
        ApiService $apiService,
        OfflineService $offlineService,
        PacienteService $pacienteService
    ) {
        $this->middleware('custom.auth');
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
        $this->pacienteService = $pacienteService;
    }

    /**
     * 🔄 SINCRONIZACIÓN COMPLETA Y SECUENCIAL
     * 
     * Ejecuta la sincronización en el siguiente orden:
     * 1. Pacientes
     * 2. Agendas
     * 3. Citas
     * 4. Historias Clínicas
     * 
     * Si falla algún paso, se detiene la ejecución y se devuelve el error
     */
    public function syncAll(Request $request)
    {
        try {
            Log::info('🔄 SyncController@syncAll - Iniciando sincronización unificada');

            // Verificar conectividad
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin conexión al servidor. No se puede sincronizar.',
                    'module' => 'conectividad'
                ], 503);
            }

            $results = [
                'pacientes' => null,
                'agendas' => null,
                'citas' => null,
                'historias' => null
            ];

            // ========================================
            // 1️⃣ SINCRONIZAR PACIENTES
            // ========================================
            Log::info('📋 Paso 1/4: Sincronizando Pacientes...');
            
            try {
                $pacientesResult = $this->pacienteService->syncPendingPacientes();
                
                if (!$pacientesResult['success']) {
                    throw new \Exception($pacientesResult['error'] ?? 'Error sincronizando pacientes');
                }
                
                $results['pacientes'] = [
                    'success' => true,
                    'synced_count' => $pacientesResult['synced_count'] ?? 0,
                    'failed_count' => $pacientesResult['failed_count'] ?? 0,
                    'message' => 'Pacientes sincronizados correctamente'
                ];
                
                Log::info('✅ Paso 1/4 completado: Pacientes', [
                    'synced' => $results['pacientes']['synced_count'],
                    'failed' => $results['pacientes']['failed_count']
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Error en Paso 1/4: Pacientes', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Error sincronizando Pacientes: ' . $e->getMessage(),
                    'module' => 'pacientes',
                    'step' => 1,
                    'results' => $results
                ], 500);
            }

            // ========================================
            // 2️⃣ SINCRONIZAR AGENDAS
            // ========================================
            Log::info('📅 Paso 2/4: Sincronizando Agendas...');
            
            try {
                $agendasResult = $this->offlineService->syncPendingAgendas();
                
                if (!$agendasResult['success']) {
                    throw new \Exception($agendasResult['error'] ?? 'Error sincronizando agendas');
                }
                
                $results['agendas'] = [
                    'success' => true,
                    'synced_count' => $agendasResult['synced_count'] ?? 0,
                    'failed_count' => $agendasResult['failed_count'] ?? 0,
                    'message' => 'Agendas sincronizadas correctamente'
                ];
                
                Log::info('✅ Paso 2/4 completado: Agendas', [
                    'synced' => $results['agendas']['synced_count'],
                    'failed' => $results['agendas']['failed_count']
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Error en Paso 2/4: Agendas', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Error sincronizando Agendas: ' . $e->getMessage(),
                    'module' => 'agendas',
                    'step' => 2,
                    'results' => $results
                ], 500);
            }

            // ========================================
            // 3️⃣ SINCRONIZAR CITAS
            // ========================================
            Log::info('🗓️ Paso 3/4: Sincronizando Citas...');
            
            try {
                $citasResult = $this->offlineService->syncPendingCitas();
                
                if (!$citasResult['success']) {
                    throw new \Exception($citasResult['error'] ?? 'Error sincronizando citas');
                }
                
                $results['citas'] = [
                    'success' => true,
                    'synced_count' => $citasResult['synced_count'] ?? 0,
                    'failed_count' => $citasResult['failed_count'] ?? 0,
                    'message' => 'Citas sincronizadas correctamente'
                ];
                
                Log::info('✅ Paso 3/4 completado: Citas', [
                    'synced' => $results['citas']['synced_count'],
                    'failed' => $results['citas']['failed_count']
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Error en Paso 3/4: Citas', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Error sincronizando Citas: ' . $e->getMessage(),
                    'module' => 'citas',
                    'step' => 3,
                    'results' => $results
                ], 500);
            }

            // ========================================
            // 4️⃣ SINCRONIZAR HISTORIAS CLÍNICAS (BIDIRECCIONAL)
            // ========================================
            Log::info('📋 Paso 4/4: Sincronizando Historias Clínicas...');
            
            try {
                $sedeId = session('sede_id');
                
                if (!$sedeId) {
                    throw new \Exception('No se pudo obtener la sede actual');
                }
                
                // 📤 PASO 4A: Enviar historias pendientes locales al backend
                Log::info('📤 Enviando historias pendientes a la API...');
                $resultEnvio = $this->offlineService->enviarHistoriasPendientes($sedeId, null);
                $enviadas = $resultEnvio['enviadas'] ?? 0;
                $erroresEnvio = $resultEnvio['errors'] ?? [];
                
                // 📥 PASO 4B: Descargar historias nuevas desde el backend
                Log::info('📥 Descargando historias nuevas desde la API...');
                $resultDescarga = $this->offlineService->descargarHistoriasDesdeAPI($sedeId, null);
                $descargadas = $resultDescarga['descargadas'] ?? 0;
                $erroresDescarga = $resultDescarga['errors'] ?? [];
                
                $totalErrores = count($erroresEnvio) + count($erroresDescarga);
                $totalSincronizadas = $enviadas + $descargadas;
                
                $results['historias'] = [
                    'success' => true,
                    'synced_count' => $totalSincronizadas,
                    'failed_count' => $totalErrores,
                    'enviadas' => $enviadas,
                    'descargadas' => $descargadas,
                    'message' => "Historias: {$enviadas} enviadas, {$descargadas} descargadas"
                ];
                
                Log::info('✅ Paso 4/4 completado: Historias Clínicas', [
                    'synced' => $results['historias']['synced_count'],
                    'failed' => $results['historias']['failed_count']
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Error en Paso 4/4: Historias Clínicas', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Error sincronizando Historias Clínicas: ' . $e->getMessage(),
                    'module' => 'historias',
                    'step' => 4,
                    'results' => $results
                ], 500);
            }

            // ========================================
            // ✅ SINCRONIZACIÓN COMPLETADA
            // ========================================
            $totalSynced = 
                ($results['pacientes']['synced_count'] ?? 0) +
                ($results['agendas']['synced_count'] ?? 0) +
                ($results['citas']['synced_count'] ?? 0) +
                ($results['historias']['synced_count'] ?? 0);

            $totalFailed = 
                ($results['pacientes']['failed_count'] ?? 0) +
                ($results['agendas']['failed_count'] ?? 0) +
                ($results['citas']['failed_count'] ?? 0) +
                ($results['historias']['failed_count'] ?? 0);

            Log::info('🎉 Sincronización unificada completada exitosamente', [
                'total_synced' => $totalSynced,
                'total_failed' => $totalFailed
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada exitosamente',
                'total_synced' => $totalSynced,
                'total_failed' => $totalFailed,
                'details' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error crítico en sincronización unificada', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error crítico: ' . $e->getMessage(),
                'module' => 'sistema'
            ], 500);
        }
    }

    /**
     * 📊 Obtener conteo de registros pendientes por sincronizar
     */
    public function getPendingCounts(Request $request)
    {
        try {
            $sedeId = session('sede_id');
            
            $counts = [
                'pacientes' => $this->offlineService->countPendingRecords('pacientes', $sedeId),
                'agendas' => $this->offlineService->countPendingRecords('agendas', $sedeId),
                'citas' => $this->offlineService->countPendingRecords('citas', $sedeId),
                'historias' => $this->offlineService->countPendingRecords('historias_clinicas', $sedeId)
            ];

            $total = array_sum($counts);

            return response()->json([
                'success' => true,
                'counts' => $counts,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo conteo de pendientes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
