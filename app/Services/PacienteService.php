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

        // ✅ PREPARAR PARÁMETROS PARA LA API CON TODAS LAS RELACIONES
        $apiParams = array_merge($filters, [
            'page' => $page,
            'sede_id' => $sedeId,
            // ✅ INCLUIR TODAS LAS RELACIONES POSIBLES
            'with' => implode(',', [
                'empresa',
                'regimen', 
                'tipo_documento',
                'tipo_afiliacion',
                'zona_residencia',
                'departamento_nacimiento',
                'departamento_residencia', 
                'municipio_nacimiento',
                'municipio_residencia',
                'raza',
                'escolaridad',
                'parentesco',
                'ocupacion',
                'novedad',
                'auxiliar',
                'brigada',
                'acudiente',
                'acompanante'
            ]),
            'include' => implode(',', [
                'empresa',
                'regimen',
                'tipo_documento', 
                'tipo_afiliacion',
                'zona_residencia',
                'departamento_nacimiento',
                'departamento_residencia',
                'municipio_nacimiento', 
                'municipio_residencia',
                'raza',
                'escolaridad',
                'parentesco',
                'ocupacion',
                'novedad',
                'auxiliar',
                'brigada'
            ])
        ]);

        // Limpiar parámetros vacíos (excepto los de relaciones)
        $apiParams = array_filter($apiParams, function($value, $key) {
            if (in_array($key, ['with', 'include'])) {
                return true; // Mantener siempre los parámetros de relaciones
            }
            return !empty($value) && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        Log::info('📥 Parámetros preparados para API CON TODAS LAS RELACIONES', [
            'params' => $apiParams,
            'api_online' => $this->apiService->isOnline(),
            'relations_count' => substr_count($apiParams['with'], ',') + 1
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
            // ✅ ASEGURAR QUE TENGA SEDE_ID
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
            $paciente['sync_status'] = 'synced';

            // ✅ AGREGAR TIMESTAMPS SI NO EXISTEN
            if (empty($paciente['fecha_registro'])) {
                $paciente['fecha_registro'] = now()->format('Y-m-d');
            }

            // ✅ EXTRAER TODAS LAS RELACIONES ANIDADAS
            $this->extractAllRelations($paciente);

            Log::debug('✅ Paciente enriquecido desde API', [
                'uuid' => $paciente['uuid'],
                'documento' => $paciente['documento'] ?? 'sin-documento',
                'has_empresa' => isset($paciente['empresa']) || isset($paciente['empresa_nombre']),
                'has_novedad' => isset($paciente['novedad']) || isset($paciente['novedad_tipo']),
                'has_auxiliar' => isset($paciente['auxiliar']) || isset($paciente['auxiliar_nombre']),
                'has_brigada' => isset($paciente['brigada']) || isset($paciente['brigada_nombre']),
                'novedad_data' => $paciente['novedad'] ?? 'no-data',
                'auxiliar_data' => $paciente['auxiliar'] ?? 'no-data',
                'brigada_data' => $paciente['brigada'] ?? 'no-data'
            ]);

            return $paciente;

        } catch (\Exception $e) {
            Log::warning('⚠️ Error enriqueciendo datos de paciente desde API', [
                'uuid' => $paciente['uuid'] ?? 'sin-uuid',
                'error' => $e->getMessage()
            ]);
            
            $paciente['sede_id'] = $sedeId;
            $paciente['sync_status'] = 'synced';
            return $paciente;
        }
    }, $pacientes);
}

private function extractAllRelations(array &$paciente): void
{
    try {
        // ✅ EMPRESA
        if (isset($paciente['empresa']) && is_array($paciente['empresa'])) {
            $paciente['empresa_id'] = $paciente['empresa']['uuid'] ?? $paciente['empresa']['id'] ?? null;
            $paciente['empresa_nombre'] = $paciente['empresa']['nombre'] ?? null;
            $paciente['empresa_codigo_eapb'] = $paciente['empresa']['codigo_eapb'] ?? null;
        }

        // ✅ REGIMEN
        if (isset($paciente['regimen']) && is_array($paciente['regimen'])) {
            $paciente['regimen_id'] = $paciente['regimen']['uuid'] ?? $paciente['regimen']['id'] ?? null;
            $paciente['regimen_nombre'] = $paciente['regimen']['nombre'] ?? null;
        }

        // ✅ TIPO DOCUMENTO
        if (isset($paciente['tipo_documento']) && is_array($paciente['tipo_documento'])) {
            $paciente['tipo_documento_id'] = $paciente['tipo_documento']['uuid'] ?? $paciente['tipo_documento']['id'] ?? null;
            $paciente['tipo_documento_nombre'] = $paciente['tipo_documento']['nombre'] ?? null;
            $paciente['tipo_documento_abreviacion'] = $paciente['tipo_documento']['abreviacion'] ?? null;
        }

        // ✅ TIPO AFILIACION
        if (isset($paciente['tipo_afiliacion']) && is_array($paciente['tipo_afiliacion'])) {
            $paciente['tipo_afiliacion_id'] = $paciente['tipo_afiliacion']['uuid'] ?? $paciente['tipo_afiliacion']['id'] ?? null;
            $paciente['tipo_afiliacion_nombre'] = $paciente['tipo_afiliacion']['nombre'] ?? null;
        }

        // ✅ ZONA RESIDENCIA
        if (isset($paciente['zona_residencia']) && is_array($paciente['zona_residencia'])) {
            $paciente['zona_residencia_id'] = $paciente['zona_residencia']['uuid'] ?? $paciente['zona_residencia']['id'] ?? null;
            $paciente['zona_residencia_nombre'] = $paciente['zona_residencia']['nombre'] ?? null;
            $paciente['zona_residencia_abreviacion'] = $paciente['zona_residencia']['abreviacion'] ?? null;
        }

        // ✅ DEPARTAMENTOS
        if (isset($paciente['departamento_nacimiento']) && is_array($paciente['departamento_nacimiento'])) {
            $paciente['depto_nacimiento_id'] = $paciente['departamento_nacimiento']['uuid'] ?? $paciente['departamento_nacimiento']['id'] ?? null;
            $paciente['depto_nacimiento_nombre'] = $paciente['departamento_nacimiento']['nombre'] ?? null;
        }

        if (isset($paciente['departamento_residencia']) && is_array($paciente['departamento_residencia'])) {
            $paciente['depto_residencia_id'] = $paciente['departamento_residencia']['uuid'] ?? $paciente['departamento_residencia']['id'] ?? null;
            $paciente['depto_residencia_nombre'] = $paciente['departamento_residencia']['nombre'] ?? null;
        }

        // ✅ MUNICIPIOS
        if (isset($paciente['municipio_nacimiento']) && is_array($paciente['municipio_nacimiento'])) {
            $paciente['municipio_nacimiento_id'] = $paciente['municipio_nacimiento']['uuid'] ?? $paciente['municipio_nacimiento']['id'] ?? null;
            $paciente['municipio_nacimiento_nombre'] = $paciente['municipio_nacimiento']['nombre'] ?? null;
        }

        if (isset($paciente['municipio_residencia']) && is_array($paciente['municipio_residencia'])) {
            $paciente['municipio_residencia_id'] = $paciente['municipio_residencia']['uuid'] ?? $paciente['municipio_residencia']['id'] ?? null;
            $paciente['municipio_residencia_nombre'] = $paciente['municipio_residencia']['nombre'] ?? null;
        }

        // ✅ RAZA
        if (isset($paciente['raza']) && is_array($paciente['raza'])) {
            $paciente['raza_id'] = $paciente['raza']['uuid'] ?? $paciente['raza']['id'] ?? null;
            $paciente['raza_nombre'] = $paciente['raza']['nombre'] ?? null;
        }

        // ✅ ESCOLARIDAD
        if (isset($paciente['escolaridad']) && is_array($paciente['escolaridad'])) {
            $paciente['escolaridad_id'] = $paciente['escolaridad']['uuid'] ?? $paciente['escolaridad']['id'] ?? null;
            $paciente['escolaridad_nombre'] = $paciente['escolaridad']['nombre'] ?? null;
        }

        // ✅ PARENTESCO
        if (isset($paciente['parentesco']) && is_array($paciente['parentesco'])) {
            $paciente['parentesco_id'] = $paciente['parentesco']['uuid'] ?? $paciente['parentesco']['id'] ?? null;
            $paciente['parentesco_nombre'] = $paciente['parentesco']['nombre'] ?? null;
        }

        // ✅ OCUPACION
        if (isset($paciente['ocupacion']) && is_array($paciente['ocupacion'])) {
            $paciente['ocupacion_id'] = $paciente['ocupacion']['uuid'] ?? $paciente['ocupacion']['id'] ?? null;
            $paciente['ocupacion_nombre'] = $paciente['ocupacion']['nombre'] ?? null;
            $paciente['ocupacion_codigo'] = $paciente['ocupacion']['codigo'] ?? null;
        }

        // ✅ NOVEDAD (IMPORTANTE)
        if (isset($paciente['novedad']) && is_array($paciente['novedad'])) {
            $paciente['novedad_id'] = $paciente['novedad']['uuid'] ?? $paciente['novedad']['id'] ?? null;
            $paciente['novedad_tipo'] = $paciente['novedad']['tipo_novedad'] ?? $paciente['novedad']['nombre'] ?? null;
            
            Log::info('✅ Novedad extraída de API', [
                'paciente_uuid' => $paciente['uuid'],
                'novedad_id' => $paciente['novedad_id'],
                'novedad_tipo' => $paciente['novedad_tipo'],
                'novedad_raw' => $paciente['novedad']
            ]);
        } else {
            Log::info('ℹ️ Sin novedad en respuesta API', [
                'paciente_uuid' => $paciente['uuid'],
                'has_novedad_key' => isset($paciente['novedad']),
                'novedad_value' => $paciente['novedad'] ?? 'not-set'
            ]);
        }

        // ✅ AUXILIAR (IMPORTANTE)
        if (isset($paciente['auxiliar']) && is_array($paciente['auxiliar'])) {
            $paciente['auxiliar_id'] = $paciente['auxiliar']['uuid'] ?? $paciente['auxiliar']['id'] ?? null;
            $paciente['auxiliar_nombre'] = $paciente['auxiliar']['nombre'] ?? null;
            
            Log::info('✅ Auxiliar extraído de API', [
                'paciente_uuid' => $paciente['uuid'],
                'auxiliar_id' => $paciente['auxiliar_id'],
                'auxiliar_nombre' => $paciente['auxiliar_nombre'],
                'auxiliar_raw' => $paciente['auxiliar']
            ]);
        } else {
            Log::info('ℹ️ Sin auxiliar en respuesta API', [
                'paciente_uuid' => $paciente['uuid'],
                'has_auxiliar_key' => isset($paciente['auxiliar']),
                'auxiliar_value' => $paciente['auxiliar'] ?? 'not-set'
            ]);
        }

        // ✅ BRIGADA (IMPORTANTE)
        if (isset($paciente['brigada']) && is_array($paciente['brigada'])) {
            $paciente['brigada_id'] = $paciente['brigada']['uuid'] ?? $paciente['brigada']['id'] ?? null;
            $paciente['brigada_nombre'] = $paciente['brigada']['nombre'] ?? null;
            
            Log::info('✅ Brigada extraída de API', [
                'paciente_uuid' => $paciente['uuid'],
                'brigada_id' => $paciente['brigada_id'],
                'brigada_nombre' => $paciente['brigada_nombre'],
                'brigada_raw' => $paciente['brigada']
            ]);
        } else {
            Log::info('ℹ️ Sin brigada en respuesta API', [
                'paciente_uuid' => $paciente['uuid'],
                'has_brigada_key' => isset($paciente['brigada']),
                'brigada_value' => $paciente['brigada'] ?? 'not-set'
            ]);
        }

        // ✅ ACUDIENTE
        if (isset($paciente['acudiente']) && is_array($paciente['acudiente'])) {
            $paciente['nombre_acudiente'] = $paciente['acudiente']['nombre'] ?? null;
            $paciente['parentesco_acudiente'] = $paciente['acudiente']['parentesco'] ?? null;
            $paciente['telefono_acudiente'] = $paciente['acudiente']['telefono'] ?? null;
            $paciente['direccion_acudiente'] = $paciente['acudiente']['direccion'] ?? null;
        }

        // ✅ ACOMPAÑANTE
        if (isset($paciente['acompanante']) && is_array($paciente['acompanante'])) {
            $paciente['acompanante_nombre'] = $paciente['acompanante']['nombre'] ?? null;
            $paciente['acompanante_telefono'] = $paciente['acompanante']['telefono'] ?? null;
        }

    } catch (\Exception $e) {
        Log::error('❌ Error extrayendo relaciones de paciente', [
            'uuid' => $paciente['uuid'] ?? 'sin-uuid',
            'error' => $e->getMessage()
        ]);
    }
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

        // ✅ INTENTAR OBTENER ONLINE PRIMERO CON TODAS LAS RELACIONES
        if ($this->apiService->isOnline()) {
            try {
                // ✅ PARÁMETROS CON TODAS LAS RELACIONES
                $params = [
                    'with' => implode(',', [
                        'empresa',
                        'regimen',
                        'tipo_documento',
                        'tipo_afiliacion',
                        'zona_residencia',
                        'departamento_nacimiento',
                        'departamento_residencia',
                        'municipio_nacimiento',
                        'municipio_residencia',
                        'raza',
                        'escolaridad',
                        'parentesco',
                        'ocupacion',
                        'novedad',
                        'auxiliar',
                        'brigada',
                        'acudiente',
                        'acompanante'
                    ]),
                    'include' => implode(',', [
                        'empresa',
                        'regimen',
                        'tipo_documento',
                        'tipo_afiliacion',
                        'zona_residencia',
                        'departamento_nacimiento',
                        'departamento_residencia',
                        'municipio_nacimiento',
                        'municipio_residencia',
                        'raza',
                        'escolaridad',
                        'parentesco',
                        'ocupacion',
                        'novedad',
                        'auxiliar',
                        'brigada'
                    ])
                ];

                $response = $this->apiService->get(
                    $this->getEndpoint('show', ['uuid' => $uuid]),
                    $params
                );
                
                Log::info('📥 Respuesta API para show CON TODAS LAS RELACIONES', [
                    'uuid' => $uuid,
                    'success' => $response['success'] ?? false,
                    'error' => $response['error'] ?? null,
                    'relations_requested' => substr_count($params['with'], ',') + 1
                ]);
                
                if ($response['success']) {
                    // ✅ ÉXITO - Extraer TODAS las relaciones y actualizar datos locales
                    $apiData = $response['data'];
                    
                    // ✅ VERIFICAR SI HAY CAMBIOS PENDIENTES ANTES DE SOBRESCRIBIR
                    if ($this->offlineService->hasPendingChangesForPaciente($uuid)) {
                        Log::warning('⚠️ Paciente tiene cambios pendientes, sincronizando primero', [
                            'uuid' => $uuid
                        ]);
                        
                        // Sincronizar cambios pendientes ANTES de sobrescribir
                        $syncResult = $this->offlineService->syncPendingChangesForPaciente($uuid);
                        
                        if ($syncResult['success']) {
                            Log::info('✅ Cambios pendientes sincronizados antes de actualizar', [
                                'uuid' => $uuid
                            ]);
                            
                            // Si la sincronización devolvió datos actualizados, usarlos
                            if (isset($syncResult['data'])) {
                                $apiData = $syncResult['data'];
                            }
                        } else {
                            Log::error('❌ Error sincronizando cambios pendientes', [
                                'uuid' => $uuid,
                                'error' => $syncResult['error'] ?? 'Error desconocido'
                            ]);
                            
                            // NO sobrescribir si falló la sincronización
                            // Devolver datos locales en su lugar
                            $localData = $this->getPacienteOffline($uuid);
                            return [
                                'success' => true,
                                'data' => $localData,
                                'offline' => true,
                                'warning' => 'Hay cambios pendientes que no se pudieron sincronizar'
                            ];
                        }
                    }
                    
                    // ✅ EXTRAER TODAS LAS RELACIONES ANTES DE GUARDAR
                    $this->extractAllRelations($apiData);
                    
                    // ✅ GUARDAR OFFLINE CON TODAS LAS RELACIONES
                    $this->storePacienteOffline($apiData, false);
                    
                    Log::info('✅ Paciente obtenido y guardado desde API CON RELACIONES', [
                        'uuid' => $uuid,
                        'has_empresa' => isset($apiData['empresa']) || isset($apiData['empresa_nombre']),
                        'has_tipo_afiliacion' => isset($apiData['tipo_afiliacion']) || isset($apiData['tipo_afiliacion_nombre']),
                        'has_parentesco' => isset($apiData['parentesco']) || isset($apiData['parentesco_nombre']),
                        'has_raza' => isset($apiData['raza']) || isset($apiData['raza_nombre']),
                        'has_escolaridad' => isset($apiData['escolaridad']) || isset($apiData['escolaridad_nombre']),
                        'has_ocupacion' => isset($apiData['ocupacion']) || isset($apiData['ocupacion_nombre']),
                        'has_novedad' => isset($apiData['novedad']) || isset($apiData['novedad_tipo']),
                        'has_auxiliar' => isset($apiData['auxiliar']) || isset($apiData['auxiliar_nombre']),
                        'has_brigada' => isset($apiData['brigada']) || isset($apiData['brigada_nombre'])
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $apiData,
                        'offline' => false
                    ];
                }
                
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
            'sync_status' => $paciente['sync_status'] ?? 'unknown',
            'has_empresa_offline' => isset($paciente['empresa']) || isset($paciente['empresa_nombre']),
            'has_tipo_afiliacion_offline' => isset($paciente['tipo_afiliacion']) || isset($paciente['tipo_afiliacion_nombre']),
            'has_parentesco_offline' => isset($paciente['parentesco']) || isset($paciente['parentesco_nombre']),
            'has_raza_offline' => isset($paciente['raza']) || isset($paciente['raza_nombre']),
            'has_escolaridad_offline' => isset($paciente['escolaridad']) || isset($paciente['escolaridad_nombre']),
            'has_ocupacion_offline' => isset($paciente['ocupacion']) || isset($paciente['ocupacion_nombre']),
            'has_novedad_offline' => isset($paciente['novedad']) || isset($paciente['novedad_tipo']),
            'has_auxiliar_offline' => isset($paciente['auxiliar']) || isset($paciente['auxiliar_nombre']),
            'has_brigada_offline' => isset($paciente['brigada']) || isset($paciente['brigada_nombre'])
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
 * ✅ CORREGIDO: Buscar paciente por documento FILTRADO POR SEDE DEL LOGIN
 */
public function searchByDocument(string $documento, ?int $sedeId = null): array
{
    try {
        // ✅ OBTENER SEDE DEL LOGIN SI NO SE PROPORCIONA
        if (!$sedeId) {
            $user = $this->authService->usuario();
            $sedeId = $user['sede_id']; // ← SEDE DEL LOGIN
        }
        
        Log::info('🔍 PacienteService::searchByDocument - Filtrando por sede del login', [
            'documento' => $documento,
            'sede_filtro' => $sedeId
        ]);

        // ✅ INTENTAR BUSCAR ONLINE PRIMERO CON FILTRO DE SEDE
        if ($this->apiService->isOnline()) {
            try {
                // ✅ AGREGAR SEDE_ID A LOS PARÁMETROS DE BÚSQUEDA
                $params = [
                    'documento' => $documento,
                    'sede_id' => $sedeId // ← FORZAR FILTRO POR SEDE
                ];
                
                $response = $this->apiService->get(
                    $this->getEndpoint('search_by_document'), 
                    $params
                );

                Log::info('📥 Respuesta API para búsqueda por documento', [
                    'documento' => $documento,
                    'sede_filtro' => $sedeId,
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data'])
                ]);

                if ($response['success']) {
                    $pacienteData = $response['data'];
                    
                    // ✅ DOBLE VERIFICACIÓN: ASEGURAR QUE EL PACIENTE SEA DE LA SEDE CORRECTA
                    if (($pacienteData['sede_id'] ?? 0) != $sedeId) {
                        Log::warning('⚠️ Paciente encontrado en API pero de sede diferente', [
                            'documento' => $documento,
                            'sede_esperada' => $sedeId,
                            'sede_paciente' => $pacienteData['sede_id'] ?? 'NO_DEFINIDA'
                        ]);
                        
                        // Continuar con búsqueda offline
                    } else {
                        // ✅ PACIENTE VÁLIDO - Sincronizar localmente
                        $this->storePacienteOffline($pacienteData, false);

                        Log::info('✅ Paciente encontrado online y es de la sede correcta', [
                            'documento' => $documento,
                            'sede_id' => $pacienteData['sede_id'],
                            'uuid' => $pacienteData['uuid']
                        ]);

                        return [
                            'success' => true,
                            'data' => [$pacienteData], // ← RETORNAR COMO ARRAY PARA CONSISTENCIA
                            'offline' => false
                        ];
                    }
                }
                
                // Si no se encuentra online, continuar con búsqueda offline
                Log::info('ℹ️ Paciente no encontrado online, buscando offline', [
                    'documento' => $documento,
                    'sede_filtro' => $sedeId
                ]);
                
            } catch (\Exception $e) {
                Log::warning('⚠️ Error buscando paciente online, usando offline', [
                    'documento' => $documento,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ BUSCAR LOCALMENTE CON FILTRO DE SEDE
        Log::info('📱 Buscando paciente offline con filtro de sede', [
            'documento' => $documento,
            'sede_filtro' => $sedeId
        ]);
        
        $pacientes = $this->searchPacientesOfflineByDocument($documento, $sedeId);

        if (empty($pacientes)) {
            Log::info('❌ Paciente no encontrado offline', [
                'documento' => $documento,
                'sede_filtro' => $sedeId
            ]);
            
            return [
                'success' => false,
                'error' => 'Paciente no encontrado en esta sede',
                'data' => []
            ];
        }

        Log::info('✅ Pacientes encontrados offline filtrados por sede', [
            'documento' => $documento,
            'sede_filtro' => $sedeId,
            'total_encontrados' => count($pacientes)
        ]);

        return [
            'success' => true,
            'data' => $pacientes, // ← YA ES ARRAY
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error buscando paciente por documento', [
            'error' => $e->getMessage(),
            'documento' => $documento,
            'sede_id' => $sedeId
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno en búsqueda: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

/**
 * ✅ CORREGIDO: Búsqueda general de pacientes CON FILTRO DE SEDE
 */
public function search(array $criteria): array
{
    try {
        // ✅ OBTENER SEDE DEL LOGIN
        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];
        
        // ✅ AGREGAR FILTRO DE SEDE OBLIGATORIO
        $criteria['sede_id'] = $sedeId;
        
        Log::info('🔍 PacienteService::search - Con filtro de sede', [
            'criteria_original' => array_diff_key($criteria, ['sede_id' => null]),
            'sede_filtro' => $sedeId
        ]);

        // Intentar búsqueda online primero
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get(
                    $this->getEndpoint('search'), 
                    $criteria // ← YA INCLUYE sede_id
                );

                if ($response['success']) {
                    $pacientes = $response['data'] ?? [];
                    
                    // ✅ DOBLE FILTRADO POR SEDE
                    $pacientesFiltrados = array_filter($pacientes, function($paciente) use ($sedeId) {
                        return ($paciente['sede_id'] ?? 0) == $sedeId;
                    });
                    
                    // Sincronizar resultados localmente
                    if (!empty($pacientesFiltrados)) {
                        foreach ($pacientesFiltrados as $paciente) {
                            $this->storePacienteOffline($paciente, false);
                        }
                    }

                    Log::info('✅ Búsqueda online completada con filtro de sede', [
                        'total_api' => count($pacientes),
                        'filtrados_sede' => count($pacientesFiltrados),
                        'sede_filtro' => $sedeId
                    ]);

                    return [
                        'success' => true,
                        'data' => array_values($pacientesFiltrados),
                        'meta' => $response['meta'] ?? [],
                        'offline' => false
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error en búsqueda online', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ BÚSQUEDA OFFLINE CON SEDE
        return $this->searchOfflineWithSede($criteria, $sedeId);

    } catch (\Exception $e) {
        Log::error('❌ Error en búsqueda de pacientes', [
            'error' => $e->getMessage(), 
            'criteria' => $criteria
        ]);
        
        return [
            'success' => false,
            'error' => 'Error interno en búsqueda'
        ];
    }
}

/**
 * ✅ NUEVO: Búsqueda offline con filtro de sede
 */
private function searchOfflineWithSede(array $criteria, int $sedeId): array
{
    try {
        $allPacientes = $this->getAllPacientesOffline($sedeId); // ← YA FILTRA POR SEDE
        $filteredPacientes = $this->applySearchCriteria($allPacientes, $criteria);
        
        Log::info('✅ Búsqueda offline completada', [
            'sede_filtro' => $sedeId,
            'total_pacientes_sede' => count($allPacientes),
            'resultados_filtrados' => count($filteredPacientes)
        ]);
        
        return [
            'success' => true,
            'data' => $filteredPacientes,
            'meta' => [
                'total' => count($filteredPacientes)
            ],
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error en búsqueda offline', [
            'error' => $e->getMessage(),
            'sede_id' => $sedeId
        ]);
        
        return [
            'success' => false,
            'error' => 'Error en búsqueda offline'
        ];
    }
}
/**
 * ✅ MEJORADO: Buscar pacientes offline por documento CON FILTRO DE SEDE
 */
private function searchPacientesOfflineByDocument(string $documento, int $sedeId): array
{
    try {
        Log::info('🔍 Búsqueda offline por documento con sede', [
            'documento' => $documento,
            'sede_filtro' => $sedeId
        ]);
        
        // ✅ MÉTODO 1: Buscar por índice de documento
        $index = $this->offlineService->getData('pacientes_by_document/' . $documento . '.json');
        
        if ($index && isset($index['uuid']) && ($index['sede_id'] ?? 0) == $sedeId) {
            $paciente = $this->getPacienteOffline($index['uuid']);
            if ($paciente && isset($paciente['uuid'])) {
                Log::info('✅ Paciente encontrado por índice de documento', [
                    'documento' => $documento,
                    'uuid' => $paciente['uuid'],
                    'sede_id' => $paciente['sede_id'] ?? 'NO_DEFINIDA'
                ]);
                return [$paciente];
            }
        }
        
        // ✅ MÉTODO 2: Búsqueda completa en todos los archivos de la sede
        Log::info('🔍 Búsqueda completa en archivos offline', [
            'documento' => $documento,
            'sede_filtro' => $sedeId
        ]);
        
        $allPacientes = $this->getAllPacientesOffline($sedeId);
        $pacientesEncontrados = [];
        
        foreach ($allPacientes as $paciente) {
            // ✅ VALIDAR QUE EL PACIENTE TENGA UUID
            if (!isset($paciente['uuid']) || empty($paciente['uuid'])) {
                Log::warning('⚠️ Paciente sin UUID encontrado en búsqueda offline', [
                    'documento_paciente' => $paciente['documento'] ?? 'NO_DEFINIDO',
                    'paciente_keys' => array_keys($paciente)
                ]);
                continue;
            }
            
            $documentoPaciente = $paciente['documento'] ?? '';
            
            // ✅ BÚSQUEDA EXACTA Y PARCIAL
            if ($documentoPaciente === $documento || 
                str_contains($documentoPaciente, $documento)) {
                
                // ✅ VERIFICAR SEDE NUEVAMENTE
                if (($paciente['sede_id'] ?? 0) == $sedeId) {
                    $pacientesEncontrados[] = $paciente;
                    
                    Log::info('✅ Paciente válido encontrado', [
                        'documento' => $documento,
                        'uuid' => $paciente['uuid'],
                        'sede_id' => $paciente['sede_id'],
                        'nombre' => ($paciente['primer_nombre'] ?? '') . ' ' . ($paciente['primer_apellido'] ?? '')
                    ]);
                }
            }
        }
        
        Log::info('📊 Resultado búsqueda offline completa', [
            'documento' => $documento,
            'sede_filtro' => $sedeId,
            'total_pacientes_revisados' => count($allPacientes),
            'pacientes_encontrados' => count($pacientesEncontrados)
        ]);
        
        return $pacientesEncontrados;
        
    } catch (\Exception $e) {
        Log::error('❌ Error en búsqueda offline por documento', [
            'documento' => $documento,
            'sede_id' => $sedeId,
            'error' => $e->getMessage()
        ]);
        
        return [];
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
                    // ✅ VERIFICAR SI EL UUID CAMBIÓ
                    $oldUuid = $paciente['uuid'];
                    $newUuid = $result['data']['uuid'] ?? $oldUuid;
                    
                    if ($oldUuid !== $newUuid) {
                        Log::info('🔄 UUID de paciente cambió durante sincronización', [
                            'old_uuid' => $oldUuid,
                            'new_uuid' => $newUuid
                        ]);
                        
                        // ✅ ACTUALIZAR REFERENCIAS EN CITAS E HISTORIAS CLÍNICAS
                        $this->updatePacienteUuidInRelatedTables($oldUuid, $newUuid);
                    }
                    
                    // ✅ MARCAR COMO SINCRONIZADO
                    $this->markPacienteAsSynced($oldUuid, $newUuid, $result['data'] ?? []);
                    $results['synced'][] = $newUuid;
                    
                    Log::info('✅ Paciente sincronizado', [
                        'uuid' => $newUuid,
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
// En PacienteService.php - Método syncSinglePacienteToApi()
private function syncSinglePacienteToApi(array $paciente): array
{
    try {
        $apiData = $this->prepareDataForApi($paciente);

        if (!empty($paciente['uuid'])) {
            Log::info('🔄 Intentando actualizar paciente existente (PUT)', [
                'uuid' => $paciente['uuid']
            ]);
            
            $response = $this->apiService->put("/pacientes/{$paciente['uuid']}", $apiData);
            
            if ($response['success']) {
                return $response;
            }
            
            // ✅ DETECCIÓN MEJORADA - BUSCAR EN TODO EL ERROR
            if (!$response['success'] && isset($response['error'])) {
                $errorMessage = strtolower($response['error']);
                
                // ✅ BUSCAR MÚLTIPLES PATRONES DE 404
                $is404 = (
                    strpos($errorMessage, 'status code 404') !== false ||
                    strpos($errorMessage, 'paciente no encontrado') !== false ||
                    strpos($errorMessage, 'not found') !== false ||
                    strpos($errorMessage, '404') !== false ||
                    (isset($response['status_code']) && $response['status_code'] == 404)
                );
                
                Log::info('🔍 Analizando error para detectar 404', [
                    'uuid' => $paciente['uuid'],
                    'error_message' => substr($errorMessage, 0, 200),
                    'is_404_detected' => $is404,
                    'status_code' => $response['status_code'] ?? 'no-status'
                ]);
                
                if ($is404) {
                    Log::info('✅ 404 detectado - Intentando POST', [
                        'uuid' => $paciente['uuid']
                    ]);
                    
                    // ✅ INTENTAR POST SIN UUID
                    $postData = $apiData;
                    unset($postData['uuid']);
                    
                    $postResponse = $this->apiService->post('/pacientes', $postData);
                    
                    Log::info('📥 Resultado POST después de 404', [
                        'uuid' => $paciente['uuid'],
                        'success' => $postResponse['success'] ?? false
                    ]);
                    
                    return $postResponse;
                }
            }
            
            return $response;
        }
        
        // POST para pacientes sin UUID
        return $this->apiService->post('/pacientes', $apiData);

    } catch (\Exception $e) {
        Log::error('❌ Error enviando paciente a API', [
            'uuid' => $paciente['uuid'] ?? 'sin-uuid',
            'error' => $e->getMessage()
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

   
private function storePacienteOffline(array $pacienteData, bool $needsSync = false): void
{
    try {
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
            
            // ✅ DATOS BÁSICOS
            'primer_nombre' => $pacienteData['primer_nombre'] ?? '',
            'segundo_nombre' => $pacienteData['segundo_nombre'] ?? null,
            'primer_apellido' => $pacienteData['primer_apellido'] ?? '',
            'segundo_apellido' => $pacienteData['segundo_apellido'] ?? null,
            'nombre_completo' => $pacienteData['nombre_completo'] ?? 
                                (($pacienteData['primer_nombre'] ?? '') . ' ' . 
                                 ($pacienteData['primer_apellido'] ?? '')),
            'documento' => $pacienteData['documento'] ?? '',
            'fecha_nacimiento' => $pacienteData['fecha_nacimiento'] ?? null,
            'edad' => $pacienteData['edad'] ?? null,
            'sexo' => $pacienteData['sexo'] ?? 'M',
            'telefono' => $pacienteData['telefono'] ?? null,
            'direccion' => $pacienteData['direccion'] ?? null,
            'correo' => $pacienteData['correo'] ?? null,
            'estado_civil' => $pacienteData['estado_civil'] ?? null,
            'observacion' => $pacienteData['observacion'] ?? null,
            'registro' => $pacienteData['registro'] ?? null,
            'estado' => $pacienteData['estado'] ?? 'ACTIVO',
            
            // ✅ IDs DE RELACIONES
            'tipo_documento_id' => $pacienteData['tipo_documento_id'] ?? null,
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
            'ocupacion_id' => $pacienteData['ocupacion_id'] ?? null,
            'novedad_id' => $pacienteData['novedad_id'] ?? null,
            'auxiliar_id' => $pacienteData['auxiliar_id'] ?? null,
            'brigada_id' => $pacienteData['brigada_id'] ?? null,
            
            // ✅ NOMBRES DE RELACIONES PARA MOSTRAR
            'tipo_documento_nombre' => $pacienteData['tipo_documento_nombre'] ?? null,
            'tipo_documento_abreviacion' => $pacienteData['tipo_documento_abreviacion'] ?? null,
            'empresa_nombre' => $pacienteData['empresa_nombre'] ?? null,
            'empresa_codigo_eapb' => $pacienteData['empresa_codigo_eapb'] ?? null,
            'regimen_nombre' => $pacienteData['regimen_nombre'] ?? null,
            'tipo_afiliacion_nombre' => $pacienteData['tipo_afiliacion_nombre'] ?? null,
            'zona_residencia_nombre' => $pacienteData['zona_residencia_nombre'] ?? null,
            'zona_residencia_abreviacion' => $pacienteData['zona_residencia_abreviacion'] ?? null,
            'depto_nacimiento_nombre' => $pacienteData['depto_nacimiento_nombre'] ?? null,
            'depto_residencia_nombre' => $pacienteData['depto_residencia_nombre'] ?? null,
            'municipio_nacimiento_nombre' => $pacienteData['municipio_nacimiento_nombre'] ?? null,
            'municipio_residencia_nombre' => $pacienteData['municipio_residencia_nombre'] ?? null,
            'raza_nombre' => $pacienteData['raza_nombre'] ?? null,
            'escolaridad_nombre' => $pacienteData['escolaridad_nombre'] ?? null,
            'parentesco_nombre' => $pacienteData['parentesco_nombre'] ?? null,
            'ocupacion_nombre' => $pacienteData['ocupacion_nombre'] ?? null,
            'ocupacion_codigo' => $pacienteData['ocupacion_codigo'] ?? null,
            'novedad_tipo' => $pacienteData['novedad_tipo'] ?? null,
            'auxiliar_nombre' => $pacienteData['auxiliar_nombre'] ?? null,
            'brigada_nombre' => $pacienteData['brigada_nombre'] ?? null,
            
            // ✅ DATOS DE ACUDIENTE
            'nombre_acudiente' => $pacienteData['nombre_acudiente'] ?? null,
            'parentesco_acudiente' => $pacienteData['parentesco_acudiente'] ?? null,
            'telefono_acudiente' => $pacienteData['telefono_acudiente'] ?? null,
            'direccion_acudiente' => $pacienteData['direccion_acudiente'] ?? null,
            
            // ✅ DATOS DE ACOMPAÑANTE
            'acompanante_nombre' => $pacienteData['acompanante_nombre'] ?? null,
            'acompanante_telefono' => $pacienteData['acompanante_telefono'] ?? null,
            
            // ✅ FECHAS Y CONTROL
            'fecha_registro' => $pacienteData['fecha_registro'] ?? now()->format('Y-m-d'),
            'fecha_actualizacion' => $pacienteData['fecha_actualizacion'] ?? null,
            'sync_status' => $needsSync ? 'pending' : 'synced',
            'stored_at' => now()->toISOString(),
            'deleted_at' => $pacienteData['deleted_at'] ?? null
        ];

        $this->offlineService->storeData('pacientes/' . $pacienteData['uuid'] . '.json', $offlineData);
        
        // También indexar por documento
        if (!empty($pacienteData['documento'])) {
            $this->offlineService->storeData('pacientes_by_document/' . $pacienteData['documento'] . '.json', [
                'uuid' => $pacienteData['uuid'],
                'sede_id' => $pacienteData['sede_id']
            ]);
        }

        Log::debug('✅ Paciente almacenado offline completo', [
            'uuid' => $pacienteData['uuid'],
            'documento' => $pacienteData['documento'] ?? 'sin-documento',
            'sync_status' => $offlineData['sync_status'],
            'has_empresa' => !empty($offlineData['empresa_nombre']),
            'has_novedad' => !empty($offlineData['novedad_tipo']),
            'has_auxiliar' => !empty($offlineData['auxiliar_nombre']),
            'has_brigada' => !empty($offlineData['brigada_nombre'])
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error almacenando paciente offline', [
            'error' => $e->getMessage(),
            'uuid' => $pacienteData['uuid'] ?? 'sin-uuid',
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * ✅ NUEVO MÉTODO: Marcar paciente como sincronizado después de sync exitoso
 */
private function markPacienteAsSynced(string $oldUuid, string $newUuid, array $apiData = []): void
{
    try {
        $oldFilePath = $this->offlineService->getStoragePath() . "/pacientes/{$oldUuid}.json";
        $newFilePath = $this->offlineService->getStoragePath() . "/pacientes/{$newUuid}.json";
        
        if (file_exists($oldFilePath)) {
            $currentData = json_decode(file_get_contents($oldFilePath), true);
            
            // ✅ ACTUALIZAR CON DATOS DE LA API SI ESTÁN DISPONIBLES
            if (!empty($apiData)) {
                $currentData = array_merge($currentData, $apiData);
            }
            
            // ✅ ACTUALIZAR UUID
            $currentData['uuid'] = $newUuid;
            
            // ✅ MARCAR COMO SINCRONIZADO
            $currentData['sync_status'] = 'synced';
            $currentData['synced_at'] = now()->toISOString();
            
            // ✅ GUARDAR CON EL NUEVO UUID
            file_put_contents($newFilePath, json_encode($currentData, JSON_PRETTY_PRINT));
            
            // ✅ ELIMINAR ARCHIVO VIEJO SI EL UUID CAMBIÓ
            if ($oldUuid !== $newUuid && file_exists($oldFilePath)) {
                unlink($oldFilePath);
                Log::info('🗑️ Archivo antiguo eliminado', ['old_uuid' => $oldUuid]);
            }
            
            Log::info('✅ Paciente marcado como sincronizado', [
                'old_uuid' => $oldUuid,
                'new_uuid' => $newUuid
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error marcando paciente como sincronizado', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Actualizar el UUID del paciente en todas las tablas relacionadas
 */
private function updatePacienteUuidInRelatedTables(string $oldUuid, string $newUuid): void
{
    try {
        Log::info('🔄 Actualizando paciente_uuid en tablas relacionadas', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
        
        $sedeId = $this->authService->usuario()['sede_id'];
        
        // ✅ ACTUALIZAR EN CITAS (SQLite)
        $citasUpdated = \DB::connection('offline')->table('citas')
            ->where('paciente_uuid', $oldUuid)
            ->where('sede_id', $sedeId)
            ->update(['paciente_uuid' => $newUuid]);
        
        Log::info('✅ Citas actualizadas en SQLite', [
            'updated_count' => $citasUpdated,
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
        
        // ✅ ACTUALIZAR EN CITAS (JSON)
        $citasDir = storage_path('app/offline/citas/');
        if (is_dir($citasDir)) {
            $files = glob($citasDir . '*.json');
            $jsonUpdated = 0;
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if (isset($data['paciente_uuid']) && $data['paciente_uuid'] === $oldUuid) {
                    $data['paciente_uuid'] = $newUuid;
                    
                    // Actualizar también el objeto paciente si existe
                    if (isset($data['paciente']['uuid'])) {
                        $data['paciente']['uuid'] = $newUuid;
                    }
                    
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    $jsonUpdated++;
                    
                    Log::info('✅ Archivo JSON de cita actualizado', [
                        'file' => basename($file),
                        'cita_uuid' => $data['uuid'] ?? 'unknown',
                        'old_paciente_uuid' => $oldUuid,
                        'new_paciente_uuid' => $newUuid
                    ]);
                }
            }
            
            if ($jsonUpdated > 0) {
                Log::info('✅ Archivos JSON de citas actualizados', ['count' => $jsonUpdated]);
            }
        }
        
        // ✅ ACTUALIZAR EN HISTORIAS CLÍNICAS (SQLite)
        $historiasUpdated = \DB::connection('offline')->table('historias_clinicas')
            ->where('paciente_uuid', $oldUuid)
            ->where('sede_id', $sedeId)
            ->update(['paciente_uuid' => $newUuid]);
        
        Log::info('✅ Historias clínicas actualizadas en SQLite', [
            'updated_count' => $historiasUpdated,
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid
        ]);
        
        // ✅ ACTUALIZAR EN HISTORIAS CLÍNICAS (JSON)
        $historiasDir = storage_path('app/offline/historias_clinicas/');
        if (is_dir($historiasDir)) {
            $files = glob($historiasDir . '*.json');
            $jsonUpdated = 0;
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if (isset($data['paciente_uuid']) && $data['paciente_uuid'] === $oldUuid) {
                    $data['paciente_uuid'] = $newUuid;
                    
                    // Actualizar también campos relacionados
                    if (isset($data['cita']['paciente_uuid'])) {
                        $data['cita']['paciente_uuid'] = $newUuid;
                    }
                    if (isset($data['cita']['paciente']['uuid'])) {
                        $data['cita']['paciente']['uuid'] = $newUuid;
                    }
                    
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    $jsonUpdated++;
                    
                    Log::info('✅ Archivo JSON de historia actualizado', [
                        'file' => basename($file),
                        'historia_uuid' => $data['uuid'] ?? 'unknown',
                        'old_paciente_uuid' => $oldUuid,
                        'new_paciente_uuid' => $newUuid
                    ]);
                }
            }
            
            if ($jsonUpdated > 0) {
                Log::info('✅ Archivos JSON de historias actualizados', ['count' => $jsonUpdated]);
            }
        }
        
        Log::info('✅ Actualización de UUIDs completada', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid,
            'citas_sqlite' => $citasUpdated,
            'historias_sqlite' => $historiasUpdated
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error actualizando paciente_uuid en tablas relacionadas', [
            'old_uuid' => $oldUuid,
            'new_uuid' => $newUuid,
            'error' => $e->getMessage()
        ]);
    }
}

   private function getPacienteOffline(string $uuid): ?array
{
    try {
        Log::info('🔍 Buscando paciente offline', [
            'uuid' => $uuid,
            'uuid_length' => strlen($uuid)
        ]);

        // ✅ VERIFICAR ARCHIVOS EXISTENTES PRIMERO
        $pacientePath = storage_path("app/offline/pacientes/{$uuid}.json");
        $fileExists = file_exists($pacientePath);
        
        Log::info('📁 Verificando archivo de paciente', [
            'uuid' => $uuid,
            'path' => $pacientePath,
            'file_exists' => $fileExists,
            'readable' => $fileExists ? is_readable($pacientePath) : false
        ]);

        if (!$fileExists) {
            // ✅ LISTAR ARCHIVOS DISPONIBLES PARA DEBUG
            $offlineDir = storage_path('app/offline/pacientes/');
            $availableFiles = [];
            
            if (is_dir($offlineDir)) {
                $files = scandir($offlineDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && str_ends_with($file, '.json')) {
                        $availableFiles[] = str_replace('.json', '', $file);
                    }
                }
            }
            
            Log::warning('❌ Archivo de paciente no encontrado', [
                'uuid_buscado' => $uuid,
                'directorio' => $offlineDir,
                'archivos_disponibles' => array_slice($availableFiles, 0, 10), // Solo primeros 10
                'total_archivos' => count($availableFiles)
            ]);
            
            return null;
        }

        // ✅ LEER ARCHIVO
        $content = file_get_contents($pacientePath);
        if ($content === false) {
            Log::error('❌ No se pudo leer archivo de paciente', [
                'uuid' => $uuid,
                'path' => $pacientePath
            ]);
            return null;
        }

        // ✅ DECODIFICAR JSON
        $pacienteData = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Error decodificando JSON de paciente', [
                'uuid' => $uuid,
                'json_error' => json_last_error_msg(),
                'content_preview' => substr($content, 0, 200)
            ]);
            return null;
        }

        Log::info('✅ Paciente encontrado offline', [
            'uuid' => $uuid,
            'documento' => $pacienteData['documento'] ?? 'sin-documento',
            'nombre' => $pacienteData['nombre_completo'] ?? 'sin-nombre',
            'sync_status' => $pacienteData['sync_status'] ?? 'unknown',
            'file_size' => strlen($content),
            'data_keys' => array_keys($pacienteData)
        ]);

        return $pacienteData;

    } catch (\Exception $e) {
        Log::error('💥 Error buscando paciente offline', [
            'uuid' => $uuid,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return null;
    }
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
                    isset($data['sede_id']) &&
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
        $pendingCount = 0;
        $syncedCount = 0;
        
        foreach ($pacientes as $paciente) {
            $uuid = $paciente['uuid'] ?? null;
            
            if (!$uuid) {
                Log::warning('⚠️ Paciente sin UUID, saltando', [
                    'documento' => $paciente['documento'] ?? 'sin-documento'
                ]);
                continue;
            }
            
            // ✅ VERIFICAR SI HAY CAMBIOS PENDIENTES ANTES DE SOBRESCRIBIR
            if ($this->offlineService->hasPendingChangesForPaciente($uuid)) {
                $pendingCount++;
                
                Log::warning('⚠️ Paciente tiene cambios pendientes, sincronizando primero', [
                    'uuid' => $uuid,
                    'documento' => $paciente['documento'] ?? 'sin-documento'
                ]);
                
                // Sincronizar cambios pendientes ANTES de sobrescribir
                $syncResult = $this->offlineService->syncPendingChangesForPaciente($uuid);
                
                if ($syncResult['success']) {
                    Log::info('✅ Cambios pendientes sincronizados exitosamente', [
                        'uuid' => $uuid
                    ]);
                    
                    // Si la sincronización devolvió datos actualizados, usarlos
                    if (isset($syncResult['data'])) {
                        $paciente = $syncResult['data'];
                    }
                    
                    $syncedCount++;
                } else {
                    Log::error('❌ Error sincronizando cambios pendientes, NO sobrescribiendo', [
                        'uuid' => $uuid,
                        'error' => $syncResult['error'] ?? 'Error desconocido'
                    ]);
                    
                    // NO sobrescribir si falló la sincronización
                    continue;
                }
            }
            
            // ✅ AHORA SÍ GUARDAR (sin cambios pendientes o después de sincronizar)
            $this->storePacienteOffline($paciente, false);
        }
        
        if ($pendingCount > 0) {
            Log::info('📊 Resumen de sincronización de cambios pendientes', [
                'total_pacientes' => count($pacientes),
                'con_cambios_pendientes' => $pendingCount,
                'sincronizados_exitosamente' => $syncedCount,
                'fallidos' => $pendingCount - $syncedCount
            ]);
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
