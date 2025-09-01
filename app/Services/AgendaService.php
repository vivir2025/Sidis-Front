<?php
// app/Services/AgendaService.php
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgendaService
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
            $user = $this->authService->usuario();
            $data['sede_id'] = $user['sede_id'];
            $data['usuario_id'] = $user['id'];

            // Intentar crear online
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->post('/agendas', $data);
                
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
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error creando agenda'
                ];
            }

            // Crear offline
            $data['uuid'] = Str::uuid();
            $data['estado'] = $data['estado'] ?? 'ACTIVO';
            $this->offlineService->storeAgendaOffline($data, true);

            return [
                'success' => true,
                'data' => $data,
                'message' => 'Agenda creada (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error creando agenda', [
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

            // Actualizar offline
            $updatedData = array_merge($agenda, $data);
            $this->offlineService->storeAgendaOffline($updatedData, true);

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

            // Marcar como eliminada offline
            $agenda['deleted_at'] = now()->toISOString();
            $this->offlineService->storeAgendaOffline($agenda, true);

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
}
