<?php
// app/Services/PacienteService.php
namespace App\Services;

use App\Services\{ApiService, AuthService, OfflineService};
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class PacienteService
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
     * Obtener URL del endpoint
     */
    private function getEndpoint(string $action, array $params = []): string
    {
        // ✅ USAR ENDPOINTS DIRECTOS PARA EVITAR PROBLEMAS
        $endpoints = [
            'index' => '/pacientes',
            'store' => '/pacientes',
            'show' => '/pacientes/{uuid}',
            'update' => '/pacientes/{uuid}',
            'destroy' => '/pacientes/{uuid}',
            'search' => '/pacientes/search',
            'search_by_document' => '/pacientes/search/document',
            'bulk_sync' => '/pacientes/sync',
        ];
        
        if (!isset($endpoints[$action])) {
            throw new \InvalidArgumentException("Endpoint '{$action}' no encontrado");
        }
        
        $endpoint = $endpoints[$action];
        
        // Reemplazar parámetros en la URL
        foreach ($params as $key => $value) {
            $endpoint = str_replace('{' . $key . '}', $value, $endpoint);
        }
        
        Log::info('✅ Endpoint resuelto', [
            'action' => $action,
            'endpoint' => $endpoint
        ]);
        
        return $endpoint;
    }

    /**
     * ✅ CORREGIDO: Listar pacientes con paginación
     * Ahora recibe array de filtros y página en lugar de Request
     */
   public function index(array $filters = [], int $page = 1): array
{
    try {
        Log::info("🏥 PacienteService::index - Iniciando", [
            'filters' => $filters,
            'page' => $page
        ]);

        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];

        // ✅ PREPARAR PARÁMETROS PARA LA API
        $apiParams = array_merge($filters, [
            'page' => $page,
            'sede_id' => $sedeId
        ]);

        // Limpiar parámetros vacíos
        $apiParams = array_filter($apiParams, function($value) {
            return !empty($value) && $value !== '';
        });

        Log::info('📥 Parámetros preparados para API', [
            'params' => $apiParams,
            'api_online' => $this->apiService->isOnline()
        ]);

        // ✅ INTENTAR OBTENER DESDE API PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get(
                    $this->getEndpoint('index'), 
                    $apiParams
                );

                Log::info('📡 Respuesta de API recibida', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'data_count' => is_array($response['data'] ?? null) ? count($response['data']) : 0
                ]);

                if ($response['success'] && isset($response['data'])) {
                    $pacientes = $response['data'] ?? [];
                    $meta = $response['meta'] ?? [];

                    // ✅ ENRIQUECER DATOS DESDE API (SIN ERRORES)
                    $pacientes = $this->enrichPacientesDataFromApi($pacientes, $sedeId);

                    // ✅ SINCRONIZAR DATOS LOCALMENTE
                    if (!empty($pacientes)) {
                        $this->syncPacientesFromApi($pacientes);
                    }

                    Log::info('✅ Pacientes obtenidos desde API exitosamente', [
                        'count' => count($pacientes),
                        'current_page' => $meta['current_page'] ?? $page,
                        'total' => $meta['total'] ?? 0
                    ]);

                    return [
                        'success' => true,
                        'data' => $pacientes,
                        'meta' => $meta,
                        'message' => '✅ Datos actualizados desde el servidor',
                        'offline' => false
                    ];
                }

                Log::warning('⚠️ API no retornó datos exitosos', [
                    'response_success' => $response['success'] ?? 'undefined',
                    'has_data' => isset($response['data'])
                ]);

            } catch (\Exception $e) {
                Log::warning('⚠️ Error conectando con API, usando datos offline', [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]);
            }
        } else {
            Log::info('🌐 API offline, usando datos locales');
        }

        // ✅ OBTENER DATOS OFFLINE
        Log::info('📱 Obteniendo pacientes desde almacenamiento offline');
        $result = $this->getOfflinePacientes($filters, $page);
        
        // ✅ PERSONALIZAR MENSAJE SEGÚN LA SITUACIÓN
        if (empty($result['data'])) {
            $result['message'] = '📭 No hay pacientes registrados. Crea tu primer paciente.';
        } else {
            $result['message'] = $this->apiService->isOnline() 
                ? '⚠️ Usando datos locales (problema temporal con servidor)'
                : '📱 Trabajando en modo offline - Datos locales';
        }
        
        return $result;

    } catch (\Exception $e) {
        Log::error('💥 Error en PacienteService::index', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'filters' => $filters,
            'page' => $page
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
            'message' => '❌ Error cargando pacientes: ' . $e->getMessage(),
            'offline' => true
        ];
    }
}

private function enrichPacientesDataFromApi(array $pacientes, int $sedeId): array
{
    return array_map(function($paciente) use ($sedeId) {
        try {
            // ✅ ASEGURAR QUE TENGA SEDE_ID (sin generar errores)
            if (!isset($paciente['sede_id'])) {
                $paciente['sede_id'] = $sedeId;
            }

            // ✅ CALCULAR EDAD SI TIENE FECHA DE NACIMIENTO
            if (!empty($paciente['fecha_nacimiento'])) {
                try {
                    $fechaNacimiento = new \DateTime($paciente['fecha_nacimiento']);
                    $hoy = new \DateTime();
                    $paciente['edad'] = $hoy->diff($fechaNacimiento)->y;
                } catch (\Exception $e) {
                    $paciente['edad'] = null;
                    Log::debug('⚠️ Error calculando edad', [
                        'uuid' => $paciente['uuid'] ?? 'sin-uuid',
                        'fecha_nacimiento' => $paciente['fecha_nacimiento']
                    ]);
                }
            }

            // ✅ CONSTRUIR NOMBRE COMPLETO SI NO EXISTE
            if (empty($paciente['nombre_completo'])) {
                $nombres = array_filter([
                    $paciente['primer_nombre'] ?? '',
                    $paciente['segundo_nombre'] ?? '',
                    $paciente['primer_apellido'] ?? '',
                    $paciente['segundo_apellido'] ?? ''
                ]);
                $paciente['nombre_completo'] = implode(' ', $nombres);
            }

            // ✅ ASEGURAR CAMPOS REQUERIDOS
            $paciente['estado'] = $paciente['estado'] ?? 'ACTIVO';
            $paciente['sexo'] = $paciente['sexo'] ?? 'M';
            $paciente['uuid'] = $paciente['uuid'] ?? \Str::uuid();
            $paciente['sync_status'] = 'synced'; // Viene de API, está sincronizado

            // ✅ AGREGAR TIMESTAMPS SI NO EXISTEN
            if (empty($paciente['fecha_registro'])) {
                $paciente['fecha_registro'] = now()->format('Y-m-d');
            }

            // ✅ EXTRAER DATOS DE RELACIONES PARA ALMACENAMIENTO OFFLINE
            if (isset($paciente['empresa']) && is_array($paciente['empresa'])) {
                $paciente['empresa_id'] = $paciente['empresa']['uuid'] ?? null;
                $paciente['empresa_nombre'] = $paciente['empresa']['nombre'] ?? null;
            }

            if (isset($paciente['regimen']) && is_array($paciente['regimen'])) {
                $paciente['regimen_id'] = $paciente['regimen']['uuid'] ?? null;
                $paciente['regimen_nombre'] = $paciente['regimen']['nombre'] ?? null;
            }

            if (isset($paciente['tipo_documento']) && is_array($paciente['tipo_documento'])) {
                $paciente['tipo_documento_id'] = $paciente['tipo_documento']['uuid'] ?? null;
                $paciente['tipo_documento_nombre'] = $paciente['tipo_documento']['nombre'] ?? null;
            }

            if (isset($paciente['zona_residencia']) && is_array($paciente['zona_residencia'])) {
                $paciente['zona_residencia_id'] = $paciente['zona_residencia']['uuid'] ?? null;
                $paciente['zona_residencia_nombre'] = $paciente['zona_residencia']['nombre'] ?? null;
            }

            if (isset($paciente['acudiente']) && is_array($paciente['acudiente'])) {
                $paciente['nombre_acudiente'] = $paciente['acudiente']['nombre'] ?? null;
                $paciente['parentesco_acudiente'] = $paciente['acudiente']['parentesco'] ?? null;
                $paciente['telefono_acudiente'] = $paciente['acudiente']['telefono'] ?? null;
                $paciente['direccion_acudiente'] = $paciente['acudiente']['direccion'] ?? null;
            }

            if (isset($paciente['acompanante']) && is_array($paciente['acompanante'])) {
                $paciente['acompanante_nombre'] = $paciente['acompanante']['nombre'] ?? null;
                $paciente['acompanante_telefono'] = $paciente['acompanante']['telefono'] ?? null;
            }

            return $paciente;

        } catch (\Exception $e) {
            Log::warning('⚠️ Error enriqueciendo datos de paciente', [
                'uuid' => $paciente['uuid'] ?? 'sin-uuid',
                'error' => $e->getMessage()
            ]);
            
            // ✅ RETORNAR PACIENTE CON DATOS MÍNIMOS EN CASO DE ERROR
            $paciente['sede_id'] = $sedeId;
            $paciente['sync_status'] = 'synced';
            return $paciente;
        }
    }, $pacientes);
}

    /**
     * ✅ ENRIQUECER DATOS DE PACIENTES
     */
    private function enrichPacientesData(array $pacientes): array
    {
        return array_map(function($paciente) {
            // ✅ CALCULAR EDAD SI TIENE FECHA DE NACIMIENTO
            if (!empty($paciente['fecha_nacimiento'])) {
                try {
                    $fechaNacimiento = new \DateTime($paciente['fecha_nacimiento']);
                    $hoy = new \DateTime();
                    $paciente['edad'] = $hoy->diff($fechaNacimiento)->y;
                } catch (\Exception $e) {
                    $paciente['edad'] = null;
                }
            }

            // ✅ CONSTRUIR NOMBRE COMPLETO SI NO EXISTE
            if (empty($paciente['nombre_completo'])) {
                $nombres = array_filter([
                    $paciente['primer_nombre'] ?? '',
                    $paciente['segundo_nombre'] ?? '',
                    $paciente['primer_apellido'] ?? '',
                    $paciente['segundo_apellido'] ?? ''
                ]);
                $paciente['nombre_completo'] = implode(' ', $nombres);
            }

            // ✅ ASEGURAR CAMPOS REQUERIDOS
            $paciente['estado'] = $paciente['estado'] ?? 'ACTIVO';
            $paciente['sexo'] = $paciente['sexo'] ?? 'M';
            $paciente['uuid'] = $paciente['uuid'] ?? \Str::uuid();

            return $paciente;
        }, $pacientes);
    }

    /**
     * Crear paciente
     */
    public function store(array $data): array
    {
        try {
            $user = $this->authService->usuario();
            $data['sede_id'] = $user['sede_id'];
            $data['fecha_registro'] = now()->format('Y-m-d');

            // Intentar crear online primero
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->post(
                    $this->getEndpoint('store'), 
                    $data
                );
                
                if ($response['success']) {
                    // Guardar localmente
                    $pacienteData = $response['data'];
                    $this->storePacienteOffline($pacienteData, false);
                    
                    return [
                        'success' => true,
                        'data' => $pacienteData,
                        'message' => 'Paciente creado exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error creando paciente'
                ];
            }

            // Crear offline
            $data['uuid'] = \Str::uuid();
            $data['sync_status'] = 'pending';
            $this->storePacienteOffline($data, true);

            // Marcar para sincronización
            $this->offlineService->storePendingChange('post', $this->getEndpoint('store'), $data);

            return [
                'success' => true,
                'data' => $data,
                'message' => 'Paciente creado (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error creando paciente', ['error' => $e->getMessage(), 'data' => $data]);
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

   public function show(string $uuid): array
{
    try {
        Log::info('🔍 PacienteService::show - Iniciando', [
            'uuid' => $uuid,
            'api_online' => $this->apiService->isOnline()
        ]);

        // ✅ INTENTAR OBTENER ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get(
                    $this->getEndpoint('show', ['uuid' => $uuid])
                );
                
                Log::info('📥 Respuesta API para show', [
                    'uuid' => $uuid,
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null
                ]);
                
                if ($response['success']) {
                    // ✅ ÉXITO - Actualizar datos locales
                    $apiData = $response['data'];
                    $this->storePacienteOffline($apiData, false);
                    
                    return [
                        'success' => true,
                        'data' => $apiData,
                        'offline' => false
                    ];
                }
                
                // ✅ SI NO SE ENCUENTRA ONLINE, BUSCAR OFFLINE
                Log::info('ℹ️ Paciente no encontrado online, buscando offline', [
                    'uuid' => $uuid
                ]);
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo paciente desde API', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ BUSCAR LOCALMENTE
        $paciente = $this->getPacienteOffline($uuid);
        
        if (!$paciente) {
            return [
                'success' => false,
                'error' => 'Paciente no encontrado'
            ];
        }

        Log::info('✅ Paciente encontrado offline', [
            'uuid' => $uuid,
            'documento' => $paciente['documento'] ?? 'sin-documento',
            'sync_status' => $paciente['sync_status'] ?? 'unknown'
        ]);

        return [
            'success' => true,
            'data' => $paciente,
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error obteniendo paciente', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno'
        ];
    }
}
    public function update(string $uuid, array $data): array
{
    try {
        Log::info('🔄 PacienteService::update - Iniciando', [
            'uuid' => $uuid,
            'data_keys' => array_keys($data),
            'api_online' => $this->apiService->isOnline()
        ]);

        // ✅ BUSCAR PACIENTE LOCALMENTE PRIMERO
        $paciente = $this->getPacienteOffline($uuid);
        
        if (!$paciente) {
            Log::warning('⚠️ Paciente no encontrado localmente', ['uuid' => $uuid]);
            return [
                'success' => false,
                'error' => 'Paciente no encontrado'
            ];
        }

        $data['fecha_actualizacion'] = now()->format('Y-m-d H:i:s');

        // ✅ INTENTAR ACTUALIZAR ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                Log::info('📡 Intentando actualizar paciente online', [
                    'uuid' => $uuid,
                    'endpoint' => $this->getEndpoint('update', ['uuid' => $uuid])
                ]);

                $response = $this->apiService->put(
                    $this->getEndpoint('update', ['uuid' => $uuid]), 
                    $data
                );
                
                Log::info('📥 Respuesta de API para actualización', [
                    'uuid' => $uuid,
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null
                ]);

                if ($response['success']) {
                    // ✅ ÉXITO ONLINE - Actualizar datos locales con respuesta de API
                    $apiData = $response['data'] ?? array_merge($paciente, $data);
                    $this->storePacienteOffline($apiData, false); // synced = true
                    
                    Log::info('✅ Paciente actualizado online exitosamente', [
                        'uuid' => $uuid,
                        'sync_status' => 'synced'
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $apiData,
                        'message' => 'Paciente actualizado exitosamente',
                        'offline' => false
                    ];
                } else {
                    // ✅ ERROR DE API - Verificar si es 404 (paciente no existe online)
                    $errorMessage = $response['error'] ?? 'Error desconocido';
                    
                    if (strpos(strtolower($errorMessage), 'no encontrado') !== false || 
                        strpos(strtolower($errorMessage), 'not found') !== false) {
                        
                        Log::info('ℹ️ Paciente no existe online, creando nuevo registro', [
                            'uuid' => $uuid
                        ]);
                        
                        // ✅ INTENTAR CREAR EN LUGAR DE ACTUALIZAR
                        $createResponse = $this->apiService->post(
                            $this->getEndpoint('store'), 
                            array_merge($paciente, $data)
                        );
                        
                        if ($createResponse['success']) {
                            $apiData = $createResponse['data'] ?? array_merge($paciente, $data);
                            $this->storePacienteOffline($apiData, false);
                            
                            Log::info('✅ Paciente creado online (era update)', [
                                'uuid' => $uuid
                            ]);
                            
                            return [
                                'success' => true,
                                'data' => $apiData,
                                'message' => 'Paciente actualizado exitosamente (sincronizado con servidor)',
                                'offline' => false
                            ];
                        }
                    }
                    
                    // ✅ ERROR REAL DE API - Continuar con actualización offline
                    Log::warning('⚠️ Error de API, continuando offline', [
                        'uuid' => $uuid,
                        'error' => $errorMessage
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Excepción conectando con API, usando modo offline', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::info('🌐 API offline, actualizando localmente', ['uuid' => $uuid]);
        }

        // ✅ ACTUALIZAR OFFLINE (si API falló o está offline)
        $updatedData = array_merge($paciente, $data);
        $updatedData['sync_status'] = 'pending';
        $this->storePacienteOffline($updatedData, true); // needsSync = true

        // ✅ MARCAR PARA SINCRONIZACIÓN
        $this->offlineService->storePendingChange(
            'put', 
            $this->getEndpoint('update', ['uuid' => $uuid]), 
            $data
        );

        Log::info('✅ Paciente actualizado offline', [
            'uuid' => $uuid,
            'sync_status' => 'pending'
        ]);

        return [
            'success' => true,
            'data' => $updatedData,
            'message' => 'Paciente actualizado (se sincronizará cuando vuelva la conexión)',
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error actualizando paciente', [
            'uuid' => $uuid,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno: ' . $e->getMessage()
        ];
    }
}

    /**
     * Eliminar paciente
     */
    public function destroy(string $uuid): array
    {
        try {
            $paciente = $this->getPacienteOffline($uuid);
            
            if (!$paciente) {
                return [
                    'success' => false,
                    'error' => 'Paciente no encontrado'
                ];
            }

            // Intentar eliminar online
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->delete(
                    $this->getEndpoint('destroy', ['uuid' => $uuid])
                );
                
                if ($response['success']) {
                    $this->deletePacienteOffline($uuid);
                    return [
                        'success' => true,
                        'message' => 'Paciente eliminado exitosamente',
                        'offline' => false
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Error eliminando paciente'
                ];
            }

            // Marcar como eliminado offline
            $paciente['deleted_at'] = now()->toISOString();
            $paciente['sync_status'] = 'pending';
            $this->storePacienteOffline($paciente, true);

            // Marcar para sincronización
            $this->offlineService->storePendingChange('delete', $this->getEndpoint('destroy', ['uuid' => $uuid]), []);

            return [
                'success' => true,
                'message' => 'Paciente eliminado (se sincronizará cuando vuelva la conexión)',
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error eliminando paciente', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * Buscar paciente por documento
     */
    public function searchByDocument(string $documento): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];

            // Intentar buscar online primero
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get(
                    $this->getEndpoint('search_by_document'), 
                    ['documento' => $documento]
                );

                if ($response['success']) {
                    // Sincronizar datos localmente
                    $this->storePacienteOffline($response['data'], false);

                    return [
                        'success' => true,
                        'data' => $response['data'],
                        'offline' => false
                    ];
                }
                
                // Si no se encuentra online, buscar offline
                if (isset($response['error']) && strpos($response['error'], 'no encontrado') !== false) {
                    // Continuar con búsqueda offline
                } else {
                    return [
                        'success' => false,
                        'error' => $response['error'] ?? 'Error en búsqueda'
                    ];
                }
            }

            // Buscar localmente
            $paciente = $this->searchPacienteOfflineByDocument($documento, $sedeId);

            if (!$paciente) {
                return [
                    'success' => false,
                    'error' => 'Paciente no encontrado'
                ];
            }

            return [
                'success' => true,
                'data' => $paciente,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error buscando paciente por documento', ['error' => $e->getMessage(), 'documento' => $documento]);
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * Búsqueda general de pacientes
     */
    public function search(array $criteria): array
    {
        try {
            // Intentar búsqueda online primero
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get(
                    $this->getEndpoint('search'), 
                    $criteria
                );

                if ($response['success']) {
                    // Sincronizar resultados localmente
                    if (isset($response['data']) && is_array($response['data'])) {
                        foreach ($response['data'] as $paciente) {
                            $this->storePacienteOffline($paciente, false);
                        }
                    }

                    return [
                        'success' => true,
                        'data' => $response['data'],
                        'meta' => $response['meta'] ?? [],
                        'offline' => false
                    ];
                }
            }

            // Búsqueda offline
            return $this->searchOffline($criteria);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de pacientes', ['error' => $e->getMessage(), 'criteria' => $criteria]);
            return [
                'success' => false,
                'error' => 'Error interno'
            ];
        }
    }

    /**
     * Sincronizar pacientes pendientes
     */
   public function syncPendingPacientes(): array
{
    try {
        Log::info('🔄 Iniciando sincronización de pacientes pendientes');
        
        if (!$this->apiService->isOnline()) {
            return [
                'success' => false,
                'error' => 'Sin conexión al servidor'
            ];
        }

        // ✅ OBTENER PACIENTES PENDIENTES DE SINCRONIZACIÓN
        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];
        $allPacientes = $this->getAllPacientesOffline($sedeId);
        
        // Filtrar solo los pendientes
        $pendingPacientes = array_filter($allPacientes, function($paciente) {
            return ($paciente['sync_status'] ?? 'synced') === 'pending';
        });

        if (empty($pendingPacientes)) {
            return [
                'success' => true,
                'message' => 'No hay pacientes pendientes para sincronizar',
                'synced_count' => 0
            ];
        }

        Log::info('📤 Pacientes pendientes encontrados', [
            'count' => count($pendingPacientes)
        ]);

        $results = [
            'synced' => [],
            'failed' => [],
            'total' => count($pendingPacientes)
        ];

        foreach ($pendingPacientes as $paciente) {
            try {
                $result = $this->syncSinglePacienteToApi($paciente);
                
                if ($result['success']) {
                    // Marcar como sincronizado
                    $paciente['sync_status'] = 'synced';
                    $paciente['synced_at'] = now()->toISOString();
                    
                    // Si viene un ID de la API, actualizarlo
                    if (isset($result['data']['id'])) {
                        $paciente['id'] = $result['data']['id'];
                    }
                    
                    $this->storePacienteOffline($paciente, false);
                    $results['synced'][] = $paciente['uuid'];
                    
                    Log::info('✅ Paciente sincronizado', [
                        'uuid' => $paciente['uuid'],
                        'documento' => $paciente['documento']
                    ]);
                } else {
                    $results['failed'][] = [
                        'uuid' => $paciente['uuid'],
                        'documento' => $paciente['documento'],
                        'error' => $result['error']
                    ];
                    
                    Log::warning('❌ Error sincronizando paciente', [
                        'uuid' => $paciente['uuid'],
                        'error' => $result['error']
                    ]);
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'uuid' => $paciente['uuid'],
                    'documento' => $paciente['documento'] ?? 'sin-documento',
                    'error' => $e->getMessage()
                ];
                
                Log::error('💥 Excepción sincronizando paciente', [
                    'uuid' => $paciente['uuid'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        $syncedCount = count($results['synced']);
        $failedCount = count($results['failed']);

        return [
            'success' => true,
            'message' => "Sincronización completada: {$syncedCount} exitosos, {$failedCount} fallidos",
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'results' => $results
        ];

    } catch (\Exception $e) {
        Log::error('💥 Error general en sincronización', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno en sincronización: ' . $e->getMessage()
        ];
    }
}
private function syncSinglePacienteToApi(array $paciente): array
{
    try {
        // ✅ PREPARAR DATOS PARA LA API
        $apiData = $this->prepareDataForApi($paciente);

        Log::info('📤 Enviando paciente a API', [
            'uuid' => $paciente['uuid'],
            'documento' => $paciente['documento'],
            'data_keys' => array_keys($apiData),
            'prepared_data' => $apiData // ✅ Log de datos preparados
        ]);

        // ✅ INTENTAR CREAR DIRECTAMENTE (más simple y confiable)
        $response = $this->apiService->post('/pacientes', $apiData);

        Log::info('📥 Respuesta de API para paciente', [
            'uuid' => $paciente['uuid'],
            'success' => $response['success'] ?? false,
            'has_data' => isset($response['data']),
            'response_keys' => array_keys($response)
        ]);

        // ✅ SI FALLA POR DUPLICADO, INTENTAR ACTUALIZAR
        if (!$response['success'] && isset($response['error'])) {
            $errorMessage = strtolower($response['error']);
            
            // Verificar si es error de duplicado
            if (strpos($errorMessage, 'duplicate') !== false || 
                strpos($errorMessage, 'duplicado') !== false ||
                strpos($errorMessage, 'already exists') !== false ||
                strpos($errorMessage, 'ya existe') !== false) {
                
                Log::info('🔄 Paciente duplicado, intentando actualizar', [
                    'uuid' => $paciente['uuid'],
                    'documento' => $paciente['documento']
                ]);
                
                // Buscar el paciente existente por documento
                $searchResponse = $this->apiService->get('/pacientes/search/document', [
                    'documento' => $paciente['documento']
                ]);
                
                if ($searchResponse['success'] && isset($searchResponse['data']['uuid'])) {
                    $existingUuid = $searchResponse['data']['uuid'];
                    
                    // Actualizar el paciente existente
                    $updateResponse = $this->apiService->put("/pacientes/{$existingUuid}", $apiData);
                    
                    Log::info('🔄 Resultado de actualización', [
                        'existing_uuid' => $existingUuid,
                        'success' => $updateResponse['success'] ?? false
                    ]);
                    
                    return $updateResponse;
                }
            }
        }

        return $response;

    } catch (\Exception $e) {
        Log::error('❌ Error enviando paciente a API', [
            'uuid' => $paciente['uuid'] ?? 'sin-uuid',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Error de conexión: ' . $e->getMessage()
        ];
    }
}
private function prepareDataForApi(array $paciente): array
{
    // ✅ MAPEAR SOLO LOS CAMPOS QUE LA API ESPERA
    $apiData = [
        // Campos básicos obligatorios
        'primer_nombre' => $paciente['primer_nombre'] ?? '',
        'primer_apellido' => $paciente['primer_apellido'] ?? '',
        'documento' => $paciente['documento'] ?? '',
        'fecha_nacimiento' => $paciente['fecha_nacimiento'] ?? null,
        'sexo' => $paciente['sexo'] ?? 'M',
        
        // Campos opcionales
        'segundo_nombre' => $paciente['segundo_nombre'] ?? null,
        'segundo_apellido' => $paciente['segundo_apellido'] ?? null,
        'direccion' => $paciente['direccion'] ?? null,
        'telefono' => $paciente['telefono'] ?? null,
        'correo' => $paciente['correo'] ?? null,
        'estado_civil' => $paciente['estado_civil'] ?? null,
        'observacion' => $paciente['observacion'] ?? null,
        'registro' => $paciente['registro'] ?? null,
        'estado' => $paciente['estado'] ?? 'ACTIVO',
        
        // IDs de relaciones (usar los IDs originales, no los nombres)
        'tipo_documento_id' => $paciente['tipo_documento_id'] ?? null,
        'empresa_id' => $paciente['empresa_id'] ?? null,
        'regimen_id' => $paciente['regimen_id'] ?? null,
        'tipo_afiliacion_id' => $paciente['tipo_afiliacion_id'] ?? null,
        'zona_residencia_id' => $paciente['zona_residencia_id'] ?? null,
        'depto_nacimiento_id' => $paciente['depto_nacimiento_id'] ?? null,
        'depto_residencia_id' => $paciente['depto_residencia_id'] ?? null,
        'municipio_nacimiento_id' => $paciente['municipio_nacimiento_id'] ?? null,
        'municipio_residencia_id' => $paciente['municipio_residencia_id'] ?? null,
        'raza_id' => $paciente['raza_id'] ?? null,
        'escolaridad_id' => $paciente['escolaridad_id'] ?? null,
        'parentesco_id' => $paciente['parentesco_id'] ?? null,
        'ocupacion_id' => $paciente['ocupacion_id'] ?? null,
        'novedad_id' => $paciente['novedad_id'] ?? null,
        'auxiliar_id' => $paciente['auxiliar_id'] ?? null,
        'brigada_id' => $paciente['brigada_id'] ?? null,
        
        // Datos de acudiente
        'nombre_acudiente' => $paciente['nombre_acudiente'] ?? null,
        'parentesco_acudiente' => $paciente['parentesco_acudiente'] ?? null,
        'telefono_acudiente' => $paciente['telefono_acudiente'] ?? null,
        'direccion_acudiente' => $paciente['direccion_acudiente'] ?? null,
        
        // Datos de acompañante
        'acompanante_nombre' => $paciente['acompanante_nombre'] ?? null,
        'acompanante_telefono' => $paciente['acompanante_telefono'] ?? null,
    ];

    // ✅ LIMPIAR CAMPOS VACÍOS Y NULOS
    $apiData = array_filter($apiData, function($value) {
        return $value !== null && $value !== '';
    });

    // ✅ ASEGURAR CAMPOS OBLIGATORIOS
    if (empty($apiData['primer_nombre'])) {
        $apiData['primer_nombre'] = 'Sin nombre';
    }
    if (empty($apiData['primer_apellido'])) {
        $apiData['primer_apellido'] = 'Sin apellido';
    }
    if (empty($apiData['documento'])) {
        $apiData['documento'] = 'SIN_DOCUMENTO_' . time();
    }

    return $apiData;
}
    /**
     * ✅ MÉTODOS OFFLINE
     */

    /**
     * Obtener pacientes offline con filtros y paginación
     */
    private function getOfflinePacientes(array $filters = [], int $page = 1): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];
            $perPage = config('api.response.pagination.per_page', 15);

            Log::info('📱 Obteniendo pacientes offline', [
                'sede_id' => $sedeId,
                'filters' => $filters,
                'page' => $page
            ]);

            $result = $this->getLocalPacientes($sedeId, array_merge($filters, ['page' => $page]), $perPage);
            
            // ✅ AGREGAR MENSAJE INFORMATIVO
            $result['message'] = 'Datos cargados desde almacenamiento local (modo offline)';
            
            Log::info('📱 Pacientes offline obtenidos', [
                'total' => $result['meta']['total'] ?? 0,
                'current_page' => $result['meta']['current_page'] ?? 1
            ]);
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Error obteniendo pacientes offline', ['error' => $e->getMessage()]);
            
            return [
                'success' => true, // ✅ Cambiar a true para mostrar mensaje
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0
                ],
                'offline' => true,
                'message' => 'No hay datos offline disponibles'
            ];
        }
    }

    /**
     * Búsqueda offline
     */
    private function searchOffline(array $criteria): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];
            
            $allPacientes = $this->getAllPacientesOffline($sedeId);
            $filteredPacientes = $this->applySearchCriteria($allPacientes, $criteria);
            
            return [
                'success' => true,
                'data' => $filteredPacientes,
                'meta' => [
                    'total' => count($filteredPacientes)
                ],
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error en búsqueda offline', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Error en búsqueda offline'
            ];
        }
    }

    /**
     * Aplicar criterios de búsqueda
     */
    private function applySearchCriteria(array $pacientes, array $criteria): array
    {
        return array_filter($pacientes, function ($paciente) use ($criteria) {
            foreach ($criteria as $field => $value) {
                if (empty($value)) continue;
                
                switch ($field) {
                    case 'documento':
                        if (stripos($paciente['documento'], $value) === false) {
                            return false;
                        }
                        break;
                        
                    case 'nombre':
                        $nombreCompleto = trim(
                            ($paciente['primer_nombre'] ?? '') . ' ' .
                            ($paciente['segundo_nombre'] ?? '') . ' ' .
                            ($paciente['primer_apellido'] ?? '') . ' ' .
                            ($paciente['segundo_apellido'] ?? '')
                        );
                        
                        if (stripos($nombreCompleto, $value) === false) {
                            return false;
                        }
                        break;
                        
                    case 'telefono':
                        if (stripos($paciente['telefono'] ?? '', $value) === false) {
                            return false;
                        }
                        break;
                        
                    case 'estado':
                        if ($paciente['estado'] !== $value) {
                            return false;
                        }
                        break;
                        
                    case 'sexo':
                        if ($paciente['sexo'] !== $value) {
                            return false;
                        }
                        break;
                }
            }
            
            return true;
        });
    }

    /**
     * Almacenar paciente offline
     */
   private function storePacienteOffline(array $pacienteData, bool $needsSync = false): void
{
    try {
        // ✅ VALIDAR DATOS MÍNIMOS REQUERIDOS
        if (empty($pacienteData['uuid'])) {
            Log::warning('⚠️ Intentando guardar paciente sin UUID', [
                'documento' => $pacienteData['documento'] ?? 'sin-documento'
            ]);
            return;
        }

        // ✅ ASEGURAR SEDE_ID
        if (empty($pacienteData['sede_id'])) {
            $user = $this->authService->usuario();
            $pacienteData['sede_id'] = $user['sede_id'] ?? 1;
        }

        $offlineData = [
            'id' => $pacienteData['id'] ?? null,
            'uuid' => $pacienteData['uuid'],
            'sede_id' => $pacienteData['sede_id'],
            
            // ✅ IDs de relaciones (pueden ser UUIDs o números)
            'empresa_id' => $pacienteData['empresa_id'] ?? null,
            'regimen_id' => $pacienteData['regimen_id'] ?? null,
            'tipo_afiliacion_id' => $pacienteData['tipo_afiliacion_id'] ?? null,
            'zona_residencia_id' => $pacienteData['zona_residencia_id'] ?? null,
            'depto_nacimiento_id' => $pacienteData['depto_nacimiento_id'] ?? null,
            'depto_residencia_id' => $pacienteData['depto_residencia_id'] ?? null,
            'municipio_nacimiento_id' => $pacienteData['municipio_nacimiento_id'] ?? null,
            'municipio_residencia_id' => $pacienteData['municipio_residencia_id'] ?? null,
            'raza_id' => $pacienteData['raza_id'] ?? null,
            'escolaridad_id' => $pacienteData['escolaridad_id'] ?? null,
            'parentesco_id' => $pacienteData['parentesco_id'] ?? null,
            'tipo_documento_id' => $pacienteData['tipo_documento_id'] ?? null,
            'ocupacion_id' => $pacienteData['ocupacion_id'] ?? null,
            
            // ✅ Nombres de relaciones para mostrar
            'empresa_nombre' => $pacienteData['empresa_nombre'] ?? null,
            'regimen_nombre' => $pacienteData['regimen_nombre'] ?? null,
            'tipo_documento_nombre' => $pacienteData['tipo_documento_nombre'] ?? null,
            'zona_residencia_nombre' => $pacienteData['zona_residencia_nombre'] ?? null,
            
            // ✅ Datos básicos del paciente
            'registro' => $pacienteData['registro'] ?? null,
            'primer_nombre' => $pacienteData['primer_nombre'] ?? '',
            'segundo_nombre' => $pacienteData['segundo_nombre'] ?? null,
            'primer_apellido' => $pacienteData['primer_apellido'] ?? '',
            'segundo_apellido' => $pacienteData['segundo_apellido'] ?? null,
            'nombre_completo' => $pacienteData['nombre_completo'] ?? '',
            'documento' => $pacienteData['documento'] ?? '',
            'fecha_nacimiento' => $pacienteData['fecha_nacimiento'] ?? null,
            'edad' => $pacienteData['edad'] ?? null,
            'sexo' => $pacienteData['sexo'] ?? 'M',
            'direccion' => $pacienteData['direccion'] ?? null,
            'telefono' => $pacienteData['telefono'] ?? null,
            'correo' => $pacienteData['correo'] ?? null,
            'observacion' => $pacienteData['observacion'] ?? null,
            'estado_civil' => $pacienteData['estado_civil'] ?? null,
            
            // ✅ Datos de acudiente
            'nombre_acudiente' => $pacienteData['nombre_acudiente'] ?? null,
            'parentesco_acudiente' => $pacienteData['parentesco_acudiente'] ?? null,
            'telefono_acudiente' => $pacienteData['telefono_acudiente'] ?? null,
            'direccion_acudiente' => $pacienteData['direccion_acudiente'] ?? null,
            
            // ✅ Datos de acompañante
            'acompanante_nombre' => $pacienteData['acompanante_nombre'] ?? null,
            'acompanante_telefono' => $pacienteData['acompanante_telefono'] ?? null,
            
            // ✅ Estados y fechas
            'estado' => $pacienteData['estado'] ?? 'ACTIVO',
            'fecha_registro' => $pacienteData['fecha_registro'] ?? now()->format('Y-m-d'),
            'fecha_actualizacion' => $pacienteData['fecha_actualizacion'] ?? null,
            
            // ✅ Otros campos
            'novedad_id' => $pacienteData['novedad_id'] ?? null,
            'auxiliar_id' => $pacienteData['auxiliar_id'] ?? null,
            'brigada_id' => $pacienteData['brigada_id'] ?? null,
            
            // ✅ Control de sincronización
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'stored_at' => now()->toISOString(),
            'deleted_at' => $pacienteData['deleted_at'] ?? null
        ];

        $this->offlineService->storeData('pacientes/' . $pacienteData['uuid'] . '.json', $offlineData);
        
        // También indexar por documento para búsquedas rápidas
        if (!empty($pacienteData['documento'])) {
            $this->offlineService->storeData('pacientes_by_document/' . $pacienteData['documento'] . '.json', [
                'uuid' => $pacienteData['uuid'],
                'sede_id' => $pacienteData['sede_id']
            ]);
        }

        Log::debug('✅ Paciente almacenado offline', [
            'uuid' => $pacienteData['uuid'],
            'documento' => $pacienteData['documento'] ?? 'sin-documento',
            'nombre' => $pacienteData['nombre_completo'] ?? 'sin-nombre',
            'sync_status' => $offlineData['sync_status']
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando paciente offline', [
            'error' => $e->getMessage(),
            'uuid' => $pacienteData['uuid'] ?? 'sin-uuid',
            'line' => $e->getLine()
        ]);
    }
}
    /**
     * Obtener paciente offline
     */
    private function getPacienteOffline(string $uuid): ?array
    {
        return $this->offlineService->getData('pacientes/' . $uuid . '.json');
    }

    /**
     * Buscar paciente offline por documento
     */
    private function searchPacienteOfflineByDocument(string $documento, int $sedeId): ?array
    {
        $index = $this->offlineService->getData('pacientes_by_document/' . $documento . '.json');
        
        if (!$index || $index['sede_id'] != $sedeId) {
            return null;
        }

        return $this->getPacienteOffline($index['uuid']);
    }

    /**
     * Eliminar paciente offline
     */
    private function deletePacienteOffline(string $uuid): void
    {
        $paciente = $this->getPacienteOffline($uuid);
        if ($paciente) {
            // Eliminar archivo principal
            $this->offlineService->deleteData('pacientes/' . $uuid . '.json');
            
            // Eliminar índice por documento
            $this->offlineService->deleteData('pacientes_by_document/' . $paciente['documento'] . '.json');
        }
    }

       /**
     * Obtener pacientes locales con paginación
     */
    private function getLocalPacientes(int $sedeId, array $filters, int $perPage): array
    {
        $allPacientes = $this->getAllPacientesOffline($sedeId);
        
        // Aplicar filtros
        $filteredPacientes = $this->applyFilters($allPacientes, $filters);
        
        // Ordenar por fecha de registro (más recientes primero)
        usort($filteredPacientes, function ($a, $b) {
            return strtotime($b['fecha_registro']) - strtotime($a['fecha_registro']);
        });
        
        // Paginación manual
        $total = count($filteredPacientes);
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($filteredPacientes, $offset, $perPage);
        
        return [
            'success' => true,
            'data' => $paginatedData,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total
            ],
            'offline' => true
        ];
    }

    /**
     * Obtener todos los pacientes offline
     */
    private function getAllPacientesOffline(int $sedeId): array
    {
        $pacientesPath = $this->offlineService->getStoragePath() . '/pacientes';
        $pacientes = [];
        
        if (is_dir($pacientesPath)) {
            $files = glob($pacientesPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if ($data && 
                    $data['sede_id'] == $sedeId && 
                    (!isset($data['deleted_at']) || !$data['deleted_at'])) {
                    $pacientes[] = $data;
                }
            }
        }
        
        return $pacientes;
    }

    /**
     * Aplicar filtros a pacientes
     */
    private function applyFilters(array $pacientes, array $filters): array
    {
        if (empty($filters)) {
            return $pacientes;
        }

        return array_filter($pacientes, function ($paciente) use ($filters) {
            // Filtro por documento
            if (isset($filters['documento']) && $filters['documento']) {
                if (stripos($paciente['documento'], $filters['documento']) === false) {
                    return false;
                }
            }

            // Filtro por nombre
            if (isset($filters['nombre']) && $filters['nombre']) {
                $nombreCompleto = trim(
                    ($paciente['primer_nombre'] ?? '') . ' ' .
                    ($paciente['segundo_nombre'] ?? '') . ' ' .
                    ($paciente['primer_apellido'] ?? '') . ' ' .
                    ($paciente['segundo_apellido'] ?? '')
                );
                
                if (stripos($nombreCompleto, $filters['nombre']) === false) {
                    return false;
                }
            }

            // Filtro por estado
            if (isset($filters['estado']) && $filters['estado']) {
                if ($paciente['estado'] !== $filters['estado']) {
                    return false;
                }
            }

            // Filtro por sexo
            if (isset($filters['sexo']) && $filters['sexo']) {
                if ($paciente['sexo'] !== $filters['sexo']) {
                    return false;
                }
            }

            // Filtro por teléfono
            if (isset($filters['telefono']) && $filters['telefono']) {
                if (stripos($paciente['telefono'] ?? '', $filters['telefono']) === false) {
                    return false;
                }
            }

            // ✅ FILTROS DE FECHA
            if (isset($filters['fecha_desde']) && $filters['fecha_desde']) {
                $fechaRegistro = $paciente['fecha_registro'] ?? '';
                if ($fechaRegistro < $filters['fecha_desde']) {
                    return false;
                }
            }

            if (isset($filters['fecha_hasta']) && $filters['fecha_hasta']) {
                $fechaRegistro = $paciente['fecha_registro'] ?? '';
                if ($fechaRegistro > $filters['fecha_hasta']) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Sincronizar pacientes desde API
     */
    private function syncPacientesFromApi(array $pacientes): void
    {
        foreach ($pacientes as $paciente) {
            $this->storePacienteOffline($paciente, false);
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: Limpiar cache de pacientes
     */
    public function clearCache(): void
    {
        try {
            $pacientesPath = $this->offlineService->getStoragePath() . '/pacientes';
            $documentPath = $this->offlineService->getStoragePath() . '/pacientes_by_document';
            
            // Limpiar archivos de pacientes
            if (is_dir($pacientesPath)) {
                $files = glob($pacientesPath . '/*.json');
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            
            // Limpiar índices por documento
            if (is_dir($documentPath)) {
                $files = glob($documentPath . '/*.json');
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            
            Log::info('✅ Cache de pacientes limpiado');
            
        } catch (\Exception $e) {
            Log::error('❌ Error limpiando cache de pacientes', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: Obtener estadísticas de pacientes
     */
    public function getStats(): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];
            
            $allPacientes = $this->getAllPacientesOffline($sedeId);
            
            $stats = [
                'total_pacientes' => count($allPacientes),
                'pacientes_activos' => count(array_filter($allPacientes, function($p) {
                    return ($p['estado'] ?? 'ACTIVO') === 'ACTIVO';
                })),
                'pacientes_inactivos' => count(array_filter($allPacientes, function($p) {
                    return ($p['estado'] ?? 'ACTIVO') === 'INACTIVO';
                })),
                'hombres' => count(array_filter($allPacientes, function($p) {
                    return ($p['sexo'] ?? 'M') === 'M';
                })),
                'mujeres' => count(array_filter($allPacientes, function($p) {
                    return ($p['sexo'] ?? 'M') === 'F';
                })),
                'registros_hoy' => count(array_filter($allPacientes, function($p) {
                    return ($p['fecha_registro'] ?? '') === now()->format('Y-m-d');
                })),
                'registros_mes' => count(array_filter($allPacientes, function($p) {
                    $fechaRegistro = $p['fecha_registro'] ?? '';
                    return $fechaRegistro && 
                           substr($fechaRegistro, 0, 7) === now()->format('Y-m');
                })),
                'pendientes_sync' => count(array_filter($allPacientes, function($p) {
                    return ($p['sync_status'] ?? 'synced') === 'pending';
                }))
            ];
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo estadísticas', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error obteniendo estadísticas'
            ];
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: Validar integridad de datos offline
     */
    public function validateOfflineData(): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];
            
            $allPacientes = $this->getAllPacientesOffline($sedeId);
            $errors = [];
            $warnings = [];
            
            foreach ($allPacientes as $paciente) {
                $uuid = $paciente['uuid'] ?? 'sin-uuid';
                
                // Validar campos obligatorios
                if (empty($paciente['primer_nombre'])) {
                    $errors[] = "Paciente {$uuid}: Falta primer nombre";
                }
                
                if (empty($paciente['primer_apellido'])) {
                    $errors[] = "Paciente {$uuid}: Falta primer apellido";
                }
                
                if (empty($paciente['documento'])) {
                    $errors[] = "Paciente {$uuid}: Falta documento";
                }
                
                if (empty($paciente['fecha_nacimiento'])) {
                    $errors[] = "Paciente {$uuid}: Falta fecha de nacimiento";
                }
                
                // Validar formato de fecha
                if (!empty($paciente['fecha_nacimiento'])) {
                    try {
                        new \DateTime($paciente['fecha_nacimiento']);
                    } catch (\Exception $e) {
                        $errors[] = "Paciente {$uuid}: Fecha de nacimiento inválida";
                    }
                }
                
                // Validar sexo
                if (!in_array($paciente['sexo'] ?? '', ['M', 'F'])) {
                    $warnings[] = "Paciente {$uuid}: Sexo no válido";
                }
                
                // Validar estado
                if (!in_array($paciente['estado'] ?? '', ['ACTIVO', 'INACTIVO'])) {
                    $warnings[] = "Paciente {$uuid}: Estado no válido";
                }
                
                // Validar email si existe
                if (!empty($paciente['correo']) && !filter_var($paciente['correo'], FILTER_VALIDATE_EMAIL)) {
                    $warnings[] = "Paciente {$uuid}: Email inválido";
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'total_pacientes' => count($allPacientes),
                    'errors_count' => count($errors),
                    'warnings_count' => count($warnings),
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'is_valid' => empty($errors)
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error validando datos offline', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error validando datos offline'
            ];
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: Exportar pacientes para backup
     */
    public function exportPacientes(): array
    {
        try {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id'];
            
            $allPacientes = $this->getAllPacientesOffline($sedeId);
            
            $exportData = [
                'exported_at' => now()->toISOString(),
                'sede_id' => $sedeId,
                'user_id' => $user['id'],
                'total_records' => count($allPacientes),
                'pacientes' => $allPacientes
            ];
            
            return [
                'success' => true,
                'data' => $exportData,
                'filename' => 'pacientes_backup_' . now()->format('Y-m-d_H-i-s') . '.json'
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error exportando pacientes', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error exportando pacientes'
            ];
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: Importar pacientes desde backup
     */
    public function importPacientes(array $backupData): array
    {
        try {
            $imported = 0;
            $errors = [];
            
            if (!isset($backupData['pacientes']) || !is_array($backupData['pacientes'])) {
                throw new \Exception('Formato de backup inválido');
            }
            
            foreach ($backupData['pacientes'] as $paciente) {
                try {
                    // Validar datos mínimos
                    if (empty($paciente['uuid']) || empty($paciente['documento'])) {
                        $errors[] = 'Paciente sin UUID o documento';
                        continue;
                    }
                    
                    // Verificar si ya existe
                    $existing = $this->getPacienteOffline($paciente['uuid']);
                    if ($existing) {
                        continue; // Skip si ya existe
                    }
                    
                    // Importar paciente
                    $this->storePacienteOffline($paciente, true); // Marcar como pendiente de sync
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = 'Error importando paciente ' . ($paciente['uuid'] ?? 'sin-uuid') . ': ' . $e->getMessage();
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'imported_count' => $imported,
                    'errors_count' => count($errors),
                    'errors' => $errors
                ],
                'message' => "Se importaron {$imported} pacientes correctamente"
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error importando pacientes', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error importando pacientes: ' . $e->getMessage()
            ];
        }
    }


    public function getTestSyncData(int $sedeId): array
{
    try {
        $allPacientes = $this->getAllPacientesOffline($sedeId);
        
        // Filtrar solo los pendientes
        $pendingPacientes = array_filter($allPacientes, function($paciente) {
            return ($paciente['sync_status'] ?? 'synced') === 'pending';
        });

        $pendingDetails = array_map(function($paciente) {
            return [
                'uuid' => $paciente['uuid'],
                'documento' => $paciente['documento'] ?? 'sin-documento',
                'nombre' => $paciente['nombre_completo'] ?? 
                           (($paciente['primer_nombre'] ?? '') . ' ' . ($paciente['primer_apellido'] ?? '')),
                'stored_at' => $paciente['stored_at'] ?? null
            ];
        }, $pendingPacientes);

        return [
            'total_count' => count($allPacientes),
            'pending_count' => count($pendingPacientes),
            'pending_details' => array_values($pendingDetails)
        ];

    } catch (\Exception $e) {
        Log::error('Error obteniendo datos de test', [
            'error' => $e->getMessage()
        ]);
        
        return [
            'total_count' => 0,
            'pending_count' => 0,
            'pending_details' => []
        ];
    }
}
}
