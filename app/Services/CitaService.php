<?php
// app/Services/CitaService.php
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     * ✅ CREAR CITA
     */
    public function store(array $data): array
    {
        try {
            $user = $this->authService->usuario();
            $data['sede_id'] = $user['sede_id'];
            $data['usuario_creo_cita_id'] = $user['id'];

            // Intentar crear online
            if ($this->apiService->isOnline()) {
                $url = $this->buildApiUrl('store');
                $response = $this->apiService->post($url, $data);
                
                if ($response['success']) {
                    $citaData = $response['data'];
                    $this->offlineService->storeCitaOffline($citaData, false);
                    
                    return [
                        'success' => true,
                        'data' => $citaData,
                        'message' => 'Cita creada exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error creando cita'
                ];
            }

            // Crear offline
            $data['uuid'] = Str::uuid();
            $data['estado'] = $data['estado'] ?? 'PROGRAMADA';
            $this->offlineService->storeCitaOffline($data, true);

            return [
                'success' => true,
                'data' => $data,
                'message' => 'Cita creada (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error creando cita', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
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
}
