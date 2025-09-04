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
    public function index(array $filters = [], int $page = 1): array
    {
        try {
            Log::info("📅 AgendaService::index - Iniciando", [
                'filters' => $filters,
                'page' => $page
            ]);

            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];

            // Preparar parámetros para API
            $apiParams = array_merge($filters, [
                'page' => $page,
                'sede_id' => $sedeId
            ]);

            $apiParams = array_filter($apiParams, function($value) {
                return !empty($value) && $value !== '';
            });

            // Intentar obtener desde API
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/agendas', $apiParams);

                    if ($response['success'] && isset($response['data'])) {
                        $agendas = $response['data']['data'] ?? $response['data'];
                        $meta = $response['data']['meta'] ?? $response['meta'] ?? [];

                        // Sincronizar offline
                        if (!empty($agendas)) {
                            foreach ($agendas as $agenda) {
                                $this->offlineService->storeAgendaOffline($agenda, false);
                            }
                        }

                        return [
                            'success' => true,
                            'data' => $agendas,
                            'meta' => $meta,
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

            // Obtener datos offline
            $agendas = $this->offlineService->getAgendasOffline($sedeId, $filters);
            
            // Paginación manual
            $perPage = 15;
            $total = count($agendas);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($agendas, $offset, $perPage);

            return [
                'success' => true,
                'data' => $paginatedData,
                'meta' => [
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                    'per_page' => $perPage,
                    'total' => $total
                ],
                'message' => '📱 Trabajando en modo offline - Datos locales',
                'offline' => true
            ];

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
                    'per_page' => 15,
                    'total' => 0
                ],
                'message' => '❌ Error cargando agendas: ' . $e->getMessage(),
                'offline' => true
            ];
        }
    }

    /**
     * ✅ CREAR AGENDA
     */
public function store(array $data): array
{
    try {
        Log::info('🔍 AgendaService::store - Datos recibidos', [
            'data' => $data,
            'proceso_id' => $data['proceso_id'] ?? 'no-set',
            'brigada_id' => $data['brigada_id'] ?? 'no-set',
            'intervalo' => $data['intervalo'] ?? 'no-set',
            'intervalo_type' => gettype($data['intervalo'] ?? null)
        ]);

        $user = $this->authService->usuario();
        $data['sede_id'] = $user['sede_id'];
        $data['usuario_id'] = $user['id'];

        // ✅ VALIDAR Y LIMPIAR DATOS ANTES DE PROCESAR
        $data = $this->validateAndCleanAgendaData($data);

        Log::info('🧹 Datos después de limpiar', [
            'data' => $data,
            'proceso_id_final' => $data['proceso_id'],
            'brigada_id_final' => $data['brigada_id'],
            'intervalo_final' => $data['intervalo'],
            'intervalo_type_final' => gettype($data['intervalo'])
        ]);

        // Intentar crear online
        if ($this->apiService->isOnline()) {
            Log::info('🌐 Intentando crear online');
            
            $response = $this->apiService->post('/agendas', $data);
            
            Log::info('📥 Respuesta de API online', [
                'success' => $response['success'] ?? false,
                'error' => $response['error'] ?? null,
                'response' => $response
            ]);
            
            if ($response['success']) {
                $agendaData = $response['data'];
                $this->offlineService->storeAgendaOffline($agendaData, false);
                
                return [
                    'success' => true,
                    'data' => $agendaData,
                    'message' => 'Agenda creada exitosamente',
                    'offline' => false
                ];
            }
            
            Log::error('❌ Error creando online', [
                'error' => $response['error'] ?? 'Error desconocido'
            ]);
            
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error creando agenda'
            ];
        }

        // ✅ CREAR OFFLINE CON DATOS LIMPIOS
        Log::info('📱 Creando offline');
        
        $data['uuid'] = Str::uuid();
        $data['estado'] = $data['estado'] ?? 'ACTIVO';
        $data['sync_status'] = 'pending';
        
        Log::info('📤 Datos finales para offline', [
            'uuid' => $data['uuid'],
            'proceso_id' => $data['proceso_id'],
            'brigada_id' => $data['brigada_id'],
            'intervalo' => $data['intervalo'],
            'sync_status' => $data['sync_status']
        ]);
        
        // ✅ GUARDAR DATOS ORIGINALES LIMPIOS
        $originalData = $data;
        
        $this->offlineService->storeAgendaOffline($data, true);
        $this->offlineService->storePendingChange('post', '/agendas', $originalData);

        return [
            'success' => true,
            'data' => $data,
            'message' => 'Agenda creada (se sincronizará cuando vuelva la conexión)',
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico en AgendaService::store', [
            'error' => $e->getMessage(),
            'data' => $data ?? [],
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ];
    }
}

// ✅ NUEVO MÉTODO PARA VALIDAR Y LIMPIAR DATOS
private function validateAndCleanAgendaData(array $data): array
{
    Log::info('🧹 Limpiando datos de agenda', [
        'original_proceso_id' => $data['proceso_id'] ?? 'null',
        'original_brigada_id' => $data['brigada_id'] ?? 'null'
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

    Log::info('✅ Datos limpiados', [
        'clean_proceso_id' => $data['proceso_id'],
        'clean_brigada_id' => $data['brigada_id']
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
        
        // ✅ CORREGIR: Enriquecer proceso
        if (!empty($agenda['proceso_id']) && isset($masterData['procesos'])) {
            foreach ($masterData['procesos'] as $proceso) {
                // ✅ VERIFICAR QUE EXISTE LA CLAVE 'id' ANTES DE USARLA
                $procesoId = $proceso['id'] ?? null;
                $procesoUuid = $proceso['uuid'] ?? null;
                
                if (($procesoId && $procesoId == $agenda['proceso_id']) || 
                    ($procesoUuid && $procesoUuid == $agenda['proceso_id'])) {
                    $agenda['proceso'] = $proceso;
                    break;
                }
            }
        }
        
        // ✅ CORREGIR: Enriquecer brigada
        if (!empty($agenda['brigada_id']) && isset($masterData['brigadas'])) {
            foreach ($masterData['brigadas'] as $brigada) {
                // ✅ VERIFICAR QUE EXISTE LA CLAVE 'id' ANTES DE USARLA
                $brigadaId = $brigada['id'] ?? null;
                $brigadaUuid = $brigada['uuid'] ?? null;
                
                if (($brigadaId && $brigadaId == $agenda['brigada_id']) || 
                    ($brigadaUuid && $brigadaUuid == $agenda['brigada_id'])) {
                    $agenda['brigada'] = $brigada;
                    break;
                }
            }
        }
        
        // Datos por defecto para usuario y sede
        if (!isset($agenda['usuario'])) {
            $agenda['usuario'] = [
                'nombre_completo' => 'Usuario del Sistema'
            ];
        }
        
        if (!isset($agenda['sede'])) {
            $agenda['sede'] = [
                'nombre' => 'Sede Principal'
            ];
        }
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo agenda con relaciones', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown'
        ]);
        
        return $agenda;
    }
}
/**
 * ✅ NUEVO: Verificar si SQLite está disponible (método faltante)
 */
private function isSQLiteAvailable(): bool
{
    return $this->offlineService->isSQLiteAvailable();
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
private function getCitasCountForAgenda(string $agendaUuid): int
{
    try {
        // ✅ INTENTAR DESDE API PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$agendaUuid}/citas/count");
                
                if ($response['success'] && isset($response['data']['citas_count'])) {
                    return (int) $response['data']['citas_count'];
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo conteo de citas desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // ✅ CONTAR DESDE DATOS OFFLINE
        if ($this->offlineService->isSQLiteAvailable()) {
            $count = DB::connection('offline')
                ->table('citas')
                ->where('agenda_uuid', $agendaUuid)
                ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
                ->whereNull('deleted_at')
                ->count();
                
            return $count;
        }
        
        return 0;
        
    } catch (\Exception $e) {
        Log::error('Error contando citas para agenda', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
        
        return 0;
    }
}
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

    /**
     * ✅ OBTENER AGENDAS DISPONIBLES
     */
    public function disponibles(array $filters = []): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];

            // Intentar obtener desde API
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/agendas/disponibles', $filters);
                    
                    if ($response['success']) {
                        return [
                            'success' => true,
                            'data' => $response['data'],
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error obteniendo agendas disponibles desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtener offline - solo agendas activas y futuras
            $filters['estado'] = 'ACTIVO';
            $filters['fecha_desde'] = now()->format('Y-m-d');
            
            $agendas = $this->offlineService->getAgendasOffline($sedeId, $filters);

            return [
                'success' => true,
                'data' => $agendas,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo agendas disponibles', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno'
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
            'intervalo' => (int) ($agenda['intervalo'] ?? 15), // ✅ Convertir a entero
            'etiqueta' => $agenda['etiqueta'] ?? '',
            'estado' => $agenda['estado'] ?? 'ACTIVO',
            'sede_id' => (int) ($agenda['sede_id'] ?? 1), // ✅ Convertir a entero
            'usuario_id' => (int) ($agenda['usuario_id'] ?? 1), // ✅ Convertir a entero
        ];

        // ✅ MANEJAR CAMPOS OPCIONALES CORRECTAMENTE
        if (!empty($agenda['proceso_id']) && $agenda['proceso_id'] !== 'null') {
            $apiData['proceso_id'] = (int) $agenda['proceso_id'];
        }
        
        if (!empty($agenda['brigada_id']) && $agenda['brigada_id'] !== 'null') {
            $apiData['brigada_id'] = (int) $agenda['brigada_id'];
        }

        // ✅ LIMPIAR CAMPOS VACÍOS
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

}

