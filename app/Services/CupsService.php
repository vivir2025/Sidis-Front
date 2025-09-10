<?php
// app/Services/CupsService.php - VERSIÓN CORREGIDA
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;

class CupsService
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
     * ✅ BUSCAR CUPS POR CÓDIGO O NOMBRE - CORREGIDO
     */
   public function buscarCups(string $termino, int $limit = 10): array
{
    Log::info('🔍 CupsService@buscarCups iniciado', [
        'termino' => $termino,
        'limit' => $limit
    ]);

    try {
        // ✅ VERIFICAR TOKEN VÁLIDO PRIMERO
        $hasValidToken = $this->authService->hasValidToken();
        $isOnline = $this->apiService->isOnline();
        
        Log::info('🔐 Estado de autenticación y conexión', [
            'has_valid_token' => $hasValidToken,
            'is_online' => $isOnline
        ]);

        // ✅ INTENTAR API SOLO SI HAY TOKEN Y CONEXIÓN
        if ($hasValidToken && $isOnline) {
            Log::info('🌐 Intentando búsqueda CUPS via API');
            
            try {
                // ✅ CAMBIAR A POST - ESTE ES EL FIX PRINCIPAL
                $response = $this->apiService->post('/cups/buscar', [
                    'q' => $termino
                    // Nota: limit se maneja en el backend (línea 20 del controlador)
                ]);
                
                Log::info('📡 Respuesta API CUPS completa', [
                    'response_keys' => array_keys($response),
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'data_count' => is_array($response['data'] ?? null) ? count($response['data']) : 0
                ]);
                
                // ✅ AGREGAR información de contratos a cada CUPS
    if ($response['success'] && !empty($response['data'])) {
        $cups = is_array($response['data']) ? $response['data'] : [$response['data']];
        
        // Enriquecer con información de contratos
        foreach ($cups as &$cupsItem) {
            if (isset($cupsItem['uuid'])) {
                // Buscar si tiene contrato vigente
                $contratoResponse = $this->apiService->get("/cups-contratados/por-cups/{$cupsItem['uuid']}");
                if ($contratoResponse['success']) {
                    $cupsItem['contrato_vigente'] = $contratoResponse['data'];
                    $cupsItem['tiene_contrato'] = true;
                } else {
                    $cupsItem['tiene_contrato'] = false;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $cups,
            'source' => 'api',
            'message' => 'Datos obtenidos del servidor',
            'total' => count($cups)
        ];
    
                } else {
                    Log::warning('⚠️ API no retornó datos válidos', [
                        'response_success' => $response['success'] ?? false,
                        'response_data' => $response['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error en API CUPS, usando offline', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        } else {
            Log::info('🔐 Sin token válido o conexión, usando offline directamente', [
                'has_token' => $hasValidToken,
                'is_online' => $isOnline
            ]);
        }
        
        // ✅ FALLBACK A OFFLINE
        Log::info('💾 Usando búsqueda CUPS offline');
        $cups = $this->offlineService->buscarCupsOffline($termino, $limit);
        
        Log::info('📦 Resultados offline CUPS', [
            'count' => count($cups),
            'termino' => $termino
        ]);
        
        return [
            'success' => true,
            'data' => $cups,
            'source' => 'offline',
            'message' => count($cups) > 0 ? 'Datos locales encontrados' : 'No se encontraron resultados',
            'total' => count($cups)
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error en CupsService@buscarCups', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'termino' => $termino
        ]);
        
        return [
            'success' => false,
            'error' => 'Error buscando CUPS: ' . $e->getMessage(),
            'data' => []
        ];
    }
}
    /**
     * ✅ OBTENER CUPS POR CÓDIGO EXACTO - CORREGIDO
     */
    public function obtenerPorCodigo(string $codigo): array
    {
        try {
            // Intentar online
            if ($this->authService->hasValidToken() && $this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/cups', ['codigo' => $codigo]);
                    
                    if ($response['success'] && isset($response['data'])) {
                        $data = $response['data'];
                        
                        // Si viene paginado, extraer el array de datos
                        if (isset($data['data']) && is_array($data['data'])) {
                            $cupsList = $data['data'];
                        } else {
                            $cupsList = is_array($data) ? $data : [$data];
                        }
                        
                        // Buscar por código exacto
                        $cups = collect($cupsList)->first(function ($item) use ($codigo) {
                            return isset($item['codigo']) && $item['codigo'] === $codigo;
                        });

                        if ($cups) {
                            $this->offlineService->storeCupsOffline($cups);
                            return [
                                'success' => true,
                                'data' => $cups,
                                'source' => 'api'
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error obteniendo CUPS online', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Búsqueda offline
            $cups = $this->offlineService->obtenerCupsPorCodigoOffline($codigo);

            if ($cups) {
                return [
                    'success' => true,
                    'data' => $cups,
                    'source' => 'offline'
                ];
            }

            return [
                'success' => false,
                'error' => 'CUPS no encontrado'
            ];

        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo CUPS por código', [
                'error' => $e->getMessage(),
                'codigo' => $codigo
            ]);

            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * ✅ SINCRONIZAR CUPS DESDE LA API - CORREGIDO
     */
    public function sincronizarCups(): array
    {
        try {
            if (!$this->authService->hasValidToken()) {
                return [
                    'success' => false,
                    'error' => 'Sin token de autenticación válido'
                ];
            }

            if (!$this->apiService->isOnline()) {
                return [
                    'success' => false,
                    'error' => 'Sin conexión al servidor'
                ];
            }

            Log::info('🔄 Iniciando sincronización de CUPS');

            $response = $this->apiService->get('/cups', ['per_page' => 1000]);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error obteniendo CUPS del servidor'
                ];
            }

            $data = $response['data'];
            $cupsList = [];
            
            // Manejar diferentes formatos de respuesta
            if (isset($data['data']) && is_array($data['data'])) {
                $cupsList = $data['data'];
            } elseif (is_array($data)) {
                $cupsList = $data;
            }

            Log::info('📥 CUPS obtenidos de API', [
                'total' => count($cupsList)
            ]);

            $syncCount = 0;
            foreach ($cupsList as $cups) {
                if (is_array($cups) && isset($cups['uuid'], $cups['codigo'])) {
                    $this->offlineService->storeCupsOffline($cups);
                    $syncCount++;
                }
            }

            Log::info('✅ Sincronización CUPS completada', [
                'sincronizados' => $syncCount
            ]);

            return [
                'success' => true,
                'message' => "Sincronizados {$syncCount} CUPS correctamente",
                'count' => $syncCount
            ];

        } catch (\Exception $e) {
            Log::error('💥 Error sincronizando CUPS', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ OBTENER TODOS LOS CUPS ACTIVOS - CORREGIDO
     */
    public function obtenerCupsActivos(): array
    {
        try {
            // Intentar online
            if ($this->authService->hasValidToken() && $this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/cups/activos');
                    
                    if ($response['success'] && isset($response['data'])) {
                        $cups = is_array($response['data']) ? $response['data'] : [$response['data']];
                        
                        // Sincronizar offline
                        foreach ($cups as $cupsItem) {
                            if (is_array($cupsItem) && isset($cupsItem['uuid'], $cupsItem['codigo'])) {
                                $this->offlineService->storeCupsOffline($cupsItem);
                            }
                        }

                        return [
                            'success' => true,
                            'data' => $cups,
                            'source' => 'api'
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error obteniendo CUPS activos online', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Fallback offline
            $cups = $this->offlineService->obtenerCupsActivosOffline();

            return [
                'success' => true,
                'data' => $cups,
                'source' => 'offline'
            ];

        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo CUPS activos', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error interno',
                'data' => []
            ];
        }
    }
}
