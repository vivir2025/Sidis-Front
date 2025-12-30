<?php
// app/Http/Controllers/HistoriaClinicaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService, OfflineService, PacienteService, CitaService};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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

        // ✅ OBTENER DATOS DE LA CITA
        $citaResult = $this->citaService->show($citaUuid);
        
        if (!$citaResult['success']) {
            return back()->with('error', 'Cita no encontrada');
        }

        $cita = $citaResult['data'];

        // ✅ VERIFICAR QUE NO TENGA HISTORIA
        if (isset($cita['historia_clinica_uuid'])) {
            return redirect()->route('historia-clinica.show', $cita['historia_clinica_uuid'])
                ->with('info', 'Esta cita ya tiene una historia clínica asociada');
        }

        // ✅ OBTENER ESPECIALIDAD
        $especialidad = $this->obtenerEspecialidadMedico($cita);
        $pacienteUuid = $cita['paciente_uuid'] ?? $cita['paciente']['uuid'] ?? null;
        
        if (!$pacienteUuid) {
            return back()->with('error', 'No se pudo obtener información del paciente');
        }

        // ✅✅✅ DETERMINAR TIPO CONSULTA (PASANDO CITA_ID PARA EXCLUIRLA) ✅✅✅
        $citaId = $cita['id'] ?? $cita['uuid'] ?? null;
        $tipoConsulta = $this->determinarTipoConsultaOffline($pacienteUuid, $especialidad, $citaId);

        // ✅✅✅ CARGAR HISTORIA PREVIA SOLO SI ES CONTROL ✅✅✅
     Log::info('🔄 Cargando historia previa sin importar tipo de consulta', [
    'paciente_uuid' => $pacienteUuid,
    'especialidad' => $especialidad,
    'tipo_consulta' => $tipoConsulta
]);

$historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);

if (!empty($historiaPrevia)) {
    Log::info('✅ Historia previa cargada exitosamente', [
        'tipo_consulta' => $tipoConsulta,
        'especialidad' => $especialidad,
        'medicamentos_count' => count($historiaPrevia['medicamentos'] ?? []),
        'diagnosticos_count' => count($historiaPrevia['diagnosticos'] ?? []),
        'remisiones_count' => count($historiaPrevia['remisiones'] ?? []),
        'cups_count' => count($historiaPrevia['cups'] ?? [])
    ]);
} else {
    Log::info('ℹ️ No se encontró historia previa para cargar', [
        'tipo_consulta' => $tipoConsulta,
        'especialidad' => $especialidad
    ]);
}

        // ✅ OBTENER DATOS MAESTROS
        $masterData = $this->getMasterDataForForm();

        return view('historia-clinica.create', compact(
            'cita',
            'usuario',
            'isOffline',
            'masterData',
            'historiaPrevia',
            'especialidad',
            'tipoConsulta'
        ));

    } catch (\Exception $e) {
        Log::error('❌ Error creando historia clínica', [
            'error' => $e->getMessage()
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
            'usuario' => $usuario['nombre_completo'],
            'is_offline' => $isOffline
        ]);

        $historia = null;
        
        // ✅ 1. PRIORIZAR BÚSQUEDA OFFLINE SI NO HAY CONEXIÓN
        if (!$this->apiService->isOnline()) {
            Log::info('🔌 Modo offline detectado, buscando en almacenamiento local', [
                'historia_uuid' => $uuid
            ]);
            
            $historia = $this->obtenerHistoriaOffline($uuid);
            
            if ($historia) {
                // ✅ FORMATEAR DATOS OFFLINE
                $historia = $this->formatearHistoriaParaVista($historia);
                
                Log::info('✅ Historia obtenida y formateada desde offline', [
                    'historia_uuid' => $uuid,
                    'medicamentos_count' => count($historia['medicamentos'] ?? []),
                    'remisiones_count' => count($historia['remisiones'] ?? []),
                    'diagnosticos_count' => count($historia['diagnosticos'] ?? [])
                ]);
            } else {
                Log::error('❌ Historia no encontrada en modo offline', [
                    'historia_uuid' => $uuid
                ]);
                
                return back()->with('error', 'Historia clínica no encontrada (modo offline)');
            }
        } else {
            // ✅ 2. SI HAY CONEXIÓN, INTENTAR API PRIMERO
            try {
                $response = $this->apiService->get("/historias-clinicas/{$uuid}");
                
                if ($response['success']) {
                    $historia = $response['data'];
                    
                    // ✅ FORMATEAR ARRAYS ANTES DE USAR
                    $historia = $this->formatearHistoriaParaVista($historia);
                    
                    Log::info('✅ Historia obtenida y formateada desde API', [
                        'historia_uuid' => $uuid,
                        'medicamentos_count' => count($historia['medicamentos'] ?? []),
                        'remisiones_count' => count($historia['remisiones'] ?? []),
                        'diagnosticos_count' => count($historia['diagnosticos'] ?? [])
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo historia desde API, intentando offline', [
                    'error' => $e->getMessage()
                ]);
                
                // ✅ FALLBACK OFFLINE SI API FALLA
                $historia = $this->obtenerHistoriaOffline($uuid);
                
                if ($historia) {
                    $historia = $this->formatearHistoriaParaVista($historia);
                    
                    Log::info('✅ Historia obtenida desde offline (fallback)', [
                        'historia_uuid' => $uuid
                    ]);
                }
            }
        }
        
        // ✅ 3. VALIDAR QUE SE ENCONTRÓ LA HISTORIA
        if (!$historia) {
            Log::error('❌ Historia no encontrada ni online ni offline', [
                'historia_uuid' => $uuid
            ]);
            
            return back()->with('error', 'Historia clínica no encontrada');
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
 * ✅ FORMATEAR HISTORIA PARA VISTA (CORRIGE ARRAYS VACÍOS) - VERSIÓN CORREGIDA
 */
private function formatearHistoriaParaVista(array $historia): array
{
    try {
        Log::info('🔧 Formateando historia para vista', [
            'historia_uuid' => $historia['uuid'] ?? 'N/A',
            'tiene_medicamentos_raw' => isset($historia['medicamentos']),
            'medicamentos_raw_count' => isset($historia['medicamentos']) ? count($historia['medicamentos']) : 0,
            'tiene_remisiones_raw' => isset($historia['remisiones']),
            'remisiones_raw_count' => isset($historia['remisiones']) ? count($historia['remisiones']) : 0,
            'tiene_diagnosticos_raw' => isset($historia['diagnosticos']),
            'diagnosticos_raw_count' => isset($historia['diagnosticos']) ? count($historia['diagnosticos']) : 0,
            'tiene_cups_raw' => isset($historia['cups']),
            'cups_raw_count' => isset($historia['cups']) ? count($historia['cups']) : 0
        ]);

        // ✅ FORMATEAR MEDICAMENTOS
        if (isset($historia['medicamentos']) && is_array($historia['medicamentos']) && !empty($historia['medicamentos'])) {
            Log::info('🔧 Formateando medicamentos', [
                'count_antes' => count($historia['medicamentos']),
                'primer_medicamento' => $historia['medicamentos'][0] ?? 'N/A'
            ]);

            $historia['medicamentos'] = array_map(function($item) {
                // ✅ VERIFICAR SI YA ESTÁ FORMATEADO
                if (isset($item['medicamento']) && is_array($item['medicamento']) && isset($item['medicamento']['nombre'])) {
                    return $item;
                }
                
                // ✅ FORMATEAR SI VIENE SIN ESTRUCTURA
                return [
                    'medicamento' => [
                        'nombre' => $item['medicamento']['nombre'] ?? $item['nombre'] ?? 'Medicamento sin nombre',
                        'codigo' => $item['medicamento']['codigo'] ?? $item['codigo'] ?? 'N/A',
                        'principio_activo' => $item['medicamento']['principio_activo'] ?? $item['principio_activo'] ?? 'N/A'
                    ],
                    'cantidad' => $item['cantidad'] ?? 'N/A',
                    'dosis' => $item['dosis'] ?? 'N/A',
                    'via_administracion' => $item['via_administracion'] ?? 'N/A',
                    'frecuencia' => $item['frecuencia'] ?? 'N/A'
                ];
            }, $historia['medicamentos']);

            Log::info('✅ Medicamentos formateados', [
                'count_despues' => count($historia['medicamentos'])
            ]);
        } else {
            Log::warning('⚠️ No hay medicamentos para formatear', [
                'isset' => isset($historia['medicamentos']),
                'is_array' => isset($historia['medicamentos']) ? is_array($historia['medicamentos']) : false,
                'empty' => isset($historia['medicamentos']) ? empty($historia['medicamentos']) : true
            ]);
            $historia['medicamentos'] = [];
        }

        // ✅ FORMATEAR REMISIONES
        if (isset($historia['remisiones']) && is_array($historia['remisiones']) && !empty($historia['remisiones'])) {
            Log::info('🔧 Formateando remisiones', [
                'count_antes' => count($historia['remisiones']),
                'primera_remision' => $historia['remisiones'][0] ?? 'N/A'
            ]);

            $historia['remisiones'] = array_map(function($item) {
                // ✅ VERIFICAR SI YA ESTÁ FORMATEADO
                if (isset($item['remision']) && is_array($item['remision']) && isset($item['remision']['nombre'])) {
                    return $item;
                }
                
                // ✅ FORMATEAR SI VIENE SIN ESTRUCTURA
                return [
                    'remision' => [
                        'nombre' => $item['remision']['nombre'] ?? $item['nombre'] ?? 'Remisión sin nombre',
                        'tipo' => $item['remision']['tipo'] ?? $item['tipo'] ?? 'N/A'
                    ],
                    'observacion' => $item['observacion'] ?? 'Sin observaciones'
                ];
            }, $historia['remisiones']);

            Log::info('✅ Remisiones formateadas', [
                'count_despues' => count($historia['remisiones'])
            ]);
        } else {
            Log::warning('⚠️ No hay remisiones para formatear', [
                'isset' => isset($historia['remisiones']),
                'is_array' => isset($historia['remisiones']) ? is_array($historia['remisiones']) : false,
                'empty' => isset($historia['remisiones']) ? empty($historia['remisiones']) : true
            ]);
            $historia['remisiones'] = [];
        }

        // ✅ FORMATEAR DIAGNÓSTICOS
        if (isset($historia['diagnosticos']) && is_array($historia['diagnosticos']) && !empty($historia['diagnosticos'])) {
            Log::info('🔧 Formateando diagnósticos', [
                'count_antes' => count($historia['diagnosticos']),
                'primer_diagnostico' => $historia['diagnosticos'][0] ?? 'N/A'
            ]);

            $historia['diagnosticos'] = array_map(function($item) {
                // ✅ VERIFICAR SI YA ESTÁ FORMATEADO
                if (isset($item['diagnostico']) && is_array($item['diagnostico']) && isset($item['diagnostico']['nombre'])) {
                    return $item;
                }
                
                // ✅ FORMATEAR SI VIENE SIN ESTRUCTURA
                return [
                    'diagnostico' => [
                        'codigo' => $item['diagnostico']['codigo'] ?? $item['codigo'] ?? 'N/A',
                        'nombre' => $item['diagnostico']['nombre'] ?? $item['nombre'] ?? 'Diagnóstico sin nombre'
                    ],
                    'tipo' => $item['tipo'] ?? 'SECUNDARIO',
                    'tipo_diagnostico' => $item['tipo_diagnostico'] ?? 'N/A'
                ];
            }, $historia['diagnosticos']);

            Log::info('✅ Diagnósticos formateados', [
                'count_despues' => count($historia['diagnosticos'])
            ]);
        } else {
            Log::warning('⚠️ No hay diagnósticos para formatear', [
                'isset' => isset($historia['diagnosticos']),
                'is_array' => isset($historia['diagnosticos']) ? is_array($historia['diagnosticos']) : false,
                'empty' => isset($historia['diagnosticos']) ? empty($historia['diagnosticos']) : true
            ]);
            $historia['diagnosticos'] = [];
        }

        // ✅ FORMATEAR CUPS
        if (isset($historia['cups']) && is_array($historia['cups']) && !empty($historia['cups'])) {
            Log::info('🔧 Formateando CUPS', [
                'count_antes' => count($historia['cups']),
                'primer_cups' => $historia['cups'][0] ?? 'N/A'
            ]);

            $historia['cups'] = array_map(function($item) {
                // ✅ VERIFICAR SI YA ESTÁ FORMATEADO
                if (isset($item['cups']) && is_array($item['cups']) && isset($item['cups']['nombre'])) {
                    return $item;
                }
                
                // ✅ FORMATEAR SI VIENE SIN ESTRUCTURA
                return [
                    'cups' => [
                        'codigo' => $item['cups']['codigo'] ?? $item['codigo'] ?? 'N/A',
                        'nombre' => $item['cups']['nombre'] ?? $item['nombre'] ?? 'CUPS sin nombre'
                    ],
                    'cantidad' => $item['cantidad'] ?? 1,
                    'observacion' => $item['observacion'] ?? ''
                ];
            }, $historia['cups']);

            Log::info('✅ CUPS formateados', [
                'count_despues' => count($historia['cups'])
            ]);
        } else {
            Log::warning('⚠️ No hay CUPS para formatear', [
                'isset' => isset($historia['cups']),
                'is_array' => isset($historia['cups']) ? is_array($historia['cups']) : false,
                'empty' => isset($historia['cups']) ? empty($historia['cups']) : true
            ]);
            $historia['cups'] = [];
        }

        Log::info('✅ Historia formateada correctamente', [
            'medicamentos_count' => count($historia['medicamentos']),
            'remisiones_count' => count($historia['remisiones']),
            'diagnosticos_count' => count($historia['diagnosticos']),
            'cups_count' => count($historia['cups'])
        ]);

        return $historia;

    } catch (\Exception $e) {
        Log::error('❌ Error formateando historia para vista', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Retornar historia sin cambios en caso de error
        return $historia;
    }
}

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
        'MEDICINA GENERAL' => 'especial-control',
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
        $historiasPath = storage_path('app/offline/historias_clinicas');
        $filePath = "{$historiasPath}/{$uuid}.json";
        
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                Log::info('✅ Historia encontrada en JSON offline', [
                    'historia_uuid' => $uuid
                ]);
                
                // ✅ ENRIQUECER DATOS COMPLETOS DEL PACIENTE
                $pacienteUuid = $data['paciente_uuid'] ?? $data['cita']['paciente_uuid'] ?? $data['cita']['paciente']['uuid'] ?? null;
                
                if ($pacienteUuid) {
                    $pacientePath = storage_path("app/offline/pacientes/{$pacienteUuid}.json");
                    
                    if (file_exists($pacientePath)) {
                        $pacienteData = json_decode(file_get_contents($pacientePath), true);
                        
                        if ($pacienteData) {
                            // ✅ ESTRUCTURAR OBJETOS ANIDADOS QUE ESPERAN LAS VISTAS
                            $pacienteData['regimen'] = ['nombre' => $pacienteData['regimen_nombre'] ?? 'N/A'];
                            $pacienteData['ocupacion'] = [
                                'nombre' => $pacienteData['ocupacion_nombre'] ?? 'N/A',
                                'codigo' => $pacienteData['ocupacion_codigo'] ?? 'N/A'
                            ];
                            $pacienteData['brigada'] = ['nombre' => $pacienteData['brigada_nombre'] ?? 'N/A'];
                            $pacienteData['departamento'] = ['nombre' => $pacienteData['departamento_nombre'] ?? 'N/A'];
                            $pacienteData['municipio'] = ['nombre' => $pacienteData['municipio_nombre'] ?? 'N/A'];
                            $pacienteData['empresa'] = ['nombre' => $pacienteData['empresa_nombre'] ?? $pacienteData['regimen_nombre'] ?? 'N/A'];
                            
                            // ✅ REEMPLAZAR DATOS DEL PACIENTE EN CITA CON DATOS COMPLETOS
                            $data['cita']['paciente'] = $pacienteData;
                            
                            // ✅ TAMBIÉN ACTUALIZAR data['paciente'] SI EXISTE
                            if (isset($data['paciente'])) {
                                $data['paciente'] = $pacienteData;
                            }
                            
                            Log::info('✅ Datos completos del paciente cargados en historia offline', [
                                'historia_uuid' => $uuid,
                                'paciente_uuid' => $pacienteUuid,
                                'paciente_nombre' => $pacienteData['primer_nombre'] . ' ' . $pacienteData['primer_apellido'],
                                'regimen' => $pacienteData['regimen']['nombre'],
                                'ocupacion' => $pacienteData['ocupacion']['nombre']
                            ]);
                        }
                    }
                }
                
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
 * ✅✅✅ OBTENER ÚLTIMA HISTORIA PARA FORMULARIO - VERSIÓN CORREGIDA ✅✅✅
 * (Busca en TODAS las especialidades, pero solo carga si es CONTROL)
 */
private function obtenerUltimaHistoriaParaFormulario(string $pacienteUuid, string $especialidad): ?array
{
    Log::info('🔥🔥🔥 [FORMULARIO] Inicio de obtenerUltimaHistoriaParaFormulario', [
        'paciente_uuid' => $pacienteUuid,
        'especialidad' => $especialidad,
        'archivo' => __FILE__,
        'linea' => __LINE__
    ]);

    try {
        // ✅ OBTENER TODAS LAS HISTORIAS DEL PACIENTE
        $todasLasHistorias = $this->offlineService->obtenerTodasLasHistoriasOffline($pacienteUuid, null);
        
        if (empty($todasLasHistorias)) {
            Log::info('ℹ️ [FORMULARIO] No se encontraron historias');
            return null;
        }

        // ✅ ORDENAR POR FECHA DE CREACIÓN DESC
        usort($todasLasHistorias, function($a, $b) {
            $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
            $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
            return strtotime($fechaB) - strtotime($fechaA);
        });

        $ultimaHistoria = $todasLasHistorias[0];

        Log::info('✅ [FORMULARIO] Última historia encontrada', [
            'historia_uuid' => $ultimaHistoria['uuid'] ?? null,
            'created_at' => $ultimaHistoria['created_at'] ?? null,
            'especialidad' => $ultimaHistoria['especialidad'] ?? null,
            'medicamentos_count' => count($ultimaHistoria['medicamentos'] ?? []),
            'diagnosticos_count' => count($ultimaHistoria['diagnosticos'] ?? [])
        ]);

        // ✅ FORMATEAR PARA EL FORMULARIO
        $historiaFormateada = $this->formatearHistoriaParaFormulario($ultimaHistoria);

        // ✅✅✅ COMPLETAR DATOS FALTANTES (NUEVO) ✅✅✅
        $historiaFormateada = $this->offlineService->completarDatosFaltantesOffline($pacienteUuid, $historiaFormateada);

        Log::info('✅ [FORMULARIO] Historia completa después de rellenar', [
            'medicamentos_final' => count($historiaFormateada['medicamentos'] ?? []),
            'diagnosticos_final' => count($historiaFormateada['diagnosticos'] ?? []),
            'remisiones_final' => count($historiaFormateada['remisiones'] ?? []),
            'cups_final' => count($historiaFormateada['cups'] ?? []),
            'tiene_clasificaciones' => !empty($historiaFormateada['clasificacion_estado_metabolico'])
        ]);

        return $historiaFormateada;

    } catch (\Exception $e) {
        Log::error('❌ [FORMULARIO] Error obteniendo historia', [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        
        return null;
    }
}


/**
 * ✅✅✅ COMPLETAR DATOS FALTANTES OFFLINE ✅✅✅
 */
private function completarDatosFaltantesOffline(string $pacienteUuid, array $historiaBase): array
{
    try {
        // ✅ VERIFICAR SI LOS ARRAYS YA TIENEN DATOS
        $necesitaMedicamentos = empty($historiaBase['medicamentos']);
        $necesitaDiagnosticos = empty($historiaBase['diagnosticos']);
        $necesitaRemisiones = empty($historiaBase['remisiones']);
        $necesitaCups = empty($historiaBase['cups']);

        // ✅ IDENTIFICAR CAMPOS ESCALARES VACÍOS
        $camposPorCompletar = [
            'clasificacion_estado_metabolico' => empty($historiaBase['clasificacion_estado_metabolico']),
            'clasificacion_hta' => empty($historiaBase['clasificacion_hta']),
            'talla' => empty($historiaBase['talla']),
            // ... (agregar más campos según necesites)
        ];

        // ✅ SI TODO ESTÁ LLENO, RETORNAR
        if (!in_array(true, $camposPorCompletar) && 
            !$necesitaMedicamentos && 
            !$necesitaDiagnosticos && 
            !$necesitaRemisiones && 
            !$necesitaCups) {
            return $historiaBase;
        }

        // ✅ BUSCAR EN HISTORIAS ANTERIORES
        $historiasPath = storage_path('app/offline/historias-clinicas');
        $files = glob($historiasPath . '/*.json');

        foreach ($files as $file) {
            $historia = json_decode(file_get_contents($file), true);
            
            if (!$historia) continue;

            // ✅ VERIFICAR QUE SEA DEL MISMO PACIENTE
            $historiaPatienteUuid = $historia['paciente_uuid'] ?? null;
            if ($historiaPatienteUuid !== $pacienteUuid) continue;

            // ✅ COMPLETAR MEDICAMENTOS SI ESTÁN VACÍOS
            if ($necesitaMedicamentos && !empty($historia['medicamentos'])) {
                $historiaBase['medicamentos'] = $historia['medicamentos'];
                $necesitaMedicamentos = false;
            }

            // ✅ COMPLETAR DIAGNÓSTICOS SI ESTÁN VACÍOS
            if ($necesitaDiagnosticos && !empty($historia['diagnosticos'])) {
                $historiaBase['diagnosticos'] = $historia['diagnosticos'];
                $necesitaDiagnosticos = false;
            }

            // ✅ COMPLETAR CLASIFICACIONES SI ESTÁN VACÍAS
            if ($camposPorCompletar['clasificacion_estado_metabolico'] && 
                !empty($historia['clasificacion_estado_metabolico'])) {
                $historiaBase['clasificacion_estado_metabolico'] = $historia['clasificacion_estado_metabolico'];
                $camposPorCompletar['clasificacion_estado_metabolico'] = false;
            }

            // ✅ SI YA COMPLETAMOS TODO, SALIR
            if (!in_array(true, $camposPorCompletar) && 
                !$necesitaMedicamentos && 
                !$necesitaDiagnosticos) {
                break;
            }
        }

        return $historiaBase;

    } catch (\Exception $e) {
        Log::error('❌ Error completando datos offline', [
            'error' => $e->getMessage()
        ]);
        
        return $historiaBase;
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
            'clasificacion_erc_estadodos' => $historia['clasificacion_erc_estadodos'] ?? '',
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
        // ✅ ASEGURAR QUE SIEMPRE SEA UUID
        $medicamentoUuid = $medicamento['medicamento']['uuid'] ?? 
                          $medicamento['medicamento_uuid'] ?? 
                          $this->obtenerMedicamentoUuid($medicamento['medicamento_id'] ?? $medicamento['id'] ?? null);
        
        // ✅ OBTENER NOMBRE - PRIMERO DESDE DATOS, LUEGO DESDE OFFLINE
        $nombre = $medicamento['medicamento']['nombre'] ?? '';
        $principioActivo = $medicamento['medicamento']['principio_activo'] ?? '';
        
        // ✅ SI NO HAY NOMBRE, BUSCARLO EN LA BASE DE DATOS OFFLINE
        if (empty($nombre) && $medicamentoUuid) {
            Log::debug('🔍 Buscando nombre de medicamento desde offline', [
                'medicamento_uuid' => $medicamentoUuid
            ]);
            $medicamentoOffline = $this->obtenerMedicamentoCompleto($medicamentoUuid);
            if ($medicamentoOffline) {
                $nombre = $medicamentoOffline['nombre'] ?? '';
                $principioActivo = $medicamentoOffline['principio_activo'] ?? '';
                Log::debug('✅ Nombre de medicamento recuperado desde offline', [
                    'medicamento_uuid' => $medicamentoUuid,
                    'nombre' => $nombre
                ]);
            } else {
                Log::warning('⚠️ Medicamento no encontrado en offline', [
                    'medicamento_uuid' => $medicamentoUuid
                ]);
            }
        }
        
        return [
            'medicamento_id' => $medicamentoUuid, // ✅ SIEMPRE UUID
            'cantidad' => $medicamento['cantidad'] ?? '',
            'dosis' => $medicamento['dosis'] ?? '',
            'medicamento' => [
                'uuid' => $medicamentoUuid,
                'nombre' => $nombre,
                'principio_activo' => $principioActivo
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
        // ✅ ASEGURAR QUE SIEMPRE SEA UUID
        $remisionUuid = $remision['remision']['uuid'] ?? 
                       $remision['remision_uuid'] ?? 
                       $this->obtenerRemisionUuid($remision['remision_id'] ?? $remision['id'] ?? null);
        
        // ✅ OBTENER NOMBRE - PRIMERO DESDE DATOS, LUEGO DESDE OFFLINE
        $nombre = $remision['remision']['nombre'] ?? '';
        $tipo = $remision['remision']['tipo'] ?? '';
        
        // ✅ SI NO HAY NOMBRE, BUSCARLO EN LA BASE DE DATOS OFFLINE
        if (empty($nombre) && $remisionUuid) {
            Log::debug('🔍 Buscando nombre de remisión desde offline', [
                'remision_uuid' => $remisionUuid
            ]);
            $remisionOffline = $this->obtenerRemisionCompleta($remisionUuid);
            if ($remisionOffline) {
                $nombre = $remisionOffline['nombre'] ?? '';
                $tipo = $remisionOffline['tipo'] ?? '';
                Log::debug('✅ Nombre de remisión recuperado desde offline', [
                    'remision_uuid' => $remisionUuid,
                    'nombre' => $nombre
                ]);
            } else {
                Log::warning('⚠️ Remisión no encontrada en offline', [
                    'remision_uuid' => $remisionUuid
                ]);
            }
        }
        
        return [
            'remision_id' => $remisionUuid, // ✅ SIEMPRE UUID
            'observacion' => $remision['observacion'] ?? '',
            'remision' => [
                'uuid' => $remisionUuid,
                'nombre' => $nombre,
                'tipo' => $tipo
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
        // ✅ ASEGURAR QUE SIEMPRE SEA UUID
        $diagnosticoUuid = $diagnostico['diagnostico']['uuid'] ?? 
                          $diagnostico['diagnostico_uuid'] ?? 
                          $this->obtenerDiagnosticoUuid($diagnostico['diagnostico_id'] ?? $diagnostico['id'] ?? null);
        
        // ✅ OBTENER NOMBRE Y CÓDIGO - PRIMERO DESDE DATOS, LUEGO DESDE OFFLINE
        $codigo = $diagnostico['diagnostico']['codigo'] ?? '';
        $nombre = $diagnostico['diagnostico']['nombre'] ?? '';
        
        // ✅ SI NO HAY NOMBRE, BUSCARLO EN LA BASE DE DATOS OFFLINE
        if (empty($nombre) && $diagnosticoUuid) {
            Log::debug('🔍 Buscando nombre de diagnóstico desde offline', [
                'diagnostico_uuid' => $diagnosticoUuid
            ]);
            $diagnosticoOffline = $this->obtenerDiagnosticoCompleto($diagnosticoUuid);
            if ($diagnosticoOffline) {
                $codigo = $diagnosticoOffline['codigo'] ?? '';
                $nombre = $diagnosticoOffline['nombre'] ?? '';
                Log::debug('✅ Nombre de diagnóstico recuperado desde offline', [
                    'diagnostico_uuid' => $diagnosticoUuid,
                    'codigo' => $codigo,
                    'nombre' => $nombre
                ]);
            } else {
                Log::warning('⚠️ Diagnóstico no encontrado en offline', [
                    'diagnostico_uuid' => $diagnosticoUuid
                ]);
            }
        }
        
        return [
            'diagnostico_id' => $diagnosticoUuid, // ✅ SIEMPRE UUID
            'tipo' => $diagnostico['tipo'] ?? 'PRINCIPAL',
            'tipo_diagnostico' => $diagnostico['tipo_diagnostico'] ?? '',
            'diagnostico' => [
                'uuid' => $diagnosticoUuid,
                'codigo' => $codigo,
                'nombre' => $nombre
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
        // ✅ ASEGURAR QUE SIEMPRE SEA UUID
        $cupsUuid = $cup['cups']['uuid'] ?? 
                   $cup['cups_uuid'] ?? 
                   $this->obtenerCupsUuid($cup['cups_id'] ?? $cup['id'] ?? null);
        
        // ✅ OBTENER NOMBRE Y CÓDIGO - PRIMERO DESDE DATOS, LUEGO DESDE OFFLINE
        $codigo = $cup['cups']['codigo'] ?? '';
        $nombre = $cup['cups']['nombre'] ?? '';
        
        // ✅ SI NO HAY NOMBRE, BUSCARLO EN LA BASE DE DATOS OFFLINE
        if (empty($nombre) && $cupsUuid) {
            $cupsOffline = $this->obtenerCupsCompleto($cupsUuid);
            if ($cupsOffline) {
                $codigo = $cupsOffline['codigo'] ?? '';
                $nombre = $cupsOffline['nombre'] ?? '';
            }
        }
        
        return [
            'cups_id' => $cupsUuid, // ✅ SIEMPRE UUID
            'observacion' => $cup['observacion'] ?? '',
            'cups' => [
                'uuid' => $cupsUuid,
                'codigo' => $codigo,
                'nombre' => $nombre
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

        // ✅✅✅ DEBUG: Ver campos de psicología y complementarios que llegan ✅✅✅
        Log::info('🔍 CAMPOS COMPLEMENTARIOS RECIBIDOS DEL FORMULARIO', [
            // Psicología
            'estructura_familiar' => $request->input('estructura_familiar'),
            'psicologia_red_apoyo' => $request->input('psicologia_red_apoyo'),
            'psicologia_comportamiento_consulta' => $request->input('psicologia_comportamiento_consulta'),
            'psicologia_tratamiento_actual_adherencia' => $request->input('psicologia_tratamiento_actual_adherencia'),
            'psicologia_descripcion_problema' => $request->input('psicologia_descripcion_problema'),
            'analisis_conclusiones' => $request->input('analisis_conclusiones'),
            'psicologia_plan_intervencion_recomendacion' => $request->input('psicologia_plan_intervencion_recomendacion'),
            'avance_paciente' => $request->input('avance_paciente'),
            // Fisioterapia
            'actitud' => $request->input('actitud'),
            'evaluacion_d' => $request->input('evaluacion_d'),
            'plan_seguir' => $request->input('plan_seguir'),
            // Nutrición
            'enfermedad_diagnostica' => $request->input('enfermedad_diagnostica'),
            'analisis_nutricional' => $request->input('analisis_nutricional'),
        ]);

        // ✅ VALIDAR DATOS BÁSICOS
        $validatedData = $this->validateHistoriaClinica($request);

        // ✅✅✅ DEBUG: Ver campos de psicología después de validación ✅✅✅
        Log::info('🔍 CAMPOS COMPLEMENTARIOS DESPUÉS DE VALIDAR', [
            // Psicología
            'estructura_familiar' => $validatedData['estructura_familiar'] ?? 'NO_EXISTE',
            'psicologia_red_apoyo' => $validatedData['psicologia_red_apoyo'] ?? 'NO_EXISTE',
            'psicologia_descripcion_problema' => $validatedData['psicologia_descripcion_problema'] ?? 'NO_EXISTE',
            'analisis_conclusiones' => $validatedData['analisis_conclusiones'] ?? 'NO_EXISTE',
            'psicologia_plan_intervencion_recomendacion' => $validatedData['psicologia_plan_intervencion_recomendacion'] ?? 'NO_EXISTE',
        ]);

        // ✅ PREPARAR DATOS PARA ENVÍO
        $historiaData = $this->prepareHistoriaData($validatedData, $usuario);

        // ✅✅✅ DEBUG: Ver campos complementarios en historiaData preparado ✅✅✅
        Log::info('🔍 CAMPOS COMPLEMENTARIOS EN HISTORIA DATA PREPARADO', [
            // Psicología
            'estructura_familiar' => $historiaData['estructura_familiar'] ?? 'NO_EXISTE',
            'psicologia_red_apoyo' => $historiaData['psicologia_red_apoyo'] ?? 'NO_EXISTE',
            'psicologia_descripcion_problema' => $historiaData['psicologia_descripcion_problema'] ?? 'NO_EXISTE',
            'analisis_conclusiones' => $historiaData['analisis_conclusiones'] ?? 'NO_EXISTE',
            'psicologia_plan_intervencion_recomendacion' => $historiaData['psicologia_plan_intervencion_recomendacion'] ?? 'NO_EXISTE',
        ]);

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
        'especialidad' => 'nullable|string|max:100',
        'tipo_consulta' => 'nullable|string|max:50',
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
        'clasificacion_estado_metabolico' => 'nullable|string|max:200',
        'hipertension_arterial_personal' => 'nullable|in:SI,NO',
        'obs_hipertension_arterial_personal' => 'nullable|string|max:500',
        'clasificacion_hta' => 'nullable|string|max:200',
        'diabetes_mellitus_personal' => 'nullable|in:SI,NO',
        'obs_diabetes_mellitus_personal' => 'nullable|string|max:500',
        'clasificacion_dm' => 'nullable|string|max:200',
        'clasificacion_erc_estado' => 'nullable|string|max:200',
        'clasificacion_erc_estadodos' => 'nullable|string|max:200',
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
        'medicamentos.*.idMedicamento' => 'required|string', // ✅ CAMBIO: acepta ID o UUID, conversión después
        'medicamentos.*.cantidad' => 'required|string|max:50',
        'medicamentos.*.dosis' => 'required|string|max:200',
        
        'remisiones' => 'nullable|array',
        'remisiones.*.idRemision' => 'required|string', // ✅ CAMBIO: acepta ID o UUID, conversión después
        'remisiones.*.remObservacion' => 'nullable|string|max:500',
        
        
        'cups' => 'nullable|array',
        'cups.*.idCups' => 'required|string', // ✅ CAMBIO: acepta ID o UUID, conversión después
        'cups.*.cupObservacion' => 'nullable|string|max:500',
        
        'diagnosticos_adicionales' => 'nullable|array',
        'diagnosticos_adicionales.*.idDiagnostico' => 'required|string|uuid', // ✅ CAMBIO: string|uuid
        'diagnosticos_adicionales.*.tipo_diagnostico' => 'required|string',
    ]);
}

/**
 * ✅ VISTA DE MEDICAMENTOS
 */
public function medicamentos($uuid)
{
    try {
        Log::info('💊 Mostrando vista de medicamentos', [
            'historia_uuid' => $uuid
        ]);

        $historia = $this->obtenerHistoriaCompleta($uuid);
        
        return view('historia-clinica.historial-historias.medicamentos.medicamentos', compact('historia'));
        
    } catch (\Exception $e) {
        Log::error('❌ Error mostrando medicamentos', [
            'error' => $e->getMessage(),
            'historia_uuid' => $uuid
        ]);
        
        return back()->with('error', 'Error cargando medicamentos');
    }
}

/**
 * ✅ VISTA DE REMISIONES
 */
public function remisiones($uuid)
{
    try {
        Log::info('📋 Mostrando vista de remisiones', [
            'historia_uuid' => $uuid
        ]);

        $historia = $this->obtenerHistoriaCompleta($uuid);
        
        return view('historia-clinica.historial-historias.remisiones.remisiones', compact('historia'));
        
    } catch (\Exception $e) {
        Log::error('❌ Error mostrando remisiones', [
            'error' => $e->getMessage(),
            'historia_uuid' => $uuid
        ]);
        
        return back()->with('error', 'Error cargando remisiones');
    }
}

/**
 * ✅ VISTA DE AYUDAS DIAGNÓSTICAS
 */
public function ayudasDiagnosticas($uuid)
{
    try {
        Log::info('🧪 Mostrando vista de ayudas diagnósticas', [
            'historia_uuid' => $uuid
        ]);

        $historia = $this->obtenerHistoriaCompleta($uuid);
        
        return view('historia-clinica.historial-historias.ayudas-diagnosticas.ayudas-diagnosticas', compact('historia'));
        
    } catch (\Exception $e) {
        Log::error('❌ Error mostrando ayudas diagnósticas', [
            'error' => $e->getMessage(),
            'historia_uuid' => $uuid
        ]);
        
        return back()->with('error', 'Error cargando ayudas diagnósticas');
    }
}

/**
 * ✅ OBTENER HISTORIA COMPLETA DESDE API (REUTILIZA FORMATEO)
 */
private function obtenerHistoriaCompleta($uuid)
{
    try {
        Log::info('🔍 Obteniendo historia completa desde API', [
            'historia_uuid' => $uuid
        ]);

        // ✅ 1. INTENTAR OBTENER DESDE API
        $historia = null;
        
        if ($this->apiService->isOnline()) {
            try {
                $response = $this->apiService->get("/historias-clinicas/{$uuid}");
                
                if ($response['success']) {
                    $historia = $response['data'];
                    
                    Log::info('✅ Historia obtenida desde API', [
                        'historia_uuid' => $uuid,
                        'tiene_medicamentos' => !empty($historia['medicamentos']),
                        'tiene_remisiones' => !empty($historia['remisiones']),
                        'tiene_diagnosticos' => !empty($historia['diagnosticos']),
                        'tiene_cups' => !empty($historia['cups'])
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
                
                abort(404, 'Historia clínica no encontrada');
            }
            
            Log::info('✅ Historia obtenida desde offline', [
                'historia_uuid' => $uuid
            ]);
        }

        // ✅ 3. FORMATEAR HISTORIA (REUTILIZA EL MÉTODO EXISTENTE)
        return $this->formatearHistoriaParaVista($historia);
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo historia completa', [
            'error' => $e->getMessage(),
            'historia_uuid' => $uuid,
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(500, 'Error cargando historia clínica');
    }
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
   // ✅ CUPS - CORREGIDO PARA PAGINACIÓN
try {
    if ($this->apiService->isOnline()) {
        $response = $this->apiService->get('/cups');
        if ($response['success'] && !empty($response['data'])) {
            // 🔍 VERIFICAR SI ES PAGINADO
            $cupsArray = [];
            
            if (isset($response['data']['data']) && is_array($response['data']['data'])) {
                // ✅ RESPUESTA PAGINADA - EXTRAER data.data
                $cupsArray = $response['data']['data'];
                
                Log::info('✅ CUPS paginados detectados', [
                    'total_en_pagina' => count($cupsArray),
                    'pagina_actual' => $response['data']['current_page'] ?? 1,
                    'total_registros' => $response['data']['total'] ?? 'N/A',
                    'total_paginas' => $response['data']['last_page'] ?? 'N/A'
                ]);
            } elseif (is_array($response['data']) && isset($response['data'][0]['uuid'])) {
                // ✅ RESPUESTA DIRECTA (SIN PAGINACIÓN)
                $cupsArray = $response['data'];
                
                Log::info('✅ CUPS directos detectados', [
                    'total' => count($cupsArray)
                ]);
            } else {
                Log::warning('⚠️ Estructura de CUPS desconocida', [
                    'response_keys' => array_keys($response['data']),
                    'first_item_type' => gettype($response['data'][0] ?? null)
                ]);
                throw new \Exception('Estructura de respuesta CUPS no reconocida');
            }
            
            $datos['cups'] = $cupsArray;
            Log::info('✅ CUPS obtenidos desde API', ['count' => count($cupsArray)]);
            
            // 🆕 GUARDAR CADA CUPS EN SQLITE
            $guardados = 0;
            foreach ($cupsArray as $cup) {
                try {
                    // Verificar que sea un array válido
                    if (is_array($cup) && isset($cup['uuid']) && isset($cup['codigo'])) {
                        $this->offlineService->storeCupsOffline($cup);
                        $guardados++;
                    } else {
                        Log::warning('⚠️ CUPS inválido', [
                            'cup' => is_array($cup) ? array_keys($cup) : $cup,
                            'tipo' => gettype($cup)
                        ]);
                    }
                } catch (\Exception $storeError) {
                    Log::error('❌ Error guardando CUPS individual', [
                        'cup_uuid' => $cup['uuid'] ?? 'N/A',
                        'error' => $storeError->getMessage()
                    ]);
                }
            }
            
            Log::info('💾 CUPS guardados en SQLite', [
                'total_obtenidos' => count($cupsArray),
                'guardados_exitosos' => $guardados
            ]);
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
        'especialidad' => $validatedData['especialidad'] ?? null, // ✅ AGREGADO - ESPECIALIDAD DESDE FORMULARIO
        
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
        'clasificacion_estado_metabolico' => $validatedData['clasificacion_estado_metabolico'] ?? null,
        'hipertension_arterial_personal' => $validatedData['hipertension_arterial_personal'] ?? null,
        'obs_personal_hipertension_arterial' => $validatedData['obs_hipertension_arterial_personal'] ?? null,
        'clasificacion_hta' => $validatedData['clasificacion_hta'] ?? null,
        'diabetes_mellitus_personal' => $validatedData['diabetes_mellitus_personal'] ?? null,
        'obs_personal_mellitus' => $validatedData['obs_diabetes_mellitus_personal'] ?? null,
        'clasificacion_dm' => $validatedData['clasificacion_dm'] ?? null,
        'clasificacion_erc_estado' => $validatedData['clasificacion_erc_estado'] ?? null,
        'clasificacion_erc_estadodos' => $validatedData['clasificacion_erc_estadodos'] ?? null,
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
    
    // ✅ DIAGNÓSTICO PRINCIPAL - CONVERTIR ID A UUID
    $diagnosticoUuid = $this->obtenerDiagnosticoUuid($validatedData['idDiagnostico']);
    
    if ($diagnosticoUuid) {
        $diagnosticos[] = [
            'diagnostico_id' => $diagnosticoUuid,
            'tipo' => 'PRINCIPAL',
            'tipo_diagnostico' => $validatedData['tipo_diagnostico'],
            'observacion' => null
        ];
    }
    
    // ✅ DIAGNÓSTICOS ADICIONALES
    if (!empty($validatedData['diagnosticos_adicionales'])) {
        foreach ($validatedData['diagnosticos_adicionales'] as $index => $diagAdicional) {
            $diagnosticoId = $diagAdicional['idDiagnostico'] ?? 
                            $diagAdicional['uuid'] ?? 
                            $diagAdicional['id'] ?? 
                            null;
            
            if (!$diagnosticoId) {
                continue;
            }
            
            // ✅ CONVERTIR ID A UUID
            $diagnosticoUuid = $this->obtenerDiagnosticoUuid($diagnosticoId);
            
            if ($diagnosticoUuid) {
                $diagnosticos[] = [
                    'diagnostico_id' => $diagnosticoUuid,
                    'tipo' => 'SECUNDARIO',
                    'tipo_diagnostico' => $diagAdicional['tipo_diagnostico'],
                    'observacion' => $diagAdicional['observacion'] ?? null
                ];
            }
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
            $medicamentoId = $medicamento['idMedicamento'] ?? 
                            $medicamento['uuid'] ?? 
                            $medicamento['id'] ?? 
                            null;
            
            if (!$medicamentoId) {
                continue;
            }
            
            // ✅ CONVERTIR ID A UUID
            $medicamentoUuid = $this->obtenerMedicamentoUuid($medicamentoId);
            
            if ($medicamentoUuid) {
                $medicamentos[] = [
                    'medicamento_id' => $medicamentoUuid,
                    'cantidad' => $medicamento['cantidad'] ?? '',
                    'dosis' => $medicamento['dosis'] ?? '',
                ];
            }
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
            $remisionId = $remision['idRemision'] ?? 
                         $remision['uuid'] ?? 
                         $remision['id'] ?? 
                         null;
            
            if (!$remisionId) {
                continue;
            }
            
            // ✅ CONVERTIR ID A UUID
            $remisionUuid = $this->obtenerRemisionUuid($remisionId);
            
            if ($remisionUuid) {
                $remisiones[] = [
                    'remision_id' => $remisionUuid,
                    'observacion' => $remision['remObservacion'] ?? null,
                ];
            }
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
            
            // ✅ CONVERTIR ID A UUID
            $cupsUuid = $this->obtenerCupsUuid($cupsId);
            
            if ($cupsUuid) {
                $cups[] = [
                    'cups_id' => $cupsUuid,
                    'observacion' => $cup['cupObservacion'] ?? null,
                ];
            } else {
                Log::warning('⚠️ No se encontró UUID para CUPS', [
                    'cups_id' => $cupsId
                ]);
            }
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


    private function determinarTipoConsulta(string $citaUuid, string $pacienteUuid): string
{
    try {
        Log::info('🧠 Determinando tipo de consulta inteligente (CORREGIDO)', [
            'cita_uuid' => $citaUuid,
            'paciente_uuid' => $pacienteUuid
        ]);

        // ✅ 1. OBTENER ESPECIALIDAD DE LA CITA
        $especialidad = $this->obtenerEspecialidadOffline($citaUuid);
        
        if (!$especialidad) {
            Log::warning('⚠️ No se pudo determinar especialidad, usando fallback');
            $especialidad = 'MEDICINA GENERAL';
        }

        Log::info('✅ Especialidad determinada para tipo de consulta', [
            'especialidad' => $especialidad,
            'cita_uuid' => $citaUuid
        ]);

        // ✅ 2. VERIFICAR HISTORIAS DE LA MISMA ESPECIALIDAD
        $tieneHistoriasDeEspecialidad = $this->verificarHistoriasAnterioresPorEspecialidad(
            $pacienteUuid, 
            $especialidad
        );
        
        $tipoConsulta = $tieneHistoriasDeEspecialidad ? 'CONTROL' : 'PRIMERA VEZ';

        Log::info('✅ Tipo de consulta determinado (con filtro de especialidad)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'tiene_historias_especialidad' => $tieneHistoriasDeEspecialidad,
            'tipo_consulta' => $tipoConsulta
        ]);
        
        return $tipoConsulta;

    } catch (\Exception $e) {
        Log::error('❌ Error determinando tipo consulta, usando PRIMERA VEZ por defecto', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'line' => $e->getLine()
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
 /**
 * ✅ OBTENER ESPECIALIDAD OFFLINE - VERSIÓN CORREGIDA (PRIORIZA ESPECIALIDAD DEL MÉDICO)
 */
private function obtenerEspecialidadOffline(string $citaUuid): ?string
{
    try {
        Log::info('🔍 Obteniendo especialidad offline', [
            'cita_uuid' => $citaUuid
        ]);

        // ✅ 1. OBTENER CITA DESDE OFFLINE SERVICE
        $cita = $this->offlineService->getCitaOffline($citaUuid);
        
        if (!$cita) {
            Log::warning('⚠️ Cita no encontrada offline', [
                'cita_uuid' => $citaUuid
            ]);
            return null;
        }

        // ✅✅✅ 2. BUSCAR ESPECIALIDAD DEL MÉDICO PRIMERO (NO EL PROCESO) ✅✅✅
        $especialidad = $cita['agenda']['medico']['especialidad']['nombre'] ?? 
                       $cita['agenda']['usuario_medico']['especialidad']['nombre'] ?? 
                       $cita['medico']['especialidad']['nombre'] ?? 
                       $cita['usuario_medico']['especialidad']['nombre'] ?? 
                       null;

        if ($especialidad) {
            Log::info('✅ Especialidad encontrada desde médico en cita offline', [
                'especialidad' => $especialidad,
                'fuente' => 'cita.medico'
            ]);
            return $especialidad;
        }

        // ✅ 3. SI NO HAY ESPECIALIDAD DEL MÉDICO, BUSCAR EN LA AGENDA
        $agendaUuid = $cita['agenda_uuid'] ?? $cita['agenda']['uuid'] ?? null;
        
        if (!$agendaUuid) {
            Log::warning('⚠️ No se encontró agenda_uuid');
            return 'MEDICINA GENERAL'; // ✅ FALLBACK SEGURO
        }

        Log::info('🔍 Buscando especialidad en agenda', [
            'agenda_uuid' => $agendaUuid
        ]);

        // ✅ 4. BUSCAR AGENDA OFFLINE
        $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
        
        if ($agenda) {
            // ✅ BUSCAR ESPECIALIDAD DEL MÉDICO EN LA AGENDA (NO EL PROCESO)
            $especialidad = $agenda['usuario_medico']['especialidad']['nombre'] ?? 
                           $agenda['medico']['especialidad']['nombre'] ?? 
                           $agenda['usuario']['especialidad']['nombre'] ?? 
                           null;

            if ($especialidad) {
                Log::info('✅ Especialidad encontrada desde médico en agenda offline', [
                    'especialidad' => $especialidad,
                    'agenda_uuid' => $agendaUuid,
                    'fuente' => 'agenda.medico'
                ]);
                return $especialidad;
            }

            // ✅ ÚLTIMO RECURSO: USAR EL PROCESO SOLO SI NO HAY ESPECIALIDAD DEL MÉDICO
            $especialidadProceso = $agenda['proceso']['nombre'] ?? null;
            
            if ($especialidadProceso) {
                Log::warning('⚠️ Usando proceso como especialidad (último recurso)', [
                    'proceso_nombre' => $especialidadProceso,
                    'agenda_uuid' => $agendaUuid
                ]);
                
                // ✅ MAPEAR PROCESOS CONOCIDOS A ESPECIALIDADES REALES
                $mapeo = [
                    'ESPECIAL CONTROL' => 'MEDICINA GENERAL',
                    'CONTROL ESPECIAL' => 'MEDICINA GENERAL',
                    'MEDICINA GENERAL CONTROL' => 'MEDICINA GENERAL',
                    'MEDICINA GENERAL PRIMERA VEZ' => 'MEDICINA GENERAL',
                ];
                
                $especialidadMapeada = $mapeo[strtoupper($especialidadProceso)] ?? $especialidadProceso;
                
                Log::info('🔄 Proceso mapeado a especialidad', [
                    'proceso_original' => $especialidadProceso,
                    'especialidad_mapeada' => $especialidadMapeada
                ]);
                
                return $especialidadMapeada;
            }
        }

        Log::warning('⚠️ No se pudo determinar especialidad offline, usando fallback');
        return 'MEDICINA GENERAL'; // ✅ FALLBACK SEGURO

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo especialidad offline', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid,
            'line' => $e->getLine()
        ]);
        
        return 'MEDICINA GENERAL'; // ✅ FALLBACK SEGURO
    }
}


public function determinarVista(Request $request, string $citaUuid)
{
    try {
        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();

        Log::info('🔍 FRONTEND: Determinando vista de historia clínica', [
            'cita_uuid' => $citaUuid,
            'usuario' => $usuario['nombre_completo'],
            'is_offline' => $isOffline
        ]);

        // ✅ CONSULTAR AL BACKEND PARA DETERMINAR LA VISTA
        if ($this->apiService->isOnline()) {
            try {
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
            } catch (\Exception $e) {
                Log::warning('⚠️ Error API, cayendo a modo offline', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅✅✅ FALLBACK OFFLINE - VERSIÓN CORREGIDA ✅✅✅
        Log::info('📴 Modo offline activado, determinando vista localmente');
        
        // ✅ 1. OBTENER CITA OFFLINE
        $citaResult = $this->citaService->show($citaUuid);
        if (!$citaResult['success']) {
            Log::error('❌ Cita no encontrada offline', [
                'cita_uuid' => $citaUuid
            ]);
            return back()->with('error', 'Cita no encontrada offline');
        }

        $cita = $citaResult['data'];
        
        // ✅✅✅ 2. OBTENER ESPECIALIDAD OFFLINE (MÉTODO NUEVO) 🔥🔥🔥
        $especialidad = $this->obtenerEspecialidadOffline($citaUuid);
        
        if (!$especialidad) {
            Log::warning('⚠️ No se pudo determinar especialidad offline, usando fallback', [
                'cita_uuid' => $citaUuid
            ]);
            // ✅ FALLBACK: Intentar desde la cita directamente
            $especialidad = $this->obtenerEspecialidadMedico($cita);
            
            if (!$especialidad) {
                // ✅ ÚLTIMO FALLBACK: MEDICINA GENERAL
                $especialidad = 'MEDICINA GENERAL';
                Log::warning('⚠️ Usando especialidad por defecto', [
                    'especialidad' => $especialidad
                ]);
            }
        }

        Log::info('✅ Especialidad determinada offline', [
            'especialidad' => $especialidad,
            'cita_uuid' => $citaUuid
        ]);

        // ✅ 3. OBTENER PACIENTE UUID
        $pacienteUuid = $cita['paciente_uuid'] ?? $cita['paciente']['uuid'] ?? null;
        
        if (!$pacienteUuid) {
            Log::error('❌ No se pudo obtener paciente_uuid', [
                'cita_keys' => array_keys($cita),
                'tiene_paciente' => isset($cita['paciente'])
            ]);
            return back()->with('error', 'No se pudo obtener información del paciente');
        }

        // ✅ 4. DETERMINAR TIPO CONSULTA OFFLINE
        $tipoConsulta = $this->determinarTipoConsultaOffline($pacienteUuid, $especialidad);
        
        // ✅ 5. VERIFICAR SI ES ESPECIALIDAD SOLO-CONTROL
        $esSoloControl = in_array($especialidad, ['NEFROLOGIA', 'INTERNISTA']);
        
        // ✅ 6. CONSTRUIR VISTA INFO
        $vistaInfo = [
            'vista' => $this->determinarVistaOffline($especialidad, $tipoConsulta),
            'usa_complementaria' => in_array($especialidad, [
                'REFORMULACION', 'NUTRICIONISTA', 'PSICOLOGIA', 'NEFROLOGIA', 
                'INTERNISTA', 'FISIOTERAPIA', 'TRABAJO SOCIAL'
            ]),
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'solo_control' => $esSoloControl
        ];

        Log::info('✅ Vista determinada offline', [
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'vista' => $vistaInfo['vista'],
            'es_solo_control' => $esSoloControl
        ]);

        // ✅✅✅ 7. CARGAR HISTORIA PREVIA SIEMPRE (PRIMERA VEZ Y CONTROL) ✅✅✅
        Log::info('🔄 Cargando historia previa sin importar tipo de consulta', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta,
            'es_solo_control' => $esSoloControl
        ]);

        $historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);

        if (!empty($historiaPrevia)) {
            Log::info('✅ Historia previa cargada exitosamente (offline)', [
                'tipo_consulta' => $tipoConsulta,
                'especialidad' => $especialidad,
                'medicamentos_count' => count($historiaPrevia['medicamentos'] ?? []),
                'diagnosticos_count' => count($historiaPrevia['diagnosticos'] ?? []),
                'remisiones_count' => count($historiaPrevia['remisiones'] ?? []),
                'cups_count' => count($historiaPrevia['cups'] ?? [])
            ]);
        } else {
            Log::info('ℹ️ No se encontró historia previa para cargar (offline)', [
                'tipo_consulta' => $tipoConsulta,
                'especialidad' => $especialidad
            ]);
            
            // ✅ SI NO HAY HISTORIA PREVIA, CARGAR CUPS SEGÚN ESPECIALIDAD Y TIPO DE CONSULTA
            $categoriaCups = $tipoConsulta === 'PRIMERA VEZ' ? 'PRIMERA VEZ' : 'CONTROL';
            
            Log::info('🔍 Intentando cargar CUPS automáticos', [
                'especialidad' => $especialidad,
                'tipo_consulta' => $tipoConsulta,
                'categoria_cups' => $categoriaCups
            ]);
            
            $cupsAutomaticos = $this->obtenerCupsPorEspecialidadYCategoria($especialidad, $categoriaCups);
            
            Log::info('📊 Resultado de búsqueda CUPS automáticos', [
                'cups_encontrados' => count($cupsAutomaticos),
                'especialidad' => $especialidad,
                'categoria' => $categoriaCups
            ]);
            
            if (!empty($cupsAutomaticos)) {
                // Crear estructura de historia previa con CUPS automáticos
                $historiaPrevia = [
                    'cups' => $cupsAutomaticos
                ];
                
                Log::info('✅ CUPS automáticos cargados', [
                    'especialidad' => $especialidad,
                    'categoria' => $categoriaCups,
                    'cups_count' => count($cupsAutomaticos)
                ]);
            } else {
                Log::warning('⚠️ No se encontraron CUPS automáticos', [
                    'especialidad' => $especialidad,
                    'categoria' => $categoriaCups
                ]);
            }
        }

        // ✅ 8. RENDERIZAR VISTA
        return $this->renderizarVistaEspecifica($vistaInfo, $cita, $historiaPrevia, $usuario, $isOffline);

    } catch (\Exception $e) {
        Log::error('❌ Error determinando vista de historia clínica', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid,
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'trace' => $e->getTraceAsString()
        ]);

        return back()->with('error', 'Error determinando el tipo de historia clínica: ' . $e->getMessage());
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
            'clasificacion_erc_estadodos' => $historiaAPI['clasificacion_erc_estadodos'] ?? '',
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
private function verificarHistoriasAnterioresPorEspecialidad(string $pacienteUuid, string $especialidad): bool
{
    try {
        Log::info('🔍 Verificando historias por especialidad (modo offline)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        // ✅ USAR EL MÉTODO DEL OFFLINE SERVICE QUE YA TIENES
        $esPrimeraVez = $this->offlineService->esPrimeraConsultaOffline(
            $pacienteUuid,
            $especialidad,
            null // No excluir ninguna cita al verificar
        );

        $tieneHistorias = !$esPrimeraVez;

        Log::info('✅ Resultado verificación por especialidad', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'tiene_historias' => $tieneHistorias,
            'es_primera_vez' => $esPrimeraVez
        ]);

        return $tieneHistorias;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando historias por especialidad', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'line' => $e->getLine()
        ]);
        
        return false; // ✅ Fallback: asumir primera vez
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
 * ✅✅✅ DETERMINAR TIPO CONSULTA OFFLINE - VERSIÓN CORREGIDA CON LÓGICA DEL BACKEND ✅✅✅
 */
private function determinarTipoConsultaOffline(string $pacienteUuid, ?string $especialidad = null, ?string $citaActualId = null): string
{
    try {
        Log::info('🔍 OFFLINE: Determinando tipo de consulta (con lógica backend)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'cita_actual_id' => $citaActualId
        ]);

        $especialidadFinal = $especialidad ?? 'MEDICINA GENERAL';
        
        // ✅ USAR EL MÉTODO DEL OFFLINE SERVICE QUE REPLICA EL BACKEND
        $esPrimeraVez = $this->offlineService->esPrimeraConsultaOffline(
            $pacienteUuid,
            $especialidadFinal,
            $citaActualId
        );
        
        $tipoConsulta = $esPrimeraVez ? 'PRIMERA VEZ' : 'CONTROL';

        Log::info('✅ Tipo de consulta determinado offline (lógica backend)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad_final' => $especialidadFinal,
            'es_primera_vez' => $esPrimeraVez,
            'tipo_consulta' => $tipoConsulta
        ]);

        return $tipoConsulta;

    } catch (\Exception $e) {
        Log::error('❌ Error en determinarTipoConsultaOffline', [
            'error' => $e->getMessage()
        ]);

        return 'PRIMERA VEZ'; // ✅ FALLBACK SEGURO
    }
}

/**
 * ✅✅✅ VERIFICAR SI ES PRIMERA CONSULTA DE LA ESPECIALIDAD (OFFLINE) - VERSIÓN ULTRA CORREGIDA ✅✅✅
 */
private function esPrimeraConsultaDeEspecialidadOffline(
    string $pacienteUuid, 
    string $especialidad, 
    ?string $citaActualId = null
): bool {
    try {
        Log::info('🔍 OFFLINE: Verificando si es PRIMERA CONSULTA de la especialidad', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'cita_actual_id' => $citaActualId
        ]);

        // ✅ 1. BUSCAR HISTORIAS EN JSON
        $historiasPath = storage_path('app/offline/historias-clinicas');
        
        if (!is_dir($historiasPath)) {
            Log::info('📁 Directorio de historias no existe, creándolo', [
                'path' => $historiasPath
            ]);
            
            // ✅ CREAR DIRECTORIO SI NO EXISTE
            if (!mkdir($historiasPath, 0755, true) && !is_dir($historiasPath)) {
                Log::error('❌ No se pudo crear directorio de historias');
            }
            
            // ✅ SI NO EXISTE, BUSCAR EN SQLITE
            return $this->verificarPrimeraVezEnSQLite($pacienteUuid, $especialidad, $citaActualId);
        }

        $files = glob($historiasPath . '/*.json');
        
        Log::info('📂 Archivos de historias encontrados', [
            'total_archivos' => count($files),
            'path' => $historiasPath
        ]);

        if (empty($files)) {
            Log::info('📁 No hay archivos JSON de historias, buscando en SQLite');
            
            // ✅ FALLBACK A SQLITE
            return $this->verificarPrimeraVezEnSQLite($pacienteUuid, $especialidad, $citaActualId);
        }

        $historiasDeEspecialidad = [];

        // ✅ 2. FILTRAR HISTORIAS DE LA MISMA ESPECIALIDAD (EXCLUYENDO CITA ACTUAL)
        foreach ($files as $file) {
            $historia = json_decode(file_get_contents($file), true);
            
            if (!$historia || json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('⚠️ Archivo JSON corrupto', [
                    'file' => basename($file)
                ]);
                continue;
            }

            // ✅ VERIFICAR QUE SEA DEL MISMO PACIENTE
            $historiaPatienteUuid = $historia['paciente_uuid'] ?? 
                                   $historia['cita']['paciente_uuid'] ?? 
                                   $historia['cita']['paciente']['uuid'] ?? 
                                   null;

            if ($historiaPatienteUuid !== $pacienteUuid) {
                continue;
            }

            // ✅ EXCLUIR LA CITA ACTUAL (SI SE PROPORCIONÓ)
            if ($citaActualId) {
                $historiaCitaId = $historia['cita_id'] ?? 
                                 $historia['cita']['id'] ?? 
                                 $historia['cita_uuid'] ?? 
                                 null;

                if ($historiaCitaId === $citaActualId) {
                    Log::info('⏭️ Excluyendo cita actual del conteo', [
                        'cita_id' => $citaActualId
                    ]);
                    continue;
                }
            }

            // ✅ VERIFICAR ESPECIALIDAD
            $historiaEspecialidad = $historia['especialidad'] ?? 
                                   $historia['cita']['agenda']['proceso']['nombre'] ?? 
                                   $historia['cita']['proceso']['nombre'] ?? 
                                   null;

            if (!$historiaEspecialidad) {
                Log::debug('⚠️ Historia sin especialidad', [
                    'historia_uuid' => $historia['uuid'] ?? 'N/A'
                ]);
                continue;
            }

            // ✅ NORMALIZAR Y COMPARAR
            $especialidadNormalizada = $this->normalizarTexto($especialidad);
            $historiaEspecialidadNormalizada = $this->normalizarTexto($historiaEspecialidad);

            Log::debug('🔍 Comparando especialidades', [
                'especialidad_buscada' => $especialidad,
                'especialidad_normalizada_buscada' => $especialidadNormalizada,
                'especialidad_historia' => $historiaEspecialidad,
                'especialidad_historia_normalizada' => $historiaEspecialidadNormalizada,
                'coinciden' => $especialidadNormalizada === $historiaEspecialidadNormalizada
            ]);

            if ($especialidadNormalizada === $historiaEspecialidadNormalizada) {
                $historiasDeEspecialidad[] = $historia;
                
                Log::info('📋 Historia de la especialidad encontrada (JSON)', [
                    'historia_uuid' => $historia['uuid'] ?? 'N/A',
                    'especialidad' => $historiaEspecialidad
                ]);
            }
        }

        $totalHistorias = count($historiasDeEspecialidad);
        $esPrimeraVez = $totalHistorias === 0;

        // ✅ SI NO SE ENCONTRARON EN JSON, BUSCAR EN SQLITE
        if ($esPrimeraVez) {
            Log::info('📁 No se encontraron historias en JSON, buscando en SQLite');
            return $this->verificarPrimeraVezEnSQLite($pacienteUuid, $especialidad, $citaActualId);
        }

        Log::info('✅ OFFLINE: Resultado verificación primera consulta (JSON)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'total_historias_especialidad' => $totalHistorias,
            'es_primera_vez' => $esPrimeraVez,
            'tipo_consulta' => $esPrimeraVez ? '🆕 PRIMERA VEZ' : '🔄 CONTROL'
        ]);

        return $esPrimeraVez;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando primera consulta offline', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return true; // ✅ FALLBACK: ASUMIR PRIMERA VEZ
    }
}

/**
 * ✅✅✅ NUEVO MÉTODO: VERIFICAR EN SQLITE ✅✅✅
 */
private function verificarPrimeraVezEnSQLite(
    string $pacienteUuid, 
    string $especialidad, 
    ?string $citaActualId = null
): bool {
    try {
        Log::info('🗄️ Verificando primera vez en SQLite', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        // ✅ BUSCAR HISTORIAS EN SQLITE
        $historiasSQL = $this->offlineService->getHistoriasClinicasByPacienteYEspecialidad(
            $pacienteUuid, 
            $especialidad
        );

        if (empty($historiasSQL)) {
            Log::info('✅ No hay historias en SQLite → PRIMERA VEZ');
            return true;
        }

        // ✅ EXCLUIR CITA ACTUAL SI SE PROPORCIONÓ
        if ($citaActualId) {
            $historiasSQL = array_filter($historiasSQL, function($historia) use ($citaActualId) {
                $historiaCitaId = $historia['cita_id'] ?? 
                                 $historia['cita_uuid'] ?? 
                                 null;
                
                return $historiaCitaId !== $citaActualId;
            });
        }

        $totalHistorias = count($historiasSQL);
        $esPrimeraVez = $totalHistorias === 0;

        Log::info('✅ OFFLINE: Resultado verificación primera consulta (SQLite)', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad,
            'total_historias_especialidad' => $totalHistorias,
            'es_primera_vez' => $esPrimeraVez,
            'tipo_consulta' => $esPrimeraVez ? '🆕 PRIMERA VEZ' : '🔄 CONTROL'
        ]);

        return $esPrimeraVez;

    } catch (\Exception $e) {
        Log::error('❌ Error verificando en SQLite', [
            'error' => $e->getMessage()
        ]);
        
        return true; // ✅ FALLBACK: ASUMIR PRIMERA VEZ
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
 * ✅ OBTENER ÚLTIMA HISTORIA OFFLINE (CUALQUIER ESPECIALIDAD)
 */
private function obtenerUltimaHistoriaOffline(string $pacienteUuid, string $especialidad): ?array
{
    Log::info('🔥🔥🔥 [CONTROLADOR CORRECTO] Inicio de obtenerUltimaHistoriaOffline', [
        'paciente_uuid' => $pacienteUuid,
        'especialidad' => $especialidad,
        'archivo' => __FILE__,
        'linea' => __LINE__
    ]);

    try {
        // 🔥 OBTENER TODAS LAS HISTORIAS DEL PACIENTE
        $todasLasHistorias = $this->offlineService->obtenerTodasLasHistoriasOffline($pacienteUuid, null);
        
        Log::info('📊 [CONTROLADOR] Historias obtenidas', [
            'total' => count($todasLasHistorias),
            'paciente_uuid' => $pacienteUuid
        ]);

        if (empty($todasLasHistorias)) {
            Log::info('ℹ️ [CONTROLADOR] No se encontraron historias del paciente');
            return null;
        }

        // 🔥 ORDENAR POR FECHA DE CREACIÓN (created_at) DESC
        usort($todasLasHistorias, function($a, $b) {
            $fechaA = $a['created_at'] ?? '1970-01-01 00:00:00';
            $fechaB = $b['created_at'] ?? '1970-01-01 00:00:00';
            
            // Convertir a timestamp para comparar
            $timestampA = strtotime($fechaA);
            $timestampB = strtotime($fechaB);
            
            return $timestampB - $timestampA; // DESC: más reciente primero
        });

        $ultimaHistoria = $todasLasHistorias[0];

        Log::info('✅ [CONTROLADOR] Última historia encontrada', [
            'historia_uuid' => $ultimaHistoria['uuid'] ?? null,
            'created_at' => $ultimaHistoria['created_at'] ?? null,
            'especialidad_historia' => $ultimaHistoria['especialidad'] ?? null,
            'tiene_medicamentos' => !empty($ultimaHistoria['medicamentos']),
            'medicamentos_count' => count($ultimaHistoria['medicamentos'] ?? []),
            'tiene_diagnosticos' => !empty($ultimaHistoria['diagnosticos']),
            'diagnosticos_count' => count($ultimaHistoria['diagnosticos'] ?? [])
        ]);

        return $ultimaHistoria;

    } catch (\Exception $e) {
        Log::error('❌ [CONTROLADOR] Error obteniendo última historia offline', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
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
     * ✅ OBTENER HISTORIAS CLÍNICAS CON FILTROS (OFFLINE-FIRST)
     */
    private function obtenerHistoriasClinicas(array $filters, int $page = 1, int $perPage = 15): array
    {
        try {
            Log::info('🔍 Obteniendo historias clínicas', [
                'filters' => $filters,
                'page' => $page,
                'per_page' => $perPage,
                'is_online' => $this->apiService->isOnline()
            ]);

            // ✅ SI ESTÁ OFFLINE, IR DIRECTO A DATOS LOCALES
            if (!$this->apiService->isOnline()) {
                Log::info('🔌 Modo offline, usando almacenamiento local');
                return $this->obtenerHistoriasOffline($filters, $page, $perPage);
            }

            // ✅ SI HAY CONEXIÓN, INTENTAR API PRIMERO
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

            // ✅ FALLBACK OFFLINE SI API FALLA
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
     * ✅ OBTENER HISTORIAS OFFLINE (MEJORADO CON SQLITE + JSON)
     */
    private function obtenerHistoriasOffline(array $filters, int $page = 1, int $perPage = 15): array
    {
        try {
            Log::info('📂 Buscando historias offline', [
                'filters' => $filters,
                'page' => $page,
                'per_page' => $perPage
            ]);

            $historias = [];
            
            // ✅ 1. BUSCAR EN CARPETA DE HISTORIAS JSON (CON GUION BAJO)
            $historiasPath = $this->offlineService->getStoragePath() . '/historias_clinicas';
            
            Log::info('📁 Verificando carpeta de historias JSON', [
                'path' => $historiasPath,
                'exists' => is_dir($historiasPath)
            ]);
            
            if (is_dir($historiasPath)) {
                $files = glob($historiasPath . '/*.json');
                
                Log::info('📄 Archivos JSON de historias encontrados', [
                    'count' => count($files)
                ]);
                
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    
                    if (!$data) {
                        Log::warning('⚠️ Archivo JSON corrupto o vacío', [
                            'file' => basename($file)
                        ]);
                        continue;
                    }
                    
                    Log::debug('🔍 Procesando historia JSON', [
                        'file' => basename($file),
                        'uuid' => $data['uuid'] ?? 'N/A',
                        'paciente_documento' => $data['paciente']['documento'] ?? 'N/A'
                    ]);
                    
                    // ✅ APLICAR FILTROS
                    if (!$this->aplicarFiltrosHistoria($data, $filters)) {
                        continue;
                    }
                    
                    Log::info('✅ Historia cumple filtros (JSON historias)', [
                        'uuid' => $data['uuid'] ?? 'N/A',
                        'documento' => $data['paciente']['documento'] ?? 'N/A'
                    ]);
                    
                    // ✅ ENRIQUECER DATOS
                    $historias[] = $this->enrichHistoriaForList($data);
                }
            }
            
            // ✅ 1.5. BUSCAR EN CARPETA DE PACIENTES JSON (SI HAY FILTRO DE DOCUMENTO)
            if (!empty($filters['documento'])) {
                $pacientesPath = $this->offlineService->getStoragePath() . '/pacientes';
                
                Log::info('📁 Verificando carpeta de pacientes JSON', [
                    'path' => $pacientesPath,
                    'exists' => is_dir($pacientesPath)
                ]);
                
                if (is_dir($pacientesPath)) {
                    $pacientesFiles = glob($pacientesPath . '/*.json');
                    
                    Log::info('📄 Archivos JSON de pacientes encontrados', [
                        'count' => count($pacientesFiles)
                    ]);
                    
                    foreach ($pacientesFiles as $pacienteFile) {
                        $pacienteData = json_decode(file_get_contents($pacienteFile), true);
                        
                        if (!$pacienteData) continue;
                        
                        // Verificar si el documento coincide
                        $documentoPaciente = $pacienteData['documento'] ?? '';
                        
                        if (stripos($documentoPaciente, $filters['documento']) !== false) {
                            $pacienteUuid = $pacienteData['uuid'] ?? null;
                            
                            Log::info('✅ Paciente encontrado en JSON', [
                                'documento' => $documentoPaciente,
                                'uuid' => $pacienteUuid,
                                'nombre' => ($pacienteData['primer_nombre'] ?? '') . ' ' . ($pacienteData['primer_apellido'] ?? '')
                            ]);
                            
                            // ✅ BUSCAR HISTORIAS DIRECTAMENTE EN LA CARPETA DE HISTORIAS POR UUID
                            if ($pacienteUuid && is_dir($historiasPath)) {
                                $historiasFiles = glob($historiasPath . '/*.json');
                                
                                Log::info('🔍 Buscando historias del paciente en carpeta historias_clinicas', [
                                    'paciente_uuid' => $pacienteUuid,
                                    'total_historias' => count($historiasFiles)
                                ]);
                                
                                foreach ($historiasFiles as $historiaFile) {
                                    $historiaData = json_decode(file_get_contents($historiaFile), true);
                                    
                                    if (!$historiaData) continue;
                                    
                                    // Verificar si la historia es de este paciente
                                    if ($this->aplicarFiltrosHistoria($historiaData, $filters, $pacienteUuid)) {
                                        Log::info('✅ Historia del paciente encontrada', [
                                            'historia_uuid' => $historiaData['uuid'] ?? 'N/A',
                                            'paciente_uuid' => $pacienteUuid
                                        ]);
                                        
                                        $historias[] = $this->enrichHistoriaForList($historiaData);
                                    }
                                }
                            }
                            
                            // Buscar historias de este paciente en citas
                            $citasPath = $this->offlineService->getStoragePath() . '/citas';
                            
                            if (is_dir($citasPath)) {
                                $citasFiles = glob($citasPath . '/*.json');
                                
                                Log::info('🔍 Buscando citas del paciente', [
                                    'paciente_uuid' => $pacienteUuid,
                                    'total_citas' => count($citasFiles)
                                ]);
                                
                                foreach ($citasFiles as $citaFile) {
                                    $citaData = json_decode(file_get_contents($citaFile), true);
                                    
                                    if (!$citaData) continue;
                                    
                                    // Verificar si la cita es de este paciente
                                    $citaPacienteUuid = $citaData['paciente_uuid'] ?? 
                                                       $citaData['paciente']['uuid'] ?? null;
                                    
                                    if ($citaPacienteUuid === $pacienteUuid) {
                                        // Verificar si la cita tiene historia clínica
                                        $historiaUuid = $citaData['historia_clinica_uuid'] ?? null;
                                        
                                        if ($historiaUuid) {
                                            Log::info('✅ Cita con historia encontrada', [
                                                'cita_uuid' => $citaData['uuid'] ?? 'N/A',
                                                'historia_uuid' => $historiaUuid
                                            ]);
                                            
                                            // Buscar el archivo de la historia
                                            $historiaFilePath = $historiasPath . '/' . $historiaUuid . '.json';
                                            
                                            if (file_exists($historiaFilePath)) {
                                                $historiaData = json_decode(file_get_contents($historiaFilePath), true);
                                                
                                                if ($historiaData && $this->aplicarFiltrosHistoria($historiaData, $filters, $pacienteUuid)) {
                                                    $historias[] = $this->enrichHistoriaForList($historiaData);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            
                            break; // Ya encontramos al paciente
                        }
                    }
                }
            }
            
            // ✅ 2. BUSCAR EN SQLITE (SI HAY FILTRO DE DOCUMENTO Y NO SE ENCONTRÓ EN JSON)
            if (!empty($filters['documento']) && empty($historias)) {
                Log::info('🔍 Buscando en SQLite por documento', [
                    'documento' => $filters['documento']
                ]);
                
                try {
                    // ✅ VERIFICAR SI EXISTEN PACIENTES EN SQLITE
                    $totalPacientes = DB::connection('offline')
                        ->table('pacientes')
                        ->count();
                    
                    Log::info('📊 Estado de tabla pacientes en SQLite', [
                        'total_pacientes' => $totalPacientes
                    ]);
                    
                    // ✅ MOSTRAR ALGUNOS DOCUMENTOS DE EJEMPLO
                    if ($totalPacientes > 0) {
                        $documentosEjemplo = DB::connection('offline')
                            ->table('pacientes')
                            ->select('documento', 'primer_nombre', 'primer_apellido')
                            ->limit(5)
                            ->get()
                            ->map(function($p) {
                                return $p->documento . ' - ' . $p->primer_nombre . ' ' . $p->primer_apellido;
                            })
                            ->toArray();
                        
                        Log::info('📋 Ejemplos de pacientes en SQLite', [
                            'documentos' => $documentosEjemplo
                        ]);
                    }
                    
                    // Buscar paciente por documento en SQLite
                    $paciente = DB::connection('offline')
                        ->table('pacientes')
                        ->where('documento', 'LIKE', '%' . $filters['documento'] . '%')
                        ->first();
                    
                    if ($paciente) {
                        Log::info('✅ Paciente encontrado en SQLite', [
                            'uuid' => $paciente->uuid,
                            'documento' => $paciente->documento
                        ]);
                        
                        // Buscar historias de ese paciente
                        $historiasRows = DB::connection('offline')
                            ->table('historias_clinicas')
                            ->where('paciente_uuid', $paciente->uuid)
                            ->get();
                        
                        Log::info('📊 Historias encontradas en SQLite', [
                            'count' => $historiasRows->count()
                        ]);
                        
                        foreach ($historiasRows as $row) {
                            // Intentar obtener datos completos del JSON si existe
                            $jsonPath = $historiasPath . '/' . $row->uuid . '.json';
                            
                            if (file_exists($jsonPath)) {
                                $data = json_decode(file_get_contents($jsonPath), true);
                            } else {
                                // Usar datos de SQLite
                                $data = json_decode(json_encode($row), true);
                                
                                // Decodificar campo data si existe
                                if (!empty($data['data']) && is_string($data['data'])) {
                                    $dataDecoded = json_decode($data['data'], true);
                                    if ($dataDecoded) {
                                        $data = array_merge($data, $dataDecoded);
                                    }
                                }
                            }
                            
                            if ($data) {
                                $historias[] = $this->enrichHistoriaForList($data);
                            }
                        }
                    } else {
                        Log::warning('⚠️ Paciente no encontrado en SQLite', [
                            'documento_buscado' => $filters['documento'],
                            'total_pacientes_en_db' => $totalPacientes
                        ]);
                    }
                } catch (\Exception $sqliteError) {
                    Log::error('❌ Error buscando en SQLite', [
                        'error' => $sqliteError->getMessage(),
                        'line' => $sqliteError->getLine()
                    ]);
                }
            }
            
            Log::info('📊 Resultados finales de búsqueda offline', [
                'total_encontradas' => count($historias),
                'filters_aplicados' => $filters
            ]);
            
            // ✅ LOG DETALLADO DE LAS HISTORIAS ENCONTRADAS
            if (!empty($historias)) {
                foreach ($historias as $index => $historia) {
                    Log::info("📄 Historia #{$index}", [
                        'uuid' => $historia['uuid'] ?? 'N/A',
                        'paciente' => $historia['paciente'] ?? 'N/A',
                        'especialidad' => $historia['especialidad'] ?? 'N/A',
                        'fecha' => $historia['created_at'] ?? $historia['fecha'] ?? 'N/A'
                    ]);
                }
            }
            
            // ✅ ORDENAR POR FECHA (MÁS RECIENTE PRIMERO)
            usort($historias, function($a, $b) {
                return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
            });
            
            // ✅ PAGINAR
            $total = count($historias);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($historias, $offset, $perPage);
            
            Log::info('📦 Datos paginados a retornar', [
                'total' => $total,
                'offset' => $offset,
                'per_page' => $perPage,
                'paginated_count' => count($paginatedData)
            ]);
            
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
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error obteniendo historias offline',
                'data' => []
            ];
        }
    }

    /**
     * ✅ APLICAR FILTROS A HISTORIA (MEJORADO CON LOGS Y BÚSQUEDA POR UUID)
     */
    private function aplicarFiltrosHistoria(array $historia, array $filters, ?string $pacienteUuidBuscado = null): bool
    {
        // Filtro por documento o UUID de paciente
        if (!empty($filters['documento']) || $pacienteUuidBuscado) {
            // Intentar obtener documento de diferentes estructuras posibles
            $documento = $historia['paciente']['documento'] ?? 
                        $historia['documento'] ?? 
                        '';
            
            // Intentar obtener UUID del paciente
            $pacienteUuid = $historia['paciente_uuid'] ??
                           $historia['paciente']['uuid'] ??
                           $historia['cita']['paciente_uuid'] ??
                           $historia['cita']['paciente']['uuid'] ??
                           '';
            
            $encontrado = false;
            
            // Buscar por UUID si se proporcionó
            if ($pacienteUuidBuscado && $pacienteUuid === $pacienteUuidBuscado) {
                $encontrado = true;
            }
            
            // Buscar por documento si se proporcionó
            if (!empty($filters['documento']) && !empty($documento)) {
                $documentoBuscado = $filters['documento'];
                if (stripos($documento, $documentoBuscado) !== false) {
                    $encontrado = true;
                }
            }
            
            Log::debug('🔍 Comparando paciente', [
                'historia_documento' => $documento,
                'historia_paciente_uuid' => $pacienteUuid,
                'filtro_documento' => $filters['documento'] ?? null,
                'paciente_uuid_buscado' => $pacienteUuidBuscado,
                'encontrado' => $encontrado,
                'historia_uuid' => $historia['uuid'] ?? 'N/A'
            ]);
            
            if (!$encontrado) {
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
     * ✅ ENRIQUECER DATOS DE HISTORIA PARA LISTA (MEJORADO CON BÚSQUEDA DE PACIENTE)
     */
    private function enrichHistoriaForList(array $historia): array
    {
        // ✅ INTENTAR OBTENER DATOS DEL PACIENTE
        $pacienteData = [
            'nombre_completo' => $historia['paciente']['nombre_completo'] ?? 'N/A',
            'documento' => $historia['paciente']['documento'] ?? 'N/A',
            'tipo_documento' => $historia['paciente']['tipo_documento'] ?? 'CC'
        ];
        
        // ✅ SI NO HAY DATOS COMPLETOS, BUSCAR POR UUID
        if ($pacienteData['nombre_completo'] === 'N/A' || $pacienteData['documento'] === 'N/A') {
            $pacienteUuid = $historia['paciente_uuid'] ?? 
                           $historia['paciente']['uuid'] ?? 
                           $historia['cita']['paciente_uuid'] ?? null;
            
            if ($pacienteUuid) {
                $pacientesPath = $this->offlineService->getStoragePath() . '/pacientes';
                $pacienteFile = $pacientesPath . '/' . $pacienteUuid . '.json';
                
                if (file_exists($pacienteFile)) {
                    $pacienteCompleto = json_decode(file_get_contents($pacienteFile), true);
                    
                    if ($pacienteCompleto) {
                        $pacienteData = [
                            'nombre_completo' => ($pacienteCompleto['primer_nombre'] ?? '') . ' ' . 
                                               ($pacienteCompleto['segundo_nombre'] ?? '') . ' ' . 
                                               ($pacienteCompleto['primer_apellido'] ?? '') . ' ' . 
                                               ($pacienteCompleto['segundo_apellido'] ?? ''),
                            'documento' => $pacienteCompleto['documento'] ?? 'N/A',
                            'tipo_documento' => $pacienteCompleto['tipo_documento']['codigo'] ?? 
                                               $pacienteCompleto['tipo_documento'] ?? 'CC'
                        ];
                        
                        // Limpiar espacios extra del nombre
                        $pacienteData['nombre_completo'] = trim(preg_replace('/\s+/', ' ', $pacienteData['nombre_completo']));
                        
                        Log::debug('✅ Datos de paciente enriquecidos desde archivo', [
                            'paciente_uuid' => $pacienteUuid,
                            'nombre' => $pacienteData['nombre_completo'],
                            'documento' => $pacienteData['documento']
                        ]);
                    }
                }
            }
        }
        
        return [
            'uuid' => $historia['uuid'],
            'paciente' => $pacienteData,
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
     * ✅ BUSCAR HISTORIAS POR DOCUMENTO DE PACIENTE (OFFLINE-FIRST)
     */
    public function buscarPorDocumento(Request $request)
    {
        try {
            $request->validate([
                'documento' => 'required|string|min:3'
            ]);

            $documento = $request->documento;
            
            Log::info('🔍 Buscando historias por documento', [
                'documento' => $documento,
                'is_online' => $this->apiService->isOnline()
            ]);

            // ✅ PRIORIZAR BÚSQUEDA OFFLINE SI NO HAY CONEXIÓN
            if (!$this->apiService->isOnline()) {
                Log::info('🔌 Modo offline, buscando historias en almacenamiento local');
                
                $filters = ['documento' => $documento];
                $result = $this->obtenerHistoriasOffline($filters, 1, 50);
                
                // ✅ DEBUG: Ver qué retorna obtenerHistoriasOffline()
                Log::info('🔍 DEBUG - Resultado de obtenerHistoriasOffline()', [
                    'success' => $result['success'] ?? false,
                    'data_count' => isset($result['data']) ? count($result['data']) : 0,
                    'data_empty' => empty($result['data']),
                    'tiene_data' => isset($result['data']),
                    'primer_historia' => isset($result['data'][0]) ? $result['data'][0]['uuid'] ?? 'sin-uuid' : 'no-hay-data'
                ]);
                
                // ✅ FORMATEAR RESPUESTA PARA COMPATIBILIDAD
                if ($result['success'] && !empty($result['data'])) {
                    Log::info('✅ Historias encontradas en modo offline', [
                        'documento' => $documento,
                        'count' => count($result['data'])
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'historias' => $result['data'],
                        'offline' => true
                    ]);
                } else {
                    Log::info('ℹ️ No se encontraron historias en modo offline', [
                        'documento' => $documento,
                        'result_keys' => array_keys($result)
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'historias' => [],
                        'offline' => true
                    ]);
                }
            }
            
            // ✅ SI HAY CONEXIÓN, INTENTAR API PRIMERO
            $filters = ['documento' => $documento];
            $result = $this->obtenerHistoriasClinicas($filters, 1, 50);
            
            // ✅ FORMATEAR RESPUESTA PARA SIDEBAR
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'historias' => $result['data'],
                    'offline' => $result['offline'] ?? false
                ]);
            }

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

    /**
     * ✅ OBTENER UUID DE DIAGNÓSTICO DESDE ID
     */
    private function obtenerDiagnosticoUuid($idOUuid): ?string
    {
        // Si ya es UUID, retornar directamente
        if (is_string($idOUuid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $idOUuid)) {
            return $idOUuid;
        }
        
        try {
            $diagnostico = $this->offlineService->getDbConnection()
                ->table('diagnosticos')
                ->where('id', $idOUuid)
                ->first();
            
            if ($diagnostico && !empty($diagnostico->uuid)) {
                return $diagnostico->uuid;
            }
            
            Log::warning('⚠️ No se encontró UUID para diagnóstico', ['id' => $idOUuid]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo UUID de diagnóstico', [
                'id' => $idOUuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER UUID DE MEDICAMENTO DESDE ID
     */
    private function obtenerMedicamentoUuid($idOUuid): ?string
    {
        // Si ya es UUID, retornar directamente
        if (is_string($idOUuid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $idOUuid)) {
            return $idOUuid;
        }
        
        try {
            $medicamento = $this->offlineService->getDbConnection()
                ->table('medicamentos')
                ->where('id', $idOUuid)
                ->first();
            
            if ($medicamento && !empty($medicamento->uuid)) {
                return $medicamento->uuid;
            }
            
            Log::warning('⚠️ No se encontró UUID para medicamento', ['id' => $idOUuid]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo UUID de medicamento', [
                'id' => $idOUuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER UUID DE REMISIÓN DESDE ID
     */
    private function obtenerRemisionUuid($idOUuid): ?string
    {
        // Si ya es UUID, retornar directamente
        if (is_string($idOUuid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $idOUuid)) {
            return $idOUuid;
        }
        
        try {
            $remision = $this->offlineService->getDbConnection()
                ->table('remisiones')
                ->where('id', $idOUuid)
                ->first();
            
            if ($remision && !empty($remision->uuid)) {
                return $remision->uuid;
            }
            
            Log::warning('⚠️ No se encontró UUID para remisión', ['id' => $idOUuid]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo UUID de remisión', [
                'id' => $idOUuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER UUID DE CUPS DESDE ID
     */
    private function obtenerCupsUuid($idOUuid): ?string
    {
        // Si ya es UUID, retornar directamente
        if (is_string($idOUuid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $idOUuid)) {
            return $idOUuid;
        }
        
        try {
            $cups = $this->offlineService->getDbConnection()
                ->table('cups')
                ->where('id', $idOUuid)
                ->first();
            
            if ($cups && !empty($cups->uuid)) {
                return $cups->uuid;
            }
            
            Log::warning('⚠️ No se encontró UUID para CUPS', ['id' => $idOUuid]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo UUID de CUPS', [
                'id' => $idOUuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER DATOS COMPLETOS DE UN MEDICAMENTO DESDE OFFLINE
     */
    private function obtenerMedicamentoCompleto($uuidOId): ?array
    {
        try {
            // ✅ PRIMERO: Buscar en archivos JSON de medicamentos
            $jsonPath = storage_path('app/offline/medicamentos/' . $uuidOId . '.json');
            if (file_exists($jsonPath)) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if ($data) {
                    return $data;
                }
            }
            
            // ✅ SEGUNDO: Buscar en SQLite por UUID
            $medicamento = $this->offlineService->getDbConnection()
                ->table('medicamentos')
                ->where('uuid', $uuidOId)
                ->first();
            
            if ($medicamento) {
                return [
                    'uuid' => $medicamento->uuid,
                    'nombre' => $medicamento->nombre ?? '',
                    'principio_activo' => $medicamento->principio_activo ?? ''
                ];
            }
            
            // ✅ TERCERO: Buscar por ID numérico
            if (is_numeric($uuidOId)) {
                $medicamento = $this->offlineService->getDbConnection()
                    ->table('medicamentos')
                    ->where('id', $uuidOId)
                    ->first();
                
                if ($medicamento) {
                    return [
                        'uuid' => $medicamento->uuid ?? '',
                        'nombre' => $medicamento->nombre ?? '',
                        'principio_activo' => $medicamento->principio_activo ?? ''
                    ];
                }
            }
            
            Log::debug('ℹ️ Medicamento no encontrado en offline', ['uuid_o_id' => $uuidOId]);
            return null;
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo medicamento completo', [
                'uuid_o_id' => $uuidOId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER DATOS COMPLETOS DE UNA REMISIÓN DESDE OFFLINE
     */
    private function obtenerRemisionCompleta($uuidOId): ?array
    {
        try {
            // ✅ PRIMERO: Buscar en archivos JSON de remisiones
            $jsonPath = storage_path('app/offline/remisiones/' . $uuidOId . '.json');
            if (file_exists($jsonPath)) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if ($data) {
                    return $data;
                }
            }
            
            // ✅ SEGUNDO: Buscar en SQLite por UUID
            $remision = $this->offlineService->getDbConnection()
                ->table('remisiones')
                ->where('uuid', $uuidOId)
                ->first();
            
            if ($remision) {
                return [
                    'uuid' => $remision->uuid,
                    'nombre' => $remision->nombre ?? '',
                    'tipo' => $remision->tipo ?? ''
                ];
            }
            
            // ✅ TERCERO: Buscar por ID numérico
            if (is_numeric($uuidOId)) {
                $remision = $this->offlineService->getDbConnection()
                    ->table('remisiones')
                    ->where('id', $uuidOId)
                    ->first();
                
                if ($remision) {
                    return [
                        'uuid' => $remision->uuid ?? '',
                        'nombre' => $remision->nombre ?? '',
                        'tipo' => $remision->tipo ?? ''
                    ];
                }
            }
            
            Log::debug('ℹ️ Remisión no encontrada en offline', ['uuid_o_id' => $uuidOId]);
            return null;
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo remisión completa', [
                'uuid_o_id' => $uuidOId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER DATOS COMPLETOS DE UN DIAGNÓSTICO DESDE OFFLINE
     */
    private function obtenerDiagnosticoCompleto($uuidOId): ?array
    {
        try {
            // ✅ PRIMERO: Buscar en archivos JSON de diagnósticos
            $jsonPath = storage_path('app/offline/diagnosticos/' . $uuidOId . '.json');
            if (file_exists($jsonPath)) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if ($data) {
                    return $data;
                }
            }
            
            // ✅ SEGUNDO: Buscar en SQLite por UUID
            $diagnostico = $this->offlineService->getDbConnection()
                ->table('diagnosticos')
                ->where('uuid', $uuidOId)
                ->first();
            
            if ($diagnostico) {
                return [
                    'uuid' => $diagnostico->uuid,
                    'codigo' => $diagnostico->codigo ?? '',
                    'nombre' => $diagnostico->nombre ?? ''
                ];
            }
            
            // ✅ TERCERO: Buscar por ID numérico
            if (is_numeric($uuidOId)) {
                $diagnostico = $this->offlineService->getDbConnection()
                    ->table('diagnosticos')
                    ->where('id', $uuidOId)
                    ->first();
                
                if ($diagnostico) {
                    return [
                        'uuid' => $diagnostico->uuid ?? '',
                        'codigo' => $diagnostico->codigo ?? '',
                        'nombre' => $diagnostico->nombre ?? ''
                    ];
                }
            }
            
            Log::debug('ℹ️ Diagnóstico no encontrado en offline', ['uuid_o_id' => $uuidOId]);
            return null;
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo diagnóstico completo', [
                'uuid_o_id' => $uuidOId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER DATOS COMPLETOS DE UN CUPS DESDE OFFLINE
     */
    private function obtenerCupsCompleto($uuidOId): ?array
    {
        try {
            // ✅ PRIMERO: Buscar en archivos JSON de cups
            $jsonPath = storage_path('app/offline/cups/' . $uuidOId . '.json');
            if (file_exists($jsonPath)) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if ($data) {
                    return $data;
                }
            }
            
            // ✅ SEGUNDO: Buscar en SQLite por UUID
            $cups = $this->offlineService->getDbConnection()
                ->table('cups')
                ->where('uuid', $uuidOId)
                ->first();
            
            if ($cups) {
                return [
                    'uuid' => $cups->uuid,
                    'codigo' => $cups->codigo ?? '',
                    'nombre' => $cups->nombre ?? ''
                ];
            }
            
            // ✅ TERCERO: Buscar por ID numérico
            if (is_numeric($uuidOId)) {
                $cups = $this->offlineService->getDbConnection()
                    ->table('cups')
                    ->where('id', $uuidOId)
                    ->first();
                
                if ($cups) {
                    return [
                        'uuid' => $cups->uuid ?? '',
                        'codigo' => $cups->codigo ?? '',
                        'nombre' => $cups->nombre ?? ''
                    ];
                }
            }
            
            Log::debug('ℹ️ CUPS no encontrado en offline', ['uuid_o_id' => $uuidOId]);
            return null;
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo CUPS completo', [
                'uuid_o_id' => $uuidOId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ OBTENER CUPS POR ESPECIALIDAD Y CATEGORÍA (PRIMERA VEZ / CONTROL)
     */
    private function obtenerCupsPorEspecialidadYCategoria(string $especialidad, string $categoria): array
    {
        try {
            Log::info('🔍 Buscando CUPS contratados', [
                'especialidad' => $especialidad,
                'categoria' => $categoria
            ]);
            
            // Mapeo de especialidades a palabras clave para buscar en CUPS
            $palabrasClave = $this->obtenerPalabrasClaveEspecialidad($especialidad);
            
            if (empty($palabrasClave)) {
                Log::info('ℹ️ No hay palabras clave definidas para especialidad', [
                    'especialidad' => $especialidad
                ]);
                return [];
            }
            
            // Obtener cups_contratados que coincidan con la categoría
            $cupsContratados = DB::connection('offline')
                ->table('cups_contratados as cc')
                ->join('categorias_cups as cat', 'cc.categoria_cups_id', '=', 'cat.id')
                ->where('cat.nombre', 'LIKE', "%{$categoria}%")
                ->where('cc.estado', 'ACTIVO')
                ->select('cc.*', 'cat.nombre as categoria_nombre')
                ->get();
            
            if ($cupsContratados->isEmpty()) {
                Log::info('ℹ️ No se encontraron CUPS contratados', [
                    'categoria' => $categoria
                ]);
                return [];
            }
            
            // Filtrar por palabras clave de la especialidad
            $cupsFormateados = [];
            foreach ($cupsContratados as $cup) {
                $nombreCups = strtoupper($cup->cups_nombre ?? '');
                
                // Verificar si el nombre del CUPS contiene alguna palabra clave
                $coincide = false;
                foreach ($palabrasClave as $palabra) {
                    if (stripos($nombreCups, strtoupper($palabra)) !== false) {
                        $coincide = true;
                        break;
                    }
                }
                
                if ($coincide) {
                    $cupsFormateados[] = [
                        'cups_id' => $cup->cups_uuid,
                        'observacion' => '',
                        'cups' => [
                            'uuid' => $cup->cups_uuid,
                            'codigo' => $cup->cups_codigo,
                            'nombre' => $cup->cups_nombre
                        ]
                    ];
                }
            }
            
            Log::info('✅ CUPS encontrados', [
                'especialidad' => $especialidad,
                'categoria' => $categoria,
                'cups_count' => count($cupsFormateados),
                'total_revisados' => count($cupsContratados)
            ]);
            
            return $cupsFormateados;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo CUPS por especialidad', [
                'especialidad' => $especialidad,
                'categoria' => $categoria,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * ✅ OBTENER PALABRAS CLAVE PARA BUSCAR CUPS SEGÚN ESPECIALIDAD
     */
    private function obtenerPalabrasClaveEspecialidad(string $especialidad): array
    {
        $mapa = [
            'MEDICINA GENERAL' => ['MEDICINA GENERAL', 'GENERAL'],
            'ODONTOLOGIA' => ['ODONTOLOGIA', 'ODONTOLOGICA', 'DENTAL'],
            'GINECOLOGIA' => ['GINECOLOGIA', 'GINECOLOGICA', 'PRENATAL'],
            'ENFERMERIA' => ['ENFERMERIA', 'TOMA DE MUESTRA'],
            'PSICOLOGIA' => ['PSICOLOGIA', 'PSICOLOGICA'],
            'NUTRICION' => ['NUTRICION', 'NUTRICIONAL'],
            'TRABAJO SOCIAL' => ['TRABAJO SOCIAL'],
        ];
        
        $especialidadUpper = strtoupper($especialidad);
        
        foreach ($mapa as $key => $palabras) {
            if (stripos($especialidadUpper, $key) !== false) {
                return $palabras;
            }
        }
        
        // Si no encuentra coincidencia, usar la misma especialidad como palabra clave
        return [$especialidad];
    }

}

