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

            $citas = $this->offlineService->getCitasOffline($sedeId, $filters);
            
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
     * ✅ CREAR CITA - VERSIÓN SIMPLIFICADA SIN CONVERSIÓN DE UUIDs
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
        $data['estado'] = $data['estado'] ?? 'PROGRAMADA';

        // ✅ VALIDAR QUE VENGA EL CUPS_CONTRATADO_UUID CORRECTO
        if (isset($data['cups_contratado_id']) && !empty($data['cups_contratado_id'])) {
            // Ya viene el UUID del CUPS_CONTRATADO, solo cambiar el nombre del campo
            $data['cups_contratado_uuid'] = $data['cups_contratado_id'];
            unset($data['cups_contratado_id']);
            
            Log::info('✅ CUPS contratado UUID configurado', [
                'cups_contratado_uuid' => $data['cups_contratado_uuid']
            ]);
        }

        $isOnline = $this->apiService->isOnline();
        
        if ($isOnline) {
                Log::info('🌐 Conexión disponible, intentando crear cita en API...');
                
                try {
                    // ✅ CAMBIAR cups_contratado_id por cups_contratado_uuid si viene
                    if (isset($data['cups_contratado_id']) && !empty($data['cups_contratado_id'])) {
                        $data['cups_contratado_uuid'] = $data['cups_contratado_id'];
                        unset($data['cups_contratado_id']);
                    }

                    $endpoint = $this->buildApiUrl('store');
                    Log::info('📤 Enviando cita a API', [
                        'endpoint' => $endpoint,
                        'data_keys' => array_keys($data)
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
                    
                    Log::warning('⚠️ API respondió con error, creando offline como fallback', [
                        'api_error' => $response['error'] ?? 'Error desconocido',
                        'full_response' => $response
                    ]);
                    
                } catch (\Exception $e) {
                    Log::warning('⚠️ Excepción conectando con API, creando offline como fallback', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                }
            } else {
                Log::info('📱 Sin conexión detectada, creando cita offline directamente...');
            }

            // ✅ CREAR OFFLINE
            Log::info('💾 Creando cita en modo offline...');
            
            $data['uuid'] = Str::uuid();
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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
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
