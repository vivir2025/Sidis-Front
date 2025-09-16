<?php
// app/Services/CupsService.php - VERSIÓN COMPLETAMENTE CORREGIDA
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
     * ✅ CORREGIDO: Buscar CUPS con validación correcta de contratos
     */
    public function buscarCups(string $termino, int $limit = 10): array
    {
        Log::info('🔍 CupsService@buscarCups iniciado', [
            'termino' => $termino,
            'limit' => $limit
        ]);

        try {
            $hasValidToken = $this->authService->hasValidToken();
            $isOnline = $this->apiService->isOnline();
            
            // ✅ INTENTAR API SOLO SI HAY TOKEN Y CONEXIÓN
            if ($hasValidToken && $isOnline) {
                try {
                    $response = $this->apiService->post('/cups/buscar', [
                        'q' => $termino
                    ]);
                    
                    if ($response['success'] && !empty($response['data'])) {
                        $cups = is_array($response['data']) ? $response['data'] : [$response['data']];
                        
                        // ✅ ENRIQUECER CON CONTRATOS VIGENTES
                        foreach ($cups as &$cupsItem) {
                            $cupsItem = $this->enrichCupsWithContract($cupsItem);
                        }
                        
                        return [
                            'success' => true,
                            'data' => $cups,
                            'source' => 'api',
                            'message' => 'Datos obtenidos del servidor',
                            'total' => count($cups)
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error en API CUPS, usando offline', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // ✅ USAR OFFLINE CON VALIDACIÓN MEJORADA
            Log::info('💾 Usando búsqueda CUPS offline');
            $cups = $this->offlineService->buscarCupsOffline($termino, $limit);
            
            // ✅ ENRIQUECER CON CONTRATOS OFFLINE
            foreach ($cups as &$cupsItem) {
                $cupsItem = $this->enrichCupsWithContract($cupsItem);
            }
            
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
                'termino' => $termino
            ]);
            
            return [
                'success' => false,
                'error' => 'Error buscando CUPS: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
private function enrichCupsWithContract($cups)
{
    Log::info('🔍 Buscando CUPS contratado', ['cups_uuid' => $cups['uuid']]);
    
    // ✅ INTENTAR API PRIMERO
    try {
        $response = $this->apiService->get("/cups-contratados/por-cups/{$cups['uuid']}");
        
        if (isset($response['success']) && $response['success'] && isset($response['data'])) {
            // ✅ ÉXITO: Actualizar cache offline
            $this->offlineService->storeCupsContratadoOffline($response['data']);
            $cups['contrato'] = $response['data'];
            
            Log::info('✅ CUPS contratado actualizado desde API', [
                'cups_uuid' => $cups['uuid'],
                'contrato_uuid' => $response['data']['uuid']
            ]);
            
            return $cups;
        }
        
        // ✅ ERROR 404 o NO ENCONTRADO: Limpiar cache obsoleto
        if (isset($response['success']) && !$response['success']) {
            $errorMessage = $response['message'] ?? $response['error'] ?? '';
            
            if (str_contains($errorMessage, 'No se encontró') ||
                str_contains($errorMessage, 'contrato vigente') ||
                str_contains($errorMessage, 'not found')) {
                
                Log::info('🗑️ Limpiando cache obsoleto por respuesta 404', [
                    'cups_uuid' => $cups['uuid'],
                    'error_message' => $errorMessage
                ]);
                
                // ✅ LIMPIAR CACHE INMEDIATAMENTE
                $this->offlineService->invalidateCupsContratadoCache($cups['uuid']);
                
                $cups['contrato'] = null;
                return $cups;
            }
        }
        
    } catch (\Exception $e) {
        Log::warning('⚠️ Error API, verificando cache offline', [
            'error' => $e->getMessage(),
            'cups_uuid' => $cups['uuid']
        ]);
    }
    
    // ✅ SOLO USAR CACHE OFFLINE SI NO HUBO ERROR 404
    return $this->searchContractOffline($cups);
}


    /**
     * ✅ NUEVO: Maneja errores de API para contratos y limpia cache si es necesario
     */
    private function handleApiContractError($exception, $cupsUuid)
    {
        $errorMessage = $exception->getMessage();
        Log::warning('⚠️ Error al obtener contrato de API', [
            'cups_uuid' => $cupsUuid,
            'error' => $errorMessage
        ]);
        
        // Si es un 404, significa que no hay contrato vigente - limpiar cache
        if (strpos($errorMessage, '404') !== false || 
            strpos($errorMessage, 'No se encontró') !== false ||
            strpos($errorMessage, 'contrato vigente') !== false) {
            
            Log::info('🗑️ API devolvió 404, limpiando cache offline', ['cups_uuid' => $cupsUuid]);
            $this->offlineService->invalidateCupsContratadoCache($cupsUuid);
        }
    }

    /**
     * ✅ NUEVO: Busca contrato en cache offline
     */
    private function searchContractOffline($cups)
    {
        Log::info('🔍 Buscando CUPS contratado vigente', [
            'cups_uuid' => $cups['uuid'],
            'fecha_actual' => now()->format('Y-m-d')
        ]);
        
        $contratoVigente = $this->offlineService->getCupsContratadoVigenteOffline($cups['uuid']);
        
        if ($contratoVigente) {
            Log::info('✅ CUPS contratado vigente encontrado en SQLite', [
                'cups_contratado_uuid' => $contratoVigente['uuid'],
                'cups_codigo' => $contratoVigente['cups_codigo'] ?? 'N/A',
                'fecha_inicio' => $contratoVigente['fecha_inicio'] ?? 'N/A',
                'fecha_fin' => $contratoVigente['fecha_fin'] ?? 'N/A',
                'tarifa' => $contratoVigente['tarifa'] ?? 'N/A'
            ]);
            
            $cups['contrato'] = $contratoVigente;
        } else {
            Log::warning('⚠️ No hay contrato vigente para este CUPS', [
                'cups_uuid' => $cups['uuid']
            ]);
            
            $cups['contrato'] = null;
        }
        
        return $cups;
    }

    /**
     * ✅ NUEVO: Sincronizar CUPS contratado específico
     */
    private function sincronizarCupsContratadoPorCups(string $cupsUuid): void
    {
        try {
            $response = $this->apiService->get("/cups-contratados/por-cups/{$cupsUuid}");
            
            if ($response['success']) {
                $this->offlineService->storeCupsContratadoOffline($response['data']);
                
                Log::info('✅ CUPS contratado sincronizado', [
                    'cups_uuid' => $cupsUuid,
                    'cups_contratado_uuid' => $response['data']['uuid'] ?? 'N/A'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ No se pudo sincronizar CUPS contratado', [
                'cups_uuid' => $cupsUuid,
                'error' => $e->getMessage()
            ]);
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

    /**
     * ✅ NUEVO: Invalidar cache de CUPS contratado cuando API devuelve 404
     */
    public function invalidarCacheContratoPorCups(string $cupsUuid): bool
    {
        try {
            Log::info('🗑️ Invalidando cache de contrato CUPS', [
                'cups_uuid' => $cupsUuid
            ]);

            return $this->offlineService->invalidateCupsContratadoCache($cupsUuid);

        } catch (\Exception $e) {
            Log::error('❌ Error invalidando cache de contrato CUPS', [
                'cups_uuid' => $cupsUuid,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ NUEVO: Obtener contrato vigente con limpieza automática de cache
     */
    public function obtenerContratoVigente(string $cupsUuid): ?array
    {
        try {
            Log::info('🔍 Obteniendo contrato vigente para CUPS', [
                'cups_uuid' => $cupsUuid
            ]);

            // ✅ INTENTAR API PRIMERO
            if ($this->authService->hasValidToken() && $this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get("/cups-contratados/por-cups/{$cupsUuid}");
                    
                    if ($response['success']) {
                        // Almacenar offline para futuro uso
                        $this->offlineService->storeCupsContratadoOffline($response['data']);
                        
                        Log::info('✅ Contrato vigente obtenido de API', [
                            'cups_uuid' => $cupsUuid,
                            'contrato_uuid' => $response['data']['uuid'] ?? 'N/A'
                        ]);
                        
                        return $response['data'];
                    }
                } catch (\Exception $e) {
                    // ✅ SI LA API DEVUELVE 404, LIMPIAR CACHE
                    if (str_contains($e->getMessage(), '404')) {
                        Log::info('🗑️ API devolvió 404, limpiando cache offline', [
                            'cups_uuid' => $cupsUuid
                        ]);
                        
                        $this->offlineService->invalidateCupsContratadoCache($cupsUuid);
                        
                        return null; // No hay contrato vigente
                    }
                    
                    Log::warning('⚠️ Error API contrato, usando offline', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ✅ USAR OFFLINE SOLO SI NO HUBO ERROR 404
            return $this->offlineService->getCupsContratadoVigenteOffline($cupsUuid);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo contrato vigente', [
                'cups_uuid' => $cupsUuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
