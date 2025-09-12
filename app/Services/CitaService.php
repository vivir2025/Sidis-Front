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

        // ✅ MODO OFFLINE - INCLUIR sync_status Y FILTROS
        $citas = $this->offlineService->getCitasOffline($sedeId, $filters);
        
        // ✅ AGREGAR INFORMACIÓN DE SYNC STATUS
        $citas = array_map(function($cita) {
            // Marcar como offline si tiene sync_status pending
            $cita['offline'] = ($cita['sync_status'] ?? 'synced') === 'pending';
            return $cita;
        }, $citas);
        
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
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
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
                'total' => 0,
                'from' => 0,
                'to' => 0
            ],
            'message' => '❌ Error cargando citas: ' . $e->getMessage(),
            'offline' => true
        ];
    }
}

    /**
     * ✅ CREAR CITA - VERSIÓN SIMPLIFICADA SIN CONVERSIÓN DE UUIDs
     */
   public function store(array $data): array
{
    try {
        Log::info('🩺 CitaService::store - Datos recibidos', [
            'data' => $data,
            'has_cups_contratado_uuid' => isset($data['cups_contratado_uuid']),
            'cups_contratado_uuid' => $data['cups_contratado_uuid'] ?? 'NO_ENVIADO'
        ]);

        $user = $this->authService->usuario();
        $data['sede_id'] = $user['sede_id'];
        $data['usuario_creo_cita_id'] = $user['id'];
        $data['estado'] = $data['estado'] ?? 'PROGRAMADA';

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
        // Obtener la agenda offline
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if (!$agenda) {
            Log::warning('⚠️ No se encontró agenda offline para actualizar', [
                'agenda_uuid' => $agendaUuid
            ]);
            return;
        }
        
        // ✅ EXTRAER FECHA LIMPIA DE LA AGENDA
        $fechaAgenda = $agenda['fecha'];
        if (strpos($fechaAgenda, 'T') !== false) {
            $fechaAgenda = explode('T', $fechaAgenda)[0];
        }
        
        // Recalcular cupos disponibles
        $user = $this->authService->usuario();
        $citasActivas = $this->offlineService->getCitasOffline($user['sede_id'], [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fechaAgenda // ✅ USAR FECHA LIMPIA
        ]);
        
        // Filtrar solo citas no canceladas
        $citasValidas = array_filter($citasActivas, function($cita) {
            return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
        });
        
        // Calcular cupos totales
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $totalCupos = floor($duracionMinutos / $intervalo);
        
        // Actualizar cupos disponibles
        $agenda['cupos_disponibles'] = max(0, $totalCupos - count($citasValidas));
        $agenda['citas_count'] = count($citasValidas);
        $agenda['total_cupos'] = $totalCupos;
        
        // Guardar agenda actualizada
        $this->offlineService->storeAgendaOffline($agenda, false);
        
        Log::info('✅ Agenda offline actualizada después de crear cita', [
            'agenda_uuid' => $agendaUuid,
            'fecha_agenda' => $fechaAgenda,
            'cupos_disponibles' => $agenda['cupos_disponibles'],
            'citas_count' => $agenda['citas_count'],
            'total_cupos' => $totalCupos
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error actualizando agenda offline después de cita', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
    }
}


    public function show(string $uuid): array
    {
        try {
            if ($this->apiService->isOnline()) {
                try {
                    $endpoint = $this->buildApiUrl('show');
                    $url = str_replace('{uuid}', $uuid, $endpoint);
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
 * ✅ NUEVO: Calcular horarios disponibles
 */
private function calcularHorariosDisponibles(array $agenda, string $fecha): array
{
    try {
        $horarios = [];
        
        $horaInicio = $agenda['hora_inicio'];
        $horaFin = $agenda['hora_fin'];
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        // ✅ EXTRAER SOLO LA FECHA (YYYY-MM-DD) SIN ZONA HORARIA
        $fechaLimpia = $fecha;
        if (strpos($fecha, 'T') !== false) {
            $fechaLimpia = explode('T', $fecha)[0]; // "2025-09-12T00:00:00.000000Z" -> "2025-09-12"
        }
        
        Log::info('🔍 Calculando horarios con fecha limpia', [
            'fecha_original' => $fecha,
            'fecha_limpia' => $fechaLimpia,
            'agenda_uuid' => $agenda['uuid']
        ]);
        
        // ✅ OBTENER CITAS EXISTENTES CON FECHA LIMPIA
        $user = $this->authService->usuario();
        $citasExistentes = $this->offlineService->getCitasOffline($user['sede_id'], [
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
        
        Log::info('📊 Citas encontradas para horarios', [
            'total_citas' => count($citasExistentes),
            'citas_activas' => count($citasActivas),
            'fecha_consulta' => $fechaLimpia
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
        
        Log::info('✅ Horarios calculados completamente', [
            'agenda_uuid' => $agenda['uuid'],
            'fecha_limpia' => $fechaLimpia,
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
