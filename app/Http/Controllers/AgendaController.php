<?php
// app/Http/Controllers/AgendaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AgendaService, AuthService, ApiService, OfflineService};
use Illuminate\Support\Facades\Log;

class AgendaController extends Controller
{
    protected $agendaService;
    protected $authService;
    protected $apiService;
    protected $offlineService;

    public function __construct(AgendaService $agendaService, AuthService $authService, ApiService $apiService, OfflineService $offlineService)
    {
        $this->middleware('custom.auth');
        $this->agendaService = $agendaService;
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'fecha_desde', 'fecha_hasta', 'estado', 'modalidad', 'consultorio'
            ]);
            
            $page = $request->get('page', 1);
            
            Log::info('AgendaController@index', [
                'filters' => $filters,
                'page' => $page,
                'is_ajax' => $request->ajax()
            ]);

            $result = $this->agendaService->index($filters, $page);

            if ($request->ajax()) {
                return response()->json($result);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();

            return view('agendas.index', compact('usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }

            return back()->with('error', 'Error cargando agendas');
        }
    }

    public function create()
    {
        try {
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $masterData = $this->getMasterData();

            return view('agendas.create', compact('usuario', 'isOffline', 'masterData'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@create', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error cargando formulario de creación');
        }
    }

  public function store(Request $request)
{
    try {
        Log::info('🔍 AgendaController@store - Datos RAW recibidos', [
            'all_data' => $request->all(),
            'proceso_id_raw' => $request->input('proceso_id'),
            'brigada_id_raw' => $request->input('brigada_id')
        ]);

        // ✅ VALIDACIÓN BÁSICA
        $validatedData = $request->validate([
            'modalidad' => 'required|in:Telemedicina,Ambulatoria',
            'fecha' => 'required|date|after_or_equal:today',
            'consultorio' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
             'intervalo' => 'required|string|in:15,20,30,45,60', // ✅ CAMBIAR A INTEGER
            'etiqueta' => 'required|string|max:50',
            'proceso_id' => 'nullable|string|max:100',
            'brigada_id' => 'nullable|string|max:100',
        ]);

        // ✅ PROCESAR IDs CORRECTAMENTE
        $masterData = $this->getMasterData();
        
        // ✅ RESOLVER proceso_id
        if (!empty($validatedData['proceso_id']) && $validatedData['proceso_id'] !== '') {
            $resolvedProcesoId = $this->resolveProcesoId($validatedData['proceso_id'], $masterData);
            $validatedData['proceso_id'] = $resolvedProcesoId;
            
            Log::info('✅ proceso_id procesado', [
                'original' => $request->input('proceso_id'),
                'resolved' => $resolvedProcesoId
            ]);
        } else {
            $validatedData['proceso_id'] = null;
        }

        // ✅ RESOLVER brigada_id
        if (!empty($validatedData['brigada_id']) && $validatedData['brigada_id'] !== '') {
            $resolvedBrigadaId = $this->resolveBrigadaId($validatedData['brigada_id'], $masterData);
            $validatedData['brigada_id'] = $resolvedBrigadaId;
            
            Log::info('✅ brigada_id procesado', [
                'original' => $request->input('brigada_id'),
                'resolved' => $resolvedBrigadaId
            ]);
        } else {
            $validatedData['brigada_id'] = null;
        }

        // ✅ ASEGURAR TIPOS CORRECTOS
        $validatedData['intervalo'] = (string) $validatedData['intervalo'];
        
        Log::info('📤 Datos finales para guardar', [
            'proceso_id' => $validatedData['proceso_id'],
            'brigada_id' => $validatedData['brigada_id'],
            'intervalo' => $validatedData['intervalo'],
            'intervalo_type' => gettype($validatedData['intervalo'])
        ]);

        $result = $this->agendaService->store($validatedData);

        if ($request->ajax()) {
            $response = $result;
            if ($result['success']) {
                $response['redirect_url'] = route('agendas.index');
            }
            return response()->json($response);
        }

        if ($result['success']) {
            return redirect()->route('agendas.index')
                ->with('success', $result['message'] ?? 'Agenda creada exitosamente');
        }

        return back()
            ->withErrors(['error' => $result['error']])
            ->withInput();
            
    } catch (\Exception $e) {
        Log::error('💥 Error crítico en AgendaController@store', [
            'error' => $e->getMessage(),
            'data' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }

        return back()->with('error', 'Error interno del servidor')->withInput();
    }
}

    // ✅ NUEVO: Resolver proceso_id desde datos maestros
   private function resolveProcesoId($procesoValue, array $masterData): mixed
{
    if (empty($procesoValue) || $procesoValue === 'null' || $procesoValue === '') {
        return null;
    }

    Log::info('🔍 Resolviendo proceso_id', [
        'input_value' => $procesoValue,
        'input_type' => gettype($procesoValue),
        'has_procesos' => isset($masterData['procesos']),
        'procesos_count' => isset($masterData['procesos']) ? count($masterData['procesos']) : 0
    ]);

    // Si ya es numérico, devolverlo como entero
    if (is_numeric($procesoValue)) {
        return (int) $procesoValue;
    }

    // Si no hay procesos en datos maestros, devolver null
    if (!isset($masterData['procesos']) || empty($masterData['procesos'])) {
        Log::warning('⚠️ No hay procesos en datos maestros');
        return null;
    }

    // Buscar en datos maestros
    foreach ($masterData['procesos'] as $proceso) {
        Log::debug('🔍 Comparando proceso', [
            'proceso' => $proceso,
            'buscando' => $procesoValue
        ]);
        
        // Coincidencia por UUID
        if (isset($proceso['uuid']) && $proceso['uuid'] === $procesoValue) {
            // ✅ CAMBIO AQUÍ: Si no hay ID, usar el UUID como string
            if (isset($proceso['id']) && !empty($proceso['id'])) {
                $resultado = (int) $proceso['id'];
                Log::info('✅ Proceso encontrado por UUID con ID', [
                    'uuid' => $procesoValue,
                    'id_resuelto' => $resultado
                ]);
                return $resultado;
            } else {
                // ✅ NUEVO: Si no hay ID, devolver el UUID como string
                Log::info('✅ Proceso encontrado por UUID sin ID, usando UUID', [
                    'uuid' => $procesoValue
                ]);
                return $procesoValue; // Devolver el UUID original
            }
        }
        
        // Coincidencia exacta por ID
        if (isset($proceso['id']) && (string)$proceso['id'] === (string)$procesoValue) {
            Log::info('✅ Proceso encontrado por ID', [
                'id' => $procesoValue
            ]);
            return (int) $proceso['id'];
        }
    }

    Log::warning('⚠️ proceso_id no encontrado en datos maestros', [
        'proceso_value' => $procesoValue,
        'available_processes' => array_map(function($p) {
            return [
                'id' => $p['id'] ?? null, 
                'uuid' => $p['uuid'] ?? null, 
                'nombre' => $p['nombre'] ?? null
            ];
        }, $masterData['procesos'])
    ]);

    return null;
}

private function resolveBrigadaId($brigadaValue, array $masterData): mixed
{
    if (empty($brigadaValue) || $brigadaValue === 'null' || $brigadaValue === '') {
        return null;
    }

    Log::info('🔍 Resolviendo brigada_id', [
        'input_value' => $brigadaValue,
        'input_type' => gettype($brigadaValue),
        'has_brigadas' => isset($masterData['brigadas']),
        'brigadas_count' => isset($masterData['brigadas']) ? count($masterData['brigadas']) : 0
    ]);

    // Si ya es numérico, devolverlo como entero
    if (is_numeric($brigadaValue)) {
        return (int) $brigadaValue;
    }

    // Si no hay brigadas en datos maestros, devolver null
    if (!isset($masterData['brigadas']) || empty($masterData['brigadas'])) {
        Log::warning('⚠️ No hay brigadas en datos maestros');
        return null;
    }

    // Buscar en datos maestros
    foreach ($masterData['brigadas'] as $brigada) {
        Log::debug('🔍 Comparando brigada', [
            'brigada' => $brigada,
            'buscando' => $brigadaValue
        ]);
        
        // Coincidencia por UUID
       if (isset($brigada['uuid']) && $brigada['uuid'] === $brigadaValue) {
    // ✅ CAMBIO: Si no hay ID, usar el UUID como string
    if (isset($brigada['id']) && !empty($brigada['id'])) {
        $resultado = (int) $brigada['id'];
        Log::info('✅ Brigada encontrada por UUID con ID', [
            'uuid' => $brigadaValue,
            'id_resuelto' => $resultado
        ]);
        return $resultado;
    } else {
        // ✅ NUEVO: Si no hay ID, devolver el UUID como string
        Log::info('✅ Brigada encontrada por UUID sin ID, usando UUID', [
            'uuid' => $brigadaValue
        ]);
        return $brigadaValue; // Devolver el UUID original
    }
}
        
        // Coincidencia exacta por ID
        if (isset($brigada['id']) && (string)$brigada['id'] === (string)$brigadaValue) {
            Log::info('✅ Brigada encontrada por ID', [
                'id' => $brigadaValue
            ]);
            return (int) $brigada['id'];
        }
    }

    Log::warning('⚠️ brigada_id no encontrado en datos maestros', [
        'brigada_value' => $brigadaValue,
        'available_brigades' => array_map(function($b) {
            return [
                'id' => $b['id'] ?? null, 
                'uuid' => $b['uuid'] ?? null, 
                'nombre' => $b['nombre'] ?? null
            ];
        }, $masterData['brigadas'])
    ]);

    return null;
}

    // ... resto de métodos sin cambios

    public function show(string $uuid)
    {
        try {
            $result = $this->agendaService->show($uuid);

            if (!$result['success']) {
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $agenda = $result['data'];

            return view('agendas.show', compact('agenda', 'usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@show', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error interno del servidor');
        }
    }

    public function edit(string $uuid)
    {
        try {
            $result = $this->agendaService->show($uuid);

            if (!$result['success']) {
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $agenda = $result['data'];
            $masterData = $this->getMasterData();

            return view('agendas.edit', compact('agenda', 'usuario', 'isOffline', 'masterData'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@edit', [
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
                'modalidad' => 'required|in:Telemedicina,Ambulatoria',
                'fecha' => 'required|date|after_or_equal:today',
                'consultorio' => 'required|string|max:50',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'intervalo' => 'required|string|max:10',
                'etiqueta' => 'required|string|max:50',
                'estado' => 'nullable|in:ACTIVO,ANULADA,LLENA',
                'proceso_id' => 'nullable|string|max:100',
                'brigada_id' => 'nullable|string|max:100',
            ]);

            $result = $this->agendaService->update($uuid, $validatedData);

            if ($request->ajax()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('agendas.show', $uuid)
                    ->with('success', $result['message'] ?? 'Agenda actualizada exitosamente');
            }

            return back()
                ->withErrors(['error' => $result['error']])
                ->withInput();
                
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@update', [
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
            $result = $this->agendaService->destroy($uuid);
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@destroy', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function disponibles(Request $request)
    {
        try {
            $filters = $request->only(['modalidad', 'proceso_id', 'fecha_desde']);
            $result = $this->agendaService->disponibles($filters);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@disponibles', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

  private function getMasterData(): array
{
    try {
        Log::info('🔍 Obteniendo datos maestros para formulario');
        
        // ✅ SIEMPRE INTENTAR ACTUALIZAR DESDE API SI HAY CONEXIÓN
        if ($this->apiService->isOnline()) {
            try {
                Log::info('🌐 Intentando obtener datos maestros desde API');
                
                $response = $this->apiService->get('/master-data/all');
                
                if ($response['success'] && isset($response['data'])) {
                    Log::info('✅ Datos maestros obtenidos desde API', [
                        'tables_count' => count($response['data']),
                        'procesos_count' => count($response['data']['procesos'] ?? []),
                        'brigadas_count' => count($response['data']['brigadas'] ?? [])
                    ]);
                    
                    // ✅ SINCRONIZAR OFFLINE INMEDIATAMENTE
                    $this->offlineService->syncMasterDataFromApi($response['data']);
                    
                    return $response['data'];
                } else {
                    Log::warning('⚠️ Respuesta de API inválida para datos maestros');
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo datos maestros desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::info('📱 Sin conexión, usando datos offline');
        }
        
        // ✅ USAR DATOS OFFLINE
        if ($this->offlineService->hasMasterDataOffline()) {
            $offlineData = $this->offlineService->getMasterDataOffline();
            
            Log::info('📱 Usando datos maestros offline', [
                'tables_count' => count($offlineData),
                'procesos_count' => count($offlineData['procesos'] ?? []),
                'brigadas_count' => count($offlineData['brigadas'] ?? [])
            ]);
            
            return $offlineData;
        }
        
        Log::warning('⚠️ No hay datos maestros disponibles, usando defaults');
        return $this->getDefaultMasterData();
        
    } catch (\Exception $e) {
        Log::error('❌ Error crítico obteniendo datos maestros', [
            'error' => $e->getMessage()
        ]);
        
        return $this->getDefaultMasterData();
    }
}

// ✅ MEJORAR DATOS POR DEFECTO
private function getDefaultMasterData(): array
{
    return [
        'procesos' => [
            ['id' => 1, 'uuid' => 'proc-consulta-general', 'nombre' => 'Consulta General', 'n_cups' => '890201'],
            ['id' => 2, 'uuid' => 'proc-control-prenatal', 'nombre' => 'Control Prenatal', 'n_cups' => '890301'],
            ['id' => 3, 'uuid' => 'proc-planificacion', 'nombre' => 'Planificación Familiar', 'n_cups' => '890401']
        ],
        'brigadas' => [
            ['id' => 1, 'uuid' => 'bri-general', 'nombre' => 'Brigada General'],
            ['id' => 2, 'uuid' => 'bri-especializada', 'nombre' => 'Brigada Especializada'],
            ['id' => 3, 'uuid' => 'bri-rural', 'nombre' => 'Brigada Rural']
        ],
        'modalidades' => [
            'Telemedicina' => 'Telemedicina',
            'Ambulatoria' => 'Ambulatoria'
        ],
        'estados' => [
            'ACTIVO' => 'Activo',
            'ANULADA' => 'Anulada',
            'LLENA' => 'Llena'
        ]
    ];
}

    // ... resto de métodos sin cambios (syncPending, pendingCount, etc.)

    public function syncPending(Request $request)
    {
        try {
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay conexión a internet para sincronizar'
                ]);
            }

            // Sincronizar agendas
            $agendasResult = $this->offlineService->syncPendingAgendas();
            
            // Sincronizar citas
            $citasResult = $this->offlineService->syncPendingCitas();

            $totalSuccess = $agendasResult['success'] + $citasResult['success'];
            $totalErrors = $agendasResult['errors'] + $citasResult['errors'];

            $message = "Sincronización completada: {$totalSuccess} exitosos";
            if ($totalErrors > 0) {
                $message .= ", {$totalErrors} errores";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'agendas' => $agendasResult,
                    'citas' => $citasResult,
                    'totals' => [
                        'success' => $totalSuccess,
                        'errors' => $totalErrors
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en sincronización manual', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pendingCount(Request $request)
    {
        try {
            $counts = $this->offlineService->getPendingSyncCount();
            
            return response()->json([
                'success' => true,
                'data' => $counts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function syncPendingAgendas(Request $request)
    {
        try {
            Log::info('🔄 Iniciando sincronización de agendas desde controlador');

            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin conexión al servidor',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }

            $result = $this->offlineService->syncPendingAgendas();

            Log::info('🏁 Resultado de sincronización de agendas', [
                'synced_count' => $result['synced_count'] ?? 0,
                'failed_count' => $result['failed_count'] ?? 0
            ]);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en sincronización de agendas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en sincronización: ' . $e->getMessage(),
                'synced_count' => 0,
                'failed_count' => 0
            ], 500);
        }
    }

    public function testSyncManual(Request $request)
    {
        try {
            Log::info('🧪 Test de sincronización de agendas');

            $result = $this->offlineService->getTestSyncData(10);

            return response()->json([
                'success' => $result['success'],
                'pending_count' => $result['pending_count'] ?? 0,
                'total_count' => $result['total_count'] ?? 0,
                'error_count' => $result['error_count'] ?? 0,
                'data' => $result['data'] ?? [],
                'message' => $result['message'] ?? 'Test completado'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en test de agendas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'pending_count' => 0,
                'total_count' => 0
            ], 500);
        }
    }
}
