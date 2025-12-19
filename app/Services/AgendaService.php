<?php
// app/Services/AgendaService.php
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;  // ✅ AGREGAR
use Illuminate\Support\Facades\Http; // ✅ AGREGAR

class AgendaService
{
    private $baseUrl;
    protected $apiService;
    protected $authService;
    protected $offlineService;

    public function __construct(ApiService $apiService, AuthService $authService, OfflineService $offlineService)
    {
        $this->apiService = $apiService;
        $this->authService = $authService;
        $this->offlineService = $offlineService;
         $this->baseUrl = config('api.base_url', 'http://sidis.nacerparavivir.org/api/v1');
    }

    /**
     * ✅ LISTAR AGENDAS
     */
public function index(array $filters = [], int $page = 1, int $perPage = 15): array
{
    try {
        Log::info("📅 AgendaService::index - Iniciando", [
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'has_force_all' => isset($filters['force_all'])
        ]);

        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];

        // ✅ SI VIENE force_all, LIMPIAR TODOS LOS FILTROS
        if (isset($filters['force_all']) && $filters['force_all'] === 'true') {
            Log::info('🔄 FORZANDO CARGA DE TODAS LAS AGENDAS');
            $filters = [];
        }

        // ✅ LÓGICA OPTIMIZADA: SINCRONIZACIÓN MÁS LIGERA
        if ($this->apiService->isOnline()) {
            try {
                // ✅ VERIFICAR SI ES PRIMERA VEZ (sin agendas locales)
                $needsInitialSync = $this->needsInitialSync($sedeId);
                
                if ($needsInitialSync) {
                    Log::info('🔄 Primera carga - Sincronizando agendas recientes');
                    // ✅ Solo sincronizar en background si realmente no hay datos
                    $fullSyncResult = $this->performFullSyncBackground($sedeId, $filters);
                    
                    if ($fullSyncResult['success']) {
                        Log::info('✅ Sincronización inicial completada', [
                            'total_synced' => $fullSyncResult['total_synced']
                        ]);
                        
                        // ✅ MARCAR COMO SINCRONIZADO
                        $this->markFullSyncComplete($sedeId);
                    }
                }

                // ✅ DESPUÉS DE LA SYNC COMPLETA, OBTENER PÁGINA ACTUAL DESDE API
                $apiParams = array_merge($filters, [
                    'page' => $page,
                    'per_page' => $perPage,
                    'sede_id' => $sedeId,
                    'sort_by' => 'fecha',
                    'sort_order' => 'desc'
                ]);

                // ✅ LIMPIAR PARÁMETROS VACÍOS
                $apiParams = array_filter($apiParams, function($value, $key) {
                    if ($key === 'force_all') return false;
                    return !empty($value) && $value !== '' && $value !== null;
                }, ARRAY_FILTER_USE_BOTH);

                $response = $this->apiService->get('/agendas', $apiParams);

                if ($response['success'] && isset($response['data'])) {
                    $responseData = $response['data'];
                    
                    // ✅ MANEJAR ESTRUCTURA DE PAGINACIÓN DE LARAVEL
                    if (isset($responseData['data'])) {
                        $agendas = $responseData['data'];
                        $meta = [
                            'current_page' => $responseData['current_page'] ?? $page,
                            'last_page' => $responseData['last_page'] ?? 1,
                            'per_page' => $responseData['per_page'] ?? $perPage,
                            'total' => $responseData['total'] ?? count($agendas),
                            'from' => $responseData['from'] ?? null,
                            'to' => $responseData['to'] ?? null
                        ];
                    } else {
                        $agendas = $responseData;
                        $meta = [
                            'current_page' => $page,
                            'last_page' => 1,
                            'per_page' => $perPage,
                            'total' => count($agendas)
                        ];
                    }

                    // ✅ SINCRONIZAR PÁGINA ACTUAL OFFLINE (actualizar datos recientes)
                    if (!empty($agendas)) {
                        foreach ($agendas as $agenda) {
                            $this->offlineService->storeAgendaOffline($agenda, false);
                        }
                    }

                    return [
                        'success' => true,
                        'data' => $agendas,
                        'meta' => $meta,
                        'pagination' => $meta,
                        'current_page' => $meta['current_page'],
                        'total_pages' => $meta['last_page'],
                        'total_items' => $meta['total'],
                        'per_page' => $meta['per_page'],
                        'has_more_pages' => $meta['current_page'] < $meta['last_page'],
                        'message' => '✅ Agendas actualizadas desde el servidor',
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error conectando con API agendas', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ OBTENER DATOS OFFLINE CON PAGINACIÓN MEJORADA
        $result = $this->getAgendasOfflinePaginated($sedeId, $filters, $page, $perPage);
        
        return array_merge($result, [
            'message' => '📱 Trabajando en modo offline - Datos locales',
            'offline' => true
        ]);

    } catch (\Exception $e) {
        Log::error('💥 Error en AgendaService::index', [
            'error' => $e->getMessage(),
            'filters' => $filters
        ]);

        return [
            'success' => true,
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0
            ],
            'current_page' => $page,
            'total_pages' => 1,
            'total_items' => 0,
            'per_page' => $perPage,
            'has_more_pages' => false,
            'message' => '❌ Error cargando agendas: ' . $e->getMessage(),
            'offline' => true
        ];
    }
}

/**
 * ✅ OPTIMIZADO: Verificar si necesita sincronización inicial (solo primera vez)
 */
private function needsInitialSync(int $sedeId): bool
{
    try {
        // ✅ SOLO VERIFICAR SI HAY AGENDAS LOCALES
        if ($this->offlineService->isSQLiteAvailable()) {
            $count = DB::connection('offline')
                ->table('agendas')
                ->where('sede_id', $sedeId)
                ->whereNull('deleted_at')
                ->count();
            
            // ✅ SI NO HAY AGENDAS, NECESITA SYNC INICIAL
            if ($count === 0) {
                Log::info('📊 No hay agendas offline, necesita sync inicial');
                return true;
            }
            
            Log::info('📊 Ya hay agendas offline, sync no necesario', [
                'agendas_count' => $count
            ]);
            return false;
        }
        
        // ✅ SI NO HAY SQLite, necesita sync
        return true;
        
    } catch (\Exception $e) {
        Log::error('❌ Error verificando necesidad de sync inicial', [
            'error' => $e->getMessage()
        ]);
        return false; // En caso de error, NO hacer sync para evitar demoras
    }
}

/**
 * ✅ NUEVO: Realizar sincronización completa en segundo plano
 */
private function performFullSyncBackground(int $sedeId, array $baseFilters = []): array
{
    try {
        Log::info('🔄 INICIANDO SINCRONIZACIÓN COMPLETA AUTOMÁTICA');
        
        $totalSynced = 0;
        $currentPage = 1;
        $perPage = 50; // ✅ REDUCIDO PARA CARGA MÁS RÁPIDA
        $hasMorePages = true;
        $maxPages = 10; // ✅ REDUCIDO: Solo últimas 500 agendas en primera carga
        
        // ✅ FILTROS BASE PARA OBTENER TODAS LAS AGENDAS
        $baseParams = [
            'sede_id' => $sedeId,
            'sort_by' => 'fecha',
            'sort_order' => 'desc',
            'per_page' => $perPage
        ];
        
        // ✅ AGREGAR FILTROS BÁSICOS PARA PRIMERA CARGA (último mes para carga rápida)
        $baseParams['fecha_desde'] = now()->subMonth()->format('Y-m-d');
        
        while ($hasMorePages && $currentPage <= $maxPages) {
            try {
                Log::info("📄 Sincronizando página {$currentPage} de agendas");
                
                $params = array_merge($baseParams, ['page' => $currentPage]);
                $response = $this->apiService->get('/agendas', $params);
                
                if (!$response['success'] || !isset($response['data'])) {
                    Log::warning("⚠️ Error en página {$currentPage}", [
                        'error' => $response['error'] ?? 'Sin datos'
                    ]);
                    break;
                }
                
                $responseData = $response['data'];
                
                // ✅ MANEJAR ESTRUCTURA DE PAGINACIÓN
                if (isset($responseData['data'])) {
                    $agendas = $responseData['data'];
                    $currentPage = $responseData['current_page'] ?? $currentPage;
                    $lastPage = $responseData['last_page'] ?? 1;
                    $hasMorePages = $currentPage < $lastPage;
                } else {
                    $agendas = $responseData;
                    $hasMorePages = false; // No hay paginación
                }
                
                // ✅ GUARDAR AGENDAS OFFLINE
                if (!empty($agendas)) {
                    foreach ($agendas as $agenda) {
                        $this->offlineService->storeAgendaOffline($agenda, false);
                        $totalSynced++;
                    }
                    
                    Log::info("✅ Página {$currentPage} sincronizada", [
                        'agendas_in_page' => count($agendas),
                        'total_synced' => $totalSynced
                    ]);
                } else {
                    Log::info("📄 Página {$currentPage} vacía");
                    $hasMorePages = false;
                }
                
                $currentPage++;
                
                // ✅ PEQUEÑA PAUSA PARA NO SOBRECARGAR LA API
                usleep(50000); // 0.05 segundos
                
            } catch (\Exception $e) {
                Log::error("❌ Error en página {$currentPage}", [
                    'error' => $e->getMessage()
                ]);
                break;
            }
        }
        
        Log::info('🏁 SINCRONIZACIÓN COMPLETA AUTOMÁTICA FINALIZADA', [
            'total_synced' => $totalSynced,
            'pages_processed' => $currentPage - 1,
            'max_pages_reached' => $currentPage > $maxPages
        ]);
        
        return [
            'success' => true,
            'total_synced' => $totalSynced,
            'pages_processed' => $currentPage - 1
        ];
        
    } catch (\Exception $e) {
        Log::error('💥 Error en sincronización completa automática', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'total_synced' => $totalSynced ?? 0
        ];
    }
}

/**
 * ✅ NUEVO: Marcar sincronización completa como realizada
 */
private function markFullSyncComplete(int $sedeId): void
{
    try {
        $syncStatus = [
            'sede_id' => $sedeId,
            'last_full_sync' => now()->toISOString(),
            'sync_type' => 'full_auto',
            'completed_at' => now()->toISOString()
        ];
        
        $this->offlineService->storeData('full_sync_status.json', $syncStatus);
        
        Log::info('✅ Sincronización completa automática marcada como completada', [
            'sede_id' => $sedeId,
            'timestamp' => $syncStatus['last_full_sync']
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error marcando sync completo automático', [
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * ✅ MÉTODO PÚBLICO: Forzar sincronización completa manual
 */
public function forceFullSync(int $sedeId): array
{
    try {
        Log::info('🔄 FORZANDO SINCRONIZACIÓN COMPLETA MANUAL');
        
        // ✅ LIMPIAR AGENDAS EXISTENTES PARA EMPEZAR LIMPIO
        if ($this->offlineService->isSQLiteAvailable()) {
            DB::connection('offline')
                ->table('agendas')
                ->where('sede_id', $sedeId)
                ->delete();
            
            Log::info('🗑️ Agendas offline limpiadas para sync completo manual');
        }
        
        // ✅ REALIZAR SINCRONIZACIÓN COMPLETA MANUAL (SIN LÍMITES)
        $result = $this->performFullSyncManual($sedeId, []);
        
        if ($result['success']) {
            // ✅ MARCAR COMO COMPLETADO
            $this->markFullSyncComplete($sedeId);
        }
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error('💥 Error en forzar sync completo manual', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'total_synced' => 0
        ];
    }
}

/**
 * ✅ NUEVO: Sincronización completa manual (sin límites)
 */
private function performFullSyncManual(int $sedeId, array $baseFilters = []): array
{
    try {
        Log::info('🔄 INICIANDO SINCRONIZACIÓN COMPLETA MANUAL');
        
        $totalSynced = 0;
        $currentPage = 1;
        $perPage = 100;
        $hasMorePages = true;
        
        // ✅ FILTROS BASE PARA OBTENER TODAS LAS AGENDAS (SIN LÍMITE DE FECHA)
        $baseParams = [
            'sede_id' => $sedeId,
            'sort_by' => 'fecha',
            'sort_order' => 'desc',
            'per_page' => $perPage
        ];
        
        while ($hasMorePages) {
            try {
                Log::info("📄 Sincronizando página {$currentPage} (manual)");
                
                $params = array_merge($baseParams, ['page' => $currentPage]);
                $response = $this->apiService->get('/agendas', $params);
                
                if (!$response['success'] || !isset($response['data'])) {
                    Log::warning("⚠️ Error en página {$currentPage}", [
                        'error' => $response['error'] ?? 'Sin datos'
                    ]);
                    break;
                }
                
                $responseData = $response['data'];
                
                if (isset($responseData['data'])) {
                    $agendas = $responseData['data'];
                    $currentPage = $responseData['current_page'] ?? $currentPage;
                    $lastPage = $responseData['last_page'] ?? 1;
                    $hasMorePages = $currentPage < $lastPage;
                } else {
                    $agendas = $responseData;
                    $hasMorePages = false;
                }
                
                if (!empty($agendas)) {
                    foreach ($agendas as $agenda) {
                        $this->offlineService->storeAgendaOffline($agenda, false);
                        $totalSynced++;
                    }
                    
                    Log::info("✅ Página {$currentPage} sincronizada (manual)", [
                        'agendas_in_page' => count($agendas),
                        'total_synced' => $totalSynced
                    ]);
                } else {
                    Log::info("📄 Página {$currentPage} vacía");
                    $hasMorePages = false;
                }
                
                $currentPage++;
                
                // ✅ LÍMITE DE SEGURIDAD PARA MANUAL
                if ($currentPage > 200) {
                    Log::warning('⚠️ Límite de páginas alcanzado (200) en sync manual');
                    break;
                }
                
                usleep(100000); // 0.1 segundos
                
            } catch (\Exception $e) {
                Log::error("❌ Error en página {$currentPage} (manual)", [
                    'error' => $e->getMessage()
                ]);
                break;
            }
        }
        
        Log::info('🏁 SINCRONIZACIÓN COMPLETA MANUAL FINALIZADA', [
            'total_synced' => $totalSynced,
            'pages_processed' => $currentPage - 1
        ]);
        
        return [
            'success' => true,
            'total_synced' => $totalSynced,
            'pages_processed' => $currentPage - 1
        ];
        
    } catch (\Exception $e) {
        Log::error('💥 Error en sincronización completa manual', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'total_synced' => $totalSynced ?? 0
        ];
    }
}

    private function getAgendasOfflinePaginated(int $sedeId, array $filters, int $page, int $perPage): array
    {
        try {
            $allAgendas = [];

            // ✅ CORREGIDO: Usar $this->offlineService->isSQLiteAvailable()
            if ($this->offlineService->isSQLiteAvailable()) {
                Log::info('📱 Usando SQLite para paginación offline');
                
                $query = DB::connection('offline')->table('agendas')
                    ->where('sede_id', $sedeId)
                    ->whereNull('deleted_at');

                // Aplicar filtros
                if (!empty($filters['fecha_desde'])) {
                    $query->where('fecha', '>=', $filters['fecha_desde']);
                }
                if (!empty($filters['fecha_hasta'])) {
                    $query->where('fecha', '<=', $filters['fecha_hasta']);
                }
                if (!empty($filters['estado'])) {
                    $query->where('estado', $filters['estado']);
                }
                if (!empty($filters['modalidad'])) {
                    $query->where('modalidad', $filters['modalidad']);
                }
                if (!empty($filters['consultorio'])) {
                    $query->where('consultorio', 'like', '%' . $filters['consultorio'] . '%');
                }

                // ✅ ORDENAMIENTO MÚLTIPLE: FECHA DESC, HORA DESC (MÁS NUEVAS PRIMERO)
                $query->orderBy('fecha', 'desc')
                      ->orderBy('hora_inicio', 'desc')
                      ->orderBy('created_at', 'desc'); // Como criterio adicional

                // ✅ OBTENER TOTAL ANTES DE PAGINAR
                $total = $query->count();

                // ✅ APLICAR PAGINACIÓN
                $offset = ($page - 1) * $perPage;
                $results = $query->skip($offset)->take($perPage)->get();

                $allAgendas = $results->map(function($agenda) {
                    $agendaArray = (array) $agenda;
                    
                    // ✅ CALCULAR CUPOS DISPONIBLES
                    $agendaArray = $this->enrichAgendaWithCupos($agendaArray);
                    
                    return $agendaArray;
                })->toArray();

                Log::info('✅ SQLite: Obtenidas agendas paginadas', [
                    'total' => $total,
                    'returned' => count($allAgendas),
                    'page' => $page,
                    'per_page' => $perPage
                ]);

            } else {
                Log::info('📁 Usando archivos JSON para paginación offline');
                
                // ✅ FALLBACK A JSON CON ORDENAMIENTO MEJORADO
                $agendasPath = $this->offlineService->getStoragePath() . '/agendas';
                if (is_dir($agendasPath)) {
                    $files = glob($agendasPath . '/*.json');
                    foreach ($files as $file) {
                        $data = json_decode(file_get_contents($file), true);
                        if ($data && 
                            $data['sede_id'] == $sedeId && 
                            empty($data['deleted_at']) &&
                            $this->matchesFilters($data, $filters)) {
                            
                            // ✅ ENRIQUECER CON CUPOS
                            $data = $this->enrichAgendaWithCupos($data);
                            $allAgendas[] = $data;
                        }
                    }
                }

                // ✅ ORDENAR POR FECHA Y HORA (MÁS NUEVAS PRIMERO)
                usort($allAgendas, function($a, $b) {
                    // Comparar por fecha primero
                    $fechaComparison = strtotime($b['fecha']) - strtotime($a['fecha']);
                    if ($fechaComparison !== 0) {
                        return $fechaComparison;
                    }
                    
                    // Si las fechas son iguales, comparar por hora
                    $horaA = strtotime($a['hora_inicio'] ?? '00:00');
                    $horaB = strtotime($b['hora_inicio'] ?? '00:00');
                    return $horaB - $horaA;
                });

                $total = count($allAgendas);
                
                // ✅ APLICAR PAGINACIÓN MANUAL
                $offset = ($page - 1) * $perPage;
                $allAgendas = array_slice($allAgendas, $offset, $perPage);
                
                Log::info('✅ JSON: Obtenidas agendas paginadas', [
                    'total' => $total,
                    'returned' => count($allAgendas),
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            }

            // ✅ CALCULAR METADATOS DE PAGINACIÓN
            $totalPages = ceil($total / $perPage);
            $from = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
            $to = $total > 0 ? min($page * $perPage, $total) : null;

            $meta = [
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $from,
                'to' => $to
            ];

            Log::info('✅ Paginación offline completada', [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'returned_items' => count($allAgendas)
            ]);

            return [
                'success' => true,
                'data' => $allAgendas,
                'meta' => $meta,
                'pagination' => $meta,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'per_page' => $perPage,
                'has_more_pages' => $page < $totalPages
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error en paginación offline', [
                'error' => $e->getMessage(),
                'sede_id' => $sedeId,
                'page' => $page,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0
                ],
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0
                ],
                'current_page' => $page,
                'total_pages' => 1,
                'total_items' => 0,
                'per_page' => $perPage,
                'has_more_pages' => false
            ];
        }
    }

 private function enrichAgendaWithCupos(array $agenda): array
{
    try {
        if (empty($agenda['hora_inicio']) || empty($agenda['hora_fin'])) {
            $agenda['total_cupos'] = 0;
            $agenda['citas_count'] = 0;
            $agenda['cupos_disponibles'] = 0;
            return $agenda;
        }

        // Calcular cupos totales
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        if ($intervalo <= 0) $intervalo = 15;
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $totalCupos = floor($duracionMinutos / $intervalo);
        
        // ✅ CONTAR CITAS REALES (MEJORADO)
        $citasCount = $this->contarCitasReales($agenda);
        
        $cuposDisponibles = max(0, $totalCupos - $citasCount);
        
        $agenda['total_cupos'] = $totalCupos;
        $agenda['citas_count'] = $citasCount;
        $agenda['cupos_disponibles'] = $cuposDisponibles;
        
        Log::info('✅ Cupos calculados correctamente', [
            'agenda_uuid' => $agenda['uuid'],
            'fecha' => $agenda['fecha'],
            'total_cupos' => $totalCupos,
            'citas_count' => $citasCount,
            'cupos_disponibles' => $cuposDisponibles
        ]);
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo agenda con cupos', [
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        
        // Valores por defecto en caso de error
        $agenda['total_cupos'] = 0;
        $agenda['citas_count'] = 0;
        $agenda['cupos_disponibles'] = 0;
        
        return $agenda;
    }
}

/**
 * ✅ CORREGIDO: Contar citas reales de una agenda - USAR CONEXIÓN 'offline'
 */
private function contarCitasReales(array $agenda): int
{
    try {
        $citasCount = 0;
        
        // ✅ EXTRAER FECHA LIMPIA
        $fechaAgenda = $agenda['fecha'];
        if (strpos($fechaAgenda, 'T') !== false) {
            $fechaAgenda = explode('T', $fechaAgenda)[0];
        }
        
        // ✅ USAR LA SEDE DE LA AGENDA
        $sedeAgenda = $agenda['sede_id'];
        
        Log::info('📊 Contando citas con sede de la agenda', [
            'agenda_uuid' => $agenda['uuid'],
            'sede_agenda' => $sedeAgenda,
            'fecha_agenda' => $fechaAgenda,
            'sqlite_available' => $this->offlineService->isSQLiteAvailable()
        ]);
        
        // ✅ INTENTAR DESDE API PRIMERO SI HAY CONEXIÓN
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$agenda['uuid']}/citas", [
                    'fecha' => $fechaAgenda
                ]);
                
                if ($response['success'] && isset($response['data'])) {
                    $citasApi = array_filter($response['data'], function($cita) {
                        return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
                    });
                    
                    $citasCount = count($citasApi);
                    
                    Log::info('📊 Citas contadas desde API', [
                        'agenda_uuid' => $agenda['uuid'],
                        'citas_count' => $citasCount
                    ]);
                    
                    return $citasCount;
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error contando citas desde API, usando offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // ✅ CONTAR DESDE OFFLINE - USAR CONEXIÓN 'offline' COMO EN TU OFFLINESERVICE
        if ($this->offlineService->isSQLiteAvailable()) {
            try {
                // ✅ USAR 'offline' EN LUGAR DE 'sqlite'
                $citasCount = DB::connection('offline')  // ← CAMBIO CRÍTICO
                    ->table('citas')
                    ->where('agenda_uuid', $agenda['uuid'])
                    ->where('fecha', $fechaAgenda)
                    ->where('sede_id', $sedeAgenda)
                    ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
                    ->whereNull('deleted_at')
                    ->count();
                    
                Log::info('📊 Citas contadas desde SQLite offline', [
                    'agenda_uuid' => $agenda['uuid'],
                    'fecha' => $fechaAgenda,
                    'sede_agenda' => $sedeAgenda,
                    'citas_count' => $citasCount
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Error contando desde SQLite offline, usando JSON fallback', [
                    'error' => $e->getMessage()
                ]);
                
                // ✅ FALLBACK A JSON
                $citas = $this->offlineService->getCitasOffline($sedeAgenda, [
                    'agenda_uuid' => $agenda['uuid'],
                    'fecha' => $fechaAgenda
                ]);
                
                $citasActivas = array_filter($citas, function($cita) {
                    return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
                });
                
                $citasCount = count($citasActivas);
                
                Log::info('📊 Citas contadas desde JSON fallback', [
                    'agenda_uuid' => $agenda['uuid'],
                    'citas_count' => $citasCount
                ]);
            }
        } else {
            // ✅ FALLBACK A ARCHIVOS JSON
            $citas = $this->offlineService->getCitasOffline($sedeAgenda, [
                'agenda_uuid' => $agenda['uuid'],
                'fecha' => $fechaAgenda
            ]);
            
            $citasActivas = array_filter($citas, function($cita) {
                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
            });
            
            $citasCount = count($citasActivas);
            
            Log::info('📊 Citas contadas desde JSON', [
                'agenda_uuid' => $agenda['uuid'],
                'fecha' => $fechaAgenda,
                'sede_agenda' => $sedeAgenda,
                'citas_count' => $citasCount
            ]);
        }
        
        return $citasCount;
        
    } catch (\Exception $e) {
        Log::error('❌ Error contando citas reales', [
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return 0;
    }
}


    /**
     * ✅ VERIFICAR SI LOS DATOS COINCIDEN CON LOS FILTROS
     */
    private function matchesFilters(array $data, array $filters): bool
    {
        if (!empty($filters['fecha_desde']) && $data['fecha'] < $filters['fecha_desde']) {
            return false;
        }
        if (!empty($filters['fecha_hasta']) && $data['fecha'] > $filters['fecha_hasta']) {
            return false;
        }
        if (!empty($filters['estado']) && $data['estado'] !== $filters['estado']) {
            return false;
        }
        if (!empty($filters['modalidad']) && $data['modalidad'] !== $filters['modalidad']) {
            return false;
        }
        if (!empty($filters['consultorio']) && 
            stripos($data['consultorio'] ?? '', $filters['consultorio']) === false) {
            return false;
        }
        
        return true;
    }
   /**
 * ✅ CREAR AGENDA
 */
public function store(array $data): array
{
    try {
        Log::info('🔍 AgendaService::store - Datos RAW recibidos', [
            'all_data' => $data,
            'proceso_id_raw' => $data['proceso_id'] ?? null,
            'brigada_id_raw' => $data['brigada_id'] ?? null,
            'usuario_medico_uuid_raw' => $data['usuario_medico_uuid'] ?? null
        ]);

        // ✅ VALIDACIÓN MANUAL DE DATOS
        $validated = $this->validateAgendaData($data);

        // ✅ PREPARAR DATOS PARA ALMACENAR
        $agendaData = [
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'modalidad' => $validated['modalidad'],
            'fecha' => $validated['fecha'],
            'consultorio' => $validated['consultorio'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'intervalo' => $validated['intervalo'],
            'etiqueta' => $validated['etiqueta'],
            'estado' => 'ACTIVO',
            'sede_id' => $validated['sede_id'],
            'usuario_id' => $validated['usuario_id'],
            'proceso_id' => $validated['proceso_id'] ?? null,
            'brigada_id' => $validated['brigada_id'] ?? null,
            'usuario_medico_id' => $validated['usuario_medico_uuid'] ?? null,
            'cupos_disponibles' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'sync_status' => 'pending'
        ];

        // ✅ VERIFICAR CONFLICTOS OFFLINE
        if ($this->hasScheduleConflict($agendaData)) {
            return [
                'success' => false,
                'message' => 'Ya existe una agenda activa en ese horario y consultorio',
                'error' => 'Conflicto de horarios'
            ];
        }

        // ✅ SI ESTAMOS ONLINE, INTENTAR CREAR DIRECTAMENTE
        if ($this->apiService->isOnline()) {
            try {
                $apiData = $this->prepareAgendaDataForApi($agendaData);
                $response = $this->apiService->post('/agendas', $apiData);
                
                Log::info('📥 Respuesta de API al crear agenda', [
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null,
                    'response_keys' => array_keys($response)
                ]);
                
                if ($response['success']) {
                    // ✅ ÉXITO - Actualizar con datos de la API
                    if (isset($response['data']['id'])) {
                        $agendaData['id'] = $response['data']['id'];
                    }
                    if (isset($response['data']['uuid'])) {
                        $agendaData['uuid'] = $response['data']['uuid']; // Usar UUID del servidor
                    }
                    $agendaData['sync_status'] = 'synced';
                    
                    // Guardar offline como respaldo
                    $this->offlineService->storeAgendaOffline($agendaData, false);
                    
                    // Enriquecer datos para respuesta
                    $enrichedData = $this->enrichAgendaDataForResponse($agendaData);
                    
                    Log::info('✅ Agenda creada exitosamente en API');
                    
                    return [
                        'success' => true,
                        'data' => $enrichedData,
                        'message' => 'Agenda creada exitosamente'
                    ];
                } else {
                    // ✅ ERROR DE LA API - Verificar si es error de validación
                    $errorMessage = $response['error'] ?? 'Error desconocido de la API';
                    
                    Log::error('❌ Error de la API al crear agenda', [
                        'error' => $errorMessage,
                        'response' => $response
                    ]);
                    
                    // ✅ SI ES ERROR DE VALIDACIÓN, NO GUARDAR OFFLINE
                    if (isset($response['status']) && $response['status'] == 422) {
                        return [
                            'success' => false,
                            'message' => 'Error de validación en el servidor',
                            'error' => $errorMessage
                        ];
                    }
                    
                    // ✅ SI ES OTRO ERROR, GUARDAR OFFLINE PARA SINCRONIZAR DESPUÉS
                    Log::warning('⚠️ Error de servidor, guardando offline para sincronizar después');
                    // Continuar para guardar offline
                }
                
            } catch (\Exception $e) {
                Log::error('❌ Excepción al conectar con API', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // ✅ ERROR DE CONEXIÓN - Guardar offline
                Log::warning('⚠️ Error de conexión, guardando offline');
                // Continuar para guardar offline
            }
        } else {
            Log::info('📱 Sin conexión, guardando offline directamente');
        }

        // ✅ GUARDAR OFFLINE (solo si llegamos aquí)
        $this->offlineService->storeAgendaOffline($agendaData, true); // true = needs sync

        // ✅ ENRIQUECER DATOS PARA RESPUESTA
        $enrichedData = $this->enrichAgendaDataForResponse($agendaData);

        return [
            'success' => true,
            'data' => $enrichedData,
            'message' => $this->apiService->isOnline() 
                ? 'Agenda guardada (se sincronizará automáticamente)' 
                : 'Agenda creada offline (se sincronizará cuando haya conexión)'
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico creando agenda', [
            'error' => $e->getMessage(),
            'input' => $data,
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'Error interno del servidor',
            'error' => $e->getMessage()
        ];
    }
}


/**
 * ✅ VALIDACIÓN MANUAL DE DATOS (SIN CAMBIOS)
 */
private function validateAgendaData(array $data): array
{
    $errors = [];
    
    // Validaciones requeridas
    if (empty($data['sede_id'])) {
        $errors['sede_id'] = 'El campo sede_id es requerido';
    }
    
    if (empty($data['modalidad']) || !in_array($data['modalidad'], ['Telemedicina', 'Ambulatoria'])) {
        $errors['modalidad'] = 'El campo modalidad debe ser Telemedicina o Ambulatoria';
    }
    
    if (empty($data['fecha'])) {
        $errors['fecha'] = 'El campo fecha es requerido';
    } elseif (strtotime($data['fecha']) < strtotime('today')) {
        $errors['fecha'] = 'La fecha debe ser hoy o posterior';
    }
    
    if (empty($data['consultorio'])) {
        $errors['consultorio'] = 'El campo consultorio es requerido';
    }
    
    if (empty($data['hora_inicio'])) {
        $errors['hora_inicio'] = 'El campo hora_inicio es requerido';
    }
    
    if (empty($data['hora_fin'])) {
        $errors['hora_fin'] = 'El campo hora_fin es requerido';
    } elseif (!empty($data['hora_inicio']) && $data['hora_fin'] <= $data['hora_inicio']) {
        $errors['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio';
    }
    
    if (empty($data['intervalo'])) {
        $errors['intervalo'] = 'El campo intervalo es requerido';
    }
    
    if (empty($data['etiqueta'])) {
        $errors['etiqueta'] = 'El campo etiqueta es requerido';
    }
    
    if (empty($data['usuario_id'])) {
        $errors['usuario_id'] = 'El campo usuario_id es requerido';
    }
    
    if (!empty($errors)) {
        throw new \Exception('Errores de validación: ' . json_encode($errors));
    }
    
    return $data;
}

/**
 * ✅ NUEVO: Verificar conflictos de horario offline
 */
private function hasScheduleConflict(array $agendaData): bool
{
    try {
        // Verificar en SQLite si está disponible
        if ($this->offlineService->isSQLiteAvailable()) {
            $conflict = DB::connection('offline')
                ->table('agendas')
                ->where('sede_id', $agendaData['sede_id'])
                ->where('consultorio', $agendaData['consultorio'])
                ->where('fecha', $agendaData['fecha'])
                ->where('estado', 'ACTIVO')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($agendaData) {
                    $query->whereBetween('hora_inicio', [$agendaData['hora_inicio'], $agendaData['hora_fin']])
                          ->orWhereBetween('hora_fin', [$agendaData['hora_inicio'], $agendaData['hora_fin']])
                          ->orWhere(function ($q) use ($agendaData) {
                              $q->where('hora_inicio', '<=', $agendaData['hora_inicio'])
                                ->where('hora_fin', '>=', $agendaData['hora_fin']);
                          });
                })
                ->exists();
                
            return $conflict;
        }
        
        // Fallback: verificar en archivos JSON
        $existingAgendas = $this->offlineService->getAgendasOffline($agendaData['sede_id'], [
            'fecha' => $agendaData['fecha'],
            'consultorio' => $agendaData['consultorio'],
            'estado' => 'ACTIVO'
        ]);
        
        foreach ($existingAgendas as $existing) {
            if ($this->hasTimeOverlap($agendaData, $existing)) {
                return true;
            }
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::warning('⚠️ Error verificando conflictos, continuando', [
            'error' => $e->getMessage()
        ]);
        return false; // En caso de error, permitir creación
    }
}

/**
 * ✅ NUEVO: Verificar solapamiento de horarios
 */
private function hasTimeOverlap(array $newAgenda, array $existingAgenda): bool
{
    $newStart = strtotime($newAgenda['hora_inicio']);
    $newEnd = strtotime($newAgenda['hora_fin']);
    $existingStart = strtotime($existingAgenda['hora_inicio']);
    $existingEnd = strtotime($existingAgenda['hora_fin']);
    
    return ($newStart < $existingEnd) && ($newEnd > $existingStart);
}



/**
 * ✅ NUEVO: Enriquecer datos para respuesta
 */
private function enrichAgendaDataForResponse(array $agendaData): array
{
    try {
        // Obtener datos maestros para enriquecer
        $masterData = $this->offlineService->getMasterDataOffline();
        
        // Enriquecer proceso
        if (!empty($agendaData['proceso_id']) && isset($masterData['procesos'])) {
            foreach ($masterData['procesos'] as $proceso) {
                if ($proceso['id'] == $agendaData['proceso_id'] || $proceso['uuid'] == $agendaData['proceso_id']) {
                    $agendaData['proceso'] = $proceso;
                    break;
                }
            }
        }
        
        // Enriquecer brigada
        if (!empty($agendaData['brigada_id']) && isset($masterData['brigadas'])) {
            foreach ($masterData['brigadas'] as $brigada) {
                if ($brigada['id'] == $agendaData['brigada_id'] || $brigada['uuid'] == $agendaData['brigada_id']) {
                    $agendaData['brigada'] = $brigada;
                    break;
                }
            }
        }
        
        // Enriquecer usuario médico
        if (!empty($agendaData['usuario_medico_id']) && isset($masterData['usuarios_con_especialidad'])) {
            foreach ($masterData['usuarios_con_especialidad'] as $usuario) {
                if ($usuario['id'] == $agendaData['usuario_medico_id'] || $usuario['uuid'] == $agendaData['usuario_medico_id']) {
                    $agendaData['usuario_medico'] = $usuario;
                    break;
                }
            }
        }
        
        // Datos por defecto para usuario y sede
        if (!isset($agendaData['usuario'])) {
            $currentUser = $this->authService->usuario();
            $agendaData['usuario'] = [
                'nombre_completo' => $currentUser['nombre_completo'] ?? 'Usuario del Sistema'
            ];
        }
        
        if (!isset($agendaData['sede'])) {
            $currentUser = $this->authService->usuario();
            $agendaData['sede'] = [
                'nombre' => $currentUser['sede']['nombre'] ?? 'Cajibio'
            ];
        }
        
        return $agendaData;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo datos de respuesta', [
            'error' => $e->getMessage()
        ]);
        
        return $agendaData;
    }
}



// ✅ NUEVO MÉTODO PARA VALIDAR Y LIMPIAR DATOS
private function validateAndCleanAgendaData(array $data): array
{
    Log::info('🧹 Limpiando datos de agenda', [
        'original_proceso_id' => $data['proceso_id'] ?? 'null',
        'original_brigada_id' => $data['brigada_id'] ?? 'null',
        'original_usuario_medico_uuid' => $data['usuario_medico_uuid'] ?? 'null'
    ]);

    // ✅ LIMPIAR proceso_id - ACEPTA ENTEROS Y UUIDs
    if (isset($data['proceso_id']) && $data['proceso_id'] !== null && $data['proceso_id'] !== '') {
        if (is_numeric($data['proceso_id']) && $data['proceso_id'] > 0) {
            // Es un ID numérico válido
            $data['proceso_id'] = (int) $data['proceso_id'];
            Log::info('✅ proceso_id válido (numérico)', ['proceso_id' => $data['proceso_id']]);
        } elseif (is_string($data['proceso_id']) && $this->isValidUuid($data['proceso_id'])) {
            // Es un UUID válido
            Log::info('✅ proceso_id válido (UUID)', ['proceso_id' => $data['proceso_id']]);
            // Mantener como string
        } else {
            // Es inválido, limpiar
            Log::warning('⚠️ proceso_id inválido, limpiando', ['proceso_id' => $data['proceso_id']]);
            $data['proceso_id'] = null;
        }
    } else {
        $data['proceso_id'] = null;
    }

    // ✅ LIMPIAR brigada_id - ACEPTA ENTEROS Y UUIDs
    if (isset($data['brigada_id']) && $data['brigada_id'] !== null && $data['brigada_id'] !== '') {
        if (is_numeric($data['brigada_id']) && $data['brigada_id'] > 0) {
            // Es un ID numérico válido
            $data['brigada_id'] = (int) $data['brigada_id'];
            Log::info('✅ brigada_id válido (numérico)', ['brigada_id' => $data['brigada_id']]);
        } elseif (is_string($data['brigada_id']) && $this->isValidUuid($data['brigada_id'])) {
            // Es un UUID válido
            Log::info('✅ brigada_id válido (UUID)', ['brigada_id' => $data['brigada_id']]);
            // Mantener como string
        } else {
            // Es inválido, limpiar
            Log::warning('⚠️ brigada_id inválido, limpiando', ['brigada_id' => $data['brigada_id']]);
            $data['brigada_id'] = null;
        }
    } else {
        $data['brigada_id'] = null;
    }

    // ✅ LIMPIAR usuario_medico_uuid
    if (isset($data['usuario_medico_uuid']) && $data['usuario_medico_uuid'] !== null && $data['usuario_medico_uuid'] !== '') {
        if (is_string($data['usuario_medico_uuid']) && $this->isValidUuid($data['usuario_medico_uuid'])) {
            Log::info('✅ usuario_medico_uuid válido (UUID)', ['usuario_medico_uuid' => $data['usuario_medico_uuid']]);
            // Mantener como string UUID
        } else {
            Log::warning('⚠️ usuario_medico_uuid inválido, limpiando', ['usuario_medico_uuid' => $data['usuario_medico_uuid']]);
            $data['usuario_medico_uuid'] = null;
        }
    } else {
        $data['usuario_medico_uuid'] = null;
    }

    Log::info('✅ Datos limpiados', [
        'clean_proceso_id' => $data['proceso_id'],
        'clean_brigada_id' => $data['brigada_id'],
        'clean_usuario_medico_uuid' => $data['usuario_medico_uuid'] 
    ]);

    return $data;
}

private function isValidUuid(string $uuid): bool
{
    // ✅ PATRÓN MÁS FLEXIBLE - No requiere versión 4 específica
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
}
    /**
     * ✅ MOSTRAR AGENDA
     */
   public function show(string $uuid): array
{
    try {
        // Intentar obtener online
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$uuid}");
                
                if ($response['success']) {
                    $agendaData = $response['data'];
                    $this->offlineService->storeAgendaOffline($agendaData, false);
                    
                    return [
                        'success' => true,
                        'data' => $agendaData,
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo agenda desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Buscar offline
        $agenda = $this->offlineService->getAgendaOffline($uuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }

        // ✅ ENRIQUECER CON RELACIONES SI ESTÁN DISPONIBLES
        $agenda = $this->enrichAgendaWithRelations($agenda);

        return [
            'success' => true,
            'data' => $agenda,
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('Error obteniendo agenda', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}

/**
 * ✅ NUEVO: Enriquecer agenda con relaciones
 */
private function enrichAgendaWithRelations(array $agenda): array
{
    try {
        // Obtener datos maestros para relaciones
        $masterData = $this->offlineService->getMasterDataOffline();
        
        // Enriquecer proceso
        if (!empty($agenda['proceso_id']) && isset($masterData['procesos'])) {
            foreach ($masterData['procesos'] as $proceso) {
                if ($proceso['id'] == $agenda['proceso_id'] || $proceso['uuid'] == $agenda['proceso_id']) {
                    $agenda['proceso'] = $proceso;
                    break;
                }
            }
        }
        
        // Enriquecer brigada
        if (!empty($agenda['brigada_id']) && isset($masterData['brigadas'])) {
            foreach ($masterData['brigadas'] as $brigada) {
                if ($brigada['id'] == $agenda['brigada_id'] || $brigada['uuid'] == $agenda['brigada_id']) {
                    $agenda['brigada'] = $brigada;
                    break;
                }
            }
        }
          // ✅ NUEVO: ENRIQUECER USUARIO MÉDICO
        if (!empty($agenda['usuario_medico_id']) && isset($masterData['usuarios_con_especialidad'])) {
            foreach ($masterData['usuarios_con_especialidad'] as $usuario) {
                if ($usuario['id'] == $agenda['usuario_medico_id'] || $usuario['uuid'] == $agenda['usuario_medico_id']) {
                    $agenda['usuario_medico'] = $usuario;
                    break;
                }
            }
        }
        
        // Datos por defecto para usuario y sede (podrían venir de otras fuentes)
        if (!isset($agenda['usuario'])) {
            $agenda['usuario'] = [
                'nombre_completo' => 'Usuario del Sistema'
            ];
        }
        
        if (!isset($agenda['sede'])) {
            $agenda['sede'] = [
                'nombre' => 'Cajibio'
            ];
        }
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo agenda con relaciones', [
            'error' => $e->getMessage()
        ]);
        
        return $agenda;
    }
}

private function enrichAgendaData(array $agenda): array
{
    try {
        // Calcular cupos totales
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        if ($intervalo <= 0) $intervalo = 15;
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $totalCupos = floor($duracionMinutos / $intervalo);
        
        // ✅ OBTENER CITAS REALES DE LA AGENDA
        $citasCount = $this->getCitasCountForAgenda($agenda['uuid']);
        
        // Calcular cupos disponibles
        $cuposDisponibles = max(0, $totalCupos - $citasCount);
        
        // Agregar datos calculados
        $agenda['total_cupos'] = $totalCupos;
        $agenda['citas_count'] = $citasCount;
        $agenda['cupos_disponibles'] = $cuposDisponibles;
        
        Log::info('✅ Cupos calculados para agenda', [
            'uuid' => $agenda['uuid'],
            'total_cupos' => $totalCupos,
            'citas_count' => $citasCount,
            'cupos_disponibles' => $cuposDisponibles,
            'duracion_minutos' => $duracionMinutos,
            'intervalo' => $intervalo
        ]);
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo datos de agenda', [
            'error' => $e->getMessage(),
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown'
        ]);
        
        // Valores por defecto en caso de error
        $agenda['total_cupos'] = 0;
        $agenda['citas_count'] = 0;
        $agenda['cupos_disponibles'] = 0;
        
        return $agenda;
    }
}

/**
 * ✅ NUEVO: Obtener conteo real de citas para una agenda
 */

    /**
     * ✅ ACTUALIZAR AGENDA
     */
  public function update(string $uuid, array $data): array
{
    try {
        $agenda = $this->offlineService->getAgendaOffline($uuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }

        // Intentar actualizar online
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->put("/agendas/{$uuid}", $data);
            
            if ($response['success']) {
                $agendaData = $response['data'];
                $this->offlineService->storeAgendaOffline($agendaData, false);
                
                return [
                    'success' => true,
                    'data' => $agendaData,
                    'message' => 'Agenda actualizada exitosamente',
                    'offline' => false
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error actualizando agenda'
            ];
        }

        // ✅ ACTUALIZAR OFFLINE (CORREGIDO)
        $updatedData = array_merge($agenda, $data);
        $updatedData['sync_status'] = 'pending'; // ✅ AGREGAR ESTO
        
        $this->offlineService->storeAgendaOffline($updatedData, true);

        // ✅ AGREGAR ESTO QUE FALTA
        $this->offlineService->storePendingChange('put', "/agendas/{$uuid}", $data);

        return [
            'success' => true,
            'data' => $updatedData,
            'message' => 'Agenda actualizada (se sincronizará cuando vuelva la conexión)',
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('Error actualizando agenda', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}
    /**
     * ✅ ELIMINAR AGENDA
     */
   public function destroy(string $uuid): array
{
    try {
        $agenda = $this->offlineService->getAgendaOffline($uuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }

        // Intentar eliminar online
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->delete("/agendas/{$uuid}");
            
            if ($response['success']) {
                // Marcar como eliminada offline
                $agenda['deleted_at'] = now()->toISOString();
                $this->offlineService->storeAgendaOffline($agenda, false);
                
                return [
                    'success' => true,
                    'message' => 'Agenda eliminada exitosamente',
                    'offline' => false
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error eliminando agenda'
            ];
        }

        // ✅ MARCAR COMO ELIMINADA OFFLINE (CORREGIDO)
        $agenda['deleted_at'] = now()->toISOString();
        $agenda['sync_status'] = 'pending'; // ✅ AGREGAR ESTO
        $this->offlineService->storeAgendaOffline($agenda, true);

        // ✅ AGREGAR ESTO QUE FALTA
        $this->offlineService->storePendingChange('delete', "/agendas/{$uuid}", []);

        return [
            'success' => true,
            'message' => 'Agenda eliminada (se sincronizará cuando vuelva la conexión)',
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('Error eliminando agenda', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}
// ✅ AGREGAR ESTE MÉTODO HELPER EN TU AgendaService
private function ensureArray($data): array
{
    if ($data instanceof \stdClass) {
        return (array) $data;
    }
    return is_array($data) ? $data : [];
}



/**
 * ✅ OBTENER AGENDAS DISPONIBLES - CORREGIDO
 */
public function disponibles(array $filters = []): array
{
    try {
        // ✅ OBTENER SEDE DEL LOGIN
        $user = $this->authService->usuario();
        $sedeLogin = $user['sede_id']; // ← SEDE DEL LOGIN
        
        Log::info('🔍 AgendaService::disponibles - Filtrando por sede del login', [
            'sede_login' => $sedeLogin,
            'filters_originales' => $filters
        ]);
        
        // ✅ AGREGAR FILTRO DE SEDE OBLIGATORIO
        $filters['sede_id'] = $sedeLogin; // ← FORZAR SEDE DEL LOGIN
        
        // ✅ FILTROS ADICIONALES PARA AGENDAS DISPONIBLES
        $filters['estado'] = 'ACTIVO';
        $filters['fecha_desde'] = $filters['fecha_desde'] ?? now()->format('Y-m-d');

        // Intentar obtener desde API
        if ($this->apiService->isOnline()) {
            try {
                // ✅ ENVIAR FILTROS INCLUYENDO SEDE_ID
                $response = $this->apiService->get('/agendas/disponibles', $filters);
                
                if ($response['success'] && isset($response['data'])) {
                    $agendas = $response['data'];
                    
                    // ✅ DOBLE VERIFICACIÓN: FILTRAR POR SEDE EN CASO DE QUE LA API NO LO HAGA
                    $agendasFiltradas = array_filter($agendas, function($agenda) use ($sedeLogin) {
                        return ($agenda['sede_id'] ?? 0) == $sedeLogin;
                    });
                    
                    Log::info('✅ Agendas disponibles filtradas por sede desde API', [
                        'sede_login' => $sedeLogin,
                        'total_api' => count($agendas),
                        'filtradas' => count($agendasFiltradas)
                    ]);
                    
                    // Guardar offline para futura referencia
                    foreach ($agendasFiltradas as $agenda) {
                        $this->offlineService->storeAgendaOffline($agenda, false);
                    }
                    
                    return [
                        'success' => true,
                        'data' => $agendasFiltradas, // ← USAR AGENDAS FILTRADAS
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo agendas disponibles desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ OBTENER OFFLINE - YA FILTRADO POR SEDE
        Log::info('📱 Obteniendo agendas disponibles offline con filtro de sede', [
            'sede_login' => $sedeLogin,
            'filters' => $filters
        ]);
        
        $agendas = $this->offlineService->getAgendasOffline($sedeLogin, $filters); // ← YA USA SEDE CORRECTA
        
        // ✅ ENRIQUECER CON CUPOS Y FILTRAR SOLO LAS QUE TIENEN CUPOS DISPONIBLES
        $agendasConCupos = [];
        foreach ($agendas as $agenda) {
            // ✅ ASEGURAR QUE SEA ARRAY
            $agenda = $this->ensureArray($agenda);
            
            // Enriquecer con cupos
            $agenda = $this->enrichAgendaWithCupos($agenda);
            
            // Solo incluir si tiene cupos disponibles
            if (($agenda['cupos_disponibles'] ?? 0) > 0) {
                $agendasConCupos[] = $agenda;
            }
        }
        
        Log::info('✅ Agendas disponibles obtenidas offline filtradas por sede', [
            'sede_login' => $sedeLogin,
            'total_encontradas' => count($agendas),
            'con_cupos_disponibles' => count($agendasConCupos)
        ]);

        return [
            'success' => true,
            'data' => $agendasConCupos, // ← SOLO AGENDAS CON CUPOS DISPONIBLES
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo agendas disponibles', [
            'error' => $e->getMessage(),
            'sede_login' => $user['sede_id'] ?? 'NO_DEFINIDA',
            'filters' => $filters
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage(),
            'data' => []
        ];
    }
}


   
    /**
     * ✅ NUEVO: Sincronizar una agenda individual a la API
     */
    private function syncSingleAgendaToApi(array $agenda): array
    {
        try {
            // ✅ PREPARAR DATOS PARA LA API (CORREGIR TIPOS)
            $apiData = $this->prepareAgendaDataForApi($agenda);

            Log::info('📤 Enviando agenda a API', [
                'uuid' => $agenda['uuid'],
                'fecha' => $agenda['fecha'],
                'data_keys' => array_keys($apiData),
                'prepared_data' => $apiData
            ]);

            // ✅ SI LA AGENDA ESTÁ ELIMINADA, INTENTAR ELIMINAR EN API
            if (!empty($agenda['deleted_at'])) {
                $response = $this->apiService->delete("/agendas/{$agenda['uuid']}");
                
                if ($response['success'] || 
                    (isset($response['error']) && str_contains($response['error'], 'not found'))) {
                    // Éxito o ya no existe
                    return ['success' => true, 'data' => ['deleted' => true]];
                }
                
                return $response;
            }

            // ✅ INTENTAR CREAR DIRECTAMENTE
            $response = $this->apiService->post('/agendas', $apiData);

            Log::info('📥 Respuesta de API para agenda', [
                'uuid' => $agenda['uuid'],
                'success' => $response['success'] ?? false,
                'has_data' => isset($response['data']),
                'response_keys' => array_keys($response)
            ]);

            // ✅ SI FALLA POR DUPLICADO, INTENTAR ACTUALIZAR
            if (!$response['success'] && isset($response['error'])) {
                $errorMessage = strtolower($response['error']);
                
                if (str_contains($errorMessage, 'duplicate') || 
                    str_contains($errorMessage, 'duplicado') ||
                    str_contains($errorMessage, 'already exists') ||
                    str_contains($errorMessage, 'ya existe')) {
                    
                    Log::info('🔄 Agenda duplicada, intentando actualizar', [
                        'uuid' => $agenda['uuid']
                    ]);
                    
                    // Intentar actualizar la agenda existente
                    $updateResponse = $this->apiService->put("/agendas/{$agenda['uuid']}", $apiData);
                    
                    Log::info('🔄 Resultado de actualización', [
                        'uuid' => $agenda['uuid'],
                        'success' => $updateResponse['success'] ?? false
                    ]);
                    
                    return $updateResponse;
                }
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('❌ Error enviando agenda a API', [
                'uuid' => $agenda['uuid'] ?? 'sin-uuid',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ NUEVO: Preparar datos de agenda para la API
     */
    private function prepareAgendaDataForApi(array $agenda): array
{
    // ✅ MAPEAR SOLO LOS CAMPOS QUE LA API ESPERA CON TIPOS CORRECTOS
    $apiData = [
        'modalidad' => $agenda['modalidad'] ?? 'Ambulatoria',
        'fecha' => $agenda['fecha'],
        'consultorio' => (string) ($agenda['consultorio'] ?? ''), // ✅ Asegurar string
        'hora_inicio' => $agenda['hora_inicio'],
        'hora_fin' => $agenda['hora_fin'],
        'intervalo' => (string) ($agenda['intervalo'] ?? 15), // ✅ Convertir a entero
        'etiqueta' => $agenda['etiqueta'] ?? '',
        'estado' => $agenda['estado'] ?? 'ACTIVO',
        'sede_id' => (int) ($agenda['sede_id'] ?? 1), // ✅ Convertir a entero
        'usuario_id' => (int) ($agenda['usuario_id'] ?? 1), // ✅ Convertir a entero
    ];

      // ✅ MANEJAR PROCESO_ID CORRECTAMENTE - Solo incluir si tiene valor
    if (!empty($agenda['proceso_id']) && $agenda['proceso_id'] !== 'null') {
        if (is_numeric($agenda['proceso_id'])) {
            $apiData['proceso_id'] = (int) $agenda['proceso_id']; // ID numérico
        } elseif (is_string($agenda['proceso_id']) && $this->isValidUuid($agenda['proceso_id'])) {
            $apiData['proceso_id'] = $agenda['proceso_id']; // ✅ UUID como string
        }
        
        Log::info('✅ proceso_id procesado para API', [
            'original' => $agenda['proceso_id'],
            'final' => $apiData['proceso_id'] ?? 'NOT_INCLUDED',
            'type' => gettype($apiData['proceso_id'] ?? null)
        ]);
    } else {
        Log::info('⚠️ proceso_id omitido (sin valor)');
    }
    
    // ✅ MANEJAR BRIGADA_ID CORRECTAMENTE - Solo incluir si tiene valor
    if (!empty($agenda['brigada_id']) && $agenda['brigada_id'] !== 'null') {
        if (is_numeric($agenda['brigada_id'])) {
            $apiData['brigada_id'] = (int) $agenda['brigada_id']; // ID numérico
        } elseif (is_string($agenda['brigada_id']) && $this->isValidUuid($agenda['brigada_id'])) {
            $apiData['brigada_id'] = $agenda['brigada_id']; // ✅ UUID como string
        }
        
        Log::info('✅ brigada_id procesado para API', [
            'original' => $agenda['brigada_id'],
            'final' => $apiData['brigada_id'] ?? 'NOT_INCLUDED',
            'type' => gettype($apiData['brigada_id'] ?? null)
        ]);
    } else {
        // ✅ NO INCLUIR brigada_id si no tiene valor (dejar que el backend use su default)
        Log::info('⚠️ brigada_id omitido (sin valor) - backend usará su configuración por defecto');
    }
    // ✅ NUEVO: MANEJAR USUARIO MÉDICO CORRECTAMENTE
    $usuarioMedicoValue = null;
    
    // Buscar en ambos campos posibles (offline puede usar cualquiera)
    if (!empty($agenda['usuario_medico_uuid']) && $agenda['usuario_medico_uuid'] !== 'null') {
        $usuarioMedicoValue = $agenda['usuario_medico_uuid'];
    } elseif (!empty($agenda['usuario_medico_id']) && $agenda['usuario_medico_id'] !== 'null') {
        $usuarioMedicoValue = $agenda['usuario_medico_id'];
    }
    
    if ($usuarioMedicoValue) {
        $apiData['usuario_medico_uuid'] = $usuarioMedicoValue; // ✅ LA API ESPERA ESTE NOMBRE
        
        Log::info('✅ Usuario médico agregado a datos de API', [
            'usuario_medico_uuid' => $usuarioMedicoValue,
            'agenda_uuid' => $agenda['uuid'] ?? 'sin-uuid',
            'found_in_field' => !empty($agenda['usuario_medico_uuid']) ? 'usuario_medico_uuid' : 'usuario_medico_id'
        ]);
    }

    // ✅ LIMPIAR CAMPOS VACÍOS (eliminar null y strings vacíos)
    $apiData = array_filter($apiData, function($value) {
        return $value !== null && $value !== '';
    });

    // ✅ ASEGURAR CAMPOS OBLIGATORIOS
    if (empty($apiData['fecha'])) {
        throw new \Exception('Fecha es requerida');
    }
    if (empty($apiData['hora_inicio'])) {
        throw new \Exception('Hora de inicio es requerida');
    }
    if (empty($apiData['hora_fin'])) {
        throw new \Exception('Hora de fin es requerida');
    }

    // ✅ LOG FINAL PARA DEBUGGING
    Log::info('📤 Datos finales preparados para API', [
        'agenda_uuid' => $agenda['uuid'] ?? 'sin-uuid',
        'has_usuario_medico' => isset($apiData['usuario_medico_uuid']),
        'usuario_medico_uuid' => $apiData['usuario_medico_uuid'] ?? 'no-enviado',
        'has_brigada_id' => isset($apiData['brigada_id']),
        'brigada_id_value' => $apiData['brigada_id'] ?? 'OMITIDO',
        'has_proceso_id' => isset($apiData['proceso_id']),
        'proceso_id_value' => $apiData['proceso_id'] ?? 'OMITIDO',
        'all_fields' => array_keys($apiData),
        'total_fields' => count($apiData)
    ]);

    return $apiData;
}

    /**
     * ✅ NUEVO: Obtener todas las agendas offline
     */
    private function getAllAgendasOffline(int $sedeId): array
    {
        return $this->offlineService->getAgendasOffline($sedeId, []);
    }

    /**
     * ✅ NUEVO: Obtener datos de test para sincronización
     */
   public function getTestSyncData($limit = 10)
{
    try {
        Log::info('🧪 Test manual de sincronización de agendas iniciado');
        
        $this->ensureSQLiteTables();
        
        // ✅ CAMBIAR 'sqlite' POR 'offline'
        $pendingAgendas = DB::connection('offline')->table('agendas')
            ->where('sync_status', 'pending')
            ->orWhere('sync_status', 'error')
            ->limit($limit)
            ->get();
        
        Log::info('📊 Agendas pendientes encontradas', [
            'total' => $pendingAgendas->count(),
            'limit' => $limit
        ]);
        
        if ($pendingAgendas->isEmpty()) {
            return [
                'success' => true,
                'pending_count' => 0,
                'total_count' => 0,
                'error_count' => 0,
                'data' => [],
                'message' => 'No hay agendas pendientes de sincronización'
            ];
        }
        
        // ✅ CONVERTIR OBJETOS stdClass A ARRAYS
        $agendasArray = $pendingAgendas->map(function ($agenda) {
            $agendaArray = (array) $agenda;
            
            if (isset($agendaArray['original_data']) && is_string($agendaArray['original_data'])) {
                $originalData = json_decode($agendaArray['original_data'], true);
                if ($originalData) {
                    $agendaArray['original_data'] = $originalData;
                }
            }
            
            return $agendaArray;
        })->toArray();
        
        // Filtrar agendas válidas
        $validAgendas = array_filter($agendasArray, function ($agenda) {
            return isset($agenda['uuid']) && 
                   !empty($agenda['uuid']) && 
                   isset($agenda['fecha']) && 
                   !empty($agenda['fecha']);
        });
        
        // ✅ OBTENER TOTALES
        $totalCount = DB::connection('offline')->table('agendas')->count();
        $errorCount = DB::connection('offline')->table('agendas')
            ->where('sync_status', 'error')
            ->count();
        
        Log::info('✅ Agendas válidas para sincronización', [
            'total_pendientes' => count($agendasArray),
            'validas' => count($validAgendas),
            'total_count' => $totalCount,
            'error_count' => $errorCount
        ]);
        
        return [
            'success' => true,
            'pending_count' => count($validAgendas),
            'total_count' => $totalCount,
            'error_count' => $errorCount,
            'data' => array_values($validAgendas),
            'pending_details' => array_values($validAgendas),
            'message' => count($validAgendas) . ' agendas pendientes de sincronización'
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error en getTestSyncData', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        
        return [
            'success' => false,
            'pending_count' => 0,
            'total_count' => 0,
            'error_count' => 0,
            'data' => [],
            'error' => 'Error obteniendo datos de prueba: ' . $e->getMessage()
        ];
    }
}

private function syncCreateAgenda($data)
{
    try {
        $response = Http::timeout(30)->post($this->baseUrl . '/agendas', $data);
        return $response->successful();
    } catch (\Exception $e) {
        Log::error('Error creando agenda en servidor', ['error' => $e->getMessage()]);
        return false;
    }
}

private function syncUpdateAgenda($uuid, $data)
{
    try {
        $response = Http::timeout(30)->put($this->baseUrl . '/agendas/' . $uuid, $data);
        return $response->successful();
    } catch (\Exception $e) {
        Log::error('Error actualizando agenda en servidor', ['error' => $e->getMessage()]);
        return false;
    }
}

private function syncDeleteAgenda($uuid)
{
    try {
        $response = Http::timeout(30)->delete($this->baseUrl . '/agendas/' . $uuid);
        return $response->successful();
    } catch (\Exception $e) {
        Log::error('Error eliminando agenda en servidor', ['error' => $e->getMessage()]);
        return false;
    }
}
 private function checkConnection(): bool
    {
        try {
            return $this->apiService->isOnline();
        } catch (\Exception $e) {
            Log::error('❌ Error verificando conexión', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ MÉTODO FALTANTE: Asegurar tablas SQLite
     */
    private function ensureSQLiteTables(): void
    {
        try {
            // Delegar al OfflineService que ya tiene esta funcionalidad
            $this->offlineService->ensureSQLiteExists();
            
            Log::info('✅ Tablas SQLite verificadas desde AgendaService');
            
        } catch (\Exception $e) {
            Log::error('❌ Error verificando tablas SQLite desde AgendaService', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

   /**
 * ✅ CORREGIDO: Obtener citas de una agenda CON SEDE DE LA AGENDA
 */
public function getCitasForAgenda(string $agendaUuid): array
{
    try {
        Log::info('🔍 AgendaService: Obteniendo citas para agenda', [
            'agenda_uuid' => $agendaUuid
        ]);
        
        // ✅ OBTENER LA AGENDA PRIMERO PARA SABER SU SEDE Y FECHA
        $agenda = $this->getAgendaOffline($agendaUuid);
        $fechaAgenda = null;
        $sedeAgenda = null; // ← AGREGAR ESTA VARIABLE
        
        if ($agenda && isset($agenda['fecha'])) {
            $fechaAgenda = $agenda['fecha'];
            $sedeAgenda = $agenda['sede_id']; // ← OBTENER SEDE DE LA AGENDA
            
            // Limpiar la fecha (quitar la hora)
            if (strpos($fechaAgenda, 'T') !== false) {
                $fechaAgenda = explode('T', $fechaAgenda)[0];
            }
            
            Log::info('📅 Usando fecha y sede de agenda para obtener citas', [
                'fecha_agenda' => $fechaAgenda,
                'sede_agenda' => $sedeAgenda, // ← AGREGAR ESTE LOG
                'usuario_sede' => $this->authService->usuario()['sede_id'] ?? 'N/A' // Solo para comparar
            ]);
        }
        
        // ✅ SI NO TENEMOS LA AGENDA, NO PODEMOS CONTINUAR
        if (!$sedeAgenda) {
            Log::error('❌ No se pudo obtener la sede de la agenda', [
                'agenda_uuid' => $agendaUuid
            ]);
            return [
                'success' => false,
                'error' => 'No se pudo obtener información de la agenda',
                'data' => []
            ];
        }
        
        // ✅ PRIMERO INTENTAR DESDE OFFLINE (MÁS CONFIABLE)
        Log::info('📱 Obteniendo citas desde offline primero');
        
        $filters = ['agenda_uuid' => $agendaUuid];
        if ($fechaAgenda) {
            $filters['fecha'] = $fechaAgenda;
        }
        
        // ✅ USAR SEDE DE LA AGENDA, NO DEL USUARIO
        $citasOffline = $this->offlineService->getCitasOffline($sedeAgenda, $filters); // ← CAMBIO CRÍTICO
        
        // Enriquecer con datos de pacientes
        foreach ($citasOffline as &$cita) {
            if (!empty($cita['paciente_uuid']) && !isset($cita['paciente'])) {
                $cita['paciente'] = $this->getPacienteData($cita['paciente_uuid']);
            }
        }
        
        Log::info('✅ Citas obtenidas desde offline con sede correcta', [
            'count' => count($citasOffline),
            'filters_used' => $filters,
            'sede_agenda' => $sedeAgenda // ← CONFIRMAR SEDE USADA
        ]);
        
        // ✅ SI HAY CITAS OFFLINE, USARLAS
        if (!empty($citasOffline)) {
            return [
                'success' => true,
                'data' => $citasOffline,
                'offline' => true
            ];
        }
        
        // ✅ SOLO SI NO HAY CITAS OFFLINE, INTENTAR API
        if ($this->apiService->isOnline()) {
            try {
                // ENVIAR LA FECHA COMO PARÁMETRO
                $params = [];
                if ($fechaAgenda) {
                    $params['fecha'] = $fechaAgenda;
                }
                
                $response = $this->apiService->get("/agendas/{$agendaUuid}/citas", $params);
                
                Log::info('📡 Respuesta de API para citas', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'data_count' => isset($response['data']) ? count($response['data']) : 0,
                    'params_sent' => $params,
                    'data_preview' => isset($response['data']) ? array_slice($response['data'], 0, 2) : []
                ]);
                
                if ($response['success'] && isset($response['data']) && !empty($response['data'])) {
                    // GUARDAR CITAS OFFLINE PARA CACHE
                    foreach ($response['data'] as $cita) {
                        $this->offlineService->storeCitaOffline($cita, false);
                    }
                    
                    Log::info('✅ Citas obtenidas desde API', [
                        'count' => count($response['data'])
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $response['data'],
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo citas desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // ✅ SI NO HAY CITAS EN NINGÚN LADO, DEVOLVER ARRAY VACÍO
        Log::info('ℹ️ No se encontraron citas en offline ni API');
        
        return [
            'success' => true,
            'data' => [],
            'offline' => true
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo citas para agenda', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno',
            'data' => []
        ];
    }
}

// ✅ AGREGAR ESTE MÉTODO AUXILIAR
private function getAgendaOffline(string $uuid): ?array
{
    try {
        return $this->offlineService->getAgendaOffline($uuid);
    } catch (\Exception $e) {
        Log::warning('⚠️ Error obteniendo agenda offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}


/**
 * ✅ NUEVO: Obtener conteo de citas para una agenda
 */
public function getCitasCountForAgenda(string $agendaUuid): array
{
    try {
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }
        
        // Calcular cupos
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $totalCupos = floor($duracionMinutos / $intervalo);
        
        // Contar citas
        $citasCount = 0;
        
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$agendaUuid}/citas/count");
                if ($response['success']) {
                    $citasCount = $response['data']['citas_count'] ?? 0;
                }
            } catch (\Exception $e) {
                // Fallback a offline
            }
        }
        
        // Si no se pudo obtener online, contar offline
        if ($citasCount === 0) {
            $citasCount = $this->countCitasOffline($agendaUuid);
        }
        
        $cuposDisponibles = max(0, $totalCupos - $citasCount);
        
        return [
            'success' => true,
            'data' => [
                'total_cupos' => $totalCupos,
                'citas_count' => $citasCount,
                'cupos_disponibles' => $cuposDisponibles
            ]
        ];
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo conteo de citas', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}

/**
 * ✅ NUEVO: Contar citas offline
 */
private function countCitasOffline(string $agendaUuid): int
{
    try {
        if ($this->offlineService->isSQLiteAvailable()) {
            return DB::connection('offline')
                ->table('citas')
                ->where('agenda_uuid', $agendaUuid)
                ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
                ->whereNull('deleted_at')
                ->count();
        }
        
        // Fallback a contar archivos JSON
        $count = 0;
        $citasPath = storage_path('app/offline/citas');
        
        if (is_dir($citasPath)) {
            $files = glob($citasPath . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && 
                    $data['agenda_uuid'] == $agendaUuid && 
                    !in_array($data['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']) &&
                    empty($data['deleted_at'])) {
                    $count++;
                }
            }
        }
        
        return $count;
        
    } catch (\Exception $e) {
        Log::error('Error contando citas offline', [
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

/**
 * ✅ NUEVO: Obtener datos de paciente
 */
private function getPacienteData(string $pacienteUuid): ?array
{
    try {
        // Intentar desde servicio de pacientes
        $pacienteService = app(PacienteService::class);
        $result = $pacienteService->show($pacienteUuid);
        
        if ($result['success']) {
            return $result['data'];
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo datos de paciente', [
            'uuid' => $pacienteUuid,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}


}

