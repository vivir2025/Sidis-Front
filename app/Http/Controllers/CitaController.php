<?php
// app/Http/Controllers/CitaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{CitaService, AuthService, ApiService, OfflineService, PacienteService, AgendaService};
use Illuminate\Support\Facades\Log;

class CitaController extends Controller
    {
        protected $citaService;
        protected $authService;
        protected $apiService;
        protected $offlineService;
        protected $pacienteService;
        protected $agendaService;

        public function __construct(
            CitaService $citaService, 
            AuthService $authService, 
            ApiService $apiService, 
            OfflineService $offlineService,
            PacienteService $pacienteService,
            AgendaService $agendaService
        ) {
            $this->middleware('custom.auth');
            $this->citaService = $citaService;
            $this->authService = $authService;
            $this->apiService = $apiService;
            $this->offlineService = $offlineService;
            $this->pacienteService = $pacienteService;
            $this->agendaService = $agendaService;
        }
public function index(Request $request)
{

     set_time_limit(300); // 5 minutos
     ini_set('max_execution_time', 300);

    try {
        $filters = $request->only([
            'fecha', 'estado', 'paciente_documento', 'fecha_inicio', 'fecha_fin'
        ]);
        
        $page = $request->get('page', 1);
        
        $result = $this->citaService->index($filters, $page);

        if ($request->ajax()) {
            return response()->json($result);
        }

        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();

        Log::info('🔄 CitaController@index: Iniciando sincronizaciones silenciosas');

        // ✅ SINCRONIZAR CUPS CONTRATADOS
        Log::info('📋 Paso 1: Sincronizando CUPS contratados...');
        $cupsContratadosStats = $this->sincronizarCupsContratadosSilencioso();
        Log::info('📊 Resultado CUPS contratados', $cupsContratadosStats);
        
        // ✅ SINCRONIZAR CUPS NORMALES
        Log::info('📋 Paso 2: Sincronizando CUPS normales...');
        $cupsStats = $this->sincronizarCupsSilencioso();
        Log::info('📊 Resultado CUPS normales', $cupsStats);

        Log::info('✅ CitaController@index: Sincronizaciones completadas');

        return view('citas.index', compact('usuario', 'isOffline', 'cupsContratadosStats', 'cupsStats'));
        
    } catch (\Exception $e) {
        Log::error('Error en CitaController@index', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }

        return back()->with('error', 'Error cargando citas');
    }
}
/**
 * ✅ SINCRONIZACIÓN INTELIGENTE DE CUPS
 * - Primera vez: Sincroniza todo
 * - Siguientes veces: Solo sincroniza cambios nuevos
 */
private function sincronizarCupsSilencioso(): array
{
    try {
        Log::info('🔄 [CUPS] INICIO: Sincronización inteligente de CUPS');
        
        if (!$this->apiService->isOnline()) {
            Log::info('📱 [CUPS] Modo offline');
            return [
                'synced' => false,
                'reason' => 'offline',
                'message' => 'Sin conexión'
            ];
        }

        if (!$this->authService->hasValidToken()) {
            Log::info('🔐 [CUPS] Sin token válido');
            return [
                'synced' => false,
                'reason' => 'no_token',
                'message' => 'Sin token válido'
            ];
        }

        // ✅ VERIFICAR SI HAY DATOS LOCALES
        $localCount = $this->offlineService->countCups();
        $lastSync = cache()->get('cups_last_sync');
        $lastSyncTime = cache()->get('cups_last_sync_timestamp');
        
        Log::info('📊 [CUPS] Estado actual', [
            'local_count' => $localCount,
            'last_sync' => $lastSync,
            'last_sync_time' => $lastSyncTime
        ]);

        // ✅ SI NO HAY DATOS LOCALES: SINCRONIZACIÓN COMPLETA
        if ($localCount === 0) {
            Log::info('🆕 [CUPS] Primera sincronización - Cargando todos los datos');
            return $this->sincronizacionCompletaCups();
        }

        // ✅ SI YA HAY DATOS: SINCRONIZACIÓN INCREMENTAL
        Log::info('🔄 [CUPS] Sincronización incremental - Solo cambios nuevos');
        return $this->sincronizacionIncrementalCups($lastSyncTime);

    } catch (\Exception $e) {
        Log::error('❌ [CUPS] Excepción', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * 🔄 SINCRONIZACIÓN COMPLETA (primera vez)
 */
private function sincronizacionCompletaCups(): array
{
    try {
        Log::info('🔄 [CUPS] Iniciando sincronización COMPLETA');

        $this->offlineService->clearCups();

        $page = 1;
        $perPage = 100;
        $totalSynced = 0;
        $hasMorePages = true;
        
        while ($hasMorePages) {
            Log::info("📡 [CUPS] Sincronizando página {$page}");
            
            $response = $this->apiService->get('/cups', [
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            if (!$response['success']) {
                Log::warning('⚠️ [CUPS] Error en API', [
                    'page' => $page,
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
                break;
            }

            $responseData = $response['data'] ?? [];
            
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $cupsList = $responseData['data'];
                $currentPage = $responseData['current_page'] ?? $page;
                $lastPage = $responseData['last_page'] ?? $page;
                $hasMorePages = $currentPage < $lastPage;
            } else if (is_array($responseData)) {
                $cupsList = $responseData;
                $hasMorePages = count($cupsList) === $perPage;
            } else {
                break;
            }
            
            if (empty($cupsList)) {
                break;
            }

            foreach ($cupsList as $cups) {
                if (!is_array($cups)) continue;

                try {
                    $this->offlineService->storeCupsOffline($cups);
                    $totalSynced++;
                } catch (\Exception $e) {
                    Log::error('❌ [CUPS] Error guardando', [
                        'uuid' => $cups['uuid'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $page++;
            
            if ($hasMorePages) {
                usleep(100000); // 0.1 segundos
            }
        }

        // ✅ GUARDAR MARCA DE TIEMPO
        $now = now();
        cache()->put('cups_last_sync', $now->format('Y-m-d'), $now->addDay());
        cache()->put('cups_last_sync_timestamp', $now->toIso8601String(), $now->addDay());

        Log::info('✅ [CUPS] Sincronización COMPLETA finalizada', [
            'total' => $totalSynced,
            'paginas' => $page - 1
        ]);
        
        return [
            'synced' => true,
            'type' => 'complete',
            'count' => $totalSynced,
            'pages' => $page - 1,
            'message' => "✅ Sincronizados {$totalSynced} CUPS (completo)"
        ];

    } catch (\Exception $e) {
        Log::error('❌ [CUPS] Error en sincronización completa', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * 🔄 SINCRONIZACIÓN INCREMENTAL (solo cambios)
 */
private function sincronizacionIncrementalCups(?string $lastSyncTime): array
{
    try {
        Log::info('🔄 [CUPS] Iniciando sincronización INCREMENTAL', [
            'desde' => $lastSyncTime
        ]);

        // ✅ OBTENER SOLO REGISTROS NUEVOS O MODIFICADOS
        $params = [
            'per_page' => 100
        ];

        // Si la API soporta filtro por fecha
        if ($lastSyncTime) {
            $params['updated_since'] = $lastSyncTime;
        }

        $response = $this->apiService->get('/cups', $params);
        
        if (!$response['success']) {
            Log::warning('⚠️ [CUPS] Error en API incremental', [
                'error' => $response['error'] ?? 'Error desconocido'
            ]);
            
            return [
                'synced' => false,
                'reason' => 'api_error',
                'message' => $response['error'] ?? 'Error desconocido'
            ];
        }

        $responseData = $response['data'] ?? [];
        
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            $cupsList = $responseData['data'];
        } else if (is_array($responseData)) {
            $cupsList = $responseData;
        } else {
            $cupsList = [];
        }

        if (empty($cupsList)) {
            Log::info('✅ [CUPS] No hay cambios nuevos');
            
            return [
                'synced' => true,
                'type' => 'incremental',
                'count' => 0,
                'message' => '✅ No hay cambios nuevos'
            ];
        }

        Log::info('📥 [CUPS] Procesando cambios incrementales', [
            'count' => count($cupsList)
        ]);

        $syncedCount = 0;
        $updatedCount = 0;
        $newCount = 0;

        foreach ($cupsList as $cups) {
            if (!is_array($cups)) continue;

            try {
                $uuid = $cups['uuid'] ?? null;
                
                if (!$uuid) {
                    Log::warning('⚠️ [CUPS] Registro sin UUID');
                    continue;
                }

                // ✅ VERIFICAR SI YA EXISTE
                $exists = $this->offlineService->cupsExists($uuid);
                
                // ✅ GUARDAR O ACTUALIZAR
                $this->offlineService->storeCupsOffline($cups);
                
                if ($exists) {
                    $updatedCount++;
                } else {
                    $newCount++;
                }
                
                $syncedCount++;
                
            } catch (\Exception $e) {
                Log::error('❌ [CUPS] Error guardando cambio', [
                    'uuid' => $cups['uuid'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ ACTUALIZAR MARCA DE TIEMPO
        $now = now();
        cache()->put('cups_last_sync', $now->format('Y-m-d'), $now->addDay());
        cache()->put('cups_last_sync_timestamp', $now->toIso8601String(), $now->addDay());

        Log::info('✅ [CUPS] Sincronización INCREMENTAL finalizada', [
            'total_procesados' => $syncedCount,
            'nuevos' => $newCount,
            'actualizados' => $updatedCount
        ]);
        
        return [
            'synced' => true,
            'type' => 'incremental',
            'count' => $syncedCount,
            'new' => $newCount,
            'updated' => $updatedCount,
            'message' => "🔄 Sincronizados {$syncedCount} cambios ({$newCount} nuevos, {$updatedCount} actualizados)"
        ];

    } catch (\Exception $e) {
        Log::error('❌ [CUPS] Error en sincronización incremental', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * ✅ SINCRONIZACIÓN INTELIGENTE DE CUPS CONTRATADOS
 */
private function sincronizarCupsContratadosSilencioso(): array
{
    try {
        Log::info('🔄 [CUPS CONTRATADOS] INICIO: Sincronización inteligente');
        
        if (!$this->apiService->isOnline()) {
            return [
                'synced' => false,
                'reason' => 'offline',
                'message' => 'Sin conexión'
            ];
        }

        if (!$this->authService->hasValidToken()) {
            return [
                'synced' => false,
                'reason' => 'no_token',
                'message' => 'Sin token válido'
            ];
        }

        // ✅ VERIFICAR SI HAY DATOS LOCALES
        $localCount = $this->offlineService->countCupsContratados();
        $lastSyncTime = cache()->get('cups_contratados_last_sync_timestamp');
        
        Log::info('📊 [CUPS CONTRATADOS] Estado actual', [
            'local_count' => $localCount,
            'last_sync_time' => $lastSyncTime
        ]);

        // ✅ SI NO HAY DATOS: SINCRONIZACIÓN COMPLETA
        if ($localCount === 0) {
            Log::info('🆕 [CUPS CONTRATADOS] Primera sincronización');
            return $this->sincronizacionCompletaCupsContratados();
        }

        // ✅ SI YA HAY DATOS: SINCRONIZACIÓN INCREMENTAL
        Log::info('🔄 [CUPS CONTRATADOS] Sincronización incremental');
        return $this->sincronizacionIncrementalCupsContratados($lastSyncTime);

    } catch (\Exception $e) {
        Log::error('❌ [CUPS CONTRATADOS] Excepción', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * 🔄 SINCRONIZACIÓN COMPLETA DE CUPS CONTRATADOS
 */
private function sincronizacionCompletaCupsContratados(): array
{
    try {
        $response = $this->apiService->get('/cups-contratados/disponibles');
        
        if (!$response['success']) {
            return [
                'synced' => false,
                'reason' => 'api_error',
                'message' => $response['error'] ?? 'Error desconocido'
            ];
        }

        $cupsContratados = $response['data'] ?? [];
        
        if (empty($cupsContratados)) {
            return [
                'synced' => true,
                'type' => 'complete',
                'count' => 0,
                'message' => 'No hay CUPS contratados disponibles'
            ];
        }

        $this->offlineService->clearCupsContratados();

        $syncedCount = 0;
        
        foreach ($cupsContratados as $cupsContratado) {
            try {
                $this->offlineService->storeCupsContratadoOffline($cupsContratado);
                $syncedCount++;
            } catch (\Exception $e) {
                Log::warning('⚠️ Error guardando CUPS contratado', [
                    'uuid' => $cupsContratado['uuid'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $now = now();
        cache()->put('cups_contratados_last_sync', $now->format('Y-m-d'), $now->addDay());
        cache()->put('cups_contratados_last_sync_timestamp', $now->toIso8601String(), $now->addDay());

        Log::info('✅ [CUPS CONTRATADOS] Sincronización COMPLETA', [
            'total' => $syncedCount
        ]);

        return [
            'synced' => true,
            'type' => 'complete',
            'count' => $syncedCount,
            'message' => "✅ Sincronizados {$syncedCount} CUPS contratados (completo)"
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error sincronización completa CUPS contratados', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * 🔄 SINCRONIZACIÓN INCREMENTAL DE CUPS CONTRATADOS
 */
private function sincronizacionIncrementalCupsContratados(?string $lastSyncTime): array
{
    try {
        $params = [];
        if ($lastSyncTime) {
            $params['updated_since'] = $lastSyncTime;
        }

        $response = $this->apiService->get('/cups-contratados/disponibles', $params);
        
        if (!$response['success']) {
            return [
                'synced' => false,
                'reason' => 'api_error',
                'message' => $response['error'] ?? 'Error desconocido'
            ];
        }

        $cupsContratados = $response['data'] ?? [];
        
        if (empty($cupsContratados)) {
            return [
                'synced' => true,
                'type' => 'incremental',
                'count' => 0,
                'message' => '✅ No hay cambios nuevos'
            ];
        }

        $syncedCount = 0;
        $newCount = 0;
        $updatedCount = 0;
        
        foreach ($cupsContratados as $cupsContratado) {
            try {
                $uuid = $cupsContratado['uuid'] ?? null;
                if (!$uuid) continue;

                $exists = $this->offlineService->cupsContratadoExists($uuid);
                
                $this->offlineService->storeCupsContratadoOffline($cupsContratado);
                
                if ($exists) {
                    $updatedCount++;
                } else {
                    $newCount++;
                }
                
                $syncedCount++;
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Error guardando cambio CUPS contratado', [
                    'uuid' => $cupsContratado['uuid'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }
        }

        $now = now();
        cache()->put('cups_contratados_last_sync', $now->format('Y-m-d'), $now->addDay());
        cache()->put('cups_contratados_last_sync_timestamp', $now->toIso8601String(), $now->addDay());

        Log::info('✅ [CUPS CONTRATADOS] Sincronización INCREMENTAL', [
            'total' => $syncedCount,
            'nuevos' => $newCount,
            'actualizados' => $updatedCount
        ]);

        return [
            'synced' => true,
            'type' => 'incremental',
            'count' => $syncedCount,
            'new' => $newCount,
            'updated' => $updatedCount,
            'message' => "🔄 Sincronizados {$syncedCount} cambios ({$newCount} nuevos, {$updatedCount} actualizados)"
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error sincronización incremental CUPS contratados', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'synced' => false,
            'reason' => 'exception',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}



        public function create()
        {
            try {
                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                
                // Obtener agendas disponibles
                $agendasResult = $this->agendaService->disponibles();
                $agendas = $agendasResult['success'] ? $agendasResult['data'] : [];

                return view('citas.create', compact('usuario', 'isOffline', 'agendas'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@create', [
                    'error' => $e->getMessage()
                ]);

                return back()->with('error', 'Error cargando formulario de creación');
            }
        }

        public function store(Request $request)
        {
            try {
                $validatedData = $request->validate([
                    'fecha' => 'required|date',
                    'fecha_inicio' => 'required|date',
                    'fecha_final' => 'required|date|after:fecha_inicio',
                    'fecha_deseada' => 'nullable|date',
                    'motivo' => 'nullable|string|max:200',
                    'nota' => 'required|string|max:200',
                    'estado' => 'nullable|string|max:50',
                    'patologia' => 'nullable|string|max:50',
                    'paciente_uuid' => 'required|string|max:100',
                    'agenda_uuid' => 'required|string|max:100',
                    'cups_contratado_uuid' => 'nullable|string|max:100',
                ]);

                $result = $this->citaService->store($validatedData);

                if ($request->ajax()) {
                    $response = $result;
                    if ($result['success']) {
                        $response['redirect_url'] = route('citas.index');
                    }
                    return response()->json($response);
                }

                if ($result['success']) {
                    return redirect()->route('citas.index')
                        ->with('success', $result['message'] ?? 'Cita creada exitosamente');
                }

                return back()
                    ->withErrors(['error' => $result['error']])
                    ->withInput();
                    
            } catch (\Exception $e) {
                Log::error('Error en CitaController@store', [
                    'error' => $e->getMessage(),
                    'data' => $request->all()
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error interno del servidor'
                    ], 500);
                }

                return back()
                    ->with('error', 'Error interno del servidor')
                    ->withInput();
            }
        }

        public function show(string $uuid)
        {
            try {
                $result = $this->citaService->show($uuid);

                if (!$result['success']) {
                    abort(404, $result['error']);
                }

                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                $cita = $result['data'];

                return view('citas.show', compact('cita', 'usuario', 'isOffline'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@show', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                abort(500, 'Error interno del servidor');
            }
        }

        public function edit(string $uuid)
        {
            try {
                $result = $this->citaService->show($uuid);

                if (!$result['success']) {
                    abort(404, $result['error']);
                }

                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                $cita = $result['data'];
                
                // Obtener agendas disponibles
                $agendasResult = $this->agendaService->disponibles();
                $agendas = $agendasResult['success'] ? $agendasResult['data'] : [];

                return view('citas.edit', compact('cita', 'usuario', 'isOffline', 'agendas'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@edit', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return back()->with('error', 'Error cargando formulario de edición');
            }
        }

        public function update(Request $request, string $uuid)
        {
            try {
                $validatedData = $request->validate([
                    'fecha' => 'required|date',
                    'fecha_inicio' => 'required|date',
                    'fecha_final' => 'required|date|after:fecha_inicio',
                    'fecha_deseada' => 'nullable|date',
                    'motivo' => 'nullable|string|max:200',
                    'nota' => 'required|string|max:200',
                    'estado' => 'nullable|string|max:50',
                    'patologia' => 'nullable|string|max:50',
                    'paciente_uuid' => 'required|string|max:100',
                    'agenda_uuid' => 'required|string|max:100',
                    'cups_contratado_id' => 'nullable|string|max:100',
                ]);

                $result = $this->citaService->update($uuid, $validatedData);

                if ($request->ajax()) {
                    return response()->json($result);
                }

                if ($result['success']) {
                    return redirect()->route('citas.show', $uuid)
                        ->with('success', $result['message'] ?? 'Cita actualizada exitosamente');
                }

                return back()
                    ->withErrors(['error' => $result['error']])
                    ->withInput();
                    
            } catch (\Exception $e) {
                Log::error('Error en CitaController@update', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error interno del servidor'
                    ], 500);
                }

                return back()
                    ->with('error', 'Error interno del servidor')
                    ->withInput();
            }
        }

        public function destroy(string $uuid)
        {
            try {
                $result = $this->citaService->destroy($uuid);
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@destroy', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        public function citasDelDia(Request $request)
        {
            try {
                $fecha = $request->get('fecha', now()->format('Y-m-d'));
                $result = $this->citaService->citasDelDia($fecha);
                
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@citasDelDia', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        public function cambiarEstado(Request $request, string $uuid)
        {
            try {
                $request->validate([
                    'estado' => 'required|in:PROGRAMADA,EN_ATENCION,ATENDIDA,CANCELADA,NO_ASISTIO'
                ]);

                $result = $this->citaService->cambiarEstado($uuid, $request->estado);
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@cambiarEstado', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }
    public function buscarPaciente(Request $request)
    {
        try {
            $request->validate([
                'documento' => 'required|string|min:3'
            ]);

            Log::info('🔍 CitaController::buscarPaciente iniciado', [
                'documento' => $request->documento
            ]);

            $result = $this->pacienteService->searchByDocument($request->documento);
            
            // ✅ VALIDACIÓN ADICIONAL SI SE ENCUENTRA EL PACIENTE
            if ($result['success'] && isset($result['data']) && !empty($result['data'])) {
                $pacientes = $result['data'];
                $paciente = is_array($pacientes) ? $pacientes[0] : $pacientes;
                
                // ✅ VALIDAR UUID DEL PACIENTE ENCONTRADO
                if (!isset($paciente['uuid']) || empty($paciente['uuid'])) {
                    Log::error('❌ Paciente encontrado sin UUID válido', [
                        'documento' => $request->documento,
                        'paciente_keys' => array_keys($paciente),
                        'sede_id' => $paciente['sede_id'] ?? 'NO_DEFINIDA'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'El paciente encontrado no tiene un identificador válido'
                    ]);
                }

                // ✅ VALIDAR FORMATO DE UUID
                if (!$this->isValidUuid($paciente['uuid'])) {
                    Log::error('❌ UUID con formato inválido', [
                        'documento' => $request->documento,
                        'uuid' => $paciente['uuid']
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'El identificador del paciente tiene formato inválido'
                    ]);
                }

                Log::info('✅ Paciente encontrado correctamente', [
                    'documento' => $request->documento,
                    'uuid' => $paciente['uuid'],
                    'nombre' => ($paciente['primer_nombre'] ?? '') . ' ' . ($paciente['primer_apellido'] ?? ''),
                    'sede_id' => $paciente['sede_id'] ?? 'NO_DEFINIDA',
                    'offline' => $result['offline'] ?? false
                ]);

                // ✅ ASEGURAR QUE RETORNAMOS EL PACIENTE INDIVIDUAL
                $result['data'] = $paciente;
            }
            
            return response()->json($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Validación fallida en búsqueda de paciente', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en CitaController@buscarPaciente', [
                'documento' => $request->documento ?? 'NO_DEFINIDO',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ CORREGIDO: Validar formato de UUID
     */
    private function isValidUuid($uuid): bool
    {
        if (empty($uuid) || !is_string($uuid)) {
            return false;
        }
        
        // ✅ PATRÓN UUID CORREGIDO (acepta cualquier versión de UUID)
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        
        return preg_match($pattern, $uuid) === 1;
    }


    /**
     * ✅ CORREGIDO: Obtener horarios disponibles de una agenda
     */
    public function getHorariosDisponibles(Request $request, string $agendaUuid)
    {
        try {
            $fecha = $request->get('fecha');
            
            Log::info('🔍 Controlador: Obteniendo horarios disponibles', [
                'agenda_uuid' => $agendaUuid,
                'fecha_solicitada' => $fecha
            ]);
            
            $result = $this->citaService->getHorariosDisponibles($agendaUuid, $fecha);
            
            // ✅ AGREGAR LOGGING DETALLADO
            if ($result['success'] && isset($result['data'])) {
                $disponibles = count(array_filter($result['data'], fn($h) => $h['disponible']));
                $ocupados = count(array_filter($result['data'], fn($h) => !$h['disponible']));
                
                Log::info('✅ Horarios obtenidos correctamente', [
                    'agenda_uuid' => $agendaUuid,
                    'total_horarios' => count($result['data']),
                    'disponibles' => $disponibles,
                    'ocupados' => $ocupados
                ]);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo horarios disponibles', [
                'agenda_uuid' => $agendaUuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

        // ✅ NUEVO: Obtener detalles de agenda
        public function getAgendaDetails(string $agendaUuid)
        {
            try {
                $result = $this->agendaService->show($agendaUuid);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Agenda no encontrada'
                    ]);
                }

                $agenda = $result['data'];
                
                // Calcular cupos y horarios
                $horarios = $this->calcularHorariosDisponibles($agenda);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'agenda' => $agenda,
                        'horarios_disponibles' => $horarios
                    ]
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error obteniendo detalles de agenda', [
                    'agenda_uuid' => $agendaUuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        // ✅ NUEVO: Calcular horarios disponibles
        private function calcularHorariosDisponibles(array $agenda): array
        {
            try {
                $horarios = [];
                
                $fecha = $agenda['fecha'];
                $horaInicio = $agenda['hora_inicio'];
                $horaFin = $agenda['hora_fin'];
                $intervalo = (int) ($agenda['intervalo'] ?? 15);
                
                // Obtener citas existentes para esta agenda
                $citasExistentes = $this->obtenerCitasExistentes($agenda['uuid'], $fecha);
                $horariosOcupados = array_map(function($cita) {
                    return date('H:i', strtotime($cita['fecha_inicio']));
                }, $citasExistentes);
                
                // Generar todos los horarios posibles
                $inicio = \Carbon\Carbon::createFromFormat('H:i', $horaInicio);
                $fin = \Carbon\Carbon::createFromFormat('H:i', $horaFin);
                
                while ($inicio->lt($fin)) {
                    $horarioStr = $inicio->format('H:i');
                    $finHorario = $inicio->copy()->addMinutes($intervalo);
                    
                    // Verificar si el horario está disponible
                    $disponible = !in_array($horarioStr, $horariosOcupados);
                    
                    $horarios[] = [
                        'hora_inicio' => $horarioStr,
                        'hora_fin' => $finHorario->format('H:i'),
                        'fecha_inicio' => $fecha . 'T' . $horarioStr,
                        'fecha_final' => $fecha . 'T' . $finHorario->format('H:i'),
                        'disponible' => $disponible,
                        'ocupado_por' => $disponible ? null : $this->obtenerPacienteEnHorario($citasExistentes, $horarioStr)
                    ];
                    
                    $inicio->addMinutes($intervalo);
                }
                
                return $horarios;
                
            } catch (\Exception $e) {
                Log::error('Error calculando horarios disponibles', [
                    'error' => $e->getMessage()
                ]);
                
                return [];
            }
        }

/**
 * ✅ CORREGIDO: Endpoint para determinar tipo de consulta ANTES de crear cita
 */
public function determinarTipoConsultaPrevio(Request $request)
{
    try {
        $request->validate([
            'paciente_uuid' => 'required|string',
            'agenda_uuid' => 'required|string'
        ]);

        Log::info('🔍 Frontend: Determinando tipo de consulta previo', [
            'paciente_uuid' => $request->paciente_uuid,
            'agenda_uuid' => $request->agenda_uuid,
            'is_online' => $this->apiService->isOnline()
        ]);

        // ✅ INTENTAR ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->post('/citas/determinar-tipo-consulta', [
                    'paciente_uuid' => $request->paciente_uuid,
                    'agenda_uuid' => $request->agenda_uuid
                ]);

                if ($response['success']) {
                    Log::info('✅ Tipo de consulta determinado desde API', $response['data']);
                    return response()->json($response);
                }
                
                Log::warning('⚠️ API respondió con error, usando lógica offline', [
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Excepción con API, usando lógica offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ FALLBACK: LÓGICA OFFLINE
        Log::info('💾 Determinando tipo de consulta en modo offline');
        
        $resultado = $this->determinarTipoConsultaOffline(
            $request->paciente_uuid,
            $request->agenda_uuid
        );

        return response()->json($resultado);

    } catch (\Exception $e) {
        Log::error('❌ Error determinando tipo de consulta previo', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Error interno del servidor'
        ], 500);
    }
}
/**
 * ✅ CORREGIDO: Determinar tipo de consulta OFFLINE CON CUPS
 */
private function determinarTipoConsultaOffline(string $pacienteUuid, string $agendaUuid): array
{
    try {
        Log::info('🔍 Iniciando determinación offline CON CUPS', [
            'paciente_uuid' => $pacienteUuid,
            'agenda_uuid' => $agendaUuid
        ]);

        // ✅ PASO 1: OBTENER LA AGENDA
        Log::info('📋 PASO 1: Obteniendo agenda offline');
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            Log::error('❌ PASO 1 FALLÓ: Agenda no encontrada', [
                'agenda_uuid' => $agendaUuid
            ]);
            
            return [
                'success' => false,
                'error' => 'Agenda no encontrada offline'
            ];
        }

        Log::info('✅ PASO 1 COMPLETADO: Agenda encontrada', [
            'agenda_uuid' => $agenda['uuid'] ?? 'NO_UUID'
        ]);

        // ✅ PASO 2: OBTENER PROCESO DE LA AGENDA
        Log::info('📋 PASO 2: Extrayendo proceso de la agenda');
        
        if (!isset($agenda['proceso'])) {
            Log::error('❌ PASO 2 FALLÓ: Agenda sin campo proceso', [
                'agenda_uuid' => $agendaUuid,
                'agenda_keys' => array_keys($agenda)
            ]);
            
            return [
                'success' => false,
                'error' => 'La agenda no tiene información del proceso'
            ];
        }

        $procesoNombre = null;
        
        if (is_array($agenda['proceso'])) {
            $procesoNombre = $agenda['proceso']['nombre'] ?? null;
        } elseif (is_string($agenda['proceso'])) {
            $procesoNombre = $agenda['proceso'];
        }
        
        if (!$procesoNombre) {
            Log::error('❌ PASO 2 FALLÓ: No se pudo extraer nombre del proceso');
            
            return [
                'success' => false,
                'error' => 'La agenda no tiene un proceso asignado'
            ];
        }

        $procesoNombre = strtoupper(trim($procesoNombre));

        Log::info('✅ PASO 2 COMPLETADO: Proceso identificado', [
            'proceso_nombre' => $procesoNombre
        ]);

        // ✅ PASO 3: VALIDAR REQUISITO DE ESPECIAL CONTROL
        Log::info('📋 PASO 3: Validando requisito de ESPECIAL CONTROL');
        
        $validacionEspecialControl = $this->validarRequisitoEspecialControlOffline(
            $pacienteUuid, 
            $procesoNombre
        );

        if (!$validacionEspecialControl['success']) {
            Log::warning('⚠️ PASO 3: Validación de ESPECIAL CONTROL falló');
            return $validacionEspecialControl;
        }

        Log::info('✅ PASO 3 COMPLETADO: Validación de ESPECIAL CONTROL exitosa');

        // ✅ PASO 4: DETERMINAR TIPO DE CONSULTA
        Log::info('📋 PASO 4: Determinando tipo de consulta');
        
        $tipoConsulta = $this->determinarTipoConsultaConReglasOffline(
            $pacienteUuid, 
            $agendaUuid, 
            $procesoNombre
        );

        Log::info('✅ PASO 4 COMPLETADO: Tipo de consulta determinado', [
            'tipo_consulta' => $tipoConsulta,
            'proceso' => $procesoNombre
        ]);

        // ✅ PASO 5: BUSCAR CUPS RECOMENDADO
        Log::info('📋 PASO 5: Buscando CUPS recomendado');
        
        $cupsRecomendado = $this->buscarCupsRecomendadoOffline(
            $tipoConsulta, 
            $procesoNombre
        );

        if (!$cupsRecomendado) {
            Log::warning('⚠️ PASO 5: No se encontró CUPS recomendado', [
                'tipo_consulta' => $tipoConsulta,
                'proceso' => $procesoNombre
            ]);

            // ✅ DEVOLVER SIN CUPS
            return [
                'success' => true,
                'data' => [
                    'tipo_consulta' => $tipoConsulta,
                    'proceso_nombre' => $procesoNombre,
                    'requiere_especial_control' => false,
                    'mensaje' => $this->generarMensajeTipoConsulta($tipoConsulta, $procesoNombre),
                    'cups_recomendado' => null
                ],
                'offline' => true
            ];
        }

        Log::info('✅ PASO 5 COMPLETADO: CUPS recomendado encontrado', [
            'cups_contratado_uuid' => $cupsRecomendado['uuid'],
            'cups_codigo' => $cupsRecomendado['cups']['codigo'] ?? 'N/A',
            'cups_nombre' => $cupsRecomendado['cups']['nombre'] ?? 'N/A'
        ]);

        // ✅ CONSTRUIR RESPUESTA FINAL CON CUPS
        $resultado = [
            'success' => true,
            'data' => [
                'tipo_consulta' => $tipoConsulta,
                'proceso_nombre' => $procesoNombre,
                'requiere_especial_control' => false,
                'mensaje' => $this->generarMensajeTipoConsulta($tipoConsulta, $procesoNombre),
                'cups_recomendado' => [
                    'cups_contratado_uuid' => $cupsRecomendado['uuid'],
                    'uuid' => $cupsRecomendado['cups']['uuid'] ?? null,
                    'codigo' => $cupsRecomendado['cups']['codigo'] ?? 'N/A',
                    'nombre' => $cupsRecomendado['cups']['nombre'] ?? 'N/A',
                    'categoria' => $cupsRecomendado['categoria_cups']['nombre'] ?? 'N/A'
                ]
            ],
            'offline' => true
        ];

        Log::info('✅ DETERMINACIÓN OFFLINE COMPLETADA CON CUPS', [
            'tipo_consulta' => $tipoConsulta,
            'tiene_cups' => true,
            'cups_uuid' => $cupsRecomendado['uuid']
        ]);

        return $resultado;

    } catch (\Exception $e) {
        Log::error('❌ EXCEPCIÓN CRÍTICA en determinación offline', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => 'Error determinando tipo de consulta offline: ' . $e->getMessage()
        ];
    }
}

private function buscarCupsRecomendadoOffline(string $tipoConsulta, string $procesoNombre): ?array
{
    try {
        Log::info('🔍 Buscando CUPS recomendado offline', [
            'tipo_consulta' => $tipoConsulta,
            'proceso' => $procesoNombre
        ]);

        // Obtener palabras clave
        $palabrasClave = $this->obtenerPalabrasClaveProcesoParaCups($procesoNombre);
        
        Log::info('🔑 Palabras clave para búsqueda', [
            'palabras_clave' => $palabrasClave
        ]);

        // Buscar CUPS contratado
        Log::info('🔍 Obteniendo CUPS contratados desde offline');
        $cupsContratados = $this->offlineService->getCupsContratadosOffline();
        
        if (empty($cupsContratados)) {
            Log::warning('⚠️ No hay CUPS contratados en caché offline');
            return null;
        }

        Log::info('📋 CUPS contratados disponibles', [
            'total' => count($cupsContratados)
        ]);

        // ✅ NUEVO: LOGGING DETALLADO DE CADA CUPS
        $cupsAnalizados = [];
        $cupsDescartados = [];
        
        Log::info('🔍 INICIANDO ANÁLISIS DETALLADO DE CUPS', [
            'total_a_analizar' => count($cupsContratados),
            'tipo_consulta_buscado' => strtoupper($tipoConsulta),
            'palabras_clave' => $palabrasClave
        ]);
        
        foreach ($cupsContratados as $index => $cupsContratado) {
            $categoriaNombre = strtoupper($cupsContratado['categoria_cups']['nombre'] ?? 'SIN_CATEGORIA');
            $cupsNombre = strtoupper($cupsContratado['cups']['nombre'] ?? 'SIN_NOMBRE');
            $estado = strtoupper($cupsContratado['estado'] ?? 'SIN_ESTADO');
            $cupsUuid = $cupsContratado['uuid'] ?? 'SIN_UUID';
            $cupsCodigo = $cupsContratado['cups']['codigo'] ?? 'SIN_CODIGO';
            
            $analisis = [
                'index' => $index + 1,
                'uuid' => $cupsUuid,
                'codigo' => $cupsCodigo,
                'cups_nombre' => $cupsNombre,
                'categoria' => $categoriaNombre,
                'estado' => $estado,
                'categoria_coincide' => $categoriaNombre === strtoupper($tipoConsulta),
                'estado_activo' => $estado === 'ACTIVO',
                'palabras_encontradas' => []
            ];
            
            // Verificar palabras clave
            foreach ($palabrasClave as $palabra) {
                if (str_contains($cupsNombre, strtoupper($palabra))) {
                    $analisis['palabras_encontradas'][] = $palabra;
                }
            }
            
            $analisis['tiene_palabra_clave'] = !empty($analisis['palabras_encontradas']);
            $analisis['es_candidato'] = $analisis['categoria_coincide'] && 
                                        $analisis['estado_activo'] && 
                                        $analisis['tiene_palabra_clave'];
            
            // ✅ LOG CADA CUPS ANALIZADO (solo primeros 10 para no saturar)
            if ($index < 10) {
                Log::debug('📋 Analizando CUPS #' . ($index + 1), $analisis);
            }
            
            if ($analisis['es_candidato']) {
                $cupsAnalizados[] = $analisis;
                
                // ✅ ENCONTRADO - RETORNAR INMEDIATAMENTE
                Log::info('✅ ¡CUPS RECOMENDADO ENCONTRADO!', [
                    'cups_contratado_uuid' => $cupsContratado['uuid'],
                    'cups_codigo' => $cupsCodigo,
                    'cups_nombre' => $cupsNombre,
                    'categoria' => $categoriaNombre,
                    'palabras_coincidentes' => $analisis['palabras_encontradas'],
                    'analisis_completo' => $analisis
                ]);
                
                return $cupsContratado;
            } else {
                $cupsDescartados[] = $analisis;
            }
        }

        // ✅ NO SE ENCONTRÓ - MOSTRAR ANÁLISIS COMPLETO
        Log::warning('⚠️ No se encontró CUPS recomendado offline', [
            'tipo_consulta' => $tipoConsulta,
            'palabras_clave' => $palabrasClave,
            'total_cups_analizados' => count($cupsContratados),
            'cups_candidatos_encontrados' => count($cupsAnalizados),
            'cups_descartados' => count($cupsDescartados)
        ]);

        // ✅ MOSTRAR LOS PRIMEROS 5 CUPS DESCARTADOS PARA DEBUG
        Log::warning('📋 PRIMEROS 5 CUPS DESCARTADOS (para análisis)', [
            'cups_descartados' => array_slice($cupsDescartados, 0, 5)
        ]);

        // ✅ MOSTRAR RESUMEN DE CATEGORÍAS DISPONIBLES
        $categorias = array_count_values(array_column($cupsDescartados, 'categoria'));
        Log::warning('📊 CATEGORÍAS DISPONIBLES EN CUPS CONTRATADOS', [
            'categorias_encontradas' => $categorias,
            'categoria_buscada' => strtoupper($tipoConsulta),
            'tiene_categoria_buscada' => isset($categorias[strtoupper($tipoConsulta)])
        ]);

        // ✅ MOSTRAR CUPS CON LA CATEGORÍA CORRECTA (si existen)
        $cupsConCategoriaCorrecta = array_filter($cupsDescartados, function($c) use ($tipoConsulta) {
            return $c['categoria'] === strtoupper($tipoConsulta);
        });
        
        if (!empty($cupsConCategoriaCorrecta)) {
            Log::warning('🔍 CUPS CON CATEGORÍA CORRECTA PERO DESCARTADOS', [
                'total' => count($cupsConCategoriaCorrecta),
                'cups' => array_slice($cupsConCategoriaCorrecta, 0, 3)
            ]);
        }

        return null;

    } catch (\Exception $e) {
        Log::error('❌ EXCEPCIÓN en buscarCupsRecomendadoOffline', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return null;
    }
}

/**
 * ✅ NUEVO MÉTODO: Obtener palabras clave para CUPS
 */
private function obtenerPalabrasClaveProcesoParaCups(string $procesoNombre): array
{
    $procesoNombre = strtoupper(trim($procesoNombre));
    
    $mapeo = [
        'ESPECIAL CONTROL' => [
            'MEDICINA GENERAL',
            'GENERAL'
        ],
        'NUTRICIONISTA' => [
            'NUTRICION Y DIETETICA',
            'NUTRICION',
            'DIETETICA'
        ],
        'PSICOLOGIA' => [
            'PSICOLOGIA',
            'PSICOLOGO'
        ],
        'FISIOTERAPIA' => [
            'FISIOTERAPIA'
        ],
        'NEFROLOGIA' => [
            'NEFROLOGIA',
            'ESPECIALISTA EN NEFROLOGIA'
        ],
        'INTERNISTA' => [
            'MEDICINA INTERNA',
            'ESPECIALISTA EN MEDICINA INTERNA'
        ],
        'TRABAJO SOCIAL' => [
            'TRABAJO SOCIAL'
        ]
    ];
    
    if (isset($mapeo[$procesoNombre])) {
        return $mapeo[$procesoNombre];
    }
    
    return [$procesoNombre];
}

private function validarRequisitoEspecialControlOffline(string $pacienteUuid, string $procesoNombre): array
{
    try {
        // ✅ SI ES ESPECIAL CONTROL, NO VALIDAR
        if ($procesoNombre === 'ESPECIAL CONTROL') {
            Log::info('✅ Proceso es ESPECIAL CONTROL, validación omitida');
            return ['success' => true];
        }

        Log::info('🔍 Validando requisito de ESPECIAL CONTROL offline', [
            'paciente_uuid' => $pacienteUuid,
            'proceso_solicitado' => $procesoNombre
        ]);

        $usuario = $this->authService->usuario();
        $sedeId = $usuario['sede_id'];

        Log::info('📋 Obteniendo citas del paciente', [
            'paciente_uuid' => $pacienteUuid,
            'sede_id' => $sedeId
        ]);

        // ✅ BUSCAR CITAS DEL PACIENTE OFFLINE
        $citasPaciente = $this->offlineService->getCitasOffline($sedeId, [
            'paciente_uuid' => $pacienteUuid
        ]);

        Log::info('📊 Citas del paciente encontradas offline', [
            'total_citas' => count($citasPaciente),
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ VERIFICAR SI TIENE ESPECIAL CONTROL - PRIMERA VEZ
        $tienePrimeraVezEspecialControl = false;
        $citasRevisadas = 0;
        
        foreach ($citasPaciente as $cita) {
            $citasRevisadas++;
            
            // ✅ VERIFICAR ESTRUCTURA DE LA CITA
            if (!isset($cita['agenda'])) {
                Log::warning('⚠️ Cita sin datos de agenda', [
                    'cita_uuid' => $cita['uuid'] ?? 'NO_UUID',
                    'cita_keys' => array_keys($cita)
                ]);
                continue;
            }

            if (!isset($cita['agenda']['proceso'])) {
                Log::warning('⚠️ Agenda sin datos de proceso', [
                    'cita_uuid' => $cita['uuid'] ?? 'NO_UUID',
                    'agenda_keys' => array_keys($cita['agenda'])
                ]);
                continue;
            }

            $procesoNombreCita = strtoupper($cita['agenda']['proceso']['nombre'] ?? '');
            $estadoCita = $cita['estado'] ?? '';
            
            Log::debug('🔍 Revisando cita', [
                'cita_numero' => $citasRevisadas,
                'cita_uuid' => $cita['uuid'],
                'proceso' => $procesoNombreCita,
                'estado' => $estadoCita
            ]);
            
            if ($procesoNombreCita === 'ESPECIAL CONTROL' &&
                in_array($estadoCita, ['ATENDIDA', 'PROGRAMADA', 'CONFIRMADA'])) {
                $tienePrimeraVezEspecialControl = true;
                
                Log::info('✅ Encontrada cita de ESPECIAL CONTROL válida', [
                    'cita_uuid' => $cita['uuid'],
                    'estado' => $estadoCita,
                    'proceso' => $procesoNombreCita
                ]);
                break;
            }
        }

        Log::info('📊 Resultado de validación', [
            'citas_revisadas' => $citasRevisadas,
            'tiene_especial_control' => $tienePrimeraVezEspecialControl
        ]);

        if (!$tienePrimeraVezEspecialControl) {
            Log::warning('⚠️ Paciente sin ESPECIAL CONTROL - PRIMERA VEZ', [
                'paciente_uuid' => $pacienteUuid,
                'proceso_solicitado' => $procesoNombre
            ]);

            return [
                'success' => false,
                'error' => 'El paciente debe tener primero una cita de ESPECIAL CONTROL - PRIMERA VEZ antes de agendar otras especialidades',
                'requiere_especial_control' => true,
                'data' => [
                    'tipo_consulta' => null,
                    'proceso_nombre' => $procesoNombre,
                    'requiere_especial_control' => true,
                    'mensaje' => 'Se requiere cita de ESPECIAL CONTROL - PRIMERA VEZ'
                ]
            ];
        }

        Log::info('✅ Validación de ESPECIAL CONTROL exitosa');
        return ['success' => true];

    } catch (\Exception $e) {
        Log::error('❌ EXCEPCIÓN en validación de ESPECIAL CONTROL offline', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => 'Error validando requisitos del paciente: ' . $e->getMessage()
        ];
    }
}

/**
 * ✅ DETERMINAR TIPO DE CONSULTA CON REGLAS OFFLINE
 */
private function determinarTipoConsultaConReglasOffline(
    string $pacienteUuid, 
    string $agendaUuid, 
    string $procesoNombre
): string {
    try {
        // ✅ REGLA 1: NEFROLOGÍA e INTERNISTA siempre son CONTROL
        $procesosSoloControl = ['NEFROLOGIA', 'INTERNISTA'];
        
        if (in_array($procesoNombre, $procesosSoloControl)) {
            Log::info('✅ Proceso solo permite CONTROL offline', [
                'proceso' => $procesoNombre
            ]);
            return 'CONTROL';
        }

        // ✅ REGLA 2: Verificar historial del paciente
        $usuario = $this->authService->usuario();
        $sedeId = $usuario['sede_id'];

        $citasPaciente = $this->offlineService->getCitasOffline($sedeId, [
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ CONTAR CITAS ANTERIORES DEL MISMO PROCESO
        $citasAnteriores = 0;
        
        foreach ($citasPaciente as $cita) {
            $procesoNombreCita = strtoupper($cita['agenda']['proceso']['nombre'] ?? '');
            $estadoCita = $cita['estado'] ?? '';
            
            if ($procesoNombreCita === $procesoNombre &&
                in_array($estadoCita, ['ATENDIDA', 'PROGRAMADA', 'CONFIRMADA', 'EN_ATENCION'])) {
                $citasAnteriores++;
            }
        }

        Log::info('📊 Citas anteriores encontradas offline', [
            'paciente_uuid' => $pacienteUuid,
            'proceso_buscado' => $procesoNombre,
            'citas_anteriores' => $citasAnteriores
        ]);

        // ✅ DETERMINAR TIPO DE CONSULTA
        $tipoConsulta = ($citasAnteriores > 0) ? 'CONTROL' : 'PRIMERA VEZ';
        
        Log::info('✅ Tipo de consulta determinado offline', [
            'tipo_consulta' => $tipoConsulta,
            'citas_previas' => $citasAnteriores
        ]);

        return $tipoConsulta;

    } catch (\Exception $e) {
        Log::error('❌ Error determinando tipo de consulta offline', [
            'error' => $e->getMessage()
        ]);
        
        return 'PRIMERA VEZ';
    }
}

/**
 * ✅ GENERAR MENSAJE DE TIPO DE CONSULTA
 */
private function generarMensajeTipoConsulta(string $tipoConsulta, string $procesoNombre): string
{
    if ($tipoConsulta === 'PRIMERA VEZ') {
        return "Esta será la primera consulta de {$procesoNombre} para este paciente.";
    } else {
        return "Esta será una consulta de control de {$procesoNombre} para este paciente.";
    }
}

    /**
     * ✅ CORREGIDO: Obtener citas existentes CON SEDE DE LA AGENDA
     */
    private function obtenerCitasExistentes(string $agendaUuid, string $fecha): array
    {
        try {
            Log::info('🔍 Obteniendo citas existentes para agenda (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fecha
            ]);

            // ✅ PASO 1: OBTENER LA AGENDA PRIMERO PARA SABER SU SEDE
            $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
            
            if (!$agenda) {
                Log::warning('⚠️ Agenda no encontrada offline, intentando desde API');
                
                // Si no está offline, intentar desde API
                if ($this->apiService->isOnline()) {
                    $response = $this->apiService->get("/agendas/{$agendaUuid}");
                    if ($response['success']) {
                        $agenda = $response['data'];
                    }
                }
            }
            
            if (!$agenda) {
                Log::error('❌ No se pudo obtener la agenda para determinar la sede', [
                    'agenda_uuid' => $agendaUuid
                ]);
                return [];
            }
            
            // ✅ PASO 2: USAR LA SEDE DE LA AGENDA (NO DEL USUARIO)
            $sedeAgenda = $agenda['sede_id'];
            
            Log::info('✅ Agenda encontrada, usando su sede (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'sede_agenda' => $sedeAgenda,
                'usuario_sede' => $this->authService->usuario()['sede_id'] ?? 'N/A' // Solo para comparar
            ]);
            
            // ✅ PASO 3: EXTRAER FECHA LIMPIA
            $fechaLimpia = $fecha;
            if (strpos($fecha, 'T') !== false) {
                $fechaLimpia = explode('T', $fecha)[0];
            }
            
            // ✅ PASO 4: OBTENER CITAS CON LA SEDE CORRECTA
            $filters = [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fechaLimpia
            ];
            
            $citas = $this->offlineService->getCitasOffline($sedeAgenda, $filters); // ← CAMBIO CRÍTICO
            
            // ✅ PASO 5: SI ESTAMOS ONLINE, TAMBIÉN VERIFICAR API
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get("/agendas/{$agendaUuid}/citas", [
                        'fecha' => $fechaLimpia
                    ]);
                    
                    if ($response['success'] && isset($response['data'])) {
                        $citasApi = $response['data'];
                        $uuidsOffline = array_column($citas, 'uuid');
                        
                        foreach ($citasApi as $citaApi) {
                            if (!in_array($citaApi['uuid'], $uuidsOffline)) {
                                $citas[] = $citaApi;
                                // También guardar offline
                                $this->offlineService->storeCitaOffline($citaApi, false);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error sincronizando citas desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // ✅ PASO 6: FILTRAR SOLO CITAS ACTIVAS
            $citasActivas = array_filter($citas, function($cita) {
                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
            });
            
            Log::info('📊 Citas existentes obtenidas (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'sede_agenda' => $sedeAgenda,
                'fecha_consulta' => $fechaLimpia,
                'total_citas' => count($citas),
                'citas_activas' => count($citasActivas)
            ]);
            
            return $citasActivas;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo citas existentes (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fecha,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

        // ✅ NUEVO: Obtener paciente en horario específico
        private function obtenerPacienteEnHorario(array $citas, string $hora): ?string
        {
            foreach ($citas as $cita) {
                $horaCita = date('H:i', strtotime($cita['fecha_inicio']));
                if ($horaCita === $hora) {
                    return $cita['paciente']['nombre_completo'] ?? 'Paciente no identificado';
                }
            }
            
            return null;
        }

        // ✅ NUEVO: Obtener citas pendientes de sincronización
    public function getPendientesSync(Request $request)
    {
        try {
            Log::info('🔍 Obteniendo citas pendientes de sincronización');
            
            $pendingCount = $this->offlineService->getPendingSyncCount();
            
            Log::info('📊 Conteo de pendientes obtenido', [
                'citas_pendientes' => $pendingCount['citas'] ?? 0,
                'total_pendientes' => $pendingCount['total'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'pending_count' => $pendingCount['citas'] ?? 0,
                'total_pending' => $pendingCount['total'] ?? 0,
                'details' => $pendingCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo citas pendientes', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo citas pendientes: ' . $e->getMessage(),
                'pending_count' => 0
            ], 500);
        }
    }

    // ✅ NUEVO: Sincronizar citas pendientes CON MANEJO ESPECÍFICO DE CUPS
    public function sincronizarPendientes(Request $request)
    {
        try {
            Log::info('🔄 Iniciando sincronización de citas pendientes');
            
            // ✅ VERIFICAR CONEXIÓN
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin conexión al servidor',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }
            
            // ✅ VERIFICAR TOKEN
            if (!$this->authService->hasValidToken()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token de autenticación inválido',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }
            
            // ✅ EJECUTAR SINCRONIZACIÓN
            $result = $this->offlineService->syncPendingCitas();
            
            Log::info('✅ Sincronización de citas completada', [
                'success' => $result['success'] ?? 0,
                'errors' => $result['errors'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada',
                'synced_count' => $result['success'] ?? 0,
                'failed_count' => $result['errors'] ?? 0,
                'details' => $result['details'] ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error sincronizando citas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
                'synced_count' => 0,
                'failed_count' => 0
            ], 500);
        }
    }

    // ✅ NUEVO: Obtener estado de sincronización
    public function getSyncStatus(Request $request)
    {
        try {
            $stats = $this->offlineService->getPendingSyncCount();
            $isOnline = $this->apiService->isOnline();
            $hasToken = $this->authService->hasValidToken();
            
            return response()->json([
                'success' => true,
                'is_online' => $isOnline,
                'has_valid_token' => $hasToken,
                'can_sync' => $isOnline && $hasToken,
                'pending_stats' => $stats,
                'last_check' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo estado de sync', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estado: ' . $e->getMessage()
            ], 500);
        }
    }
    }
