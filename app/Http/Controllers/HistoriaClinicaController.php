<?php
// app/Http/Controllers/HistoriaClinicaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService, OfflineService, PacienteService, CitaService};
use Illuminate\Support\Facades\Log;

class HistoriaClinicaController extends Controller
{
    protected $authService;
    protected $apiService;
    protected $offlineService;
    protected $pacienteService;
    protected $citaService;

    public function __construct(
        AuthService $authService,
        ApiService $apiService,
        OfflineService $offlineService,
        PacienteService $pacienteService,
        CitaService $citaService
    ) {
        $this->middleware('custom.auth');
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
        $this->pacienteService = $pacienteService;
        $this->citaService = $citaService;
    }

   public function create(Request $request, string $citaUuid)
{
    try {
        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();

        Log::info('🩺 Creando historia clínica', [
            'cita_uuid' => $citaUuid,
            'usuario' => $usuario['nombre_completo']
        ]);

        // ✅ OBTENER DATOS DE LA CITA
        $citaResult = $this->citaService->show($citaUuid);
        
        if (!$citaResult['success']) {
            return back()->with('error', 'Cita no encontrada');
        }

        $cita = $citaResult['data'];

        // ✅ VERIFICAR QUE LA CITA NO TENGA HISTORIA CLÍNICA
        if (isset($cita['historia_clinica_uuid'])) {
            return redirect()->route('historia-clinica.show', $cita['historia_clinica_uuid'])
                ->with('info', 'Esta cita ya tiene una historia clínica asociada');
        }

        // ✅ OBTENER ESPECIALIDAD Y TIPO DE CONSULTA
        $especialidad = $this->obtenerEspecialidadMedico($cita);
        $pacienteUuid = $cita['paciente_uuid'] ?? $cita['paciente']['uuid'] ?? null;
        
        if (!$pacienteUuid) {
            return back()->with('error', 'No se pudo obtener información del paciente');
        }

        $tipoConsulta = $this->determinarTipoConsultaOffline($pacienteUuid, $especialidad);

        // ✅ OBTENER HISTORIA PREVIA SOLO PARA MEDICINA GENERAL Y CONTROL
        $historiaPrevia = null;
        if ($tipoConsulta === 'CONTROL' && $especialidad === 'MEDICINA GENERAL') {
            $historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);
            
            Log::info('🔄 Historia previa cargada para formulario', [
                'tiene_historia' => !empty($historiaPrevia),
                'especialidad' => $especialidad,
                'tipo_consulta' => $tipoConsulta
            ]);
        }

        // ✅ OBTENER DATOS MAESTROS PARA SELECTS
        $masterData = $this->getMasterDataForForm();

        return view('historia-clinica.create', compact(
            'cita',
            'usuario',
            'isOffline',
            'masterData',
            'historiaPrevia', // ✅ AGREGAR ESTA VARIABLE
            'especialidad',
            'tipoConsulta'
        ));

    } catch (\Exception $e) {
        Log::error('❌ Error creando historia clínica', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid
        ]);

        return back()->with('error', 'Error cargando formulario de historia clínica');
    }
}

/**
 * ✅ MOSTRAR UNA HISTORIA CLÍNICA ESPECÍFICA (VER HISTORIA YA GUARDADA)
 */
public function show(string $uuid)
{
    try {
        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();

        Log::info('👁️ Mostrando historia clínica guardada', [
            'historia_uuid' => $uuid,
            'usuario' => $usuario['nombre_completo']
        ]);

        // ✅ 1. OBTENER DATOS DE LA HISTORIA DESDE EL BACKEND (API)
        $historia = null;
        
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/historias-clinicas/{$uuid}");
                
                if ($response['success']) {
                    $historia = $response['data'];
                    
                    Log::info('✅ Historia obtenida desde API', [
                        'historia_uuid' => $uuid,
                        'especialidad' => $historia['especialidad'] ?? 'N/A',
                        'tipo_consulta' => $historia['tipo_consulta'] ?? 'N/A'
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo historia desde API, intentando offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ 2. FALLBACK OFFLINE SI NO SE OBTUVO ONLINE
        if (!$historia) {
            $historia = $this->obtenerHistoriaOffline($uuid);
            
            if (!$historia) {
                Log::error('❌ Historia no encontrada ni online ni offline', [
                    'historia_uuid' => $uuid
                ]);
                
                return back()->with('error', 'Historia clínica no encontrada');
            }
            
            Log::info('✅ Historia obtenida desde offline', [
                'historia_uuid' => $uuid
            ]);
        }

        // ✅ 3. EXTRAER ESPECIALIDAD Y TIPO DE CONSULTA
        $especialidad = $historia['especialidad'] ?? 
                       $historia['cita']['agenda']['proceso']['nombre'] ?? 
                       $historia['cita']['proceso']['nombre'] ?? 
                       'MEDICINA GENERAL';
        
        $tipoConsulta = $historia['tipo_consulta'] ?? 'PRIMERA VEZ';
        
        Log::info('✅ Datos de historia extraídos', [
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'paciente' => $historia['paciente']['nombre_completo'] ?? 'N/A'
        ]);

        // ✅ 4. DETERMINAR VISTA SEGÚN ESPECIALIDAD Y TIPO DE CONSULTA
        return $this->renderizarVistaShow($especialidad, $tipoConsulta, $historia, $usuario, $isOffline);

    } catch (\Exception $e) {
        Log::error('❌ Error mostrando historia clínica', [
            'error' => $e->getMessage(),
            'historia_uuid' => $uuid,
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'trace' => $e->getTraceAsString()
        ]);

        return back()->with('error', 'Error al cargar la historia clínica: ' . $e->getMessage());
    }
}

/**
 * ✅ RENDERIZAR VISTA DE SHOW SEGÚN ESPECIALIDAD Y TIPO
 */
/**
 * ✅ RENDERIZAR VISTA SEGÚN ESPECIALIDAD Y TIPO DE CONSULTA
 */
private function renderizarVistaShow(string $especialidad, string $tipoConsulta, array $historia, array $usuario, bool $isOffline): \Illuminate\View\View
{
    // ✅ NORMALIZAR ESPECIALIDAD Y TIPO DE CONSULTA
    $especialidadNormalizada = $this->normalizarEspecialidad($especialidad);
    $tipoConsultaNormalizado = strtolower(str_replace(' ', '-', $tipoConsulta));
    
    Log::info('🎨 Renderizando vista show', [
        'especialidad_original' => $especialidad,
        'especialidad_normalizada' => $especialidadNormalizada,
        'tipo_consulta_original' => $tipoConsulta,
        'tipo_consulta_normalizado' => $tipoConsultaNormalizado
    ]);

    // ✅ CONSTRUIR RUTA DE LA VISTA
    // Ruta: resources/views/historia-clinica/historial-historias/{especialidad}/{tipo-consulta}.blade.php
    $vistaEspecifica = "historia-clinica.historial-historias.{$especialidadNormalizada}.{$tipoConsultaNormalizado}";
    $vistaGenerica = "historia-clinica.historial-historias.generica";
    
    // ✅ VERIFICAR SI EXISTE LA VISTA ESPECÍFICA
    if (view()->exists($vistaEspecifica)) {
        Log::info("✅ Vista específica encontrada: {$vistaEspecifica}");
        
        return view($vistaEspecifica, [
            'historia' => $historia,
            'usuario' => $usuario,
            'isOffline' => $isOffline,
            'especialidad' => $especialidad,
            'tipoConsulta' => $tipoConsulta
        ]);
    }
    
    // ✅ FALLBACK A VISTA GENÉRICA
    Log::warning("⚠️ Vista específica no encontrada: {$vistaEspecifica}, usando vista genérica");
    
    if (view()->exists($vistaGenerica)) {
        return view($vistaGenerica, [
            'historia' => $historia,
            'usuario' => $usuario,
            'isOffline' => $isOffline,
            'especialidad' => $especialidad,
            'tipoConsulta' => $tipoConsulta
        ]);
    }
    
    // ✅ ERROR SI NO EXISTE NINGUNA VISTA
    Log::error("❌ No se encontró ninguna vista para mostrar la historia", [
        'vista_especifica' => $vistaEspecifica,
        'vista_generica' => $vistaGenerica
    ]);
    
    abort(500, "No se encontró una vista para mostrar esta historia clínica");
}

/**
 * ✅ NORMALIZAR NOMBRE DE ESPECIALIDAD PARA RUTAS DE VISTAS
 */
private function normalizarEspecialidad(string $especialidad): string
{
    // Mapeo de especialidades a nombres de carpetas
    $mapeo = [
        'PSICOLOGIA' => 'psicologia',
        'PSICOLOGÍA' => 'psicologia',
        'MEDICINA GENERAL' => 'medicina-general',
        'NUTRICIONISTA' => 'nutricionista',
        'NUTRICIÓN' => 'nutricionista',
        'ENFERMERIA' => 'enfermeria',
        'ENFERMERÍA' => 'enfermeria',
        'ODONTOLOGIA' => 'odontologia',
        'ODONTOLOGÍA' => 'odontologia',
    ];
    
    $especialidadUpper = strtoupper(trim($especialidad));
    
    if (isset($mapeo[$especialidadUpper])) {
        return $mapeo[$especialidadUpper];
    }
    
    // Si no está en el mapeo, normalizar manualmente
    return strtolower(str_replace([' ', 'Í', 'Ó', 'Á', 'É', 'Ú'], ['-', 'i', 'o', 'a', 'e', 'u'], $especialidad));
}


/**
 * ✅ NORMALIZAR TEXTO (QUITAR TILDES)
 */
private function normalizarTexto(string $texto): string
{
    $texto = strtoupper($texto);
    
    $tildes = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u'
    ];
    
    return strtr($texto, $tildes);
}

/**
 * ✅ OBTENER HISTORIA OFFLINE (FALLBACK)
 */
private function obtenerHistoriaOffline(string $uuid): ?array
{
    try {
        Log::info('🔍 Buscando historia offline', [
            'historia_uuid' => $uuid
        ]);

        // ✅ 1. BUSCAR EN JSON
        $historiasPath = storage_path('app/offline/historias-clinicas');
        $filePath = "{$historiasPath}/{$uuid}.json";
        
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                Log::info('✅ Historia encontrada en JSON offline', [
                    'historia_uuid' => $uuid
                ]);
                return $data;
            }
        }

        // ✅ 2. BUSCAR EN SQLITE (SI EXISTE EL MÉTODO)
        try {
            $historiaOffline = $this->offlineService->getHistoriaClinicaOffline($uuid);
            
            if ($historiaOffline) {
                Log::info('✅ Historia encontrada en SQLite offline', [
                    'historia_uuid' => $uuid
                ]);
                return $historiaOffline;
            }
        } catch (\Exception $sqliteError) {
            Log::debug('ℹ️ No se pudo buscar en SQLite (normal si no existe)', [
                'error' => $sqliteError->getMessage()
            ]);
        }

        Log::warning('⚠️ Historia no encontrada offline', [
            'historia_uuid' => $uuid
        ]);

        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historia offline', [
            'error' => $e->getMessage(),
            'uuid' => $uuid
        ]);
        
        return null;
    }
}


/**
 * ✅ OBTENER ÚLTIMA HISTORIA FORMATEADA PARA EL FORMULARIO
 */
private function obtenerUltimaHistoriaParaFormulario(string $pacienteUuid, string $especialidad): ?array
{
    try {
        Log::info('🔍 Obteniendo última historia para formulario', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        // ✅ INTENTAR OBTENER DESDE API PRIMERO
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get("/pacientes/{$pacienteUuid}/ultima-historia", [
                'especialidad' => $especialidad
            ]);
            
            if ($response['success'] && !empty($response['data'])) {
                Log::info('✅ Historia previa obtenida desde API');
                return $response['data'];
            }
        }

        // ✅ FALLBACK A OFFLINE
        $historiaOffline = $this->obtenerUltimaHistoriaOffline($pacienteUuid, $especialidad);
        
        if ($historiaOffline) {
            Log::info('✅ Historia previa obtenida desde offline');
            return $this->formatearHistoriaParaFormulario($historiaOffline);
        }

        Log::info('ℹ️ No se encontró historia previa');
        return null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historia previa para formulario', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return null;
    }
}

private function formatearHistoriaParaFormulario(array $historia): array
{
    try {
        return [
            // ✅ TEST DE MORISKY
            'test_morisky_olvida_tomar_medicamentos' => $historia['olvida_tomar_medicamentos'] ?? 'NO',
            'test_morisky_toma_medicamentos_hora_indicada' => $historia['toma_medicamentos_hora_indicada'] ?? 'NO',
            'test_morisky_cuando_esta_bien_deja_tomar_medicamentos' => $historia['cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO',
            'test_morisky_siente_mal_deja_tomarlos' => $historia['siente_mal_deja_tomarlos'] ?? 'NO',
            'test_morisky_valoracio_psicologia' => $historia['valoracion_psicologia'] ?? 'NO',
            'adherente' => $historia['adherente'] ?? 'NO',

            // ✅ ANTECEDENTES PERSONALES
            'hipertension_arterial_personal' => $historia['hipertension_arterial_personal'] ?? 'NO',
            'obs_hipertension_arterial_personal' => $historia['obs_personal_hipertension_arterial'] ?? '',
            'diabetes_mellitus_personal' => $historia['diabetes_mellitus_personal'] ?? 'NO',
            'obs_diabetes_mellitus_personal' => $historia['obs_personal_mellitus'] ?? '',

            // ✅ CLASIFICACIONES
            'clasificacion_estado_metabolico' => $historia['clasificacion_estado_metabolico'] ?? '',
            'clasificacion_hta' => $historia['clasificacion_hta'] ?? '',
            'clasificacion_dm' => $historia['clasificacion_dm'] ?? '',
            'clasificacion_rcv' => $historia['clasificacion_rcv'] ?? '',
            'clasificacion_erc_estado' => $historia['clasificacion_erc_estado'] ?? '',
            'clasificacion_erc_categoria_ambulatoria_persistente' => $historia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '',

            // ✅ TASAS DE FILTRACIÓN
            'tasa_filtracion_glomerular_ckd_epi' => $historia['tasa_filtracion_glomerular_ckd_epi'] ?? '',
            'tasa_filtracion_glomerular_gockcroft_gault' => $historia['tasa_filtracion_glomerular_gockcroft_gault'] ?? '',

            // ✅ TALLA
            'talla' => $historia['talla'] ?? '',

            // ✅ MEDICAMENTOS - FORMATEAR PARA EL FRONTEND
            'medicamentos' => $this->formatearMedicamentosParaFormulario($historia['medicamentos'] ?? []),

            // ✅ REMISIONES - FORMATEAR PARA EL FRONTEND
            'remisiones' => $this->formatearRemisionesParaFormulario($historia['remisiones'] ?? []),

            // ✅ DIAGNÓSTICOS - FORMATEAR PARA EL FRONTEND
            'diagnosticos' => $this->formatearDiagnosticosParaFormulario($historia['diagnosticos'] ?? []),

            // ✅ CUPS - FORMATEAR PARA EL FRONTEND
            'cups' => $this->formatearCupsParaFormulario($historia['cups'] ?? []),

            // ✅✅✅ NUEVOS CAMPOS DE EDUCACIÓN ✅✅✅
            'alimentacion' => $historia['alimentacion'] ?? 'NO',
            'disminucion_consumo_sal_azucar' => $historia['disminucion_consumo_sal_azucar'] ?? 'NO',
            'fomento_actividad_fisica' => $historia['fomento_actividad_fisica'] ?? 'NO',
            'importancia_adherencia_tratamiento' => $historia['importancia_adherencia_tratamiento'] ?? 'NO',
            'consumo_frutas_verduras' => $historia['consumo_frutas_verduras'] ?? 'NO',
            'manejo_estres' => $historia['manejo_estres'] ?? 'NO',
            'disminucion_consumo_cigarrillo' => $historia['disminucion_consumo_cigarrillo'] ?? 'NO',
            'disminucion_peso' => $historia['disminucion_peso'] ?? 'NO',
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error formateando historia para formulario', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}


/**
 * ✅ FORMATEAR MEDICAMENTOS PARA EL FORMULARIO
 */
private function formatearMedicamentosParaFormulario(array $medicamentos): array
{
    return array_map(function($medicamento) {
        return [
            'medicamento_id' => $medicamento['medicamento_id'] ?? $medicamento['id'],
            'cantidad' => $medicamento['cantidad'] ?? '',
            'dosis' => $medicamento['dosis'] ?? '',
            'medicamento' => [
                'uuid' => $medicamento['medicamento']['uuid'] ?? $medicamento['medicamento']['id'],
                'nombre' => $medicamento['medicamento']['nombre'] ?? '',
                'principio_activo' => $medicamento['medicamento']['principio_activo'] ?? ''
            ]
        ];
    }, $medicamentos);
}

/**
 * ✅ FORMATEAR REMISIONES PARA EL FORMULARIO
 */
private function formatearRemisionesParaFormulario(array $remisiones): array
{
    return array_map(function($remision) {
        return [
            'remision_id' => $remision['remision_id'] ?? $remision['id'],
            'observacion' => $remision['observacion'] ?? '',
            'remision' => [
                'uuid' => $remision['remision']['uuid'] ?? $remision['remision']['id'],
                'nombre' => $remision['remision']['nombre'] ?? '',
                'tipo' => $remision['remision']['tipo'] ?? ''
            ]
        ];
    }, $remisiones);
}

/**
 * ✅ FORMATEAR DIAGNÓSTICOS PARA EL FORMULARIO
 */
private function formatearDiagnosticosParaFormulario(array $diagnosticos): array
{
    return array_map(function($diagnostico) {
        return [
            'diagnostico_id' => $diagnostico['diagnostico_id'] ?? $diagnostico['id'],
            'tipo' => $diagnostico['tipo'] ?? 'PRINCIPAL',
            'tipo_diagnostico' => $diagnostico['tipo_diagnostico'] ?? '',
            'diagnostico' => [
                'uuid' => $diagnostico['diagnostico']['uuid'] ?? $diagnostico['diagnostico']['id'],
                'codigo' => $diagnostico['diagnostico']['codigo'] ?? '',
                'nombre' => $diagnostico['diagnostico']['nombre'] ?? ''
            ]
        ];
    }, $diagnosticos);
}

/**
 * ✅ FORMATEAR CUPS PARA EL FORMULARIO
 */
private function formatearCupsParaFormulario(array $cups): array
{
    return array_map(function($cup) {
        return [
            'cups_id' => $cup['cups_id'] ?? $cup['id'],
            'observacion' => $cup['observacion'] ?? '',
            'cups' => [
                'uuid' => $cup['cups']['uuid'] ?? $cup['cups']['id'],
                'codigo' => $cup['cups']['codigo'] ?? '',
                'nombre' => $cup['cups']['nombre'] ?? ''
            ]
        ];
    }, $cups);
}

 public function store(Request $request)
{
    try {
        $usuario = $this->authService->usuario();
        
        Log::info('💾 Guardando historia clínica', [
            'cita_uuid' => $request->cita_uuid,
            'usuario' => $usuario['nombre_completo']
        ]);

        // ✅ VALIDAR DATOS BÁSICOS
        $validatedData = $this->validateHistoriaClinica($request);

        // ✅ PREPARAR DATOS PARA ENVÍO
        $historiaData = $this->prepareHistoriaData($validatedData, $usuario);

        // ✅ INTENTAR GUARDAR ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            Log::info('🌐 Intentando guardar online...');
            
            try {
                $result = $this->saveOnline($historiaData);
                
                if ($result['success']) {
                    Log::info('✅ Historia guardada online exitosamente', [
                        'uuid' => $result['data']['uuid'] ?? 'N/A'
                    ]);
                    
                    // ✅ GUARDAR OFFLINE COMO BACKUP
                    $this->saveOffline($historiaData, false);
                    
                    // ✅ CAMBIAR ESTADO DE LA CITA A ATENDIDA
                    $this->marcarCitaComoAtendida($request->cita_uuid);
                    
                    // ✅✅✅ ELIMINADO: Ya no se crea complementaria aquí
                    // El BACK lo maneja automáticamente en store()
                    
                    // ✅ RETORNAR RESPUESTA EXITOSA
                    return response()->json([
                        'success' => true,
                        'message' => 'Historia clínica guardada exitosamente. Cita marcada como atendida.',
                        'redirect_url' => route('cronograma.index'),
                        'historia_uuid' => $result['data']['uuid'] ?? null
                    ], 200);
                }
                
                Log::warning('⚠️ Fallo guardado online, intentando offline...');
                
            } catch (\Exception $e) {
                Log::error('❌ Error en guardado online:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::info('📴 Sin conexión, guardando offline directamente');
        }

        // ✅ GUARDAR OFFLINE
        Log::info('💾 Guardando offline...');
        $result = $this->saveOffline($historiaData, true);
        
        if (!$result['success']) {
            throw new \Exception('Error guardando offline: ' . ($result['error'] ?? 'Error desconocido'));
        }
        
        Log::info('✅ Historia guardada offline exitosamente');
        
        // ✅ CAMBIAR ESTADO OFFLINE TAMBIÉN
        $this->marcarCitaComoAtendida($request->cita_uuid);
        
        // ✅ RETORNAR RESPUESTA EXITOSA OFFLINE
        return response()->json([
            'success' => true,
            'message' => 'Historia clínica guardada offline. Cita marcada como atendida (se sincronizará cuando vuelva la conexión)',
            'redirect_url' => route('cronograma.index'),
            'offline' => true
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('❌ Error de validación:', [
            'errors' => $e->errors()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('❌ Error guardando historia clínica', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Error guardando historia clínica: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * ✅✅✅ NUEVO MÉTODO: MARCAR CITA COMO ATENDIDA ✅✅✅
 */
private function marcarCitaComoAtendida(string $citaUuid): void
{
    try {
        Log::info('🏁 Marcando cita como ATENDIDA', [
            'cita_uuid' => $citaUuid
        ]);

        // ✅ INTENTAR CAMBIAR ESTADO ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->post("/citas/{$citaUuid}/estado", [
                    'estado' => 'ATENDIDA'
                ]);

                if ($response['success']) {
                    Log::info('✅ Cita marcada como ATENDIDA online', [
                        'cita_uuid' => $citaUuid
                    ]);
                    
                    // ✅ ACTUALIZAR TAMBIÉN OFFLINE PARA SINCRONIZACIÓN
                    $this->actualizarCitaOffline($citaUuid, 'ATENDIDA');
                    return;
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error marcando cita online, usando offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ FALLBACK: MARCAR OFFLINE
        $this->actualizarCitaOffline($citaUuid, 'ATENDIDA');
        
        Log::info('✅ Cita marcada como ATENDIDA offline', [
            'cita_uuid' => $citaUuid
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error marcando cita como atendida', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid
        ]);
        
        // ✅ NO LANZAR EXCEPCIÓN PARA NO INTERRUMPIR EL GUARDADO DE LA HISTORIA
    }
}
/**
 * ✅ ACTUALIZAR CITA OFFLINE
 */
private function actualizarCitaOffline(string $citaUuid, string $nuevoEstado): void
{
    try {
        // ✅ OBTENER CITA ACTUAL
        $citaActual = $this->offlineService->getCitaOffline($citaUuid);
        
        if (!$citaActual) {
            Log::warning('⚠️ Cita no encontrada offline para actualizar', [
                'cita_uuid' => $citaUuid
            ]);
            return;
        }

        // ✅ ACTUALIZAR ESTADO
        $citaActual['estado'] = $nuevoEstado;
        $citaActual['updated_at'] = now()->toISOString();
        
        // ✅ MARCAR PARA SINCRONIZACIÓN SI ESTABA SINCRONIZADA
        if (isset($citaActual['sync_status']) && $citaActual['sync_status'] === 'synced') {
            $citaActual['sync_status'] = 'pending';
        }

        // ✅ GUARDAR CAMBIOS OFFLINE
        $this->offlineService->storeCitaOffline($citaActual, true);
        
        Log::info('✅ Cita actualizada offline', [
            'cita_uuid' => $citaUuid,
            'nuevo_estado' => $nuevoEstado
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error actualizando cita offline', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid
        ]);
    }
}
/**
 * ✅ OBTENER DATOS DE LA CITA PARA EXTRAER PACIENTE_ID
 */
private function getCitaData(string $citaUuid): ?array
{
    try {
        Log::info('🔍 Obteniendo datos de cita para historia clínica', [
            'cita_uuid' => $citaUuid
        ]);
        
        // ✅ USAR EL SERVICIO DE CITAS QUE YA TIENES
        $citaResult = $this->citaService->show($citaUuid);
        
        if ($citaResult['success']) {
            $citaData = $citaResult['data'];
            
            Log::info('✅ Datos de cita obtenidos correctamente', [
                'cita_uuid' => $citaUuid,
                'tiene_paciente_id' => isset($citaData['paciente_id']),
                'tiene_paciente_uuid' => isset($citaData['paciente_uuid']),
                'tiene_paciente_objeto' => isset($citaData['paciente']['id'])
            ]);
            
            return $citaData;
        }
        
        Log::warning('⚠️ No se pudo obtener datos de la cita', [
            'cita_uuid' => $citaUuid,
            'error' => $citaResult['error'] ?? 'Error desconocido'
        ]);
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo datos de la cita', [
            'cita_uuid' => $citaUuid,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}

 /**
 * ✅ VALIDAR DATOS DE HISTORIA CLÍNICA - CORREGIDO
 */
private function validateHistoriaClinica(Request $request): array
{
    return $request->validate([
        // ✅ DATOS BÁSICOS OBLIGATORIOS
        'cita_uuid' => 'required|string',
        'motivo' => 'nullable|string|max:1000',
        'enfermedad_actual' => 'nullable|string|max:2000',
        
        // ✅ DIAGNÓSTICO PRINCIPAL OBLIGATORIO
        'idDiagnostico' => 'required|string|uuid',
        'tipo_diagnostico' => 'required|string',
        
        // ✅ ACUDIENTE
        'acompanante' => 'nullable|string|max:200',
        'parentesco' => 'nullable|string|max:100',
        'telefono_acudiente' => 'nullable|string|max:20',
        
        // ✅ DISCAPACIDADES
        'discapacidad_fisica' => 'nullable|in:SI,NO',
        'discapacidad_visual' => 'nullable|in:SI,NO',
        'discapacidad_mental' => 'nullable|in:SI,NO',
        'discapacidad_auditiva' => 'nullable|in:SI,NO',
        'discapacidad_intelectual' => 'nullable|in:SI,NO',
        
        // ✅ DROGODEPENDENCIA
        'drogodependiente' => 'nullable|in:SI,NO',
        'drogodependiente_cual' => 'nullable|string|max:200',
        
        // ✅ MEDIDAS ANTROPOMÉTRICAS
        'peso' => 'nullable|numeric|min:0|max:300',
        'talla' => 'nullable|numeric|min:0|max:250',
        'perimetro_abdominal' => 'nullable|numeric|min:0|max:200',
        'obs_perimetro_abdominal' => 'nullable|string|max:500',
        
        // ✅ ANTECEDENTES FAMILIARES
        'hipertension_arterial' => 'nullable|in:SI,NO',
        'parentesco_hipertension' => 'nullable|string|max:300',
        'diabetes_mellitus' => 'nullable|in:SI,NO',
        'parentesco_diabetes_mellitus' => 'nullable|string|max:300',
        'artritis' => 'nullable|in:SI,NO',
        'parentesco_artritis' => 'nullable|string|max:300',
        'enfermedad_cardiovascular' => 'nullable|in:SI,NO',
        'parentesco_enfermedad_cardiovascular' => 'nullable|string|max:300',
        'antecedentes_metabolico' => 'nullable|in:SI,NO',
        'parentesco_antecedentes_metabolico' => 'nullable|string|max:300',
        'cancer' => 'nullable|in:SI,NO',
        'parentesco_cancer' => 'nullable|string|max:300',
        'lucemia' => 'nullable|in:SI,NO',
        'parentesco_lucemia' => 'nullable|string|max:300',
        'vih' => 'nullable|in:SI,NO',
        'parentesco_vih' => 'nullable|string|max:300',
        'otro' => 'nullable|in:SI,NO',
        'parentesco_otro' => 'nullable|string|max:300',
        
        // ✅ ANTECEDENTES PERSONALES
        'enfermedad_cardiovascular_personal' => 'nullable|in:SI,NO',
        'obs_enfermedad_cardiovascular_personal' => 'nullable|string|max:500',
        'arterial_periferica_personal' => 'nullable|in:SI,NO',
        'obs_arterial_periferica_personal' => 'nullable|string|max:500',
        'carotidea_personal' => 'nullable|in:SI,NO',
        'obs_carotidea_personal' => 'nullable|string|max:500',
        'aneurisma_aorta_peronal' => 'nullable|in:SI,NO',
        'obs_aneurisma_aorta_peronal' => 'nullable|string|max:500',
        'coronario_personal' => 'nullable|in:SI,NO',
        'obs_coronario_personal' => 'nullable|string|max:500',
        'artritis_personal' => 'nullable|in:SI,NO',
        'obs_artritis_personal' => 'nullable|string|max:500',
        'iam_personal' => 'nullable|in:SI,NO',
        'obs_iam_personal' => 'nullable|string|max:500',
        'revascul_coronaria_personal' => 'nullable|in:SI,NO',
        'obs_revascul_coronaria_personal' => 'nullable|string|max:500',
        'insuficiencia_cardiaca_personal' => 'nullable|in:SI,NO',
        'obs_insuficiencia_cardiaca_personal' => 'nullable|string|max:500',
        'amputacion_pie_diabetico_personal' => 'nullable|in:SI,NO',
        'obs_amputacion_pie_diabetico_personal' => 'nullable|string|max:500',
        'enfermedad_pulmonar_personal' => 'nullable|in:SI,NO',
        'obs_enfermedad_pulmonar_personal' => 'nullable|string|max:500',
        'victima_maltrato_personal' => 'nullable|in:SI,NO',
        'obs_victima_maltrato_personal' => 'nullable|string|max:500',
        'antecedentes_quirurgicos_personal' => 'nullable|in:SI,NO',
        'obs_antecedentes_quirurgicos_personal' => 'nullable|string|max:500',
        'acontosis_personal' => 'nullable|in:SI,NO',
        'obs_acontosis_personal' => 'nullable|string|max:500',
        'otro_personal' => 'nullable|in:SI,NO',
        'obs_otro_personal' => 'nullable|string|max:500',
        'insulina_requiriente' => 'nullable|in:SI,NO',
        
        // ✅ TEST MORISKY
        'test_morisky_olvida_tomar_medicamentos' => 'nullable|in:SI,NO',
        'test_morisky_toma_medicamentos_hora_indicada' => 'nullable|in:SI,NO',
        'test_morisky_cuando_esta_bien_deja_tomar_medicamentos' => 'nullable|in:SI,NO',
        'test_morisky_siente_mal_deja_tomarlos' => 'nullable|in:SI,NO',
        'test_morisky_valoracio_psicologia' => 'nullable|in:SI,NO',
        'adherente' => 'nullable|in:SI,NO',
        
        // ✅ OTROS TRATAMIENTOS
        'recibe_tratamiento_alternativo' => 'nullable|in:SI,NO',
        'recibe_tratamiento_plantas_medicinales' => 'nullable|in:SI,NO',
        'recibe_ritual_medicina_tradicional' => 'nullable|in:SI,NO',
        
        // ✅ REVISIÓN POR SISTEMAS
        'general' => 'nullable|string|max:1000',
        'cabeza' => 'nullable|string|max:1000',
        'respiratorio' => 'nullable|string|max:1000',
        'cardiovascular' => 'nullable|string|max:1000',
        'gastrointestinal' => 'nullable|string|max:1000',
        'osteoatromuscular' => 'nullable|string|max:1000',
        'snc' => 'nullable|string|max:1000',
        
        // ✅ EXAMEN FÍSICO - SIGNOS VITALES
        'ef_pa_sistolica_sentado_pie' => 'nullable|numeric|min:50|max:300',
        'ef_pa_distolica_sentado_pie' => 'nullable|numeric|min:30|max:200',
        'ef_frecuencia_fisica' => 'nullable|numeric|min:30|max:200',
        'ef_frecuencia_respiratoria' => 'nullable|numeric|min:8|max:50',
        
        // ✅ EXAMEN FÍSICO - SISTEMAS
        'ef_cabeza' => 'nullable|string|max:500',
        'ef_obs_cabeza' => 'nullable|string|max:500',
        'ef_agudeza_visual' => 'nullable|string|max:500',
        'ef_obs_agudeza_visual' => 'nullable|string|max:500',
        'ef_cuello' => 'nullable|string|max:500',
        'ef_obs_cuello' => 'nullable|string|max:500',
        'ef_torax' => 'nullable|string|max:500',
        'ef_obs_torax' => 'nullable|string|max:500',
        'ef_mamas' => 'nullable|string|max:500',
        'ef_obs_mamas' => 'nullable|string|max:500',
        'ef_abdomen' => 'nullable|string|max:500',
        'ef_obs_abdomen' => 'nullable|string|max:500',
        'ef_genito_urinario' => 'nullable|string|max:500',
        'ef_obs_genito_urinario' => 'nullable|string|max:500',
        'ef_extremidades' => 'nullable|string|max:500',
        'ef_obs_extremidades' => 'nullable|string|max:500',
        'ef_piel_anexos_pulsos' => 'nullable|string|max:500',
        'ef_obs_piel_anexos_pulsos' => 'nullable|string|max:500',
        'ef_sistema_nervioso' => 'nullable|string|max:500',
        'ef_obs_sistema_nervioso' => 'nullable|string|max:500',
        'ef_orientacion' => 'nullable|string|max:500',
        'ef_obs_orientacion' => 'nullable|string|max:500',
        'ef_hallazco_positivo_examen_fisico' => 'nullable|string|max:1000',
        
        // ✅ FACTORES DE RIESGO
        'numero_frutas_diarias' => 'nullable|integer|min:0|max:20',
        'elevado_consumo_grasa_saturada' => 'nullable|in:SI,NO',
        'adiciona_sal_despues_preparar_alimentos' => 'nullable|in:SI,NO',
        'dislipidemia' => 'nullable|in:SI,NO',
        'condicion_clinica_asociada' => 'nullable|in:SI,NO',
        'lesion_organo_blanco' => 'nullable|in:SI,NO',
        'descripcion_lesion_organo_blanco' => 'nullable|string|max:500',
        
        // ✅ EXÁMENES
        'fex_es' => 'nullable|date',
        'hcElectrocardiograma' => 'nullable|string|max:1000',
        'fex_es1' => 'nullable|date',
        'hcEcocardiograma' => 'nullable|string|max:1000',
        'fex_es2' => 'nullable|date',
        'hcEcografiaRenal' => 'nullable|string|max:1000',
        
        // ✅ CLASIFICACIÓN
        'ClasificacionEstadoMetabolico' => 'nullable|string|max:200',
        'hipertension_arterial_personal' => 'nullable|in:SI,NO',
        'obs_hipertension_arterial_personal' => 'nullable|string|max:500',
        'clasificacion_hta' => 'nullable|string|max:200',
        'diabetes_mellitus_personal' => 'nullable|in:SI,NO',
        'obs_diabetes_mellitus_personal' => 'nullable|string|max:500',
        'clasificacion_dm' => 'nullable|string|max:200',
        'clasificacion_erc_estado' => 'nullable|string|max:200',
        'clasificacion_erc_categoria_ambulatoria_persistente' => 'nullable|string|max:200',
        'clasificacion_rcv' => 'nullable|string|max:200',
        'tasa_filtracion_glomerular_ckd_epi' => 'nullable|numeric|min:0|max:200',
        'tasa_filtracion_glomerular_gockcroft_gault' => 'nullable|numeric|min:0|max:200',
        
        // ✅ EDUCACIÓN
        'alimentacion' => 'nullable|in:SI,NO',
        'disminucion_consumo_sal_azucar' => 'nullable|in:SI,NO',
        'fomento_actividad_fisica' => 'nullable|in:SI,NO',
        'importancia_adherencia_tratamiento' => 'nullable|in:SI,NO',
        'consumo_frutas_verduras' => 'nullable|in:SI,NO',
        'manejo_estres' => 'nullable|in:SI,NO',
        'disminucion_consumo_cigarrillo' => 'nullable|in:SI,NO',
        'disminucion_peso' => 'nullable|in:SI,NO',
        
        // ✅ OTROS
        'observaciones_generales' => 'nullable|string|max:2000',
        'finalidad' => 'nullable|string|max:100',
        'causa_externa' => 'nullable|string|max:200',
        'actitud' => 'nullable|string|max:500',
        'evaluacion_d' => 'nullable|string|max:1000',
        'evaluacion_p' => 'nullable|string|max:1000',
        'estado' => 'nullable|string|max:500',
        'evaluacion_dolor' => 'nullable|string|max:1000',
        'evaluacion_os' => 'nullable|string|max:1000',
        'evaluacion_neu' => 'nullable|string|max:1000',
        'comitante' => 'nullable|string|max:500',
        'plan_seguir' => 'nullable|string|max:2000',
        'estructura_familiar' => 'nullable|string|max:2000',
        'psicologia_red_apoyo' => 'nullable|string|max:2000',
        'psicologia_comportamiento_consulta' => 'nullable|string|max:2000',
        'psicologia_tratamiento_actual_adherencia' => 'nullable|string|max:2000',
        'psicologia_descripcion_problema' => 'nullable|string|max:5000',
        'analisis_conclusiones' => 'nullable|string|max:5000',
        'psicologia_plan_intervencion_recomendacion' => 'nullable|string|max:5000',
        'avance_paciente' => 'nullable|string|max:2000',

         // ✅✅✅ NUTRICIONISTA - PRIMERA VEZ ✅✅✅
        'enfermedad_diagnostica' => 'nullable|string|max:2000',
        'habito_intestinal' => 'nullable|string|max:500',
        'quirurgicos' => 'nullable|string|max:1000',
        'quirurgicos_observaciones' => 'nullable|string|max:1000',
        'alergicos' => 'nullable|string|max:1000',
        'alergicos_observaciones' => 'nullable|string|max:1000',
        'familiares' => 'nullable|string|max:1000',
        'familiares_observaciones' => 'nullable|string|max:1000',
        'psa' => 'nullable|string|max:500',
        'psa_observaciones' => 'nullable|string|max:1000',
        'farmacologicos' => 'nullable|string|max:1000',
        'farmacologicos_observaciones' => 'nullable|string|max:1000',
        'sueno' => 'nullable|string|max:500',
        'sueno_observaciones' => 'nullable|string|max:1000',
        'tabaquismo_observaciones' => 'nullable|string|max:1000',
        'tabaquismo' => 'nullable|string|max:500',
        'ejercicio' => 'nullable|string|max:500',
        'ejercicio_observaciones' => 'nullable|string|max:1000',
        
        // Gineco-obstétricos
        'metodo_conceptivo' => 'nullable|string|max:200',
        'metodo_conceptivo_cual' => 'nullable|string|max:200',
        'embarazo_actual' => 'nullable|in:SI,NO',
        'semanas_gestacion' => 'nullable|integer|min:0|max:42',
        'climatero' => 'nullable|string|max:200',
        
        // Evaluación dietética
        'tolerancia_via_oral' => 'nullable|string|max:500',
        'percepcion_apetito' => 'nullable|string|max:200',
        'percepcion_apetito_observacion' => 'nullable|string|max:1000',
        'alimentos_preferidos' => 'nullable|string|max:1000',
        'alimentos_rechazados' => 'nullable|string|max:1000',
        'suplemento_nutricionales' => 'nullable|string|max:1000',
        'dieta_especial' => 'nullable|in:SI,NO',
        'dieta_especial_cual' => 'nullable|string|max:500',
        
        // Horarios de comida
        'desayuno_hora' => 'nullable|string|max:50',
        'desayuno_hora_observacion' => 'nullable|string|max:1000',
        'media_manana_hora' => 'nullable|string|max:50',
        'media_manana_hora_observacion' => 'nullable|string|max:1000',
        'almuerzo_hora' => 'nullable|string|max:50',
        'almuerzo_hora_observacion' => 'nullable|string|max:1000',
        'media_tarde_hora' => 'nullable|string|max:50',
        'media_tarde_hora_observacion' => 'nullable|string|max:1000',
        'cena_hora' => 'nullable|string|max:50',
        'cena_hora_observacion' => 'nullable|string|max:1000',
        'refrigerio_nocturno_hora' => 'nullable|string|max:50',
        'refrigerio_nocturno_hora_observacion' => 'nullable|string|max:1000',
        
        // Plan nutricional
        'peso_ideal' => 'nullable|numeric|min:0|max:300',
        'interpretacion' => 'nullable|string|max:2000',
        'meta_meses' => 'nullable|integer|min:0|max:24',
        'analisis_nutricional' => 'nullable|string|max:5000',
        'plan_seguir_nutri' => 'nullable|string|max:5000',

        // ✅✅✅ NUTRICIONISTA - CONTROL ✅✅✅
        // Recordatorio 24h
        'comida_desayuno' => 'nullable|string|max:2000',
        'comida_medio_desayuno' => 'nullable|string|max:2000',
        'comida_almuerzo' => 'nullable|string|max:2000',
        'comida_medio_almuerzo' => 'nullable|string|max:2000',
        'comida_cena' => 'nullable|string|max:2000',
        
        // Frecuencia de consumo
        'lacteo' => 'nullable|string|max:200',
        'lacteo_observacion' => 'nullable|string|max:1000',
        'huevo' => 'nullable|string|max:200',
        'huevo_observacion' => 'nullable|string|max:1000',
        'embutido' => 'nullable|string|max:200',
        'embutido_observacion' => 'nullable|string|max:1000',
        'carne_roja' => 'nullable|string|max:200',
        'carne_blanca' => 'nullable|string|max:200',
        'carne_vicera' => 'nullable|string|max:200',
        'carne_observacion' => 'nullable|string|max:1000',
        'leguminosas' => 'nullable|string|max:200',
        'leguminosas_observacion' => 'nullable|string|max:1000',
        'frutas_jugo' => 'nullable|string|max:200',
        'frutas_porcion' => 'nullable|string|max:200',
        'frutas_observacion' => 'nullable|string|max:1000',
        'verduras_hortalizas' => 'nullable|string|max:200',
        'vh_observacion' => 'nullable|string|max:1000',
        'cereales' => 'nullable|string|max:200',
        'cereales_observacion' => 'nullable|string|max:1000',
        'rtp' => 'nullable|string|max:200',
        'rtp_observacion' => 'nullable|string|max:1000',
        'azucar_dulce' => 'nullable|string|max:200',
        'ad_observacion' => 'nullable|string|max:1000',

        'descripcion_sistema_nervioso' => 'nullable|string|max:2000',
        'sistema_hemolinfatico' => 'nullable|in:SI,NO',
        'descripcion_sistema_hemolinfatico' => 'nullable|string|max:2000',
        'aparato_digestivo' => 'nullable|in:SI,NO',
        'descripcion_aparato_digestivo' => 'nullable|string|max:2000',
        'organo_sentido' => 'nullable|in:SI,NO',
        'descripcion_organos_sentidos' => 'nullable|string|max:2000',
        'endocrino_metabolico' => 'nullable|in:SI,NO',
        'descripcion_endocrino_metabolico' => 'nullable|string|max:2000',
        'inmunologico' => 'nullable|in:SI,NO',
        'descripcion_inmunologico' => 'nullable|string|max:2000',
        'cancer_tumores_radioterapia_quimio' => 'nullable|in:SI,NO',
        'descripcion_cancer_tumores_radio_quimioterapia' => 'nullable|string|max:2000',
        'glandula_mamaria' => 'nullable|in:SI,NO',
        'descripcion_glandulas_mamarias' => 'nullable|string|max:2000',
        'hipertension_diabetes_erc' => 'nullable|in:SI,NO',
        'descripcion_hipertension_diabetes_erc' => 'nullable|string|max:2000',
        'reacciones_alergica' => 'nullable|in:SI,NO',
        'descripcion_reacion_alergica' => 'nullable|string|max:2000',
        'cardio_vasculares' => 'nullable|in:SI,NO',
        'descripcion_cardio_vasculares' => 'nullable|string|max:2000',
        'respiratorios' => 'nullable|in:SI,NO',
        'descripcion_respiratorios' => 'nullable|string|max:2000',
        'urinarias' => 'nullable|in:SI,NO',
        'descripcion_urinarias' => 'nullable|string|max:2000',
        'osteoarticulares' => 'nullable|in:SI,NO',
        'descripcion_osteoarticulares' => 'nullable|string|max:2000',
        'infecciosos' => 'nullable|in:SI,NO',
        'descripcion_infecciosos' => 'nullable|string|max:2000',
        'cirugia_trauma' => 'nullable|in:SI,NO',
        'descripcion_cirugias_traumas' => 'nullable|string|max:2000',
        'tratamiento_medicacion' => 'nullable|in:SI,NO',
        'descripcion_tratamiento_medicacion' => 'nullable|string|max:2000',
        'antecedente_quirurgico' => 'nullable|in:SI,NO',
        'descripcion_antecedentes_quirurgicos' => 'nullable|string|max:2000',
        'antecedentes_familiares' => 'nullable|in:SI,NO',
        'descripcion_antecedentes_familiares' => 'nullable|string|max:2000',
        'consumo_tabaco' => 'nullable|in:SI,NO',
        'descripcion_consumo_tabaco' => 'nullable|string|max:2000',
        'antecedentes_alcohol' => 'nullable|in:SI,NO',
        'descripcion_antecedentes_alcohol' => 'nullable|string|max:2000',
        'sedentarismo' => 'nullable|in:SI,NO',
        'descripcion_sedentarismo' => 'nullable|string|max:2000',
        'ginecologico' => 'nullable|in:SI,NO',
        'descripcion_ginecologicos' => 'nullable|string|max:2000',
        'citologia_vaginal' => 'nullable|in:SI,NO',
        'descripcion_citologia_vaginal' => 'nullable|string|max:2000',
        'menarquia' => 'nullable|integer|min:0|max:20',
        'gestaciones' => 'nullable|integer|min:0|max:30',
        'parto' => 'nullable|integer|min:0|max:30',
        'aborto' => 'nullable|integer|min:0|max:30',
        'cesaria' => 'nullable|integer|min:0|max:30',
        'antecedente_personal' => 'nullable|string|max:2000',
        'neurologico_estado_mental' => 'nullable|in:SI,NO',
        'obs_neurologico_estado_mental' => 'nullable|string|max:2000',
        // Plan de seguimiento
        'diagnostico_nutri' => 'nullable|string|max:2000',

        'medicamentos' => 'nullable|array',
        'medicamentos.*.idMedicamento' => 'required|string|uuid', // ✅ CAMBIO: string|uuid
        'medicamentos.*.cantidad' => 'required|string|max:50',
        'medicamentos.*.dosis' => 'required|string|max:200',
        
        'remisiones' => 'nullable|array',
        'remisiones.*.idRemision' => 'required|string|uuid', // ✅ CAMBIO: string|uuid
        'remisiones.*.remObservacion' => 'nullable|string|max:500',
        
        
        'cups' => 'nullable|array',
        'cups.*.idCups' => 'required|string|uuid', // ✅ CAMBIO: string|uuid
        'cups.*.cupObservacion' => 'nullable|string|max:500',
        
        'diagnosticos_adicionales' => 'nullable|array',
        'diagnosticos_adicionales.*.idDiagnostico' => 'required|string|uuid', // ✅ CAMBIO: string|uuid
        'diagnosticos_adicionales.*.tipo_diagnostico' => 'required|string',
    ]);
}

/**
 * ✅ FILTRAR ELEMENTOS VACÍOS DE ARRAYS - CORREGIDO PARA UUIDs
 */
private function filterEmptyArrayElements(Request $request): void
{
    // ✅ FILTRAR CUPS VACÍOS
    if ($request->has('cups')) {
        $cups = collect($request->input('cups', []))
            ->filter(function ($item) {
                return !empty($item['idCups']) && 
                       (is_string($item['idCups']) || is_numeric($item['idCups'])); // ✅ CAMBIO: acepta string o numeric
            })
            ->values()
            ->toArray();
        
        $request->merge(['cups' => $cups]);
        Log::info('🔧 CUPS filtrados', ['filtrados' => count($cups)]);
    }

    // ✅ FILTRAR MEDICAMENTOS VACÍOS
    if ($request->has('medicamentos')) {
        $medicamentos = collect($request->input('medicamentos', []))
            ->filter(function ($item) {
                return !empty($item['idMedicamento']) && 
                       (is_string($item['idMedicamento']) || is_numeric($item['idMedicamento'])); // ✅ CAMBIO
            })
            ->values()
            ->toArray();
        
        $request->merge(['medicamentos' => $medicamentos]);
        Log::info('🔧 Medicamentos filtrados', ['filtrados' => count($medicamentos)]);
    }

    // ✅ FILTRAR DIAGNÓSTICOS ADICIONALES VACÍOS
    if ($request->has('diagnosticos_adicionales')) {
        $diagnosticos = collect($request->input('diagnosticos_adicionales', []))
            ->filter(function ($item) {
                return !empty($item['idDiagnostico']) && 
                       (is_string($item['idDiagnostico']) || is_numeric($item['idDiagnostico'])); // ✅ CAMBIO
            })
            ->values()
            ->toArray();
        
        $request->merge(['diagnosticos_adicionales' => $diagnosticos]);
        Log::info('🔧 Diagnósticos adicionales filtrados', ['filtrados' => count($diagnosticos)]);
    }

    // ✅ FILTRAR REMISIONES VACÍAS
    if ($request->has('remisiones')) {
        $remisiones = collect($request->input('remisiones', []))
            ->filter(function ($item) {
                return !empty($item['idRemision']) && 
                       (is_string($item['idRemision']) || is_numeric($item['idRemision'])); // ✅ CAMBIO
            })
            ->values()
            ->toArray();
        
        $request->merge(['remisiones' => $remisiones]);
        Log::info('🔧 Remisiones filtradas', ['filtradas' => count($remisiones)]);
    }
}
// ✅ MÉTODO HÍBRIDO CORREGIDO
private function obtenerDatosMaestrosHibrido(): array
{
    $datos = [
        'medicamentos' => [],
        'diagnosticos' => [],
        'remisiones' => [],
        'cups' => []
    ];

    // ✅ MEDICAMENTOS - Híbrido
    try {
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get('/medicamentos');
            if ($response['success'] && !empty($response['data'])) {
                $datos['medicamentos'] = $response['data'];
                Log::info('✅ Medicamentos obtenidos desde API', ['count' => count($response['data'])]);
            } else {
                throw new \Exception('API sin datos');
            }
        } else {
            throw new \Exception('API offline');
        }
    } catch (\Exception $e) {
        Log::warning('⚠️ Medicamentos API falló, usando offline', ['error' => $e->getMessage()]);
        $datos['medicamentos'] = $this->offlineService->buscarMedicamentosOffline('', 100);
    }

    // ✅ DIAGNÓSTICOS - Híbrido  
    try {
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get('/diagnosticos');
            if ($response['success'] && !empty($response['data'])) {
                $datos['diagnosticos'] = $response['data'];
                Log::info('✅ Diagnósticos obtenidos desde API', ['count' => count($response['data'])]);
            } else {
                throw new \Exception('API sin datos');
            }
        } else {
            throw new \Exception('API offline');
        }
    } catch (\Exception $e) {
        Log::warning('⚠️ Diagnósticos API falló, usando offline', ['error' => $e->getMessage()]);
        $datos['diagnosticos'] = $this->offlineService->buscarDiagnosticosOffline('', 100);
    }

    // ✅ REMISIONES - Híbrido
    try {
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get('/remisiones');
            if ($response['success'] && !empty($response['data'])) {
                $datos['remisiones'] = $response['data'];
                Log::info('✅ Remisiones obtenidas desde API', ['count' => count($response['data'])]);
            } else {
                throw new \Exception('API sin datos');
            }
        } else {
            throw new \Exception('API offline');
        }
    } catch (\Exception $e) {
        Log::warning('⚠️ Remisiones API falló, usando offline', ['error' => $e->getMessage()]);
        $datos['remisiones'] = $this->offlineService->buscarRemisionesOffline('', 100);
    }

    // ✅ CUPS - Ya funciona
    try {
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get('/cups');
            if ($response['success'] && !empty($response['data'])) {
                $datos['cups'] = $response['data'];
                Log::info('✅ CUPS obtenidos desde API', ['count' => count($response['data'])]);
            } else {
                throw new \Exception('API sin datos');
            }
        } else {
            throw new \Exception('API offline');
        }
    } catch (\Exception $e) {
        Log::warning('⚠️ CUPS API falló, usando offline', ['error' => $e->getMessage()]);
        $datos['cups'] = $this->offlineService->getCupsActivosOffline();
    }

    return $datos;
}

  /**
 * ✅ PREPARAR DATOS PARA ENVÍO - CORREGIDO CON PACIENTE_ID Y TIPO_CONSULTA INTELIGENTE
 */
private function prepareHistoriaData(array $validatedData, array $usuario): array
{
    // ✅ OBTENER DATOS DE LA CITA PARA PACIENTE_ID
    $citaData = $this->getCitaData($validatedData['cita_uuid']);
    
    if (!$citaData) {
        throw new \Exception('No se pudo obtener información de la cita para el paciente_id');
    }
    
    // ✅ EXTRAER PACIENTE_ID DE DIFERENTES POSIBLES ESTRUCTURAS
    $pacienteId = $citaData['paciente_id'] ?? 
                  $citaData['paciente_uuid'] ?? 
                  $citaData['paciente']['id'] ?? 
                  $citaData['paciente']['uuid'] ?? 
                  null;
    
    if (!$pacienteId) {
        Log::error('❌ No se pudo extraer paciente_id de la cita', [
            'cita_uuid' => $validatedData['cita_uuid'],
            'cita_keys' => array_keys($citaData),
            'paciente_data' => $citaData['paciente'] ?? 'NO_EXISTE'
        ]);
        
        throw new \Exception('No se pudo obtener el paciente_id de la cita');
    }
    
    // ✅ DETERMINAR TIPO DE CONSULTA INTELIGENTEMENTE
    $tipoConsulta = $this->determinarTipoConsulta($validatedData['cita_uuid'], $pacienteId);
    
    Log::info('✅ Datos de cita procesados para historia clínica', [
        'cita_uuid' => $validatedData['cita_uuid'],
        'paciente_uuid' => $pacienteId,
        'sede_id' => $usuario['sede_id'],
        'usuario_id' => $usuario['id'],
        'tipo_consulta' => $tipoConsulta // ✅ AGREGADO
    ]);
    
    // ✅ CALCULAR IMC SI HAY PESO Y TALLA
    $imc = null;
    $clasificacionImc = null;
    
    if (!empty($validatedData['peso']) && !empty($validatedData['talla'])) {
        $peso = floatval($validatedData['peso']);
        $talla = floatval($validatedData['talla']) / 100; // Convertir cm a metros
        
        if ($talla > 0) {
            $imc = round($peso / ($talla * $talla), 2);
            $clasificacionImc = $this->clasificarIMC($imc);
        }
    }

    return [
        // ✅ CAMPOS OBLIGATORIOS QUE FALTABAN
        'cita_uuid' => $validatedData['cita_uuid'],
        'paciente_uuid' => $pacienteId, // ✅ AGREGADO - OBLIGATORIO
        'sede_id' => $usuario['sede_id'], // ✅ AGREGADO - OBLIGATORIO  
        'usuario_id' => $usuario['id'], // ✅ AGREGADO - OBLIGATORIO
        'tipo_consulta' => $tipoConsulta, // ✅ AGREGADO - INTELIGENTE
        
        // ✅ RESTO DE CAMPOS (mantén todo lo que ya tienes)...
        'finalidad' => $validatedData['finalidad'] ?? 'CONSULTA',
        'acompanante' => $validatedData['acompanante'] ?? null,
        'acu_telefono' => $validatedData['telefono_acudiente'] ?? null,
        'acu_parentesco' => $validatedData['parentesco'] ?? null,
        'causa_externa' => $validatedData['causa_externa'] ?? null,
        'motivo_consulta' => $validatedData['motivo'] ?? '',
        'enfermedad_actual' => $validatedData['enfermedad_actual'] ?? '',

        // ✅ DISCAPACIDADES
        'discapacidad_fisica' => $validatedData['discapacidad_fisica'] ?? null,
        'discapacidad_visual' => $validatedData['discapacidad_visual'] ?? null,
        'discapacidad_mental' => $validatedData['discapacidad_mental'] ?? null,
        'discapacidad_auditiva' => $validatedData['discapacidad_auditiva'] ?? null,
        'discapacidad_intelectual' => $validatedData['discapacidad_intelectual'] ?? null,
        
        // ✅ DROGODEPENDENCIA
        'drogo_dependiente' => $validatedData['drogodependiente'] ?? null,
        'drogo_dependiente_cual' => $validatedData['drogodependiente_cual'] ?? null,
        
        // ✅ MEDIDAS ANTROPOMÉTRICAS
        'peso' => $validatedData['peso'] ?? null,
        'talla' => $validatedData['talla'] ?? null,
        'imc' => $imc,
        'clasificacion' => $clasificacionImc,
        'perimetro_abdominal' => $validatedData['perimetro_abdominal'] ?? null,
        'obs_perimetro_abdominal' => $validatedData['obs_perimetro_abdominal'] ?? null,
        
        // ✅ ANTECEDENTES FAMILIARES
        'hipertension_arterial' => $validatedData['hipertension_arterial'] ?? null,
        'parentesco_hipertension' => $validatedData['parentesco_hipertension'] ?? null,
        'diabetes_mellitus' => $validatedData['diabetes_mellitus'] ?? null,
        'parentesco_mellitus' => $validatedData['parentesco_diabetes_mellitus'] ?? null,
        'artritis' => $validatedData['artritis'] ?? null,
        'parentesco_artritis' => $validatedData['parentesco_artritis'] ?? null,
        'enfermedad_cardiovascular' => $validatedData['enfermedad_cardiovascular'] ?? null,
        'parentesco_cardiovascular' => $validatedData['parentesco_enfermedad_cardiovascular'] ?? null,
        'antecedente_metabolico' => $validatedData['antecedentes_metabolico'] ?? null,
        'parentesco_metabolico' => $validatedData['parentesco_antecedentes_metabolico'] ?? null,
        'cancer_mama_estomago_prostata_colon' => $validatedData['cancer'] ?? null,
        'parentesco_cancer' => $validatedData['parentesco_cancer'] ?? null,
        'leucemia' => $validatedData['lucemia'] ?? null,
        'parentesco_leucemia' => $validatedData['parentesco_lucemia'] ?? null,
        'vih' => $validatedData['vih'] ?? null,
        'parentesco_vih' => $validatedData['parentesco_vih'] ?? null,
        'otro' => $validatedData['otro'] ?? null,
        'parentesco_otro' => $validatedData['parentesco_otro'] ?? null,
        
        // ✅ ANTECEDENTES PERSONALES
        'enfermedad_cardiovascular_personal' => $validatedData['enfermedad_cardiovascular_personal'] ?? null,
        'obs_personal_enfermedad_cardiovascular' => $validatedData['obs_enfermedad_cardiovascular_personal'] ?? null,
        'arterial_periferica_personal' => $validatedData['arterial_periferica_personal'] ?? null,
        'obs_personal_arterial_periferica' => $validatedData['obs_arterial_periferica_personal'] ?? null,
        'carotidea_personal' => $validatedData['carotidea_personal'] ?? null,
        'obs_personal_carotidea' => $validatedData['obs_carotidea_personal'] ?? null,
        'aneurisma_aorta_personal' => $validatedData['aneurisma_aorta_peronal'] ?? null,
        'obs_personal_aneurisma_aorta' => $validatedData['obs_aneurisma_aorta_peronal'] ?? null,
        'sindrome_coronario_agudo_angina_personal' => $validatedData['coronario_personal'] ?? null,
        'obs_personal_sindrome_coronario' => $validatedData['obs_coronario_personal'] ?? null,
        'artritis_personal' => $validatedData['artritis_personal'] ?? null,
        'obs_personal_artritis' => $validatedData['obs_artritis_personal'] ?? null,
        'iam_personal' => $validatedData['iam_personal'] ?? null,
        'obs_personal_iam' => $validatedData['obs_iam_personal'] ?? null,
        'revascul_coronaria_personal' => $validatedData['revascul_coronaria_personal'] ?? null,
        'obs_personal_revascul_coronaria' => $validatedData['obs_revascul_coronaria_personal'] ?? null,
        'insuficiencia_cardiaca_personal' => $validatedData['insuficiencia_cardiaca_personal'] ?? null,
        'obs_personal_insuficiencia_cardiaca' => $validatedData['obs_insuficiencia_cardiaca_personal'] ?? null,
        'amputacion_pie_diabetico_personal' => $validatedData['amputacion_pie_diabetico_personal'] ?? null,
        'obs_personal_amputacion_pie_diabetico' => $validatedData['obs_amputacion_pie_diabetico_personal'] ?? null,
        'enfermedad_pulmonar_personal' => $validatedData['enfermedad_pulmonar_personal'] ?? null,
        'obs_personal_enfermedad_pulmonar' => $validatedData['obs_enfermedad_pulmonar_personal'] ?? null,
        'victima_maltrato_personal' => $validatedData['victima_maltrato_personal'] ?? null,
        'obs_personal_maltrato_personal' => $validatedData['obs_victima_maltrato_personal'] ?? null,
        'antecedentes_quirurgicos' => $validatedData['antecedentes_quirurgicos_personal'] ?? null,
        'obs_personal_antecedentes_quirurgicos' => $validatedData['obs_antecedentes_quirurgicos_personal'] ?? null,
        'acontosis_personal' => $validatedData['acontosis_personal'] ?? null,
        'obs_personal_acontosis' => $validatedData['obs_acontosis_personal'] ?? null,
        'otro_personal' => $validatedData['otro_personal'] ?? null,
        'obs_personal_otro' => $validatedData['obs_otro_personal'] ?? null,
        'insulina_requiriente' => $validatedData['insulina_requiriente'] ?? null,
        
        // ✅ TEST MORISKY
        'olvida_tomar_medicamentos' => $validatedData['test_morisky_olvida_tomar_medicamentos'] ?? null,
        'toma_medicamentos_hora_indicada' => $validatedData['test_morisky_toma_medicamentos_hora_indicada'] ?? null,
        'cuando_esta_bien_deja_tomar_medicamentos' => $validatedData['test_morisky_cuando_esta_bien_deja_tomar_medicamentos'] ?? null,
        'siente_mal_deja_tomarlos' => $validatedData['test_morisky_siente_mal_deja_tomarlos'] ?? null,
        'valoracion_psicologia' => $validatedData['test_morisky_valoracio_psicologia'] ?? null,
        'adherente' => $validatedData['adherente'] ?? null,
        
        // ✅ OTROS TRATAMIENTOS
        'recibe_tratamiento_alternativo' => $validatedData['recibe_tratamiento_alternativo'] ?? null,
        'recibe_tratamiento_con_plantas_medicinales' => $validatedData['recibe_tratamiento_plantas_medicinales'] ?? null,
        'recibe_ritual_medicina_tradicional' => $validatedData['recibe_ritual_medicina_tradicional'] ?? null,
        
        // ✅ REVISIÓN POR SISTEMAS
        'general' => $validatedData['general'] ?? null,
        'cabeza' => $validatedData['cabeza'] ?? null,
        'respiratorio' => $validatedData['respiratorio'] ?? null,
        'cardiovascular' => $validatedData['cardiovascular'] ?? null,
        'gastrointestinal' => $validatedData['gastrointestinal'] ?? null,
        'osteoatromuscular' => $validatedData['osteoatromuscular'] ?? null,
        'snc' => $validatedData['snc'] ?? null,
        
        // ✅ SIGNOS VITALES
        'presion_arterial_sistolica_sentado_pie' => $validatedData['ef_pa_sistolica_sentado_pie'] ?? null,
        'presion_arterial_distolica_sentado_pie' => $validatedData['ef_pa_distolica_sentado_pie'] ?? null,
        'frecuencia_cardiaca' => $validatedData['ef_frecuencia_fisica'] ?? null,
        'frecuencia_respiratoria' => $validatedData['ef_frecuencia_respiratoria'] ?? null,
        
        // ✅ EXAMEN FÍSICO
        'ef_cabeza' => $validatedData['ef_cabeza'] ?? null,
        'obs_cabeza' => $validatedData['ef_obs_cabeza'] ?? null,
        'agudeza_visual' => $validatedData['ef_agudeza_visual'] ?? null,
        'obs_agudeza_visual' => $validatedData['ef_obs_agudeza_visual'] ?? null,
        'cuello' => $validatedData['ef_cuello'] ?? null,
        'obs_cuello' => $validatedData['ef_obs_cuello'] ?? null,
        'torax' => $validatedData['ef_torax'] ?? null,
        'obs_torax' => $validatedData['ef_obs_torax'] ?? null,
        'mamas' => $validatedData['ef_mamas'] ?? null,
        'obs_mamas' => $validatedData['ef_obs_mamas'] ?? null,
        'abdomen' => $validatedData['ef_abdomen'] ?? null,
        'obs_abdomen' => $validatedData['ef_obs_abdomen'] ?? null,
        'genito_urinario' => $validatedData['ef_genito_urinario'] ?? null,
        'obs_genito_urinario' => $validatedData['ef_obs_genito_urinario'] ?? null,
        'extremidades' => $validatedData['ef_extremidades'] ?? null,
        'obs_extremidades' => $validatedData['ef_obs_extremidades'] ?? null,
        'piel_anexos_pulsos' => $validatedData['ef_piel_anexos_pulsos'] ?? null,
        'obs_piel_anexos_pulsos' => $validatedData['ef_obs_piel_anexos_pulsos'] ?? null,
        'sistema_nervioso' => $validatedData['ef_sistema_nervioso'] ?? null,
        'obs_sistema_nervioso' => $validatedData['ef_obs_sistema_nervioso'] ?? null,
        'orientacion' => $validatedData['ef_orientacion'] ?? null,
        'obs_orientacion' => $validatedData['ef_obs_orientacion'] ?? null,
        'hallazgo_positivo_examen_fisico' => $validatedData['ef_hallazco_positivo_examen_fisico'] ?? null,
        
        // ✅ FACTORES DE RIESGO
        'numero_frutas_diarias' => $validatedData['numero_frutas_diarias'] ?? null,
        'elevado_consumo_grasa_saturada' => $validatedData['elevado_consumo_grasa_saturada'] ?? null,
        'adiciona_sal_despues_preparar_comida' => $validatedData['adiciona_sal_despues_preparar_alimentos'] ?? null,
        'dislipidemia' => $validatedData['dislipidemia'] ?? null,
        'condicion_clinica_asociada' => $validatedData['condicion_clinica_asociada'] ?? null,
        'lesion_organo_blanco' => $validatedData['lesion_organo_blanco'] ?? null,
        'descripcion_lesion_organo_blanco' => $validatedData['descripcion_lesion_organo_blanco'] ?? null,
        
        // ✅ EXÁMENES
        'fex_es' => $validatedData['fex_es'] ?? null,
        'electrocardiograma' => $validatedData['hcElectrocardiograma'] ?? null,
        'fex_es1' => $validatedData['fex_es1'] ?? null,
        'ecocardiograma' => $validatedData['hcEcocardiograma'] ?? null,
        'fex_es2' => $validatedData['fex_es2'] ?? null,
        'ecografia_renal' => $validatedData['hcEcografiaRenal'] ?? null,
        
        // ✅ CLASIFICACIÓN
        'clasificacion_estado_metabolico' => $validatedData['ClasificacionEstadoMetabolico'] ?? null,
        'hipertension_arterial_personal' => $validatedData['hipertension_arterial_personal'] ?? null,
        'obs_personal_hipertension_arterial' => $validatedData['obs_hipertension_arterial_personal'] ?? null,
        'clasificacion_hta' => $validatedData['clasificacion_hta'] ?? null,
        'diabetes_mellitus_personal' => $validatedData['diabetes_mellitus_personal'] ?? null,
        'obs_personal_mellitus' => $validatedData['obs_diabetes_mellitus_personal'] ?? null,
        'clasificacion_dm' => $validatedData['clasificacion_dm'] ?? null,
        'clasificacion_erc_estado' => $validatedData['clasificacion_erc_estado'] ?? null,
        'clasificacion_erc_categoria_ambulatoria_persistente' => $validatedData['clasificacion_erc_categoria_ambulatoria_persistente'] ?? null,
        'clasificacion_rcv' => $validatedData['clasificacion_rcv'] ?? null,
        'tasa_filtracion_glomerular_ckd_epi' => $validatedData['tasa_filtracion_glomerular_ckd_epi'] ?? null,
        'tasa_filtracion_glomerular_gockcroft_gault' => $validatedData['tasa_filtracion_glomerular_gockcroft_gault'] ?? null,
        
        // ✅ EDUCACIÓN
        'alimentacion' => $validatedData['alimentacion'] ?? null,
        'disminucion_consumo_sal_azucar' => $validatedData['disminucion_consumo_sal_azucar'] ?? null,
        'fomento_actividad_fisica' => $validatedData['fomento_actividad_fisica'] ?? null,
        'importancia_adherencia_tratamiento' => $validatedData['importancia_adherencia_tratamiento'] ?? null,
        'consumo_frutas_verduras' => $validatedData['consumo_frutas_verduras'] ?? null,
        'manejo_estres' => $validatedData['manejo_estres'] ?? null,
        'disminucion_consumo_cigarrillo' => $validatedData['disminucion_consumo_cigarrillo'] ?? null,
        'disminucion_peso' => $validatedData['disminucion_peso'] ?? null,
        
        // ✅ OTROS
        'observaciones_generales' => $validatedData['observaciones_generales'] ?? null,

        // ✅✅✅ CAMPOS DE FISIOTERAPIA ✅✅✅
        'actitud' => $validatedData['actitud'] ?? null,
        'evaluacion_d' => $validatedData['evaluacion_d'] ?? null,
        'evaluacion_p' => $validatedData['evaluacion_p'] ?? null,
        'estado' => $validatedData['estado'] ?? null,
        'evaluacion_dolor' => $validatedData['evaluacion_dolor'] ?? null,
        'evaluacion_os' => $validatedData['evaluacion_os'] ?? null,
        'evaluacion_neu' => $validatedData['evaluacion_neu'] ?? null,
        'comitante' => $validatedData['comitante'] ?? null,
        'plan_seguir' => $validatedData['plan_seguir'] ?? null,

         // ✅✅✅ CAMPOS DE PSICOLOGÍA ✅✅✅
        'estructura_familiar' => $validatedData['estructura_familiar'] ?? null,
        'psicologia_red_apoyo' => $validatedData['psicologia_red_apoyo'] ?? null,
        'psicologia_comportamiento_consulta' => $validatedData['psicologia_comportamiento_consulta'] ?? null,
        'psicologia_tratamiento_actual_adherencia' => $validatedData['psicologia_tratamiento_actual_adherencia'] ?? null,
        'psicologia_descripcion_problema' => $validatedData['psicologia_descripcion_problema'] ?? null,
        'analisis_conclusiones' => $validatedData['analisis_conclusiones'] ?? null,
        'psicologia_plan_intervencion_recomendacion' => $validatedData['psicologia_plan_intervencion_recomendacion'] ?? null,
        'avance_paciente' => $validatedData['avance_paciente'] ?? null,

         // ✅✅✅ NUTRICIONISTA - PRIMERA VEZ ✅✅✅
        'enfermedad_diagnostica' => $validatedData['enfermedad_diagnostica'] ?? null,
        'habito_intestinal' => $validatedData['habito_intestinal'] ?? null,
        'quirurgicos' => $validatedData['quirurgicos'] ?? null,
        'quirurgicos_observaciones' => $validatedData['quirurgicos_observaciones'] ?? null,
        'alergicos' => $validatedData['alergicos'] ?? null,
        'alergicos_observaciones' => $validatedData['alergicos_observaciones'] ?? null,
        'familiares' => $validatedData['familiares'] ?? null,
        'familiares_observaciones' => $validatedData['familiares_observaciones'] ?? null,
        'psa' => $validatedData['psa'] ?? null,
        'psa_observaciones' => $validatedData['psa_observaciones'] ?? null,
        'farmacologicos' => $validatedData['farmacologicos'] ?? null,
        'farmacologicos_observaciones' => $validatedData['farmacologicos_observaciones'] ?? null,
        'sueno' => $validatedData['sueno'] ?? null,
        'sueno_observaciones' => $validatedData['sueno_observaciones'] ?? null,
        'tabaquismo_observaciones' => $validatedData['tabaquismo_observaciones'] ?? null,
        'tabaquismo' => $validatedData['tabaquismo'] ?? null,
        'ejercicio' => $validatedData['ejercicio'] ?? null,
        'ejercicio_observaciones' => $validatedData['ejercicio_observaciones'] ?? null,
        
        // Gineco-obstétricos
        'metodo_conceptivo' => $validatedData['metodo_conceptivo'] ?? null,
        'metodo_conceptivo_cual' => $validatedData['metodo_conceptivo_cual'] ?? null,
        'embarazo_actual' => $validatedData['embarazo_actual'] ?? null,
        'semanas_gestacion' => $validatedData['semanas_gestacion'] ?? null,
        'climatero' => $validatedData['climatero'] ?? null,
        
        // Evaluación dietética
        'tolerancia_via_oral' => $validatedData['tolerancia_via_oral'] ?? null,
        'percepcion_apetito' => $validatedData['percepcion_apetito'] ?? null,
        'percepcion_apetito_observacion' => $validatedData['percepcion_apetito_observacion'] ?? null,
        'alimentos_preferidos' => $validatedData['alimentos_preferidos'] ?? null,
        'alimentos_rechazados' => $validatedData['alimentos_rechazados'] ?? null,
        'suplemento_nutricionales' => $validatedData['suplemento_nutricionales'] ?? null,
        'dieta_especial' => $validatedData['dieta_especial'] ?? null,
        'dieta_especial_cual' => $validatedData['dieta_especial_cual'] ?? null,
        
        // Horarios de comida
        'desayuno_hora' => $validatedData['desayuno_hora'] ?? null,
        'desayuno_hora_observacion' => $validatedData['desayuno_hora_observacion'] ?? null,
        'media_manana_hora' => $validatedData['media_manana_hora'] ?? null,
        'media_manana_hora_observacion' => $validatedData['media_manana_hora_observacion'] ?? null,
        'almuerzo_hora' => $validatedData['almuerzo_hora'] ?? null,
        'almuerzo_hora_observacion' => $validatedData['almuerzo_hora_observacion'] ?? null,
        'media_tarde_hora' => $validatedData['media_tarde_hora'] ?? null,
        'media_tarde_hora_observacion' => $validatedData['media_tarde_hora_observacion'] ?? null,
        'cena_hora' => $validatedData['cena_hora'] ?? null,
        'cena_hora_observacion' => $validatedData['cena_hora_observacion'] ?? null,
        'refrigerio_nocturno_hora' => $validatedData['refrigerio_nocturno_hora'] ?? null,
        'refrigerio_nocturno_hora_observacion' => $validatedData['refrigerio_nocturno_hora_observacion'] ?? null,
        
        // Plan nutricional
        'peso_ideal' => $validatedData['peso_ideal'] ?? null,
        'interpretacion' => $validatedData['interpretacion'] ?? null,
        'meta_meses' => $validatedData['meta_meses'] ?? null,
        'analisis_nutricional' => $validatedData['analisis_nutricional'] ?? null,
        'plan_seguir_nutri' => $validatedData['plan_seguir_nutri'] ?? null,

        // ✅✅✅ NUTRICIONISTA - CONTROL ✅✅✅
        // Recordatorio 24h
        'comida_desayuno' => $validatedData['comida_desayuno'] ?? null,
        'comida_medio_desayuno' => $validatedData['comida_medio_desayuno'] ?? null,
        'comida_almuerzo' => $validatedData['comida_almuerzo'] ?? null,
        'comida_medio_almuerzo' => $validatedData['comida_medio_almuerzo'] ?? null,
        'comida_cena' => $validatedData['comida_cena'] ?? null,
        
        // Frecuencia de consumo
        'lacteo' => $validatedData['lacteo'] ?? null,
        'lacteo_observacion' => $validatedData['lacteo_observacion'] ?? null,
        'huevo' => $validatedData['huevo'] ?? null,
        'huevo_observacion' => $validatedData['huevo_observacion'] ?? null,
        'embutido' => $validatedData['embutido'] ?? null,
        'embutido_observacion' => $validatedData['embutido_observacion'] ?? null,
        'carne_roja' => $validatedData['carne_roja'] ?? null,
        'carne_blanca' => $validatedData['carne_blanca'] ?? null,
        'carne_vicera' => $validatedData['carne_vicera'] ?? null,
        'carne_observacion' => $validatedData['carne_observacion'] ?? null,
        'leguminosas' => $validatedData['leguminosas'] ?? null,
        'leguminosas_observacion' => $validatedData['leguminosas_observacion'] ?? null,
        'frutas_jugo' => $validatedData['frutas_jugo'] ?? null,
        'frutas_porcion' => $validatedData['frutas_porcion'] ?? null,
        'frutas_observacion' => $validatedData['frutas_observacion'] ?? null,
        'verduras_hortalizas' => $validatedData['verduras_hortalizas'] ?? null,
        'vh_observacion' => $validatedData['vh_observacion'] ?? null,
        'cereales' => $validatedData['cereales'] ?? null,
        'cereales_observacion' => $validatedData['cereales_observacion'] ?? null,
        'rtp' => $validatedData['rtp'] ?? null,
        'rtp_observacion' => $validatedData['rtp_observacion'] ?? null,
        'azucar_dulce' => $validatedData['azucar_dulce'] ?? null,
        'ad_observacion' => $validatedData['ad_observacion'] ?? null,
        // Plan de seguimiento
        'diagnostico_nutri' => $validatedData['diagnostico_nutri'] ?? null,
        'descripcion_sistema_nervioso' => $validatedData['descripcion_sistema_nervioso'] ?? null,
        'sistema_hemolinfatico' => $validatedData['sistema_hemolinfatico'] ?? null,
        'descripcion_sistema_hemolinfatico' => $validatedData['descripcion_sistema_hemolinfatico'] ?? null,
        'aparato_digestivo' => $validatedData['aparato_digestivo'] ?? null,
        'descripcion_aparato_digestivo' => $validatedData['descripcion_aparato_digestivo'] ?? null,
        'organo_sentido' => $validatedData['organo_sentido'] ?? null,
        'descripcion_organos_sentidos' => $validatedData['descripcion_organos_sentidos'] ?? null,
        'endocrino_metabolico' => $validatedData['endocrino_metabolico'] ?? null,
        'descripcion_endocrino_metabolico' => $validatedData['descripcion_endocrino_metabolico'] ?? null,
        'inmunologico' => $validatedData['inmunologico'] ?? null,
        'descripcion_inmunologico' => $validatedData['descripcion_inmunologico'] ?? null,
        'cancer_tumores_radioterapia_quimio' => $validatedData['cancer_tumores_radioterapia_quimio'] ?? null,
        'descripcion_cancer_tumores_radio_quimioterapia' => $validatedData['descripcion_cancer_tumores_radio_quimioterapia'] ?? null,
        'glandula_mamaria' => $validatedData['glandula_mamaria'] ?? null,
        'descripcion_glandulas_mamarias' => $validatedData['descripcion_glandulas_mamarias'] ?? null,
        'hipertension_diabetes_erc' => $validatedData['hipertension_diabetes_erc'] ?? null,
        'descripcion_hipertension_diabetes_erc' => $validatedData['descripcion_hipertension_diabetes_erc'] ?? null,
        'reacciones_alergica' => $validatedData['reacciones_alergica'] ?? null,
        'descripcion_reacion_alergica' => $validatedData['descripcion_reacion_alergica'] ?? null,
        'cardio_vasculares' => $validatedData['cardio_vasculares'] ?? null,
        'descripcion_cardio_vasculares' => $validatedData['descripcion_cardio_vasculares'] ?? null,
        'respiratorios' => $validatedData['respiratorios'] ?? null,
        'descripcion_respiratorios' => $validatedData['descripcion_respiratorios'] ?? null,
        'urinarias' => $validatedData['urinarias'] ?? null,
        'descripcion_urinarias' => $validatedData['descripcion_urinarias'] ?? null,
        'osteoarticulares' => $validatedData['osteoarticulares'] ?? null,
        'descripcion_osteoarticulares' => $validatedData['descripcion_osteoarticulares'] ?? null,
        'infecciosos' => $validatedData['infecciosos'] ?? null,
        'descripcion_infecciosos' => $validatedData['descripcion_infecciosos'] ?? null,
        'cirugia_trauma' => $validatedData['cirugia_trauma'] ?? null,
        'descripcion_cirugias_traumas' => $validatedData['descripcion_cirugias_traumas'] ?? null,
        'tratamiento_medicacion' => $validatedData['tratamiento_medicacion'] ?? null,
        'descripcion_tratamiento_medicacion' => $validatedData['descripcion_tratamiento_medicacion'] ?? null,
        'antecedente_quirurgico' => $validatedData['antecedente_quirurgico'] ?? null,
        'descripcion_antecedentes_quirurgicos' => $validatedData['descripcion_antecedentes_quirurgicos'] ?? null,
        'antecedentes_familiares' => $validatedData['antecedentes_familiares'] ?? null,
        'descripcion_antecedentes_familiares' => $validatedData['descripcion_antecedentes_familiares'] ?? null,
        'consumo_tabaco' => $validatedData['consumo_tabaco'] ?? null,
        'descripcion_consumo_tabaco' => $validatedData['descripcion_consumo_tabaco'] ?? null,
        'antecedentes_alcohol' => $validatedData['antecedentes_alcohol'] ?? null,
        'descripcion_antecedentes_alcohol' => $validatedData['descripcion_antecedentes_alcohol'] ?? null,
        'sedentarismo' => $validatedData['sedentarismo'] ?? null,
        'descripcion_sedentarismo' => $validatedData['descripcion_sedentarismo'] ?? null,
        'ginecologico' => $validatedData['ginecologico'] ?? null,
        'descripcion_ginecologicos' => $validatedData['descripcion_ginecologicos'] ?? null,
        'citologia_vaginal' => $validatedData['citologia_vaginal'] ?? null,
        'descripcion_citologia_vaginal' => $validatedData['descripcion_citologia_vaginal'] ?? null,
        'menarquia' => $validatedData['menarquia'] ?? null,
        'gestaciones' => $validatedData['gestaciones'] ?? null,
        'parto' => $validatedData['parto'] ?? null,
        'aborto' => $validatedData['aborto'] ?? null,
        'cesaria' => $validatedData['cesaria'] ?? null, // ⚠️ Nota: tiene tilde en la lista original
        'antecedente_personal' => $validatedData['antecedente_personal'] ?? null,
        'neurologico_estado_mental' => $validatedData['neurologico_estado_mental'] ?? null,
        'obs_neurologico_estado_mental' => $validatedData['obs_neurologico_estado_mental'] ?? null,
                

        
        // ✅ ARRAYS RELACIONADOS (mantén los métodos que ya tienes)
        'diagnosticos' => $this->prepareDiagnosticos($validatedData),
        'medicamentos' => $this->prepareMedicamentos($validatedData),
        'remisiones' => $this->prepareRemisiones($validatedData),
        'cups' => $this->prepareCups($validatedData),
    ];
}


 /**
 * ✅ PREPARAR DIAGNÓSTICOS - CORREGIDO PARA UUIDs
 */
private function prepareDiagnosticos(array $validatedData): array
{
    $diagnosticos = [];
    
    // ✅ DIAGNÓSTICO PRINCIPAL
    $diagnosticos[] = [
        'diagnostico_id' => $validatedData['idDiagnostico'], // ✅ Puede ser UUID o ID
        'tipo' => 'PRINCIPAL',
        'tipo_diagnostico' => $validatedData['tipo_diagnostico'],
        'observacion' => null
    ];
    
    // ✅ DIAGNÓSTICOS ADICIONALES
    if (!empty($validatedData['diagnosticos_adicionales'])) {
        foreach ($validatedData['diagnosticos_adicionales'] as $index => $diagAdicional) {
            // ✅ VERIFICAR UUID O ID
            $diagnosticoId = $diagAdicional['idDiagnostico'] ?? 
                            $diagAdicional['uuid'] ?? 
                            $diagAdicional['id'] ?? 
                            null;
            
            if (!$diagnosticoId) {
                continue;
            }
            
            $diagnosticos[] = [
                'diagnostico_id' => $diagnosticoId, // ✅ Puede ser UUID o ID
                'tipo' => 'SECUNDARIO',
                'tipo_diagnostico' => $diagAdicional['tipo_diagnostico'],
                'observacion' => $diagAdicional['observacion'] ?? null
            ];
        }
    }
    
    return $diagnosticos;
}
/**
 * ✅ PREPARAR MEDICAMENTOS - CORREGIDO PARA UUIDs
 */
private function prepareMedicamentos(array $validatedData): array
{
    $medicamentos = [];
    
    if (!empty($validatedData['medicamentos'])) {
        foreach ($validatedData['medicamentos'] as $index => $medicamento) {
            // ✅ VERIFICAR UUID O ID
            $medicamentoId = $medicamento['idMedicamento'] ?? 
                            $medicamento['uuid'] ?? 
                            $medicamento['id'] ?? 
                            null;
            
            if (!$medicamentoId) {
                continue;
            }
            
            $medicamentos[] = [
                'medicamento_id' => $medicamentoId, // ✅ Puede ser UUID o ID
                'cantidad' => $medicamento['cantidad'] ?? '',
                'dosis' => $medicamento['dosis'] ?? '',
            ];
        }
    }
    
    return $medicamentos;
}

/**
 * ✅ PREPARAR REMISIONES - CORREGIDO PARA UUIDs
 */
private function prepareRemisiones(array $validatedData): array
{
    $remisiones = [];
    
    if (!empty($validatedData['remisiones'])) {
        foreach ($validatedData['remisiones'] as $index => $remision) {
            // ✅ VERIFICAR UUID O ID
            $remisionId = $remision['idRemision'] ?? 
                         $remision['uuid'] ?? 
                         $remision['id'] ?? 
                         null;
            
            if (!$remisionId) {
                continue;
            }
            
            $remisiones[] = [
                'remision_id' => $remisionId, // ✅ Puede ser UUID o ID
                'observacion' => $remision['remObservacion'] ?? null,
            ];
        }
    }
    
    return $remisiones;
}
 /**
 * ✅ PREPARAR CUPS - CORREGIDO PARA UUIDs
 */
private function prepareCups(array $validatedData): array
{
    $cups = [];
    
    Log::info('🔍 Preparando CUPS', [
        'cups_raw' => $validatedData['cups'] ?? 'No hay CUPS'
    ]);
    
    if (!empty($validatedData['cups'])) {
        foreach ($validatedData['cups'] as $index => $cup) {
            Log::info("🔍 Procesando CUPS {$index}", [
                'cup' => $cup,
                'keys' => array_keys($cup)
            ]);
            
            // ✅ VERIFICAR UUID O ID
            $cupsId = $cup['idCups'] ?? 
                     $cup['uuid'] ?? 
                     $cup['id'] ?? 
                     null;
            
            if (!$cupsId) {
                Log::warning('⚠️ CUPS sin ID válido', [
                    'cup' => $cup,
                    'available_keys' => array_keys($cup)
                ]);
                continue;
            }
            
            $cups[] = [
                'cups_id' => $cupsId, // ✅ Puede ser UUID o ID
                'observacion' => $cup['cupObservacion'] ?? null,
            ];
        }
    }
    
    Log::info('✅ CUPS preparados', [
        'count' => count($cups),
        'cups_data' => $cups
    ]);
    
    return $cups;
}

    /**
     * ✅ CLASIFICAR IMC
     */
    private function clasificarIMC(float $imc): string
    {
        if ($imc < 18.5) return 'Bajo peso';
        if ($imc < 25) return 'Normal';
        if ($imc < 30) return 'Sobrepeso';
        if ($imc < 35) return 'Obesidad grado I';
        if ($imc < 40) return 'Obesidad grado II';
        return 'Obesidad grado III';
    }

 /**
 * ✅ GUARDAR ONLINE - CON LOGGING DETALLADO DE ERRORES
 */
private function saveOnline(array $historiaData): array
{
    try {
        $response = $this->apiService->post('/historias-clinicas', $historiaData);
        
        if ($response['success']) {
            Log::info('✅ Historia clínica guardada online', [
                'historia_uuid' => $response['data']['uuid']
            ]);
            
            return $response;
        }
        
        Log::warning('⚠️ Error guardando online', [
            'error' => $response['error'] ?? 'Error desconocido'
        ]);
        
        return ['success' => false, 'error' => $response['error'] ?? 'Error desconocido'];
        
    } catch (\Exception $e) {
        // ✅ AGREGAR LOGGING DETALLADO AQUÍ
        Log::error('❌ Error completo guardando historia clínica', [
            'error_message' => $e->getMessage(),
            'paciente_id' => $historiaData['paciente_id'] ?? 'NO_DEFINIDO',
            'cita_uuid' => $historiaData['cita_uuid'] ?? 'NO_DEFINIDO',
            'sede_id' => $historiaData['sede_id'] ?? 'NO_DEFINIDO',
            'usuario_id' => $historiaData['usuario_id'] ?? 'NO_DEFINIDO'
        ]);
        
        // ✅ EXTRAER ERRORES DE VALIDACIÓN DETALLADOS
        if (strpos($e->getMessage(), '{') !== false) {
            $errorStart = strpos($e->getMessage(), '{');
            $errorJson = substr($e->getMessage(), $errorStart);
            
            try {
                $errorData = json_decode($errorJson, true);
                Log::error('❌ ERRORES DE VALIDACIÓN DETALLADOS', [
                    'validation_errors' => $errorData,
                    'errors_array' => $errorData['errors'] ?? 'NO_ERRORS_KEY',
                    'message' => $errorData['message'] ?? 'NO_MESSAGE',
                    'status_code' => $errorData['status_code'] ?? 'NO_STATUS'
                ]);
            } catch (\Exception $jsonError) {
                Log::error('❌ No se pudo parsear JSON del error', [
                    'json_error' => $jsonError->getMessage(),
                    'raw_error' => $errorJson
                ]);
            }
        }
        
        Log::error('❌ Excepción guardando online', [
            'error' => $e->getMessage()
        ]);
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


    /**
     * ✅ GUARDAR OFFLINE
     */
    private function saveOffline(array $historiaData, bool $needsSync = true): array
    {
        try {
            // ✅ GENERAR UUID SI NO EXISTE
            if (!isset($historiaData['uuid'])) {
                $historiaData['uuid'] = \Illuminate\Support\Str::uuid();
            }
            
            $historiaData['sync_status'] = $needsSync ? 'pending' : 'synced';
            $historiaData['created_at'] = now()->toISOString();
            $historiaData['updated_at'] = now()->toISOString();
            
            // ✅ GUARDAR EN OFFLINE SERVICE
            $this->offlineService->storeHistoriaClinicaOffline($historiaData, $needsSync);
            
            Log::info('✅ Historia clínica guardada offline', [
                'historia_uuid' => $historiaData['uuid'],
                'needs_sync' => $needsSync
            ]);
            
            return [
                'success' => true,
                'data' => $historiaData,
                'message' => $needsSync ? 'Guardada offline - se sincronizará' : 'Guardada offline'
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error guardando offline', [
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ✅ OBTENER DATOS MAESTROS PARA EL FORMULARIO
     */
  private function getMasterDataForForm(): array
{
    try {
        $masterData = $this->offlineService->getMasterDataOffline();
        
        // ✅ USAR MÉTODO HÍBRIDO CORREGIDO
        $datosMaestros = $this->obtenerDatosMaestrosHibrido();
        
        return array_merge($masterData, $datosMaestros);
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo datos maestros', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

    /**
     * ✅ OBTENER MEDICAMENTOS OFFLINE
     */
    private function getMedicamentosOffline(): array
    {
        try {
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/medicamentos');
                if ($response['success']) {
                    return $response['data'];
                }
            }
            
            // ✅ FALLBACK A OFFLINE
            return $this->offlineService->getFromSQLite('medicamentos');
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo medicamentos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ OBTENER DIAGNÓSTICOS OFFLINE
     */
    private function getDiagnosticosOffline(): array
    {
        try {
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/diagnosticos');
                if ($response['success']) {
                    return $response['data'];
                }
            }
            
            // ✅ FALLBACK A OFFLINE
            return $this->offlineService->getFromSQLite('diagnosticos');
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo diagnósticos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ OBTENER REMISIONES OFFLINE
     */
    private function getRemisionesOffline(): array
    {
        try {
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/remisiones');
                if ($response['success']) {
                    return $response['data'];
                }
            }
            
            // ✅ FALLBACK A OFFLINE
            return $this->offlineService->getFromSQLite('remisiones');
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo remisiones', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ OBTENER CUPS OFFLINE
     */
    private function getCupsOffline(): array
    {
        try {
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/cups');
                if ($response['success']) {
                    return $response['data'];
                }
            }
            
            // ✅ FALLBACK A OFFLINE
            return $this->offlineService->getCupsActivosOffline();
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo CUPS', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ BUSCAR MEDICAMENTOS AJAX
     */
    public function buscarMedicamentos(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'Término de búsqueda muy corto'
                ]);
            }
            
            $medicamentos = [];
            
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/medicamentos/buscar', ['q' => $termino]);
                if ($response['success']) {
                    $medicamentos = $response['data'];
                }
            }
            
            if (empty($medicamentos)) {
                // ✅ BUSCAR OFFLINE
                $medicamentos = $this->offlineService->buscarMedicamentosOffline($termino);
            }
            
            return response()->json([
                'success' => true,
                'data' => $medicamentos
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error buscando medicamentos', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ BUSCAR DIAGNÓSTICOS AJAX
     */
    public function buscarDiagnosticos(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'Término de búsqueda muy corto'
                ]);
            }
            
            $diagnosticos = [];
            
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/diagnosticos/buscar', ['q' => $termino]);
                if ($response['success']) {
                    $diagnosticos = $response['data'];
                }
            }
            
            if (empty($diagnosticos)) {
                // ✅ BUSCAR OFFLINE
                $diagnosticos = $this->offlineService->buscarDiagnosticosOffline($termino);
            }
            
            return response()->json([
                'success' => true,
                'data' => $diagnosticos
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error buscando diagnósticos', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ BUSCAR CUPS AJAX
     */
    public function buscarCups(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'Término de búsqueda muy corto'
                ]);
            }
            
            $cups = $this->offlineService->buscarCupsOffline($termino, 20);
            
            return response()->json([
                'success' => true,
                'data' => $cups
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error buscando CUPS', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ BUSCAR REMISIONES AJAX
     */
    public function buscarRemisiones(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'Término de búsqueda muy corto'
                ]);
            }
            
            $remisiones = [];
            
            if ($this->apiService->isOnline()) {
                $response = $this->apiService->get('/remisiones/buscar', ['q' => $termino]);
                if ($response['success']) {
                    $remisiones = $response['data'];
                }
            }
            
            if (empty($remisiones)) {
                // ✅ BUSCAR OFFLINE
                $remisiones = $this->offlineService->buscarRemisionesOffline($termino);
            }
            
            return response()->json([
                'success' => true,
                'data' => $remisiones
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error buscando remisiones', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }


    /**
 * ✅ DETERMINAR TIPO DE CONSULTA INTELIGENTEMENTE
 */
private function determinarTipoConsulta(string $citaUuid, string $pacienteUuid): string
{
    try {
        Log::info('🧠 Determinando tipo de consulta inteligente', [
            'cita_uuid' => $citaUuid,
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ VERIFICAR SI EL PACIENTE YA TIENE HISTORIAS CLÍNICAS
        $tieneHistoriasAnteriores = $this->verificarHistoriasAnteriores($pacienteUuid);
        
        if ($tieneHistoriasAnteriores) {
            Log::info('✅ Paciente con historias anteriores - CONTROL', [
                'paciente_uuid' => $pacienteUuid
            ]);
            return 'CONTROL';
        }

        Log::info('✅ Paciente sin historias anteriores - PRIMERA VEZ', [
            'paciente_uuid' => $pacienteUuid
        ]);
        
        return 'PRIMERA VEZ';

    } catch (\Exception $e) {
        Log::error('❌ Error determinando tipo consulta, usando PRIMERA VEZ por defecto', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid
        ]);
        
        return 'PRIMERA VEZ'; // ✅ FALLBACK SEGURO
    }
}

/**
 * ✅ VERIFICAR SI EL PACIENTE TIENE HISTORIAS CLÍNICAS ANTERIORES
 */
private function verificarHistoriasAnteriores(string $pacienteUuid): bool
{
    try {
        // ✅ 1. INTENTAR VERIFICAR ONLINE PRIMERO
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get("/pacientes/{$pacienteUuid}/historias-clinicas");
            
            if ($response['success'] && !empty($response['data'])) {
                Log::info('✅ Historias encontradas online', [
                    'paciente_uuid' => $pacienteUuid,
                    'count' => count($response['data'])
                ]);
                return true;
            }
        }

        // ✅ 2. VERIFICAR EN DATOS OFFLINE
        $historiasOffline = $this->offlineService->getHistoriasClinicasByPaciente($pacienteUuid);
        
        if (!empty($historiasOffline)) {
            Log::info('✅ Historias encontradas offline', [
                'paciente_uuid' => $pacienteUuid,
                'count' => count($historiasOffline)
            ]);
            return true;
        }

        // ✅ 3. VERIFICAR EN SQLITE SI EXISTE EL MÉTODO
        try {
            $historiasSQL = $this->offlineService->buscarHistoriasEnSQLite($pacienteUuid);
            if (!empty($historiasSQL)) {
                Log::info('✅ Historias encontradas en SQLite', [
                    'paciente_uuid' => $pacienteUuid,
                    'count' => count($historiasSQL)
                ]);
                return true;
            }
        } catch (\Exception $sqliteError) {
            Log::debug('ℹ️ No se pudo verificar SQLite (normal si no existe)', [
                'error' => $sqliteError->getMessage()
            ]);
        }

        return false;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando historias anteriores', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid
        ]);
        
        return false; // ✅ FALLBACK: asumir primera vez
    }
}
public function determinarVista(Request $request, string $citaUuid)
{
    try {
        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();

        Log::info('🔍 FRONTEND: Determinando vista de historia clínica', [
            'cita_uuid' => $citaUuid,
            'usuario' => $usuario['nombre_completo']
        ]);

        // ✅ CONSULTAR AL BACKEND PARA DETERMINAR LA VISTA
        if ($this->apiService->isOnline()) {
            $response = $this->apiService->get("/historias-clinicas/determinar-vista/{$citaUuid}");
            
            if ($response['success']) {
                $data = $response['data'];
                
                // ✅ VERIFICAR SI ES ESPECIALIDAD SOLO-CONTROL
                $esSoloControl = in_array($data['especialidad'], ['NEFROLOGIA', 'INTERNISTA']);
                
                if ($esSoloControl) {
                    Log::info('🔒 Especialidad solo-control detectada desde API', [
                        'especialidad' => $data['especialidad'],
                        'tipo_consulta_original' => $data['tipo_consulta'],
                        'tipo_consulta_forzado' => 'CONTROL'
                    ]);
                    
                    // ✅ FORZAR TIPO CONTROL
                    $data['tipo_consulta'] = 'CONTROL';
                    $data['vista_recomendada']['tipo_consulta'] = 'CONTROL';
                    $data['vista_recomendada']['solo_control'] = true;
                }
                
                Log::info('✅ Vista determinada por API', [
                    'especialidad' => $data['especialidad'],
                    'tipo_consulta' => $data['tipo_consulta'],
                    'vista_recomendada' => $data['vista_recomendada']['vista'],
                    'tiene_historia_previa' => !empty($data['historia_previa']),
                    'es_solo_control' => $esSoloControl
                ]);

                // ✅ FORMATEAR HISTORIA PREVIA SI EXISTE
                $historiaPrevia = null;
                if (!empty($data['historia_previa'])) {
                    $historiaPrevia = $this->formatearHistoriaDesdeAPI($data['historia_previa']);
                    
                    Log::info('🔄 Historia previa formateada desde API', [
                        'campos_formateados' => count($historiaPrevia),
                        'tiene_medicamentos' => !empty($historiaPrevia['medicamentos']),
                        'tiene_diagnosticos' => !empty($historiaPrevia['diagnosticos']),
                        'tiene_test_morisky' => isset($historiaPrevia['test_morisky_olvida_tomar_medicamentos'])
                    ]);
                }

                return $this->renderizarVistaEspecifica(
                    $data['vista_recomendada'],
                    $data['cita'],
                    $historiaPrevia,
                    $usuario,
                    $isOffline
                );
            }
        }

        // ✅ FALLBACK OFFLINE
        Log::warning('⚠️ API offline, usando determinación local');
        
        $citaResult = $this->citaService->show($citaUuid);
        if (!$citaResult['success']) {
            return back()->with('error', 'Cita no encontrada');
        }

        $cita = $citaResult['data'];
        $especialidad = $this->obtenerEspecialidadMedico($cita);
        $pacienteUuid = $cita['paciente_uuid'] ?? $cita['paciente']['uuid'] ?? null;
        
        if (!$pacienteUuid) {
            return back()->with('error', 'No se pudo obtener información del paciente');
        }

        // ✅ DETERMINAR TIPO CONSULTA (YA MANEJA SOLO-CONTROL INTERNAMENTE)
        $tipoConsulta = $this->determinarTipoConsultaOffline($pacienteUuid, $especialidad ?? 'MEDICINA GENERAL');
        
        // ✅ VERIFICAR SI ES ESPECIALIDAD SOLO-CONTROL
        $esSoloControl = in_array($especialidad, ['NEFROLOGIA', 'INTERNISTA']);
        
        $vistaInfo = [
            'vista' => $this->determinarVistaOffline($especialidad, $tipoConsulta),
            'usa_complementaria' => in_array($especialidad, [
                'REFORMULACION', 'NUTRICIONISTA', 'PSICOLOGIA', 'NEFROLOGIA', 
                'INTERNISTA', 'FISIOTERAPIA', 'TRABAJO SOCIAL'
            ]),
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'solo_control' => $esSoloControl // ✅ NUEVO FLAG
        ];

        Log::info('✅ Vista determinada offline', [
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'vista' => $vistaInfo['vista'],
            'es_solo_control' => $esSoloControl
        ]);

        // ✅ OBTENER HISTORIA PREVIA SOLO PARA CONTROL
        $historiaPrevia = null;
        if ($tipoConsulta === 'CONTROL') {
            // ✅ PARA MEDICINA GENERAL, USAR EL MÉTODO ESPECÍFICO
            if ($especialidad === 'MEDICINA GENERAL') {
                $historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);
                Log::info('🔄 Historia previa offline para Medicina General', [
                    'tiene_historia' => !empty($historiaPrevia)
                ]);
            } else {
                // ✅ PARA OTRAS ESPECIALIDADES (NEFROLOGIA, INTERNISTA, ETC)
                $historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);
                Log::info('🔄 Historia previa offline para especialidad', [
                    'especialidad' => $especialidad,
                    'tiene_historia' => !empty($historiaPrevia)
                ]);
            }
        }

        return $this->renderizarVistaEspecifica($vistaInfo, $cita, $historiaPrevia, $usuario, $isOffline);

    } catch (\Exception $e) {
        Log::error('❌ Error determinando vista de historia clínica', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid,
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);

        return back()->with('error', 'Error determinando el tipo de historia clínica');
    }
}
private function formatearHistoriaDesdeAPI(array $historiaAPI): array
{
    try {
        Log::info('🔧 Formateando historia desde API', [
            'keys_disponibles' => array_keys($historiaAPI),
            'tiene_medicamentos' => !empty($historiaAPI['medicamentos']),
            'tiene_diagnosticos' => !empty($historiaAPI['diagnosticos'])
        ]);

        $historiaFormateada = [
            // ✅ TEST DE MORISKY
            'test_morisky_olvida_tomar_medicamentos' => $historiaAPI['test_morisky_olvida_tomar_medicamentos'] ?? $historiaAPI['olvida_tomar_medicamentos'] ?? 'NO',
            'test_morisky_toma_medicamentos_hora_indicada' => $historiaAPI['test_morisky_toma_medicamentos_hora_indicada'] ?? $historiaAPI['toma_medicamentos_hora_indicada'] ?? 'NO',
            'test_morisky_cuando_esta_bien_deja_tomar_medicamentos' => $historiaAPI['test_morisky_cuando_esta_bien_deja_tomar_medicamentos'] ?? $historiaAPI['cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO',
            'test_morisky_siente_mal_deja_tomarlos' => $historiaAPI['test_morisky_siente_mal_deja_tomarlos'] ?? $historiaAPI['siente_mal_deja_tomarlos'] ?? 'NO',
            'test_morisky_valoracio_psicologia' => $historiaAPI['test_morisky_valoracio_psicologia'] ?? $historiaAPI['valoracion_psicologia'] ?? 'NO',
            'adherente' => $historiaAPI['adherente'] ?? 'NO',

            // ✅ ANTECEDENTES PERSONALES
            'hipertension_arterial_personal' => $historiaAPI['hipertension_arterial_personal'] ?? 'NO',
            'obs_hipertension_arterial_personal' => $historiaAPI['obs_hipertension_arterial_personal'] ?? $historiaAPI['obs_personal_hipertension_arterial'] ?? '',
            'diabetes_mellitus_personal' => $historiaAPI['diabetes_mellitus_personal'] ?? 'NO',
            'obs_diabetes_mellitus_personal' => $historiaAPI['obs_diabetes_mellitus_personal'] ?? $historiaAPI['obs_personal_mellitus'] ?? '',

            // ✅ CLASIFICACIONES
            'clasificacion_estado_metabolico' => $historiaAPI['clasificacion_estado_metabolico'] ?? '',
            'clasificacion_hta' => $historiaAPI['clasificacion_hta'] ?? '',
            'clasificacion_dm' => $historiaAPI['clasificacion_dm'] ?? '',
            'clasificacion_rcv' => $historiaAPI['clasificacion_rcv'] ?? '',
            'clasificacion_erc_estado' => $historiaAPI['clasificacion_erc_estado'] ?? '',
            'clasificacion_erc_categoria_ambulatoria_persistente' => $historiaAPI['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '',

            // ✅ TASAS DE FILTRACIÓN
            'tasa_filtracion_glomerular_ckd_epi' => $historiaAPI['tasa_filtracion_glomerular_ckd_epi'] ?? '',
            'tasa_filtracion_glomerular_gockcroft_gault' => $historiaAPI['tasa_filtracion_glomerular_gockcroft_gault'] ?? '',

            // ✅ TALLA
            'talla' => $historiaAPI['talla'] ?? '',

            // ✅ MEDICAMENTOS - USAR NOMBRES CORRECTOS DEL API
            'medicamentos' => $this->formatearMedicamentosDesdeAPI($historiaAPI['medicamentos'] ?? []),

            // ✅ REMISIONES - USAR NOMBRES CORRECTOS DEL API
            'remisiones' => $this->formatearRemisionesDesdeAPI($historiaAPI['remisiones'] ?? []),

            // ✅ DIAGNÓSTICOS - USAR NOMBRES CORRECTOS DEL API
            'diagnosticos' => $this->formatearDiagnosticosDesdeAPI($historiaAPI['diagnosticos'] ?? []),

            // ✅ CUPS - USAR NOMBRES CORRECTOS DEL API
            'cups' => $this->formatearCupsDesdeAPI($historiaAPI['cups'] ?? []),

            // ✅✅✅ NUEVOS CAMPOS DE EDUCACIÓN ✅✅✅
            'alimentacion' => $historiaAPI['alimentacion'] ?? 'NO',
            'disminucion_consumo_sal_azucar' => $historiaAPI['disminucion_consumo_sal_azucar'] ?? 'NO',
            'fomento_actividad_fisica' => $historiaAPI['fomento_actividad_fisica'] ?? 'NO',
            'importancia_adherencia_tratamiento' => $historiaAPI['importancia_adherencia_tratamiento'] ?? 'NO',
            'consumo_frutas_verduras' => $historiaAPI['consumo_frutas_verduras'] ?? 'NO',
            'manejo_estres' => $historiaAPI['manejo_estres'] ?? 'NO',
            'disminucion_consumo_cigarrillo' => $historiaAPI['disminucion_consumo_cigarrillo'] ?? 'NO',
            'disminucion_peso' => $historiaAPI['disminucion_peso'] ?? 'NO',
        ];

        Log::info('✅ Historia formateada desde API', [
            'campos_totales' => count($historiaFormateada),
            'medicamentos_count' => count($historiaFormateada['medicamentos']),
            'diagnosticos_count' => count($historiaFormateada['diagnosticos']),
            'remisiones_count' => count($historiaFormateada['remisiones']),
            'cups_count' => count($historiaFormateada['cups']),
            'tiene_talla' => !empty($historiaFormateada['talla']),
            'tiene_clasificacion_metabolica' => !empty($historiaFormateada['clasificacion_estado_metabolico']),
            // ✅ VERIFICAR EDUCACIÓN
            'tiene_educacion' => !empty($historiaFormateada['alimentacion'])
        ]);

        return $historiaFormateada;

    } catch (\Exception $e) {
        Log::error('❌ Error formateando historia desde API', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}



// ✅ MÉTODOS AUXILIARES DE FORMATEO
private function formatearMedicamentosDesdeAPI(array $medicamentos): array
{
    return array_map(function($medicamento) {
        return [
            'medicamento_id' => $medicamento['medicamento_id'] ?? $medicamento['medicamento']['uuid'] ?? $medicamento['medicamento']['id'],
            'cantidad' => $medicamento['cantidad'] ?? '',
            'dosis' => $medicamento['dosis'] ?? '',
            'medicamento' => [
                'uuid' => $medicamento['medicamento']['uuid'] ?? $medicamento['medicamento']['id'],
                'nombre' => $medicamento['medicamento']['nombre'] ?? '',
                'principio_activo' => $medicamento['medicamento']['principio_activo'] ?? ''
            ]
        ];
    }, $medicamentos);
}

private function formatearRemisionesDesdeAPI(array $remisiones): array
{
    return array_map(function($remision) {
        return [
            'remision_id' => $remision['remision_id'] ?? $remision['remision']['uuid'] ?? $remision['remision']['id'],
            'observacion' => $remision['observacion'] ?? '',
            'remision' => [
                'uuid' => $remision['remision']['uuid'] ?? $remision['remision']['id'],
                'nombre' => $remision['remision']['nombre'] ?? '',
                'tipo' => $remision['remision']['tipo'] ?? ''
            ]
        ];
    }, $remisiones);
}

private function formatearDiagnosticosDesdeAPI(array $diagnosticos): array
{
    return array_map(function($diagnostico) {
        return [
            'diagnostico_id' => $diagnostico['diagnostico_id'] ?? $diagnostico['diagnostico']['uuid'] ?? $diagnostico['diagnostico']['id'],
            'tipo' => $diagnostico['tipo'] ?? 'PRINCIPAL',
            'tipo_diagnostico' => $diagnostico['tipo_diagnostico'] ?? '',
            'diagnostico' => [
                'uuid' => $diagnostico['diagnostico']['uuid'] ?? $diagnostico['diagnostico']['id'],
                'codigo' => $diagnostico['diagnostico']['codigo'] ?? '',
                'nombre' => $diagnostico['diagnostico']['nombre'] ?? ''
            ]
        ];
    }, $diagnosticos);
}

private function formatearCupsDesdeAPI(array $cups): array
{
    return array_map(function($cup) {
        return [
            'cups_id' => $cup['cups_id'] ?? $cup['cups']['uuid'] ?? $cup['cups']['id'],
            'observacion' => $cup['observacion'] ?? '',
            'cups' => [
                'uuid' => $cup['cups']['uuid'] ?? $cup['cups']['id'],
                'codigo' => $cup['cups']['codigo'] ?? '',
                'nombre' => $cup['cups']['nombre'] ?? ''
            ]
        ];
    }, $cups);
}

/**
 * ✅ RENDERIZAR VISTA ESPECÍFICA
 */
private function renderizarVistaEspecifica(array $vistaInfo, array $cita, ?array $historiaPrevia, array $usuario, bool $isOffline)
{
    try {
        $vista = $vistaInfo['vista'];
        $especialidad = $vistaInfo['especialidad'];
        $tipoConsulta = $vistaInfo['tipo_consulta'];
        
        Log::info('🎨 Renderizando vista específica', [
            'vista' => $vista,
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'tiene_historia_previa' => !empty($historiaPrevia),
            'es_medicina_general' => $especialidad === 'MEDICINA GENERAL'
        ]);

        // ✅ OBTENER DATOS MAESTROS
        $masterData = $this->getMasterDataForForm();

        // ✅ DATOS COMUNES PARA TODAS LAS VISTAS
        $datosComunes = [
            'cita' => $cita,
            'usuario' => $usuario,
            'isOffline' => $isOffline,
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'historiaPrevia' => $historiaPrevia, // ✅ Solo para Medicina General
            'masterData' => $masterData,
            'vistaInfo' => $vistaInfo
        ];

        // ✅ RENDERIZAR VISTA ESPECÍFICA
        return view("historia-clinica.{$vista}", $datosComunes);

    } catch (\Exception $e) {
        Log::error('❌ Error renderizando vista específica', [
            'error' => $e->getMessage(),
            'vista' => $vistaInfo['vista'] ?? 'N/A'
        ]);

        return back()->with('error', 'Error cargando la vista de historia clínica');
    }
}

/**
 * ✅ OBTENER ESPECIALIDAD DEL MÉDICO DE LA CITA
 */
private function obtenerEspecialidadMedico(array $cita): ?string
{
    $especialidad = $cita['agenda']['medico']['especialidad']['nombre'] ?? 
                   $cita['medico']['especialidad']['nombre'] ?? 
                   $cita['especialidad']['nombre'] ?? 
                   $cita['especialidad_nombre'] ?? 
                   null;

    Log::info('🔍 Especialidad detectada', [
        'especialidad' => $especialidad
    ]);

    return $especialidad;
}

/**
 * ✅ DETERMINAR TIPO DE CONSULTA POR ESPECIALIDAD
 */
private function determinarTipoConsultaPorEspecialidad(string $pacienteUuid, string $especialidad): string
{
    try {
        $tieneHistoriasEspecialidad = $this->verificarHistoriasAnterioresPorEspecialidad($pacienteUuid, $especialidad);
        
        return $tieneHistoriasEspecialidad ? 'CONTROL' : 'PRIMERA VEZ';

    } catch (\Exception $e) {
        Log::error('❌ Error determinando tipo consulta por especialidad', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return 'PRIMERA VEZ'; // Fallback seguro
    }
}

/**
 * ✅ VERIFICAR HISTORIAS ANTERIORES POR ESPECIALIDAD
 */
private function verificarHistoriasAnterioresPorEspecialidad(string $pacienteUuid, string $especialidad): bool
{
    try {
        $count = \App\Models\HistoriaClinica::whereHas('cita', function($query) use ($pacienteUuid) {
            $query->whereHas('paciente', function($q) use ($pacienteUuid) {
                $q->where('uuid', $pacienteUuid);
            });
        })
        ->whereHas('cita.agenda.usuarioMedico.especialidad', function($query) use ($especialidad) {
            $query->where('nombre', $especialidad);
        })
        ->count();

        return $count > 0;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando historias por especialidad', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return false;
    }
}


/**
 * ✅ OBTENER ÚLTIMA HISTORIA POR ESPECIALIDAD
 */
private function obtenerUltimaHistoriaPorEspecialidad(string $pacienteUuid, string $especialidad): ?array
{
    try {
        $historia = \App\Models\HistoriaClinica::with([
            'sede',
            'cita.paciente',
            'historiaDiagnosticos.diagnostico',
            'historiaMedicamentos.medicamento'
        ])
        ->whereHas('cita', function($query) use ($pacienteUuid) {
            $query->whereHas('paciente', function($q) use ($pacienteUuid) {
                $q->where('uuid', $pacienteUuid);
            });
        })
        ->whereHas('cita.agenda.usuarioMedico.especialidad', function($query) use ($especialidad) {
            $query->where('nombre', $especialidad);
        })
        ->orderBy('created_at', 'desc')
        ->first();

        return $historia ? $historia->toArray() : null;

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo última historia por especialidad', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return null;
    }
}

/**
 * ✅ DETERMINAR VISTA ESPECÍFICA SEGÚN ESPECIALIDAD
 */
private function determinarVistaEspecifica(string $especialidad, string $tipoConsulta, array $cita, ?array $historiaPrevia, array $masterData, array $usuario, bool $isOffline)
{
    // ✅ ESPECIALIDADES QUE USAN TABLA COMPLEMENTARIA
    $especialidadesConComplementaria = [
        'REFORMULACION',
        'NUTRICIONISTA', 
        'PSICOLOGIA',
        'NEFROLOGIA',
        'INTERNISTA',
        'FISIOTERAPIA',
        'TRABAJO SOCIAL'
    ];

    $usaComplementaria = in_array($especialidad, $especialidadesConComplementaria);

    // ✅ DETERMINAR VISTA SEGÚN ESPECIALIDAD Y TIPO
    switch ($especialidad) {
        case 'MEDICINA GENERAL':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.medicina-general.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData'
                ));
            } else {
                return view('historia-clinica.medicina-general.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia'
                ));
            }

        case 'REFORMULACION':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.reformulacion.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.reformulacion.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'NUTRICIONISTA':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.nutricionista.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.nutricionista.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'PSICOLOGIA':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.psicologia.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.psicologia.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'NEFROLOGIA':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.nefrologia.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.nefrologia.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'INTERNISTA':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.internista.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.internista.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'FISIOTERAPIA':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.fisioterapia.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.fisioterapia.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        case 'TRABAJO SOCIAL':
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.trabajo-social.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'usaComplementaria'
                ));
            } else {
                return view('historia-clinica.trabajo-social.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia', 'usaComplementaria'
                ));
            }

        default:
            // ✅ FALLBACK A MEDICINA GENERAL
            Log::warning('⚠️ Especialidad no reconocida, usando Medicina General', [
                'especialidad' => $especialidad
            ]);
            
            if ($tipoConsulta === 'PRIMERA VEZ') {
                return view('historia-clinica.medicina-general.primera-vez', compact(
                    'cita', 'usuario', 'isOffline', 'masterData'
                ));
            } else {
                return view('historia-clinica.medicina-general.control', compact(
                    'cita', 'usuario', 'isOffline', 'masterData', 'historiaPrevia'
                ));
            }
    }
}
/**
 * ✅✅✅ OBTENER ESPECIALIDAD DESDE CITA - VERSIÓN CORREGIDA ✅✅✅
 */
private function obtenerEspecialidadDesdeCita(string $citaUuid): ?string
{
    try {
        Log::info('🔍 Obteniendo especialidad desde cita', [
            'cita_uuid' => $citaUuid
        ]);
        
        // ✅ 1. OBTENER LA CITA
        $citaResult = $this->citaService->show($citaUuid);
        
        if (!$citaResult['success']) {
            Log::warning('⚠️ No se pudo obtener la cita', [
                'cita_uuid' => $citaUuid
            ]);
            return null;
        }
        
        $cita = $citaResult['data'];
        
        // ✅ 2. BUSCAR ESPECIALIDAD EN LA CITA DIRECTAMENTE
        $especialidad = $cita['agenda']['proceso']['nombre'] ?? 
                       $cita['proceso']['nombre'] ?? 
                       $cita['agenda']['medico']['especialidad']['nombre'] ?? 
                       $cita['agenda']['usuario_medico']['especialidad']['nombre'] ?? 
                       $cita['medico']['especialidad']['nombre'] ?? 
                       $cita['especialidad']['nombre'] ?? 
                       $cita['especialidad_nombre'] ?? 
                       null;
        
        if ($especialidad) {
            Log::info('✅ Especialidad encontrada en cita', [
                'especialidad' => $especialidad
            ]);
            return $especialidad;
        }
        
        // ✅ 3. BUSCAR EN LA AGENDA
        $agendaUuid = $cita['agenda_uuid'] ?? $cita['agenda']['uuid'] ?? null;
        
        if (!$agendaUuid) {
            Log::warning('⚠️ No se encontró agenda_uuid en la cita');
            return null;
        }
        
        Log::info('🔍 Buscando especialidad en agenda', [
            'agenda_uuid' => $agendaUuid
        ]);
        
        // ✅ 4. BUSCAR EN AGENDA OFFLINE (JSON)
        $agendaPath = storage_path("app/offline/agendas/{$agendaUuid}.json");
        
        if (file_exists($agendaPath)) {
            $agendaContent = file_get_contents($agendaPath);
            $agenda = json_decode($agendaContent, true);
            
            if ($agenda && json_last_error() === JSON_ERROR_NONE) {
                // 🔥 BUSCAR EN PROCESO PRIMERO (es donde está en tu caso)
                $especialidad = $agenda['proceso']['nombre'] ?? 
                               $agenda['usuario_medico']['especialidad']['nombre'] ?? 
                               $agenda['medico']['especialidad']['nombre'] ?? 
                               $agenda['usuario']['especialidad']['nombre'] ?? 
                               $agenda['especialidad']['nombre'] ?? 
                               null;
                
                if ($especialidad) {
                    Log::info('✅ Especialidad encontrada en agenda offline (JSON)', [
                        'especialidad' => $especialidad,
                        'agenda_uuid' => $agendaUuid,
                        'fuente' => 'proceso'
                    ]);
                    return $especialidad;
                }
            }
        }
        
        // ✅ 5. BUSCAR EN SQLITE
        try {
            $agendaOffline = $this->offlineService->getAgendaOffline($agendaUuid);
            
            if ($agendaOffline) {
                $especialidad = $agendaOffline['proceso']['nombre'] ?? 
                               $agendaOffline['usuario_medico']['especialidad']['nombre'] ?? 
                               $agendaOffline['medico']['especialidad']['nombre'] ?? 
                               $agendaOffline['usuario']['especialidad']['nombre'] ?? 
                               $agendaOffline['especialidad']['nombre'] ?? 
                               null;
                
                if ($especialidad) {
                    Log::info('✅ Especialidad encontrada en SQLite', [
                        'especialidad' => $especialidad,
                        'agenda_uuid' => $agendaUuid
                    ]);
                    return $especialidad;
                }
            }
        } catch (\Exception $offlineError) {
            Log::debug('ℹ️ No se pudo buscar en SQLite', [
                'error' => $offlineError->getMessage()
            ]);
        }
        
        // ✅ 6. ÚLTIMO INTENTO: CONSULTAR AGENDA AL API
        if ($this->apiService->isOnline()) {
            try {
                $agendaResponse = $this->apiService->get("/agendas/{$agendaUuid}");
                
                if ($agendaResponse['success']) {
                    $agendaAPI = $agendaResponse['data'];
                    
                    $especialidad = $agendaAPI['proceso']['nombre'] ?? 
                                   $agendaAPI['usuario_medico']['especialidad']['nombre'] ?? 
                                   $agendaAPI['medico']['especialidad']['nombre'] ?? 
                                   $agendaAPI['usuario']['especialidad']['nombre'] ?? 
                                   $agendaAPI['especialidad']['nombre'] ?? 
                                   null;
                    
                    if ($especialidad) {
                        Log::info('✅ Especialidad encontrada en agenda desde API', [
                            'especialidad' => $especialidad,
                            'agenda_uuid' => $agendaUuid
                        ]);
                        return $especialidad;
                    }
                }
            } catch (\Exception $apiError) {
                Log::debug('ℹ️ No se pudo consultar agenda al API', [
                    'error' => $apiError->getMessage()
                ]);
            }
        }
        
        Log::warning('⚠️ No se pudo encontrar la especialidad en ninguna fuente', [
            'cita_uuid' => $citaUuid,
            'agenda_uuid' => $agendaUuid
        ]);
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo especialidad desde cita', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid,
            'trace' => $e->getTraceAsString()
        ]);
        
        return null;
    }
}


private function determinarVistaOffline(string $especialidad, string $tipoConsulta): string
{
    // ✅ ESPECIALIDADES QUE SOLO TIENEN CONTROL
    $especialidadesSoloControl = ['NEFROLOGIA', 'INTERNISTA'];
    
    // ✅ SI ES UNA ESPECIALIDAD SOLO-CONTROL, FORZAR TIPO CONTROL
    if (in_array($especialidad, $especialidadesSoloControl)) {
        $tipoConsulta = 'CONTROL';
        
        Log::info('🔒 OFFLINE: Especialidad solo-control detectada', [
            'especialidad' => $especialidad,
            'tipo_consulta_forzado' => 'CONTROL'
        ]);
    }
    
    $vistas = [
        'MEDICINA GENERAL' => [
            'PRIMERA VEZ' => 'medicina-general.primera-vez',
            'CONTROL' => 'medicina-general.control'
        ],
        'REFORMULACION' => [
            'PRIMERA VEZ' => 'reformulacion.primera-vez',
            'CONTROL' => 'reformulacion.control'
        ],
        'NUTRICIONISTA' => [
            'PRIMERA VEZ' => 'nutricionista.primera-vez',
            'CONTROL' => 'nutricionista.control'
        ],
        'PSICOLOGIA' => [
            'PRIMERA VEZ' => 'psicologia.primera-vez',
            'CONTROL' => 'psicologia.control'
        ],
        'NEFROLOGIA' => [
            // ✅ SOLO CONTROL - AMBOS APUNTAN A LA MISMA VISTA
            'PRIMERA VEZ' => 'nefrologia.control',
            'CONTROL' => 'nefrologia.control'
        ],
        'INTERNISTA' => [
            // ✅ SOLO CONTROL - AMBOS APUNTAN A LA MISMA VISTA
            'PRIMERA VEZ' => 'internista.control',
            'CONTROL' => 'internista.control'
        ],
        'FISIOTERAPIA' => [
            'PRIMERA VEZ' => 'fisioterapia.primera-vez',
            'CONTROL' => 'fisioterapia.control'
        ],
        'TRABAJO SOCIAL' => [
            'PRIMERA VEZ' => 'trabajo-social.primera-vez',
            'CONTROL' => 'trabajo-social.control'
        ]
    ];

    return $vistas[$especialidad][$tipoConsulta] ?? $vistas['MEDICINA GENERAL'][$tipoConsulta];
}

/**
 * ✅ DETERMINAR TIPO CONSULTA OFFLINE - VERSIÓN CORREGIDA
 */
private function determinarTipoConsultaOffline(string $pacienteUuid, ?string $especialidad = null): string
{
    try {
        Log::info('🔍 OFFLINE: Determinando tipo de consulta', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        $especialidadFinal = $especialidad ?? 'MEDICINA GENERAL';
        
        // ✅ ESPECIALIDADES QUE SOLO TIENEN CONTROL
        $especialidadesSoloControl = ['NEFROLOGIA', 'INTERNISTA'];
        
        // ✅ SI ES UNA ESPECIALIDAD SOLO-CONTROL, RETORNAR CONTROL DIRECTAMENTE
        if (in_array($especialidadFinal, $especialidadesSoloControl)) {
            Log::info('🔒 OFFLINE: Especialidad solo-control - forzando CONTROL', [
                'especialidad' => $especialidadFinal,
                'tipo_consulta' => 'CONTROL'
            ]);
            return 'CONTROL';
        }
        
        // ✅ VERIFICAR HISTORIAS ANTERIORES OFFLINE (SOLO PARA OTRAS ESPECIALIDADES)
        $tieneHistoriasAnteriores = $this->verificarHistoriasAnterioresOffline($pacienteUuid, $especialidadFinal);
        
        $tipoConsulta = $tieneHistoriasAnteriores ? 'CONTROL' : 'PRIMERA VEZ';

        Log::info('✅ Tipo de consulta determinado offline', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad_final' => $especialidadFinal,
            'tipo_consulta' => $tipoConsulta,
            'tiene_historias_anteriores' => $tieneHistoriasAnteriores
        ]);

        return $tipoConsulta;

    } catch (\Exception $e) {
        Log::error('❌ Error en determinarTipoConsultaOffline', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        // ✅ FALLBACK: Si es solo-control, retornar CONTROL incluso en error
        if (in_array($especialidad, ['NEFROLOGIA', 'INTERNISTA'])) {
            return 'CONTROL';
        }

        return 'PRIMERA VEZ';
    }
}


/**
 * ✅ VERIFICAR HISTORIAS ANTERIORES OFFLINE
 */
private function verificarHistoriasAnterioresOffline(string $pacienteUuid, string $especialidad): bool
{
    try {
        // ✅ VERIFICAR EN OFFLINE SERVICE
        $historias = $this->offlineService->getHistoriasClinicasByPacienteYEspecialidad($pacienteUuid, $especialidad);
        
        return !empty($historias);

    } catch (\Exception $e) {
        Log::error('❌ Error verificando historias offline', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);
        
        return false;
    }
}

    /**
     * ✅ OBTENER ÚLTIMA HISTORIA OFFLINE
     */
    private function obtenerUltimaHistoriaOffline(string $pacienteUuid, string $especialidad): ?array
    {
        try {
            $historias = $this->offlineService->getHistoriasClinicasByPacienteYEspecialidad($pacienteUuid, $especialidad);
            
            if (empty($historias)) {
                return null;
            }

            // Ordenar por fecha y devolver la más reciente
            usort($historias, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return $historias[0];

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo última historia offline', [
                'error' => $e->getMessage(),
                'paciente_uuid' => $pacienteUuid,
                'especialidad' => $especialidad
            ]);
            
            return null;
        }
    }

    /**
     * ✅ ÍNDICE DE HISTORIAS CLÍNICAS - BÚSQUEDA POR PACIENTE
     */
    public function index(Request $request)
    {
        try {
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            
            Log::info('📋 HistoriaClinicaController@index', [
                'usuario' => $usuario['nombre_completo'],
                'filters' => $request->all()
            ]);

            // ✅ SI ES AJAX, RETORNAR DATOS
            if ($request->ajax()) {
                $filters = $request->only([
                    'documento', 'fecha_desde', 'fecha_hasta', 
                    'especialidad', 'tipo_consulta', 'estado'
                ]);
                
                $page = $request->get('page', 1);
                $perPage = $request->get('per_page', 15);
                
                $result = $this->obtenerHistoriasClinicas($filters, $page, $perPage);
                
                return response()->json($result);
            }

            // ✅ VISTA PRINCIPAL
            $masterData = $this->getMasterDataForForm();
            
            return view('historia-clinica.index', compact(
                'usuario', 
                'isOffline', 
                'masterData'
            ));
            
        } catch (\Exception $e) {
            Log::error('❌ Error en HistoriaClinicaController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor',
                    'data' => []
                ], 500);
            }

            return back()->with('error', 'Error cargando historias clínicas');
        }
    }

    /**
     * ✅ OBTENER HISTORIAS CLÍNICAS CON FILTROS
     */
    private function obtenerHistoriasClinicas(array $filters, int $page = 1, int $perPage = 15): array
    {
        try {
            Log::info('🔍 Obteniendo historias clínicas', [
                'filters' => $filters,
                'page' => $page,
                'per_page' => $perPage
            ]);

            // ✅ INTENTAR ONLINE PRIMERO
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/historias-clinicas', array_merge($filters, [
                        'page' => $page,
                        'per_page' => $perPage
                    ]));
                    
                    if ($response['success']) {
                        Log::info('✅ Historias obtenidas desde API', [
                            'count' => count($response['data'] ?? [])
                        ]);
                        
                        return [
                            'success' => true,
                            'data' => $response['data'],
                            'pagination' => $response['pagination'] ?? null,
                            'offline' => false
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error API, usando offline', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ✅ FALLBACK OFFLINE
            return $this->obtenerHistoriasOffline($filters, $page, $perPage);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo historias clínicas', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error obteniendo historias clínicas',
                'data' => []
            ];
        }
    }

    /**
     * ✅ OBTENER HISTORIAS OFFLINE
     */
    private function obtenerHistoriasOffline(array $filters, int $page = 1, int $perPage = 15): array
    {
        try {
            $historias = [];
            $historiasPath = $this->offlineService->getStoragePath() . '/historias-clinicas';
            
            if (!is_dir($historiasPath)) {
                return [
                    'success' => true,
                    'data' => [],
                    'pagination' => null,
                    'offline' => true
                ];
            }

            $files = glob($historiasPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if (!$data) continue;
                
                // ✅ APLICAR FILTROS
                if (!$this->aplicarFiltrosHistoria($data, $filters)) {
                    continue;
                }
                
                // ✅ ENRIQUECER DATOS
                $historias[] = $this->enrichHistoriaForList($data);
            }
            
            // ✅ ORDENAR POR FECHA (MÁS RECIENTE PRIMERO)
            usort($historias, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // ✅ PAGINAR
            $total = count($historias);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($historias, $offset, $perPage);
            
            return [
                'success' => true,
                'data' => $paginatedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ],
                'offline' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo historias offline', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error obteniendo historias offline',
                'data' => []
            ];
        }
    }

    /**
     * ✅ APLICAR FILTROS A HISTORIA (CORREGIDO)
     */
    private function aplicarFiltrosHistoria(array $historia, array $filters): bool
    {
        // Filtro por documento
        if (!empty($filters['documento'])) {
            $documento = $historia['paciente']['documento'] ?? '';
            if (strpos($documento, $filters['documento']) === false) {
                return false;
            }
        }
        
        // ✅ FILTRO POR FECHA DESDE (USANDO created_at)
        if (!empty($filters['fecha_desde'])) {
            $fechaHistoria = $historia['created_at'] ?? '';
            
            // ✅ EXTRAER SOLO LA FECHA (sin hora) para comparación
            $fechaHistoriaSolo = substr($fechaHistoria, 0, 10); // "2024-11-10"
            
            if ($fechaHistoriaSolo < $filters['fecha_desde']) {
                return false;
            }
        }
        
        // ✅ FILTRO POR FECHA HASTA (USANDO created_at)
        if (!empty($filters['fecha_hasta'])) {
            $fechaHistoria = $historia['created_at'] ?? '';
            
            // ✅ EXTRAER SOLO LA FECHA (sin hora) para comparación
            $fechaHistoriaSolo = substr($fechaHistoria, 0, 10); // "2024-11-10"
            
            if ($fechaHistoriaSolo > $filters['fecha_hasta']) {
                return false;
            }
        }
        
        // Filtro por especialidad
        if (!empty($filters['especialidad'])) {
            $especialidad = $historia['especialidad'] ?? '';
            if ($especialidad !== $filters['especialidad']) {
                return false;
            }
        }
        
        // Filtro por tipo consulta
        if (!empty($filters['tipo_consulta'])) {
            $tipoConsulta = $historia['tipo_consulta'] ?? '';
            if ($tipoConsulta !== $filters['tipo_consulta']) {
                return false;
            }
        }
        
        return true;
    }


    /**
     * ✅ ENRIQUECER DATOS DE HISTORIA PARA LISTA
     */
    private function enrichHistoriaForList(array $historia): array
    {
        return [
            'uuid' => $historia['uuid'],
            'paciente' => [
                'nombre_completo' => $historia['paciente']['nombre_completo'] ?? 'N/A',
                'documento' => $historia['paciente']['documento'] ?? 'N/A',
                'tipo_documento' => $historia['paciente']['tipo_documento'] ?? 'CC'
            ],
            'especialidad' => $historia['especialidad'] ?? 'MEDICINA GENERAL',
            'tipo_consulta' => $historia['tipo_consulta'] ?? 'PRIMERA VEZ',
            'profesional' => [
                'nombre_completo' => $historia['usuario']['nombre_completo'] ?? 'N/A'
            ],
            'fecha' => $historia['created_at'] ?? now()->toISOString(),
            'estado' => $historia['estado'] ?? 'FINALIZADA',
            'diagnostico_principal' => $this->obtenerDiagnosticoPrincipal($historia),
            'created_at' => $historia['created_at'],
            'updated_at' => $historia['updated_at'] ?? $historia['created_at']
        ];
    }

    /**
     * ✅ OBTENER DIAGNÓSTICO PRINCIPAL
     */
    private function obtenerDiagnosticoPrincipal(array $historia): ?string
    {
        $diagnosticos = $historia['diagnosticos'] ?? [];
        
        foreach ($diagnosticos as $diagnostico) {
            if (($diagnostico['tipo'] ?? '') === 'PRINCIPAL') {
                return $diagnostico['diagnostico']['nombre'] ?? 
                    $diagnostico['diagnostico']['codigo'] ?? 
                    'Diagnóstico principal';
            }
        }
        
        return !empty($diagnosticos) ? 
            ($diagnosticos[0]['diagnostico']['nombre'] ?? 'Sin diagnóstico') : 
            'Sin diagnóstico';
    }

    /**
     * ✅ BUSCAR HISTORIAS POR DOCUMENTO DE PACIENTE
     */
    public function buscarPorDocumento(Request $request)
    {
        try {
            $request->validate([
                'documento' => 'required|string|min:3'
            ]);

            $documento = $request->documento;
            
            Log::info('🔍 Buscando historias por documento', [
                'documento' => $documento
            ]);

            $filters = ['documento' => $documento];
            $result = $this->obtenerHistoriasClinicas($filters, 1, 50);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('❌ Error buscando historias por documento', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en búsqueda',
                'data' => []
            ], 500);
        }
    }

}

