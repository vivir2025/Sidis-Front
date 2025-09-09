<?php
// app/Services/CitaService.php
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CitaService
{
    protected $apiService;
    protected $authService;
    protected $offlineService;

    public function __construct(ApiService $apiService, AuthService $authService, OfflineService $offlineService)
    {
        $this->apiService = $apiService;
        $this->authService = $authService;
        $this->offlineService = $offlineService;
    }

    /**
     * ✅ CONSTRUIR URL DE API DESDE CONFIGURACIÓN
     */
     private function buildApiUrl(string $endpoint, array $params = []): string
    {
        // Obtener endpoint desde configuración
        $endpointPath = config("api.endpoints.citas.{$endpoint}");
        
        if (!$endpointPath) {
            throw new \Exception("Endpoint de cita no configurado: {$endpoint}");
        }
        
        // ✅ USAR DIRECTAMENTE LA URL COMPLETA DE LA CONFIGURACIÓN
        $url = $endpointPath;
        
        // Reemplazar parámetros en la URL
        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }
        
        return $url;
    }

    /**
     * ✅ LISTAR CITAS
     */
    public function index(array $filters = [], int $page = 1): array
    {
        try {
            Log::info("🩺 CitaService::index - Iniciando", [
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
                    $url = $this->buildApiUrl('index');
                    $response = $this->apiService->get($url, $apiParams);

                    if ($response['success'] && isset($response['data'])) {
                        $citas = $response['data']['data'] ?? $response['data'];
                        $meta = $response['data']['meta'] ?? $response['meta'] ?? [];
                        
                        // Sincronizar offline
                        if (!empty($citas)) {
                            foreach ($citas as $cita) {
                                $this->offlineService->storeCitaOffline($cita, false);
                            }
                        }

                        return [
                            'success' => true,
                            'data' => $citas,
                            'meta' => $meta,
                            'message' => '✅ Citas actualizadas desde el servidor',
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error conectando con API citas', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtener datos offline
            $citas = $this->offlineService->getCitasOffline($sedeId, $filters);
            
            // Paginación manual
            $perPage = 15;
            $total = count($citas);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($citas, $offset, $perPage);

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
            Log::error('💥 Error en CitaService::index', [
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
                'message' => '❌ Error cargando citas: ' . $e->getMessage(),
                'offline' => true
            ];
        }
    }

   /**
 * ✅ CREAR CITA - VERSIÓN CORREGIDA
 */
public function store(array $data): array
{
    try {
        Log::info('🩺 CitaService::store - Datos recibidos', [
            'data' => $data
        ]);

        $user = $this->authService->usuario();
        $data['sede_id'] = $user['sede_id'];
        $data['usuario_creo_cita_id'] = $user['id'];

        // ✅ CONVERTIR UUIDs A IDs ANTES DE ENVIAR A LA API
        $processedData = $this->convertUuidsToIds($data);

        Log::info('🔄 Datos procesados para API', [
            'original' => $data,
            'processed' => $processedData
        ]);

        // ✅ INTENTAR CREAR ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            Log::info('🌐 Conexión disponible, intentando crear cita en API...');
            
            try {
                $url = $this->buildApiUrl('store');
                Log::info('📤 Enviando cita a API', [
                    'url' => $url,
                    'data_keys' => array_keys($processedData)
                ]);
                
                $response = $this->apiService->post($url, $processedData);
                
                Log::info('📥 Respuesta de API recibida', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'has_error' => isset($response['error'])
                ]);
                
                if ($response['success']) {
                    $citaData = $response['data'];
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    Log::info('🎉 Cita creada exitosamente en API', [
                        'cita_uuid' => $citaData['uuid'] ?? 'N/A'
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'message' => '✅ Cita creada exitosamente en el servidor',
                        'offline' => false
                    ];
                }
                
                // ✅ SI LA API FALLA, LOGGEAR PERO CONTINUAR AL FALLBACK OFFLINE
                Log::warning('⚠️ API respondió con error, creando offline como fallback', [
                    'api_error' => $response['error'] ?? 'Error desconocido',
                    'api_response' => $response
                ]);
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Excepción conectando con API, creando offline como fallback', [
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::info('📱 Sin conexión, creando cita offline directamente...');
        }

        // ✅ CREAR OFFLINE (como fallback o por falta de conexión)
        Log::info('💾 Creando cita en modo offline...');
        
        $data['uuid'] = Str::uuid();
        $data['estado'] = $data['estado'] ?? 'PROGRAMADA';
        $this->offlineService->storeCitaOffline($data, true);

        Log::info('✅ Cita creada offline exitosamente', [
            'uuid' => $data['uuid'],
            'needs_sync' => true
        ]);

        return [
            'success' => true,
            'data' => $data,
            'message' => '📱 Cita creada offline (se sincronizará cuando vuelva la conexión)',
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error crítico creando cita', [
            'error' => $e->getMessage(),
            'data' => $data,
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ];
    }
}

    /**
     * ✅ NUEVO: Convertir UUIDs a IDs numéricos
     */
    private function convertUuidsToIds(array $data): array
    {
        try {
            $processedData = $data;

            // ✅ CONVERTIR PACIENTE_UUID A PACIENTE_ID
            if (!empty($data['paciente_uuid'])) {
                $pacienteId = $this->convertUuidToId($data['paciente_uuid'], 'pacientes');
                
                if ($pacienteId) {
                    $processedData['paciente_id'] = $pacienteId;
                    unset($processedData['paciente_uuid']); // Remover UUID
                    
                    Log::info('✅ paciente_uuid convertido', [
                        'uuid' => $data['paciente_uuid'],
                        'id' => $pacienteId
                    ]);
                } else {
                    throw new \Exception("No se encontró el paciente con UUID: {$data['paciente_uuid']}");
                }
            }

            // ✅ CONVERTIR AGENDA_UUID A AGENDA_ID
            if (!empty($data['agenda_uuid'])) {
                $agendaId = $this->convertUuidToId($data['agenda_uuid'], 'agendas');
                
                if ($agendaId) {
                    $processedData['agenda_id'] = $agendaId;
                    unset($processedData['agenda_uuid']); // Remover UUID
                    
                    Log::info('✅ agenda_uuid convertido', [
                        'uuid' => $data['agenda_uuid'],
                        'id' => $agendaId
                    ]);
                } else {
                    throw new \Exception("No se encontró la agenda con UUID: {$data['agenda_uuid']}");
                }
            }

            // ✅ CONVERTIR CUPS_CONTRATADO_ID SI EXISTE
            if (!empty($data['cups_contratado_id']) && !is_numeric($data['cups_contratado_id'])) {
                $cupsId = $this->convertUuidToId($data['cups_contratado_id'], 'cups_contratados');
                
                if ($cupsId) {
                    $processedData['cups_contratado_id'] = $cupsId;
                    
                    Log::info('✅ cups_contratado_id convertido', [
                        'uuid' => $data['cups_contratado_id'],
                        'id' => $cupsId
                    ]);
                }
            }

            return $processedData;

        } catch (\Exception $e) {
            Log::error('❌ Error convirtiendo UUIDs a IDs', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }

    /**
     * ✅ NUEVO: Convertir UUID individual a ID
     */
  private function convertUuidToId(?string $uuid, string $table): ?int
{
    if (!$uuid) return null;

    try {
        // ✅ VERIFICAR SI YA ES UN ID NUMÉRICO
        if (is_numeric($uuid)) {
            return (int) $uuid;
        }

        Log::info("🔍 Buscando UUID en tabla {$table}", ['uuid' => $uuid]);

        // ✅ BUSCAR PRIMERO EN ARCHIVOS JSON OFFLINE
        if ($table === 'pacientes') {
            $paciente = $this->offlineService->getPacienteOffline($uuid);
            if ($paciente) {
                // ✅ SI EL PACIENTE OFFLINE TIENE ID, USARLO
                if (isset($paciente['id']) && is_numeric($paciente['id'])) {
                    Log::info("✅ Paciente encontrado en JSON offline con ID", [
                        'uuid' => $uuid,
                        'id' => $paciente['id']
                    ]);
                    return (int) $paciente['id'];
                }
                
                // ✅ SI NO TIENE ID, INTENTAR BUSCAR ONLINE PARA OBTENERLO
                Log::info("⚠️ Paciente offline sin ID, buscando online", ['uuid' => $uuid]);
            }
        }

        if ($table === 'agendas') {
            $agenda = $this->offlineService->getAgendaOffline($uuid);
            if ($agenda) {
                if (isset($agenda['id']) && is_numeric($agenda['id'])) {
                    Log::info("✅ Agenda encontrada en JSON offline con ID", [
                        'uuid' => $uuid,
                        'id' => $agenda['id']
                    ]);
                    return (int) $agenda['id'];
                }
                
                Log::info("⚠️ Agenda offline sin ID, buscando online", ['uuid' => $uuid]);
            }
        }

        // ✅ BUSCAR EN SQLite OFFLINE
        if ($this->offlineService->isSQLiteAvailable()) {
            try {
                $id = DB::connection('offline')->table($table)->where('uuid', $uuid)->value('id');
                
                if ($id) {
                    Log::info("✅ UUID encontrado en SQLite offline", [
                        'uuid' => $uuid,
                        'table' => $table,
                        'id' => $id
                    ]);
                    return (int) $id;
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Error buscando en SQLite offline", [
                    'uuid' => $uuid,
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ BUSCAR ONLINE PARA OBTENER EL ID
        if ($this->apiService->isOnline()) {
            Log::info("🌐 Buscando {$table} online para obtener ID", ['uuid' => $uuid]);
            
            if ($table === 'pacientes') {
                $response = $this->apiService->get("/pacientes/{$uuid}");
                if ($response['success'] && isset($response['data']['id'])) {
                    $pacienteCompleto = $response['data'];
                    
                    // ✅ ACTUALIZAR PACIENTE OFFLINE CON EL ID
                    $this->offlineService->storePacienteOffline($pacienteCompleto, false);
                    
                    Log::info("✅ Paciente obtenido online y actualizado offline", [
                        'uuid' => $uuid,
                        'id' => $pacienteCompleto['id']
                    ]);
                    return (int) $pacienteCompleto['id'];
                }
            }
            
            if ($table === 'agendas') {
                $response = $this->apiService->get("/agendas/{$uuid}");
                if ($response['success'] && isset($response['data']['id'])) {
                    $agendaCompleta = $response['data'];
                    
                    // ✅ ACTUALIZAR AGENDA OFFLINE CON EL ID
                    $this->offlineService->storeAgendaOffline($agendaCompleta, false);
                    
                    Log::info("✅ Agenda obtenida online y actualizada offline", [
                        'uuid' => $uuid,
                        'id' => $agendaCompleta['id']
                    ]);
                    return (int) $agendaCompleta['id'];
                }
            }
        }

        // ✅ ÚLTIMO RECURSO: GENERAR UN ID TEMPORAL PARA MODO OFFLINE PURO
        if (!$this->apiService->isOnline()) {
            $idTemporal = $this->generarIdTemporal($uuid, $table);
            
            Log::warning("🔧 Generando ID temporal para modo offline", [
                'uuid' => $uuid,
                'table' => $table,
                'id_temporal' => $idTemporal
            ]);
            
            return $idTemporal;
        }

        Log::warning("⚠️ UUID no encontrado en ningún lugar", [
            'uuid' => $uuid,
            'table' => $table
        ]);
        return null;

    } catch (\Exception $e) {
        Log::error("❌ Error convirtiendo UUID a ID", [
            'uuid' => $uuid,
            'table' => $table,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * ✅ NUEVO: Generar ID temporal para modo offline
 */
private function generarIdTemporal(string $uuid, string $table): int
{
    // Generar un ID basado en el UUID para consistencia
    $hash = crc32($uuid . $table);
    
    // Asegurar que sea positivo y dentro de rango de INT
    $id = abs($hash) % 999999 + 100000; // Entre 100000 y 1099999
    
    Log::info("🔧 ID temporal generado", [
        'uuid' => $uuid,
        'table' => $table,
        'id_temporal' => $id,
        'hash_original' => $hash
    ]);
    
    return $id;
}
    /**
     * ✅ MOSTRAR CITA
     */
    public function show(string $uuid): array
    {
        try {
            // Intentar obtener online
            if ($this->apiService->isOnline()) {
                try {
                    $url = $this->buildApiUrl('show', ['uuid' => $uuid]);
                    $response = $this->apiService->get($url);
                    
                    if ($response['success']) {
                        $citaData = $response['data'];
                        $this->offlineService->storeCitaOffline($citaData, false);
                        
                        return [
                            'success' => true,
                            'data' => $citaData,
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error obteniendo cita desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Buscar offline
            $cita = $this->offlineService->getCitaOffline($uuid);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'error' => 'Cita no encontrada'
                ];
            }

            return [
                'success' => true,
                'data' => $cita,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo cita', [
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
     * ✅ ACTUALIZAR CITA
     */
    public function update(string $uuid, array $data): array
    {
        try {
            $cita = $this->offlineService->getCitaOffline($uuid);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'error' => 'Cita no encontrada'
                ];
            }

            // Intentar actualizar online
            if ($this->apiService->isOnline()) {
                $url = $this->buildApiUrl('update', ['uuid' => $uuid]);
                $response = $this->apiService->put($url, $data);
                
                if ($response['success']) {
                    $citaData = $response['data'];
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'message' => 'Cita actualizada exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error actualizando cita'
                ];
            }

            // Actualizar offline
            $updatedData = array_merge($cita, $data);
            $this->offlineService->storeCitaOffline($updatedData, true);

            return [
                'success' => true,
                'data' => $updatedData,
                'message' => 'Cita actualizada (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error actualizando cita', [
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
     * ✅ ELIMINAR CITA
     */
    public function destroy(string $uuid): array
    {
        try {
            $cita = $this->offlineService->getCitaOffline($uuid);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'error' => 'Cita no encontrada'
                ];
            }

            // Intentar eliminar online
            if ($this->apiService->isOnline()) {
                $url = $this->buildApiUrl('destroy', ['uuid' => $uuid]);
                $response = $this->apiService->delete($url);
                
                if ($response['success']) {
                    // Marcar como eliminada offline
                    $cita['deleted_at'] = now()->toISOString();
                    $this->offlineService->storeCitaOffline($cita, false);
                    
                    return [
                        'success' => true,
                        'message' => 'Cita eliminada exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error eliminando cita'
                ];
            }

            // Marcar como eliminada offline
            $cita['deleted_at'] = now()->toISOString();
            $this->offlineService->storeCitaOffline($cita, true);

            return [
                'success' => true,
                'message' => 'Cita eliminada (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error eliminando cita', [
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
     * ✅ CITAS DEL DÍA
     */
    public function citasDelDia(string $fecha = null): array
    {
        try {
            $fecha = $fecha ?: now()->format('Y-m-d');
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];

            // Intentar obtener desde API
            if ($this->apiService->isOnline()) {
                try {
                    $url = $this->buildApiUrl('del_dia');
                    $response = $this->apiService->get($url, ['fecha' => $fecha]);
                    
                    if ($response['success']) {
                        return [
                            'success' => true,
                            'data' => $response['data'],
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error obteniendo citas del día desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtener offline
            $citas = $this->offlineService->getCitasOffline($sedeId, ['fecha' => $fecha]);

            return [
                'success' => true,
                'data' => $citas,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo citas del día', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * ✅ CAMBIAR ESTADO DE CITA
     */
    public function cambiarEstado(string $uuid, string $nuevoEstado): array
    {
        try {
            $cita = $this->offlineService->getCitaOffline($uuid);
            
            if (!$cita) {
                return [
                    'success' => false,
                    'error' => 'Cita no encontrada'
                ];
            }

            $data = ['estado' => $nuevoEstado];

            // Intentar actualizar online
            if ($this->apiService->isOnline()) {
                $url = $this->buildApiUrl('cambiar_estado', ['uuid' => $uuid]);
                $response = $this->apiService->put($url, $data);
                
                if ($response['success']) {
                    $citaData = $response['data'];
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'message' => 'Estado de cita actualizado exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error actualizando estado'
                ];
            }

            // Actualizar offline
            $cita['estado'] = $nuevoEstado;
            $this->offlineService->storeCitaOffline($cita, true);

            return [
                'success' => true,
                'data' => $cita,
                'message' => 'Estado actualizado (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error cambiando estado de cita', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'estado' => $nuevoEstado
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * ✅ CITAS POR AGENDA
     */
    public function citasPorAgenda(string $agendaUuid): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];

            // Intentar obtener desde API
            if ($this->apiService->isOnline()) {
                try {
                    $url = $this->buildApiUrl('por_agenda', ['agenda_uuid' => $agendaUuid]);
                    $response = $this->apiService->get($url);
                    
                    if ($response['success']) {
                        return [
                            'success' => true,
                            'data' => $response['data'],
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error obteniendo citas por agenda desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtener offline
            $citas = $this->offlineService->getCitasOffline($sedeId, ['agenda_uuid' => $agendaUuid]);

            return [
                'success' => true,
                'data' => $citas,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo citas por agenda', [
                'error' => $e->getMessage(),
                'agenda_uuid' => $agendaUuid
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

  /**
 * ✅ NUEVO: Obtener horarios disponibles de una agenda
 */
public function getHorariosDisponibles(string $agendaUuid, ?string $fecha = null): array
{
    try {
        $fecha = $fecha ?: now()->format('Y-m-d');
        
        // Obtener agenda
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }
        
        // Si la fecha solicitada es diferente a la fecha de la agenda, retornar vacío
        if ($agenda['fecha'] !== $fecha) {
            return [
                'success' => true,
                'data' => [],
                'message' => 'No hay horarios disponibles para esta fecha'
            ];
        }
        
        $horarios = $this->calcularHorariosDisponibles($agenda, $fecha);
        
        return [
            'success' => true,
            'data' => $horarios,
            'agenda' => $agenda
        ];
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo horarios disponibles', [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}

/**
 * ✅ NUEVO: Calcular horarios disponibles
 */
private function calcularHorariosDisponibles(array $agenda, string $fecha): array
{
    try {
        $horarios = [];
        
        $horaInicio = $agenda['hora_inicio'];
        $horaFin = $agenda['hora_fin'];
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        // Obtener citas existentes
        $user = $this->authService->usuario();
        $citasExistentes = $this->offlineService->getCitasOffline($user['sede_id'], [
            'agenda_uuid' => $agenda['uuid'],
            'fecha' => $fecha
        ]);
        
        // Filtrar solo citas no canceladas
        $citasActivas = array_filter($citasExistentes, function($cita) {
            return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
        });
        
        // Crear array de horarios ocupados
        $horariosOcupados = [];
        foreach ($citasActivas as $cita) {
            $horaCita = date('H:i', strtotime($cita['fecha_inicio']));
            $horariosOcupados[$horaCita] = $cita;
        }
        
        // Generar horarios disponibles
        $inicio = \Carbon\Carbon::createFromFormat('H:i', $horaInicio);
        $fin = \Carbon\Carbon::createFromFormat('H:i', $horaFin);
        
        while ($inicio->lt($fin)) {
            $horarioStr = $inicio->format('H:i');
            $finHorario = $inicio->copy()->addMinutes($intervalo);
            
            $disponible = !isset($horariosOcupados[$horarioStr]);
            
            $horario = [
                'hora_inicio' => $horarioStr,
                'hora_fin' => $finHorario->format('H:i'),
                'fecha_inicio' => $fecha . 'T' . $horarioStr . ':00',
                'fecha_final' => $fecha . 'T' . $finHorario->format('H:i') . ':00',
                'disponible' => $disponible,
                'intervalo' => $intervalo
            ];
            
            if (!$disponible && isset($horariosOcupados[$horarioStr])) {
                $cita = $horariosOcupados[$horarioStr];
                $horario['ocupado_por'] = [
                    'cita_uuid' => $cita['uuid'],
                    'paciente' => $cita['paciente']['nombre_completo'] ?? 'Paciente no identificado',
                    'estado' => $cita['estado']
                ];
            }
            
            $horarios[] = $horario;
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
}
