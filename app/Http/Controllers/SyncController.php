<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{ApiService, OfflineService, PacienteService};
use Illuminate\Support\Facades\{Log, DB};

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
            // 1️⃣ SINCRONIZAR PACIENTES (BIDIRECCIONAL)
            // ========================================
            Log::info('📋 Paso 1/4: Sincronizando Pacientes...');
            
            try {
                $sedeId = session('sede_id');
                
                // 📤 PASO 1A: Enviar pacientes pendientes locales al backend
                Log::info('📤 Enviando pacientes pendientes a la API...');
                $pacientesResult = $this->pacienteService->syncPendingPacientes();
                
                if (!$pacientesResult['success']) {
                    throw new \Exception($pacientesResult['error'] ?? 'Error sincronizando pacientes');
                }
                
                $enviados = $pacientesResult['synced_count'] ?? 0;
                $erroresEnvio = $pacientesResult['failed_count'] ?? 0;
                
                // 📥 PASO 1B: Descargar pacientes nuevos desde el backend
                Log::info('📥 Descargando pacientes nuevos desde la API...');
                $resultDescarga = $this->offlineService->descargarPacientesDesdeAPI($sedeId);
                $descargados = $resultDescarga['descargados'] ?? 0;
                $erroresDescarga = count($resultDescarga['errors'] ?? []);
                
                $totalErrores = $erroresEnvio + $erroresDescarga;
                $totalSincronizados = $enviados + $descargados;
                
                $results['pacientes'] = [
                    'success' => true,
                    'synced_count' => $totalSincronizados,
                    'failed_count' => $totalErrores,
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'message' => "Pacientes: {$enviados} enviados, {$descargados} descargados"
                ];
                
                Log::info('✅ Paso 1/4 completado: Pacientes', [
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'errores' => $totalErrores
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
            // 2️⃣ SINCRONIZAR AGENDAS (BIDIRECCIONAL)
            // ========================================
            Log::info('📅 Paso 2/4: Sincronizando Agendas...');
            
            try {
                $sedeId = session('sede_id');
                
                // 📤 PASO 2A: Enviar agendas pendientes locales al backend
                Log::info('📤 Enviando agendas pendientes a la API...');
                $agendasResult = $this->offlineService->syncPendingAgendas();
                
                if (!$agendasResult['success']) {
                    throw new \Exception($agendasResult['error'] ?? 'Error sincronizando agendas');
                }
                
                $enviados = $agendasResult['synced_count'] ?? 0;
                $erroresEnvio = $agendasResult['failed_count'] ?? 0;
                
                // 📥 PASO 2B: Descargar agendas nuevas desde el backend
                Log::info('📥 Descargando agendas nuevas desde la API...');
                $resultDescarga = $this->offlineService->descargarAgendasDesdeAPI($sedeId);
                $descargados = $resultDescarga['descargados'] ?? 0;
                $erroresDescarga = count($resultDescarga['errors'] ?? []);
                
                $totalErrores = $erroresEnvio + $erroresDescarga;
                $totalSincronizados = $enviados + $descargados;
                
                $results['agendas'] = [
                    'success' => true,
                    'synced_count' => $totalSincronizados,
                    'failed_count' => $totalErrores,
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'message' => "Agendas: {$enviados} enviadas, {$descargados} descargadas"
                ];
                
                Log::info('✅ Paso 2/4 completado: Agendas', [
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'errores' => $totalErrores
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
            // 3️⃣ SINCRONIZAR CITAS (BIDIRECCIONAL)
            // ========================================
            Log::info('🗓️ Paso 3/4: Sincronizando Citas...');
            
            try {
                $sedeId = session('sede_id');
                
                // 📤 PASO 3A: Enviar citas pendientes locales al backend
                Log::info('📤 Enviando citas pendientes a la API...');
                $citasResult = $this->offlineService->syncPendingCitas();
                
                if (!$citasResult['success']) {
                    throw new \Exception($citasResult['error'] ?? 'Error sincronizando citas');
                }
                
                $enviados = $citasResult['synced_count'] ?? 0;
                $erroresEnvio = $citasResult['failed_count'] ?? 0;
                
                // 📥 PASO 3B: Descargar citas nuevas desde el backend
                Log::info('📥 Descargando citas nuevas desde la API...');
                $resultDescarga = $this->offlineService->descargarCitasDesdeAPI($sedeId);
                $descargados = $resultDescarga['descargados'] ?? 0;
                $erroresDescarga = count($resultDescarga['errors'] ?? []);
                
                $totalErrores = $erroresEnvio + $erroresDescarga;
                $totalSincronizados = $enviados + $descargados;
                
                $results['citas'] = [
                    'success' => true,
                    'synced_count' => $totalSincronizados,
                    'failed_count' => $totalErrores,
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'message' => "Citas: {$enviados} enviadas, {$descargados} descargadas"
                ];
                
                Log::info('✅ Paso 3/4 completado: Citas', [
                    'enviados' => $enviados,
                    'descargados' => $descargados,
                    'errores' => $totalErrores
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
                    'enviados' => $enviadas,
                    'descargados' => $descargadas,
                    'message' => "Historias: {$enviadas} enviadas, {$descargadas} descargadas"
                ];
                
                Log::info('✅ Paso 4/4 completado: Historias Clínicas', [
                    'enviados' => $enviadas,
                    'descargados' => $descargadas,
                    'errores' => $results['historias']['failed_count']
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

    /**
     * 🔧 CORRECCIÓN DE UUID DE AGENDA
     * 
     * Busca el UUID correcto en el servidor y actualiza la agenda local
     * y todas las citas relacionadas
     */
    public function fixAgendaUuid(Request $request)
    {
        try {
            $localUuid = $request->input('local_uuid');
            $sedeId = session('sede_id', 1);

            if (!$localUuid) {
                return response()->json([
                    'success' => false,
                    'error' => 'UUID de agenda es requerido'
                ], 400);
            }

            Log::info('🔧 Iniciando corrección de UUID de agenda', [
                'local_uuid' => $localUuid,
                'sede_id' => $sedeId
            ]);

            // 1. Obtener agenda local
            $agenda = DB::connection('offline')->table('agendas')
                ->where('uuid', $localUuid)
                ->first();

            if (!$agenda) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agenda no encontrada en SQLite'
                ], 404);
            }

            // 2. Buscar en servidor
            $response = $this->apiService->get('/agendas', [
                'sede_id' => $sedeId,
                'fecha' => $agenda->fecha,
                'per_page' => 100
            ]);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error consultando servidor: ' . ($response['error'] ?? 'Unknown')
                ], 500);
            }

            $serverAgendas = $response['data'] ?? [];

            // 3. Buscar agenda coincidente
            $serverUuid = null;
            foreach ($serverAgendas as $serverAgenda) {
                if ($serverAgenda['fecha'] === $agenda->fecha &&
                    $serverAgenda['consultorio'] == $agenda->consultorio &&
                    $serverAgenda['hora_inicio'] === $agenda->hora_inicio) {
                    
                    $serverUuid = $serverAgenda['uuid'];
                    break;
                }
            }

            if (!$serverUuid) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontró agenda coincidente en servidor',
                    'agendas_disponibles' => array_map(function($ag) {
                        return [
                            'uuid' => $ag['uuid'],
                            'fecha' => $ag['fecha'],
                            'consultorio' => $ag['consultorio'],
                            'hora' => $ag['hora_inicio']
                        ];
                    }, $serverAgendas)
                ], 404);
            }

            // 4. Actualizar agenda
            DB::connection('offline')->table('agendas')
                ->where('uuid', $localUuid)
                ->update([
                    'uuid' => $serverUuid,
                    'sync_status' => 'synced',
                    'synced_at' => now()
                ]);

            Log::info('✅ UUID de agenda actualizado', [
                'old_uuid' => $localUuid,
                'new_uuid' => $serverUuid
            ]);

            // 5. Actualizar citas relacionadas
            $citasCount = DB::connection('offline')->table('citas')
                ->where('agenda_uuid', $localUuid)
                ->count();

            if ($citasCount > 0) {
                DB::connection('offline')->table('citas')
                    ->where('agenda_uuid', $localUuid)
                    ->update([
                        'agenda_uuid' => $serverUuid,
                        'sync_status' => 'pending'
                    ]);

                Log::info('✅ Citas actualizadas con nuevo UUID', [
                    'count' => $citasCount,
                    'new_agenda_uuid' => $serverUuid
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'UUID de agenda corregido exitosamente',
                'old_uuid' => $localUuid,
                'new_uuid' => $serverUuid,
                'citas_actualizadas' => $citasCount
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error corrigiendo UUID de agenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
