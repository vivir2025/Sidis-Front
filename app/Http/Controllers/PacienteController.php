<?php
// app/Http/Controllers/PacienteController.php (VALIDACIÓN CORREGIDA)
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{PacienteService, AuthService, ApiService, OfflineService};
use Illuminate\Support\Facades\Log;

class PacienteController extends Controller
{
    protected $pacienteService;
    protected $authService;
    protected $apiService;
    protected $offlineService;

    public function __construct(PacienteService $pacienteService, AuthService $authService, ApiService $apiService, OfflineService $offlineService )
    {
        $this->middleware('custom.auth');
        $this->pacienteService = $pacienteService;
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'documento', 'nombre', 'estado', 'sexo', 'telefono',
                'fecha_desde', 'fecha_hasta'
            ]);
            
            $page = $request->get('page', 1);
            
            Log::info('PacienteController@index', [
                'filters' => $filters,
                'page' => $page,
                'is_ajax' => $request->ajax()
            ]);

            $result = $this->pacienteService->index($filters, $page);

            if ($request->ajax()) {
                return response()->json($result);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();

            return view('pacientes.index', compact('usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }

            return back()->with('error', 'Error cargando pacientes');
        }
    }

    public function create(Request $request)
    {
        try {
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $masterData = $this->getMasterData();

            // ✅ MANEJAR NAVEGACIÓN DE RETORNO
            $returnUrl = $this->getReturnUrl($request);
            
            return view('pacientes.create', compact('usuario', 'isOffline', 'masterData', 'returnUrl'));
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@create', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error cargando formulario de creación');
        }
    }

    /**
     * ✅ STORE CORREGIDO - Acepta UUIDs y strings
     */
     public function store(Request $request)
    {
        try {
            Log::info('PacienteController@store - Datos recibidos', [
                'data' => $request->all()
            ]);

            // ✅ VALIDACIÓN (mantener igual que antes)
            $validatedData = $request->validate([
                // ... (toda la validación anterior)
                'primer_nombre' => 'required|string|max:50',
                'primer_apellido' => 'required|string|max:50',
                'documento' => 'required|string|max:20',
                'fecha_nacimiento' => 'required|date|before:today',
                'sexo' => 'required|in:M,F',
                
                // ✅ CAMPOS OPCIONALES BÁSICOS
                'segundo_nombre' => 'nullable|string|max:50',
                'segundo_apellido' => 'nullable|string|max:50',
                'direccion' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:50',
                'correo' => 'nullable|email|max:100',
                'estado_civil' => 'nullable|in:SOLTERO,CASADO,UNION_LIBRE,DIVORCIADO,VIUDO',
                'observacion' => 'nullable|string|max:1000',
                'registro' => 'nullable|string|max:50',
                'estado' => 'nullable|in:ACTIVO,INACTIVO',
                
                // ✅ CAMPOS DE RELACIONES
                'tipo_documento_id' => 'nullable|string|max:100',
                'empresa_id' => 'nullable|string|max:100',
                'regimen_id' => 'nullable|string|max:100',
                'tipo_afiliacion_id' => 'nullable|string|max:100',
                'zona_residencia_id' => 'nullable|string|max:100',
                'depto_nacimiento_id' => 'nullable|string|max:100',
                'depto_residencia_id' => 'nullable|string|max:100',
                'municipio_nacimiento_id' => 'nullable|string|max:100',
                'municipio_residencia_id' => 'nullable|string|max:100',
                'raza_id' => 'nullable|string|max:100',
                'escolaridad_id' => 'nullable|string|max:100',
                'parentesco_id' => 'nullable|string|max:100',
                'ocupacion_id' => 'nullable|string|max:100',
                'novedad_id' => 'nullable|string|max:100',
                'auxiliar_id' => 'nullable|string|max:100',
                'brigada_id' => 'nullable|string|max:100',
                
                // ✅ CAMPOS DE ACUDIENTE
                'nombre_acudiente' => 'nullable|string|max:100',
                'parentesco_acudiente' => 'nullable|string|max:50',
                'telefono_acudiente' => 'nullable|string|max:50',
                'direccion_acudiente' => 'nullable|string|max:255',
                
                // ✅ CAMPOS DE ACOMPAÑANTE
                'acompanante_nombre' => 'nullable|string|max:100',
                'acompanante_telefono' => 'nullable|string|max:50'
            ]);

            // ✅ ESTABLECER VALORES POR DEFECTO
            $validatedData['estado'] = $validatedData['estado'] ?? 'ACTIVO';
            
            // ✅ LIMPIAR CAMPOS VACÍOS
            foreach ($validatedData as $key => $value) {
                if (is_string($value) && trim($value) === '') {
                    $validatedData[$key] = null;
                }
            }
            
            $result = $this->pacienteService->store($validatedData);

            // ✅ MANEJAR RESPUESTA CON NAVEGACIÓN MEJORADA
            if ($request->ajax()) {
                $response = $result;
                if ($result['success']) {
                    $response['redirect_url'] = route('pacientes.index');
                    $response['show_success'] = true;
                }
                return response()->json($response);
            }

            if ($result['success']) {
                // ✅ LIMPIAR HISTORIAL DE NAVEGACIÓN Y REDIRIGIR
                return redirect()->route('pacientes.index')
                    ->with('success', $result['message'] ?? 'Paciente creado exitosamente')
                    ->with('patient_created', true); // Flag para manejar navegación
            }

            return back()
                ->withErrors(['error' => $result['error']])
                ->withInput();
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Errores de validación en PacienteController@store', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos inválidos',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->with('error', 'Error interno del servidor')
                ->withInput();
        }
    }
      private function getReturnUrl(Request $request): string
    {
        // Prioridad de URLs de retorno
        if ($request->has('return_url') && filter_var($request->return_url, FILTER_VALIDATE_URL)) {
            return $request->return_url;
        }
        
        if ($request->headers->has('referer')) {
            $referer = $request->headers->get('referer');
            // Solo permitir URLs internas
            if (str_contains($referer, config('app.url'))) {
                return $referer;
            }
        }
        
        // URL por defecto
        return route('pacientes.index');
    }

    public function show(string $uuid)
    {
        try {
            Log::info('PacienteController@show', ['uuid' => $uuid]);

            $result = $this->pacienteService->show($uuid);

            if (!$result['success']) {
                Log::warning('Paciente no encontrado', [
                    'uuid' => $uuid,
                    'error' => $result['error']
                ]);
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $paciente = $result['data'];

            return view('pacientes.show', compact('paciente', 'usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@show', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error interno del servidor');
        }
    }

    public function edit(string $uuid)
    {
        try {
            $result = $this->pacienteService->show($uuid);

            if (!$result['success']) {
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $paciente = $result['data'];
            $masterData = $this->getMasterData();

            return view('pacientes.edit', compact('paciente', 'usuario', 'isOffline', 'masterData'));
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@edit', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error cargando formulario de edición');
        }
    }

    /**
     * ✅ UPDATE CORREGIDO - Acepta UUIDs y strings
     */
    public function update(Request $request, string $uuid)
    {
        try {
            Log::info('PacienteController@update', [
                'uuid' => $uuid,
                'data' => $request->all()
            ]);

            // ✅ MISMA VALIDACIÓN CORREGIDA QUE EN STORE
            $validatedData = $request->validate([
                // ✅ CAMPOS OBLIGATORIOS
                'primer_nombre' => 'required|string|max:50',
                'primer_apellido' => 'required|string|max:50',
                'documento' => 'required|string|max:20',
                'fecha_nacimiento' => 'required|date|before:today',
                'sexo' => 'required|in:M,F',
                
                // ✅ CAMPOS OPCIONALES BÁSICOS
                'segundo_nombre' => 'nullable|string|max:50',
                'segundo_apellido' => 'nullable|string|max:50',
                'direccion' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:50',
                'correo' => 'nullable|email|max:100',
                'estado_civil' => 'nullable|in:SOLTERO,CASADO,UNION_LIBRE,DIVORCIADO,VIUDO',
                'observacion' => 'nullable|string|max:1000',
                'estado' => 'nullable|in:ACTIVO,INACTIVO',
                'registro' => 'nullable|string|max:50',
                
                // ✅ CAMPOS DE RELACIONES - ACEPTAN UUIDs (strings)
                'tipo_documento_id' => 'nullable|string|max:100',
                'empresa_id' => 'nullable|string|max:100',
                'regimen_id' => 'nullable|string|max:100',
                'tipo_afiliacion_id' => 'nullable|string|max:100',
                'zona_residencia_id' => 'nullable|string|max:100',
                'depto_nacimiento_id' => 'nullable|string|max:100',
                'depto_residencia_id' => 'nullable|string|max:100',
                'municipio_nacimiento_id' => 'nullable|string|max:100',
                'municipio_residencia_id' => 'nullable|string|max:100',
                'raza_id' => 'nullable|string|max:100',
                'escolaridad_id' => 'nullable|string|max:100',
                'parentesco_id' => 'nullable|string|max:100',
                'ocupacion_id' => 'nullable|string|max:100',
                'novedad_id' => 'nullable|string|max:100',
                'auxiliar_id' => 'nullable|string|max:100',
                'brigada_id' => 'nullable|string|max:100',
                
                // ✅ CAMPOS DE ACUDIENTE
                'nombre_acudiente' => 'nullable|string|max:100',
                'parentesco_acudiente' => 'nullable|string|max:50',
                'telefono_acudiente' => 'nullable|string|max:50',
                'direccion_acudiente' => 'nullable|string|max:255',
                
                // ✅ CAMPOS DE ACOMPAÑANTE
                'acompanante_nombre' => 'nullable|string|max:100',
                'acompanante_telefono' => 'nullable|string|max:50'
            ]);

            // ✅ LIMPIAR CAMPOS VACÍOS
            foreach ($validatedData as $key => $value) {
                if (is_string($value) && trim($value) === '') {
                    $validatedData[$key] = null;
                }
            }

            $result = $this->pacienteService->update($uuid, $validatedData);

            if ($request->ajax()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('pacientes.show', $uuid)
                    ->with('success', $result['message'] ?? 'Paciente actualizado exitosamente');
            }

            return back()
                ->withErrors(['error' => $result['error']])
                ->withInput();
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Errores de validación en PacienteController@update', [
                'uuid' => $uuid,
                'errors' => $e->errors()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos inválidos',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@update', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }

            return back()
                ->with('error', 'Error interno del servidor')
                ->withInput();
        }
    }

    public function destroy(string $uuid)
    {
        try {
            Log::info('PacienteController@destroy', ['uuid' => $uuid]);

            $result = $this->pacienteService->destroy($uuid);

            Log::info('PacienteController@destroy - Resultado', [
                'uuid' => $uuid,
                'result' => $result
            ]);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@destroy', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function searchByDocument(Request $request)
    {
        try {
            $request->validate([
                'documento' => 'required|string|min:3'
            ]);

            Log::info('PacienteController@searchByDocument', [
                'documento' => $request->documento
            ]);

            $result = $this->pacienteService->searchByDocument($request->documento);

            Log::info('PacienteController@searchByDocument - Resultado', [
                'documento' => $request->documento,
                'found' => $result['success']
            ]);

            return response()->json($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Documento inválido',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@searchByDocument', [
                'documento' => $request->get('documento'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function syncPendingPacientes(Request $request)
    {
        try {
            Log::info('PacienteController@syncPendingPacientes - Iniciando sincronización');

            $result = $this->pacienteService->syncPendingPacientes();

            Log::info('PacienteController@syncPendingPacientes - Resultado', [
                'result' => $result
            ]);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@syncPendingPacientes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $criteria = $request->only([
                'documento', 'nombre', 'telefono', 'correo', 'estado', 'sexo',
                'fecha_desde', 'fecha_hasta'
            ]);

            $criteria = array_filter($criteria, function($value) {
                return !empty($value);
            });

            Log::info('PacienteController@search', [
                'criteria' => $criteria
            ]);

            $result = $this->pacienteService->search($criteria);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en PacienteController@search', [
                'error' => $e->getMessage(),
                'criteria' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en búsqueda'
            ], 500);
        }
    }

    /**
     * ✅ DATOS MAESTROS CON UUIDs CONSISTENTES
     */
   private function getMasterData(): array
{
    try {
        Log::info('🔍 Obteniendo datos maestros para formulario');
        
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get('/master-data/all');
                
                Log::info('📡 Respuesta de master-data/all', [
                    'success' => $response['success'] ?? false,
                    'has_data' => isset($response['data']),
                    'data_keys' => isset($response['data']) ? array_keys($response['data']) : []
                ]);
                
                if ($response['success'] && isset($response['data'])) {
                    // ✅ SINCRONIZAR DATOS OFFLINE AUTOMÁTICAMENTE (ESTO FALTABA)
                    $syncSuccess = $this->offlineService->syncMasterDataFromApi($response['data']);
                    
                    if ($syncSuccess) {
                        Log::info('✅ Datos maestros sincronizados offline exitosamente');
                    } else {
                        Log::warning('⚠️ Error sincronizando datos maestros offline');
                    }
                    
                    Log::info('✅ Datos maestros obtenidos desde API');
                    return $response['data'];
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo datos maestros desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // ✅ USAR DATOS OFFLINE DE SQLITE/JSON
        if ($this->offlineService->hasMasterDataOffline()) {
            Log::info('📱 Usando datos maestros desde almacenamiento offline');
            return $this->offlineService->getMasterDataOffline();
        }
        
        Log::info('📱 Usando datos maestros por defecto (primera vez)');
        return $this->getDefaultMasterData();
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo datos maestros', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return $this->getDefaultMasterData();
    }
}

private function getDefaultMasterData(): array
{
    // ✅ DATOS MÍNIMOS POR DEFECTO (IGUAL QUE ANTES)
    return [
        'departamentos' => [
            ['uuid' => 'dept-cauca', 'codigo' => '19', 'nombre' => 'Cauca', 'municipios' => [
                ['uuid' => 'mun-popayan', 'codigo' => '19001', 'nombre' => 'Popayán']
            ]]
        ],
        'empresas' => [
            ['uuid' => 'emp-nueva-eps', 'nombre' => 'NUEVA EPS', 'nit' => '900156264-1', 'codigo_eapb' => 'EPS037']
        ],
        'regimenes' => [
            ['uuid' => 'reg-contributivo', 'nombre' => 'Contributivo']
        ],
        'tipos_afiliacion' => [
            ['uuid' => 'taf-cotizante', 'nombre' => 'Cotizante']
        ],
        'zonas_residenciales' => [
            ['uuid' => 'zr-urbana', 'nombre' => 'Urbana', 'abreviacion' => 'U']
        ],
        'razas' => [
            ['uuid' => 'rz-mestizo', 'nombre' => 'Mestizo']
        ],
        'escolaridades' => [
            ['uuid' => 'esc-primaria-com', 'nombre' => 'Primaria Completa']
        ],
        'tipos_parentesco' => [
            ['uuid' => 'tp-titular', 'nombre' => 'Titular']
        ],
        'tipos_documento' => [
            ['uuid' => 'td-cc', 'abreviacion' => 'CC', 'nombre' => 'Cédula de Ciudadanía']
        ],
        'ocupaciones' => [
            ['uuid' => 'oc-empleado', 'codigo' => '5000', 'nombre' => 'Empleado']
        ],
        'novedades' => [
            ['uuid' => 'nov-ingreso', 'tipo_novedad' => 'Ingreso']
        ],
        'auxiliares' => [
            ['uuid' => 'aux-general', 'nombre' => 'Auxiliar General']
        ],
        'brigadas' => [
            ['uuid' => 'bri-general', 'nombre' => 'Brigada General']
        ],
        
        'estados_civiles' => [
            'SOLTERO' => 'Soltero(a)',
            'CASADO' => 'Casado(a)',
            'UNION_LIBRE' => 'Unión Libre',
            'DIVORCIADO' => 'Divorciado(a)',
            'VIUDO' => 'Viudo(a)'
        ],
        'sexos' => [
            'M' => 'Masculino',
            'F' => 'Femenino'
        ],
        'estados' => [
            'ACTIVO' => 'Activo',
            'INACTIVO' => 'Inactivo'
        ]
    ];
}

public function testSyncManual(Request $request)
{
    try {
        Log::info('🧪 Test manual de sincronización iniciado');

        $user = $this->authService->usuario();
        $sedeId = $user['sede_id'];
        $isOnline = $this->apiService->isOnline();
        
        // Obtener pacientes pendientes usando el método del servicio
        $result = $this->pacienteService->getTestSyncData($sedeId);

        $testResult = [
            'success' => true,
            'connection' => $isOnline,
            'pending_count' => $result['pending_count'],
            'total_pacientes' => $result['total_count'],
            'message' => 'Test completado exitosamente',
            'details' => [
                'sede_id' => $sedeId,
                'user_login' => $user['login'],
                'api_online' => $isOnline,
                'timestamp' => now()->toISOString(),
                'pending_details' => $result['pending_details']
            ]
        ];

        if (!$isOnline) {
            $testResult['message'] = 'Sin conexión - No se puede sincronizar';
        } elseif ($result['pending_count'] === 0) {
            $testResult['message'] = 'No hay pacientes pendientes para sincronizar';
        } else {
            $testResult['message'] = $result['pending_count'] . ' pacientes listos para sincronizar';
        }

        Log::info('🧪 Test completado', $testResult);

        return response()->json($testResult);

    } catch (\Exception $e) {
        Log::error('❌ Error en test manual', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'connection' => false,
            'pending_count' => 0,
            'error' => 'Error interno: ' . $e->getMessage(),
            'message' => 'Test falló'
        ], 500);
    }
}

    public function stats(Request $request)
    {
        try {
            $stats = [
                'total_pacientes' => 0,
                'pacientes_activos' => 0,
                'pacientes_inactivos' => 0,
                'registros_hoy' => 0,
                'registros_mes' => 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estadísticas'
            ], 500);
        }
    }
}

