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
                'nombre' => $currentUser['sede']['nombre'] ?? 'Sede Principal'
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
                'nombre' => 'Sede Principal'
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
        'intervalo' => (string) ($agenda['intervalo'] ?? 15), // ✅ Convertir a entero
        'etiqueta' => $agenda['etiqueta'] ?? '',
        'estado' => $agenda['estado'] ?? 'ACTIVO',
        'sede_id' => (int) ($agenda['sede_id'] ?? 1), // ✅ Convertir a entero
        'usuario_id' => (int) ($agenda['usuario_id'] ?? 1), // ✅ Convertir a entero
    ];

      // ✅ MANEJAR PROCESO_ID CORRECTAMENTE
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
    }
    
    // ✅ MANEJAR BRIGADA_ID CORRECTAMENTE
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

    // ✅ LOG FINAL PARA DEBUGGING
    Log::info('📤 Datos finales preparados para API', [
        'agenda_uuid' => $agenda['uuid'] ?? 'sin-uuid',
        'has_usuario_medico' => isset($apiData['usuario_medico_uuid']),
        'usuario_medico_uuid' => $apiData['usuario_medico_uuid'] ?? 'no-enviado',
        'all_fields' => array_keys($apiData)
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
 * ✅ NUEVO: Obtener citas de una agenda
 */
public function getCitasForAgenda(string $agendaUuid): array
{
    try {
        // Intentar desde API primero
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$agendaUuid}/citas");
                
                if ($response['success'] && isset($response['data'])) {
                    // Guardar citas offline
                    foreach ($response['data'] as $cita) {
                        $this->offlineService->storeCitaOffline($cita, false);
                    }
                    
                    return [
                        'success' => true,
                        'data' => $response['data'],
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo citas desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Obtener desde offline
        $user = $this->authService->usuario();
        $citas = $this->offlineService->getCitasOffline($user['sede_id'], [
            'agenda_uuid' => $agendaUuid
        ]);
        
        // Enriquecer con datos de pacientes
        foreach ($citas as &$cita) {
            if (!empty($cita['paciente_uuid']) && !isset($cita['paciente'])) {
                $cita['paciente'] = $this->getPacienteData($cita['paciente_uuid']);
            }
        }
        
        return [
            'success' => true,
            'data' => $citas,
            'offline' => true
        ];
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo citas para agenda', [
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

