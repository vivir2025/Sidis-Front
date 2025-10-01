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

    private function buildApiUrl(string $action): string
    {
        $endpoints = config('api.endpoints.citas', []);
        
        Log::info('🔧 Construyendo URL de API', [
            'action' => $action,
            'available_endpoints' => array_keys($endpoints),
            'selected_endpoint' => $endpoints[$action] ?? '/citas'
        ]);
        
        return $endpoints[$action] ?? '/citas';
    }

   public function index(array $filters = [], int $page = 1): array
{
    try {
        Log::info("🩺 CitaService::index - Iniciando", [
            'filters' => $filters,
            'page' => $page
        ]);

        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];

        $apiParams = array_merge($filters, [
            'page' => $page,
            'sede_id' => $sedeId
        ]);

        $apiParams = array_filter($apiParams, function($value) {
            return !empty($value) && $value !== '';
        });

        if ($this->apiService->isOnline()) {
            try {
                $url = $this->buildApiUrl('index');
                $response = $this->apiService->get($url, $apiParams);

                if ($response['success'] && isset($response['data'])) {
                    $citas = $response['data']['data'] ?? $response['data'];
                    $meta = $response['data']['meta'] ?? $response['meta'] ?? [];
                    
                    if (!empty($citas)) {
                        foreach ($citas as &$cita) { // ✅ USAR REFERENCIA PARA MODIFICAR DIRECTAMENTE
                            // ✅ CORREGIR FECHA PRINCIPAL
                            if (isset($cita['fecha'])) {
                                $fechaOriginal = $cita['fecha'];
                                
                                // Si es un timestamp completo, extraer solo la fecha
                                if (strpos($fechaOriginal, 'T') !== false) {
                                    $fechaCorregida = explode('T', $fechaOriginal)[0];
                                    $cita['fecha'] = $fechaCorregida;
                                    
                                    Log::info('✅ Fecha corregida desde API', [
                                        'cita_uuid' => $cita['uuid'],
                                        'fecha_original' => $fechaOriginal,
                                        'fecha_corregida' => $fechaCorregida
                                    ]);
                                }
                            }
                            
                            // ✅ CORREGIR FECHA_INICIO Y FECHA_FINAL TAMBIÉN
                            if (isset($cita['fecha_inicio'])) {
                                $fechaInicio = $cita['fecha_inicio'];
                                if (strpos($fechaInicio, 'T') !== false) {
                                    $partes = explode('T', $fechaInicio);
                                    $fechaLimpia = $partes[0];
                                    $horaLimpia = isset($partes[1]) ? substr($partes[1], 0, 8) : '00:00:00';
                                    $cita['fecha_inicio'] = $fechaLimpia . 'T' . $horaLimpia;
                                }
                            }
                            
                            if (isset($cita['fecha_final'])) {
                                $fechaFinal = $cita['fecha_final'];
                                if (strpos($fechaFinal, 'T') !== false) {
                                    $partes = explode('T', $fechaFinal);
                                    $fechaLimpia = $partes[0];
                                    $horaLimpia = isset($partes[1]) ? substr($partes[1], 0, 8) : '00:00:00';
                                    $cita['fecha_final'] = $fechaLimpia . 'T' . $horaLimpia;
                                }
                            }
                            
                            // ✅ AHORA GUARDAR OFFLINE CON FECHAS YA CORREGIDAS
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

        $cleanFilters = $this->cleanFiltersForOffline($filters);

         // ✅ MODO OFFLINE MEJORADO
        Log::info('📱 Trabajando en modo offline', [
            'sede_id' => $sedeId,
            'filters_originales' => $filters,
            'filters_limpios' => $cleanFilters
        ]);

        $citas = $this->offlineService->getCitasOffline($sedeId, $cleanFilters);
        
        // ✅ APLICAR FILTROS ADICIONALES EN MEMORIA SI ES NECESARIO
        if (!empty($filters['paciente_documento'])) {
            $citas = array_filter($citas, function($cita) use ($filters) {
                return isset($cita['paciente']['documento']) && 
                       str_contains($cita['paciente']['documento'], $filters['paciente_documento']);
            });
        }

        if (!empty($filters['estado'])) {
            $citas = array_filter($citas, function($cita) use ($filters) {
                return ($cita['estado'] ?? '') === $filters['estado'];
            });
        }

        // ✅ AGREGAR INFORMACIÓN DE SYNC STATUS
        $citas = array_map(function($cita) {
            $cita['offline'] = ($cita['sync_status'] ?? 'synced') === 'pending';
            return $cita;
        }, $citas);
        
        $perPage = 15;
        $total = count($citas);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($citas, $offset, $perPage);

        Log::info('📊 Datos offline obtenidos', [
            'total_encontradas' => $total,
            'pagina_actual' => $page,
            'datos_en_pagina' => count($paginatedData)
        ]);

        return [
            'success' => true,
            'data' => $paginatedData,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total)
            ],
            'message' => "📱 Modo offline - {$total} citas encontradas",
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error en CitaService::index', [
            'error' => $e->getMessage(),
            'filters' => $filters,
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => true,
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'from' => 0,
                'to' => 0
            ],
            'message' => '❌ Error cargando citas: ' . $e->getMessage(),
            'offline' => true
        ];
    }
}

// ✅ NUEVO MÉTODO PARA LIMPIAR FILTROS
private function cleanFiltersForOffline(array $filters): array
{
    $cleanFilters = [];
    
    foreach ($filters as $key => $value) {
        if (empty($value)) continue;
        
        // ✅ LIMPIAR FECHAS
        if (in_array($key, ['fecha', 'fecha_inicio', 'fecha_fin'])) {
            if (strpos($value, 'T') !== false) {
                $cleanFilters[$key] = explode('T', $value)[0];
            } else {
                $cleanFilters[$key] = $value;
            }
        } else {
            $cleanFilters[$key] = $value;
        }
    }
    
    Log::info('🧹 Filtros limpiados para offline', [
        'originales' => $filters,
        'limpios' => $cleanFilters
    ]);
    
    return $cleanFilters;
}



   public function store(array $data): array
{
    try {
        Log::info('🩺 CitaService::store - Datos recibidos', [
            'data' => $data,
            'fecha_original' => $data['fecha'] ?? 'NO_ENVIADO',
            'fecha_inicio_original' => $data['fecha_inicio'] ?? 'NO_ENVIADO',
            'fecha_final_original' => $data['fecha_final'] ?? 'NO_ENVIADO',
            'has_cups_contratado_uuid' => isset($data['cups_contratado_uuid']),
            'cups_contratado_uuid' => $data['cups_contratado_uuid'] ?? 'NO_ENVIADO'
        ]);

        $user = $this->authService->usuario();
        $data['sede_id'] = $user['sede_id'];
        $data['usuario_creo_cita_id'] = $user['id'];
        $data['estado'] = $data['estado'] ?? 'PROGRAMADA';

        // ✅ CORREGIR FECHA PARA EVITAR DESFASE - IGUAL QUE EN AGENDAS
        if (isset($data['fecha'])) {
            // Extraer solo la fecha sin zona horaria, igual que en agendas
            $fechaOriginal = $data['fecha'];
            if (strpos($fechaOriginal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaOriginal)[0]; // "2025-09-12T00:00:00.000Z" -> "2025-09-12"
                $data['fecha'] = $fechaLimpia;
            }
            
            Log::info('✅ Fecha corregida en cita', [
                'fecha_original' => $fechaOriginal,
                'fecha_limpia' => $data['fecha']
            ]);
        }

        // ✅ TAMBIÉN CORREGIR fecha_inicio Y fecha_final SI VIENEN CON ZONA HORARIA
        if (isset($data['fecha_inicio'])) {
            $fechaInicio = $data['fecha_inicio'];
            if (strpos($fechaInicio, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaInicio)[0]; // "2025-09-12T09:00:00" -> "2025-09-12"
                $horaLimpia = explode('T', $fechaInicio)[1]; // "2025-09-12T09:00:00" -> "09:00:00"
                $horaLimpia = substr($horaLimpia, 0, 8); // "09:00:00.000Z" -> "09:00:00"
                
                // Reconstruir sin zona horaria
                $data['fecha_inicio'] = $fechaLimpia . 'T' . $horaLimpia;
            }
        }

        if (isset($data['fecha_final'])) {
            $fechaFinal = $data['fecha_final'];
            if (strpos($fechaFinal, 'T') !== false) {
                $fechaLimpia = explode('T', $fechaFinal)[0]; // "2025-09-12T09:15:00" -> "2025-09-12"
                $horaLimpia = explode('T', $fechaFinal)[1]; // "2025-09-12T09:15:00" -> "09:15:00"
                $horaLimpia = substr($horaLimpia, 0, 8); // "09:15:00.000Z" -> "09:15:00"
                
                // Reconstruir sin zona horaria
                $data['fecha_final'] = $fechaLimpia . 'T' . $horaLimpia;
            }
        }

        Log::info('✅ Fechas corregidas para cita', [
            'fecha' => $data['fecha'] ?? 'NO_SET',
            'fecha_inicio_corregida' => $data['fecha_inicio'] ?? 'NO_SET',
            'fecha_final_corregida' => $data['fecha_final'] ?? 'NO_SET'
        ]);

        $isOnline = $this->apiService->isOnline();
        
        if ($isOnline) {
            Log::info('🌐 Conexión disponible, intentando crear cita en API...');
            
            try {
                $endpoint = $this->buildApiUrl('store');
                Log::info('📤 Enviando cita a API', [
                    'endpoint' => $endpoint,
                    'data_keys' => array_keys($data),
                    'cups_contratado_uuid' => $data['cups_contratado_uuid'] ?? 'NO_ENVIADO'
                ]);
                
                $response = $this->apiService->post($endpoint, $data);
                
                Log::info('📥 Respuesta de API recibida', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'has_error' => isset($response['error']),
                    'response_keys' => array_keys($response)
                ]);
                
                if ($response['success'] ?? false) {
                    $citaData = $response['data'];
                    
                    // ✅ GUARDAR OFFLINE INMEDIATAMENTE DESPUÉS DEL ÉXITO ONLINE
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    // ✅ ACTUALIZAR TAMBIÉN LA AGENDA OFFLINE PARA REFLEJAR EL CUPO OCUPADO
                    $this->actualizarAgendaOfflineDespuesDeCita($citaData['agenda_uuid']);
                    
                    Log::info('✅ Cita creada online y sincronizada offline', [
                        'cita_uuid' => $citaData['uuid'],
                        'agenda_uuid' => $citaData['agenda_uuid']
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'message' => '✅ Cita creada exitosamente en el servidor',
                        'offline' => false
                    ];
                }
                
                Log::warning('⚠️ API respondió con error, creando offline como fallback', [
                    'api_error' => $response['error'] ?? 'Error desconocido',
                    'full_response' => $response
                ]);
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Excepción conectando con API, creando offline como fallback', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ CREAR OFFLINE
        Log::info('💾 Creando cita en modo offline...');
        
        $data['uuid'] = \Illuminate\Support\Str::uuid();
        $this->offlineService->storeCitaOffline($data, true);

        // ✅ ACTUALIZAR AGENDA OFFLINE PARA REFLEJAR EL CUPO OCUPADO
        $this->actualizarAgendaOfflineDespuesDeCita($data['agenda_uuid']);

        Log::info('✅ Cita creada offline exitosamente', [
            'uuid' => $data['uuid'],
            'agenda_uuid' => $data['agenda_uuid'],
            'fecha_corregida' => $data['fecha'],
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
            'data' => $data
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ];
    }
}

private function actualizarAgendaOfflineDespuesDeCita(string $agendaUuid): void
{
    try {
        Log::info('🔄 INICIO: actualizarAgendaOfflineDespuesDeCita', [
            'agenda_uuid' => $agendaUuid,
            'timestamp' => now()->toISOString()
        ]);
        
        // ✅ OBTENER LA AGENDA OFFLINE
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            Log::error('❌ CRÍTICO: No se encontró agenda offline para actualizar', [
                'agenda_uuid' => $agendaUuid
            ]);
            return;
        }
        
        Log::info('✅ Agenda encontrada para actualizar', [
            'agenda_uuid' => $agendaUuid,
            'agenda_data' => $agenda
        ]);
        
        // ✅ USAR LA SEDE DE LA AGENDA, NO DEL USUARIO
        $sedeAgenda = $agenda['sede_id'];
        $originalSyncStatus = $agenda['sync_status'] ?? 'synced';
        
        // ✅ EXTRAER FECHA LIMPIA DE LA AGENDA
        $fechaAgenda = $agenda['fecha'];
        if (strpos($fechaAgenda, 'T') !== false) {
            $fechaAgenda = explode('T', $fechaAgenda)[0];
        }
        
        Log::info('🔍 Datos extraídos de la agenda', [
            'agenda_uuid' => $agendaUuid,
            'sede_agenda' => $sedeAgenda,
            'fecha_agenda_original' => $agenda['fecha'],
            'fecha_agenda_limpia' => $fechaAgenda,
            'sync_status_original' => $originalSyncStatus,
            'hora_inicio' => $agenda['hora_inicio'] ?? 'NO_DEFINIDA',
            'hora_fin' => $agenda['hora_fin'] ?? 'NO_DEFINIDA',
            'intervalo' => $agenda['intervalo'] ?? 'NO_DEFINIDO'
        ]);
        
        // ✅ RECALCULAR CUPOS CON LA SEDE DE LA AGENDA
        Log::info('🔍 Obteniendo citas para recalcular cupos', [
            'sede_agenda' => $sedeAgenda,
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fechaAgenda
        ]);
        
        $citasActivas = $this->offlineService->getCitasOffline($sedeAgenda, [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fechaAgenda
        ]);
        
        Log::info('📊 Citas obtenidas para recálculo', [
            'total_citas_encontradas' => count($citasActivas),
            'citas_preview' => array_slice($citasActivas, 0, 3) // Solo las primeras 3 para no saturar logs
        ]);
        
        // ✅ FILTRAR SOLO CITAS NO CANCELADAS
        $citasValidas = array_filter($citasActivas, function($cita) {
            $estado = $cita['estado'] ?? '';
            $esValida = !in_array($estado, ['CANCELADA', 'NO_ASISTIO']);
            
            if (!$esValida) {
                Log::info('🚫 Cita excluida del conteo', [
                    'cita_uuid' => $cita['uuid'] ?? 'SIN_UUID',
                    'estado' => $estado,
                    'motivo' => 'Estado cancelada o no asistió'
                ]);
            }
            
            return $esValida;
        });
        
        Log::info('📋 Citas válidas después del filtro', [
            'citas_totales' => count($citasActivas),
            'citas_validas' => count($citasValidas),
            'citas_excluidas' => count($citasActivas) - count($citasValidas)
        ]);
        
        // ✅ CALCULAR CUPOS TOTALES
        $horaInicio = $agenda['hora_inicio'] ?? '08:00:00';
        $horaFin = $agenda['hora_fin'] ?? '17:00:00';
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        if ($intervalo <= 0) {
            Log::warning('⚠️ Intervalo inválido, usando 15 minutos por defecto', [
                'intervalo_original' => $agenda['intervalo'] ?? 'NO_DEFINIDO'
            ]);
            $intervalo = 15;
        }
        
        try {
            $inicio = \Carbon\Carbon::parse($horaInicio);
            $fin = \Carbon\Carbon::parse($horaFin);
            $duracionMinutos = $fin->diffInMinutes($inicio);
            $totalCupos = floor($duracionMinutos / $intervalo);
        } catch (\Exception $e) {
            Log::error('❌ Error calculando horarios', [
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'error' => $e->getMessage()
            ]);
            $totalCupos = 0;
            $duracionMinutos = 0;
        }
        
        Log::info('🧮 Cálculo de cupos', [
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'intervalo_minutos' => $intervalo,
            'duracion_total_minutos' => $duracionMinutos,
            'total_cupos_calculados' => $totalCupos,
            'citas_validas_count' => count($citasValidas)
        ]);
        
        // ✅ CALCULAR CUPOS DISPONIBLES
        $cuposDisponibles = max(0, $totalCupos - count($citasValidas));
        
        // ✅ ACTUALIZAR AGENDA
        $agenda['cupos_disponibles'] = $cuposDisponibles;
        $agenda['citas_count'] = count($citasValidas);
        $agenda['total_cupos'] = $totalCupos;
        $agenda['updated_at'] = now()->toISOString();
        
        Log::info('🔄 Valores calculados para actualización', [
            'cupos_disponibles_nuevo' => $cuposDisponibles,
            'cupos_disponibles_anterior' => $agenda['cupos_disponibles'] ?? 'NO_DEFINIDO',
            'citas_count_nuevo' => count($citasValidas),
            'total_cupos' => $totalCupos
        ]);
        
        // ✅ PRESERVAR EL ESTADO DE SINCRONIZACIÓN ORIGINAL
        $needsSync = ($originalSyncStatus === 'pending');
        
        Log::info('💾 Guardando agenda actualizada', [
            'agenda_uuid' => $agendaUuid,
            'needs_sync' => $needsSync,
            'sync_status_original' => $originalSyncStatus
        ]);
        
        // ✅ GUARDAR AGENDA ACTUALIZADA
        $resultado = $this->offlineService->storeAgendaOffline($agenda, $needsSync);
        
        Log::info('✅ ÉXITO: Agenda offline actualizada completamente', [
            'agenda_uuid' => $agendaUuid,
            'sede_agenda' => $sedeAgenda,
            'fecha_agenda_limpia' => $fechaAgenda,
            'cupos_disponibles_final' => $agenda['cupos_disponibles'],
            'citas_count_final' => $agenda['citas_count'],
            'total_cupos_final' => $totalCupos,
            'sync_status_preservado' => $needsSync ? 'pending' : 'synced',
            'resultado_guardado' => $resultado ? 'ÉXITO' : 'FALLO',
            'timestamp_fin' => now()->toISOString()
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ CRÍTICO: Error en actualizarAgendaOfflineDespuesDeCita', [
            'agenda_uuid' => $agendaUuid,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}



   public function show(string $uuid): array
{
    try {
        Log::info('🔍 CitaService::show iniciado', [
            'uuid' => $uuid,
            'is_online' => $this->apiService->isOnline()
        ]);

        // ✅ INTENTAR OBTENER DESDE API PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $endpoint = $this->buildApiUrl('show');
                $url = str_replace('{uuid}', $uuid, $endpoint);
                $response = $this->apiService->get($url);
                
                if ($response['success']) {
                    $citaData = $response['data'];
                    
                    // ✅ CORREGIR FECHAS ANTES DE GUARDAR OFFLINE
                    if (isset($citaData['fecha'])) {
                        $fechaOriginal = $citaData['fecha'];
                        if (strpos($fechaOriginal, 'T') !== false) {
                            $fechaCorregida = explode('T', $fechaOriginal)[0];
                            $citaData['fecha'] = $fechaCorregida;
                        }
                    }
                    
                    // ✅ ENRIQUECER CON DATOS DE LA AGENDA DESDE OFFLINE
                    if (!empty($citaData['agenda_uuid'])) {
                        $agendaOffline = $this->offlineService->getAgendaOffline($citaData['agenda_uuid']);
                        if ($agendaOffline) {
                            $citaData['agenda'] = $agendaOffline;
                            Log::info('✅ Agenda offline agregada a cita desde API', [
                                'agenda_uuid' => $agendaOffline['uuid'],
                                'etiqueta' => $agendaOffline['etiqueta'] ?? 'Sin etiqueta'
                            ]);
                        } else {
                            Log::warning('⚠️ Agenda no encontrada offline para cita desde API', [
                                'agenda_uuid' => $citaData['agenda_uuid']
                            ]);
                        }
                    }
                    
                    // ✅ ENRIQUECER CON DATOS DEL PACIENTE DESDE OFFLINE
                    if (!empty($citaData['paciente_uuid'])) {
                        $pacienteOffline = $this->offlineService->getPacienteOffline($citaData['paciente_uuid']);
                        if ($pacienteOffline) {
                            $citaData['paciente'] = $pacienteOffline;
                            Log::info('✅ Paciente offline agregado a cita desde API', [
                                'paciente_uuid' => $pacienteOffline['uuid'],
                                'nombre' => $pacienteOffline['nombre_completo'] ?? 'N/A'
                            ]);
                        }
                    }
                    
                    // Guardar offline para futura referencia
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    Log::info('✅ Cita obtenida desde API y enriquecida con datos offline', [
                        'uuid' => $uuid,
                        'has_agenda' => isset($citaData['agenda']),
                        'has_paciente' => isset($citaData['paciente']),
                        'agenda_etiqueta' => $citaData['agenda']['etiqueta'] ?? 'No disponible'
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo cita desde API, usando offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ OBTENER DESDE OFFLINE CON DATOS ENRIQUECIDOS
        Log::info('📱 Obteniendo cita desde offline');
        $cita = $this->offlineService->getCitaOffline($uuid);
        
        if (!$cita) {
            Log::warning('⚠️ Cita no encontrada offline', ['uuid' => $uuid]);
            return [
                'success' => false,
                'error' => 'Cita no encontrada'
            ];
        }

        Log::info('✅ Cita obtenida desde offline', [
            'uuid' => $uuid,
            'has_paciente' => isset($cita['paciente']),
            'has_agenda' => isset($cita['agenda']),
            'agenda_etiqueta' => $cita['agenda']['etiqueta'] ?? 'No disponible'
        ]);

        return [
            'success' => true,
            'data' => $cita,
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error en CitaService::show', [
            'error' => $e->getMessage(),
            'uuid' => $uuid,
            'trace' => $e->getTraceAsString()
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
 * ✅ MÉTODO PRINCIPAL CORREGIDO - CAMBIAR ESTADO (REEMPLAZAR EL EXISTENTE)
 */
public function cambiarEstado(string $uuid, string $nuevoEstado): array
{
    try {
        // ✅ DEBUG CRÍTICO DEL UUID
        Log::info('🔍 DEBUG CRÍTICO - CitaService cambiarEstado INICIO', [
            'uuid_recibido' => $uuid,
            'uuid_length' => strlen($uuid),
            'nuevo_estado' => $nuevoEstado
        ]);

        // ✅ VALIDACIÓN EXTREMA DEL UUID
        if (empty($uuid) || strlen($uuid) !== 36) {
            Log::error('❌ UUID inválido en CitaService', [
                'uuid' => $uuid,
                'longitud' => strlen($uuid ?? '')
            ]);
            
            return [
                'success' => false,
                'error' => 'UUID de cita inválido'
            ];
        }

        // ✅ OBTENER CITA OFFLINE PRIMERO
        $citaOffline = $this->offlineService->getCitaOffline($uuid);
        
        if (!$citaOffline) {
            Log::error('❌ Cita no encontrada offline', ['uuid' => $uuid]);
            return [
                'success' => false,
                'error' => 'Cita no encontrada'
            ];
        }

        Log::info('✅ Cita encontrada offline', [
            'uuid' => $uuid,
            'estado_actual' => $citaOffline['estado'] ?? 'N/A'
        ]);

        // ✅ INTENTAR CAMBIO ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            Log::info('🌐 Intentando cambio de estado online', [
                'uuid' => $uuid,
                'estado' => $nuevoEstado
            ]);
            
            $resultadoOnline = $this->cambiarEstadoOnline($uuid, $nuevoEstado);
            
            if ($resultadoOnline['success']) {
                Log::info('✅ Estado cambiado exitosamente online', [
                    'uuid' => $uuid,
                    'estado' => $nuevoEstado
                ]);
                return $resultadoOnline;
            } else {
                Log::warning('⚠️ Fallo cambio online, intentando offline', [
                    'uuid' => $uuid,
                    'error' => $resultadoOnline['error'] ?? 'Error desconocido'
                ]);
            }
        }

        // ✅ FALLBACK A CAMBIO OFFLINE
        Log::info('🔄 Realizando cambio offline', [
            'uuid' => $uuid,
            'estado' => $nuevoEstado
        ]);
        
        return $this->cambiarEstadoOffline($uuid, $nuevoEstado);

    } catch (\Exception $e) {
        Log::error('❌ Error crítico en CitaService::cambiarEstado', [
            'uuid' => $uuid ?? 'N/A',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // ✅ FALLBACK FINAL A OFFLINE
        return $this->cambiarEstadoOffline($uuid, $nuevoEstado);
    }
}
private function cambiarEstadoOffline(string $uuid, string $nuevoEstado): array
{
    try {
        Log::info('📱 CitaService - Cambio offline', [
            'cita_uuid' => $uuid,
            'nuevo_estado' => $nuevoEstado
        ]);

        $usuario = $this->authService->usuario();
        $sedeId = $usuario['sede_id'] ?? 1;

        $actualizado = $this->offlineService->actualizarEstadoCitaOffline($uuid, $nuevoEstado, $sedeId);

        if ($actualizado) {
            return [
                'success' => true,
                'data' => ['estado' => $nuevoEstado],
                'offline' => true
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error actualizando estado offline'
            ];
        }

    } catch (\Exception $e) {
        Log::error('❌ Error en cambio offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);

        return [
            'success' => false,
            'error' => 'Error en cambio offline: ' . $e->getMessage()
        ];
    }
}
private function getEndpoint(string $action): string
{
    $endpoints = [
        'index' => '/citas',
        'store' => '/citas',
        'show' => '/citas/{uuid}',
        'update' => '/citas/{uuid}',
        'destroy' => '/citas/{uuid}',
        'del_dia' => '/citas/del-dia',
        'cambiar_estado' => '/citas/{uuid}/estado', // ✅ ESTE ES EL CORRECTO
        'por_agenda' => '/agendas/{uuid}/citas',
        'horarios_disponibles' => '/agendas/{uuid}/horarios-disponibles'
    ];

    $endpoint = $endpoints[$action] ?? '/citas';
    
    Log::info('🔧 Construyendo URL de API con getEndpoint', [
        'action' => $action,
        'available_endpoints' => array_keys($endpoints),
        'selected_endpoint' => $endpoint
    ]);

    return $endpoint;
}
/**
 * ✅ MÉTODO CORREGIDO - REEMPLAZAR UUID EN URL
 */
private function cambiarEstadoOnline(string $uuid, string $nuevoEstado): array
{
    try {
        Log::info('🔍 DEBUG CRÍTICO - cambiarEstadoOnline INICIO', [
            'uuid_recibido' => $uuid,
            'uuid_length' => strlen($uuid),
            'nuevo_estado' => $nuevoEstado
        ]);

        // ✅ CONSTRUIR ENDPOINT CORRECTAMENTE - REEMPLAZAR {uuid}
        $endpointTemplate = $this->getEndpoint('cambiar_estado'); // "/citas/{uuid}/estado"
        $endpoint = str_replace('{uuid}', $uuid, $endpointTemplate);

        Log::info('🔧 Construcción de endpoint CORREGIDA', [
            'template_original' => $endpointTemplate,
            'uuid_para_reemplazar' => $uuid,
            'endpoint_final' => $endpoint,
            'contiene_placeholder' => strpos($endpoint, '{uuid}') !== false ? 'SÍ - ERROR' : 'NO - CORRECTO'
        ]);

        // ✅ VERIFICACIÓN CRÍTICA - NO DEBE CONTENER {uuid}
        if (strpos($endpoint, '{uuid}') !== false) {
            Log::error('❌ ENDPOINT TODAVÍA CONTIENE PLACEHOLDER', [
                'endpoint_malformado' => $endpoint,
                'template' => $endpointTemplate,
                'uuid' => $uuid
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno: endpoint malformado'
            ];
        }

        Log::info('🌐 CitaService - Llamando API externa con endpoint corregido', [
            'endpoint_final' => $endpoint,
            'uuid_usado' => $uuid,
            'data_a_enviar' => ['estado' => $nuevoEstado]
        ]);

        // ✅ HACER PETICIÓN CON EL ENDPOINT CORREGIDO
        $response = $this->apiService->put($endpoint, [
            'estado' => $nuevoEstado
        ]);

        Log::info('📡 Respuesta API recibida', [
            'response' => $response,
            'endpoint_usado' => $endpoint
        ]);

        if (isset($response['success']) && $response['success']) {
            // ✅ ACTUALIZAR CACHÉ OFFLINE
            $usuario = $this->authService->usuario();
            $sedeId = $usuario['sede_id'] ?? 1;
            
            $this->offlineService->actualizarEstadoCitaOffline($uuid, $nuevoEstado, $sedeId);

            return [
                'success' => true,
                'data' => $response['data'] ?? ['estado' => $nuevoEstado],
                'offline' => false
            ];
        } else {
            Log::error('❌ API devolvió error', [
                'response' => $response,
                'endpoint' => $endpoint
            ]);

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error en API externa'
            ];
        }

    } catch (\Exception $e) {
        Log::error('❌ Excepción en cambiarEstadoOnline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid ?? 'N/A',
            'trace' => $e->getTraceAsString()
        ]);

        // ✅ FALLBACK A OFFLINE
        Log::info('🔄 Fallback a cambio offline por excepción');
        return $this->cambiarEstadoOffline($uuid, $nuevoEstado);
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

 public function getHorariosDisponibles(string $agendaUuid, ?string $fecha = null): array
{
    try {
        $fecha = $fecha ?: now()->format('Y-m-d');
        
        // ✅ LIMPIAR FECHA SI VIENE CON TIMESTAMP
        if (strpos($fecha, 'T') !== false) {
            $fecha = explode('T', $fecha)[0];
        }
        
        Log::info('🔍 Obteniendo horarios disponibles', [
            'agenda_uuid' => $agendaUuid,
            'fecha_solicitada' => $fecha
        ]);
        
        // Obtener agenda
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            return [
                'success' => false,
                'error' => 'Agenda no encontrada'
            ];
        }
        
        // ✅ EXTRAER FECHA DE LA AGENDA SIN TIMESTAMP
        $fechaAgenda = $agenda['fecha'];
        if (strpos($fechaAgenda, 'T') !== false) {
            $fechaAgenda = explode('T', $fechaAgenda)[0];
        }
        
        Log::info('📅 Comparando fechas', [
            'fecha_solicitada' => $fecha,
            'fecha_agenda' => $fechaAgenda,
            'coinciden' => $fechaAgenda === $fecha
        ]);
        
        // Si la fecha solicitada es diferente a la fecha de la agenda, retornar vacío
        if ($fechaAgenda !== $fecha) {
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
 * ✅ CORREGIDO: Calcular horarios disponibles
 */
private function calcularHorariosDisponibles(array $agenda, string $fecha): array
{
    try {
        $horarios = [];
        
        $horaInicio = $agenda['hora_inicio'];
        $horaFin = $agenda['hora_fin'];
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        // ✅ USAR LA SEDE DE LA AGENDA, NO DEL USUARIO
        $sedeAgenda = $agenda['sede_id']; // ← CAMBIO CRÍTICO
        
        // ✅ EXTRAER SOLO LA FECHA (YYYY-MM-DD) SIN ZONA HORARIA
        $fechaLimpia = $fecha;
        if (strpos($fecha, 'T') !== false) {
            $fechaLimpia = explode('T', $fecha)[0];
        }
        
        Log::info('🔍 Calculando horarios con sede de la agenda', [
            'fecha_original' => $fecha,
            'fecha_limpia' => $fechaLimpia,
            'agenda_uuid' => $agenda['uuid'],
            'sede_agenda' => $sedeAgenda // ← USAR SEDE DE LA AGENDA
        ]);
        
        // ✅ OBTENER CITAS EXISTENTES CON SEDE DE LA AGENDA
        $citasExistentes = $this->offlineService->getCitasOffline($sedeAgenda, [ // ← CAMBIO AQUÍ
            'agenda_uuid' => $agenda['uuid'],
            'fecha' => $fechaLimpia
        ]);
        
        // ✅ SI ESTAMOS ONLINE, TAMBIÉN VERIFICAR CITAS RECIENTES DE LA API
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/agendas/{$agenda['uuid']}/citas", [
                    'fecha' => $fechaLimpia
                ]);
                
                if ($response['success'] && isset($response['data'])) {
                    $citasApi = $response['data'];
                    $uuidsOffline = array_column($citasExistentes, 'uuid');
                    
                    foreach ($citasApi as $citaApi) {
                        if (!in_array($citaApi['uuid'], $uuidsOffline)) {
                            $citasExistentes[] = $citaApi;
                            // También guardar offline para futura referencia
                            $this->offlineService->storeCitaOffline($citaApi, false);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo citas recientes de API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Filtrar solo citas no canceladas
        $citasActivas = array_filter($citasExistentes, function($cita) {
            return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
        });
        
        Log::info('📊 Citas encontradas para horarios con sede correcta', [
            'total_citas' => count($citasExistentes),
            'citas_activas' => count($citasActivas),
            'fecha_consulta' => $fechaLimpia,
            'sede_agenda' => $sedeAgenda // ← CONFIRMAR SEDE USADA
        ]);
        
        // Crear array de horarios ocupados con información del paciente
        $horariosOcupados = [];
        foreach ($citasActivas as $cita) {
            // ✅ EXTRAER HORA DE FECHA_INICIO CORRECTAMENTE
            $fechaInicioCita = $cita['fecha_inicio'];
            if (strpos($fechaInicioCita, 'T') !== false) {
                $horaCita = explode('T', $fechaInicioCita)[1]; // "2025-09-12T09:01:00" -> "09:01:00"
                $horaCita = substr($horaCita, 0, 5); // "09:01:00" -> "09:01"
            } else {
                $horaCita = date('H:i', strtotime($fechaInicioCita));
            }
            
            $horariosOcupados[$horaCita] = [
                'cita_uuid' => $cita['uuid'],
                'paciente' => $cita['paciente']['nombre_completo'] ?? 'Paciente no identificado',
                'estado' => $cita['estado']
            ];
        }
        
        Log::info('🕒 Horarios ocupados identificados', [
            'horarios_ocupados' => array_keys($horariosOcupados)
        ]);
        
        // ✅ GENERAR TODOS LOS HORARIOS (disponibles y ocupados)
        $inicio = \Carbon\Carbon::createFromFormat('H:i', $horaInicio);
        $fin = \Carbon\Carbon::createFromFormat('H:i', $horaFin);
        
        while ($inicio->lt($fin)) {
            $horarioStr = $inicio->format('H:i');
            $finHorario = $inicio->copy()->addMinutes($intervalo);
            
            $disponible = !isset($horariosOcupados[$horarioStr]);
            
            $horario = [
                'hora_inicio' => $horarioStr,
                'hora_fin' => $finHorario->format('H:i'),
                'fecha_inicio' => $fechaLimpia . 'T' . $horarioStr . ':00',
                'fecha_final' => $fechaLimpia . 'T' . $finHorario->format('H:i') . ':00',
                'disponible' => $disponible,
                'intervalo' => $intervalo
            ];
            
            if (!$disponible && isset($horariosOcupados[$horarioStr])) {
                $horario['ocupado_por'] = $horariosOcupados[$horarioStr];
            }
            
            $horarios[] = $horario;
            $inicio->addMinutes($intervalo);
        }
        
        Log::info('✅ Horarios calculados con sede correcta', [
            'agenda_uuid' => $agenda['uuid'],
            'fecha_limpia' => $fechaLimpia,
            'sede_agenda' => $sedeAgenda, // ← CONFIRMAR SEDE USADA
            'total_horarios' => count($horarios),
            'horarios_disponibles' => count(array_filter($horarios, fn($h) => $h['disponible'])),
            'horarios_ocupados' => count(array_filter($horarios, fn($h) => !$h['disponible']))
        ]);
        
        return $horarios;
        
    } catch (\Exception $e) {
        Log::error('Error calculando horarios disponibles', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [];
    }
}

}
