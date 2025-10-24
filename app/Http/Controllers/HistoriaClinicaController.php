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
                    
                    // ✅✅✅ NUEVO: CAMBIAR ESTADO DE LA CITA A ATENDIDA ✅✅✅
                    $this->marcarCitaComoAtendida($request->cita_uuid);
                    
                    // ✅ VERIFICAR SI NECESITA DATOS COMPLEMENTARIOS
                    $especialidadesConComplementaria = [
                        'REFORMULACION', 'NUTRICIONISTA', 'PSICOLOGIA', 'NEFROLOGIA', 
                        'INTERNISTA', 'FISIOTERAPIA', 'TRABAJO SOCIAL'
                    ];

                    $especialidad = $this->obtenerEspecialidadDesdeCita($request->cita_uuid);

                    if (in_array($especialidad, $especialidadesConComplementaria)) {
                        Log::info('📋 Creando historia complementaria para: ' . $especialidad);
                        
                        $historiaUuid = $result['data']['uuid'] ?? null;
                        
                        if ($historiaUuid) {
                            $this->crearHistoriaComplementaria($historiaUuid, $request);
                        }
                    }
                    
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
        
        // ✅✅✅ NUEVO: CAMBIAR ESTADO OFFLINE TAMBIÉN ✅✅✅
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
                
                Log::info('✅ Vista determinada por API', [
                    'especialidad' => $data['especialidad'],
                    'tipo_consulta' => $data['tipo_consulta'],
                    'vista_recomendada' => $data['vista_recomendada']['vista'],
                    'tiene_historia_previa' => !empty($data['historia_previa'])
                ]);

                // ✅ FORMATEAR HISTORIA PREVIA SI EXISTE - ESTO ES LO QUE FALTABA
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
                    $historiaPrevia, // ✅ AHORA SÍ PASA LA HISTORIA FORMATEADA
                    $usuario,
                    $isOffline
                );
            }
        }

        // ✅ RESTO DEL CÓDIGO IGUAL...
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

        $tipoConsulta = $this->determinarTipoConsultaOffline($pacienteUuid, $especialidad ?? 'MEDICINA GENERAL');
        
        $vistaInfo = [
            'vista' => $this->determinarVistaOffline($especialidad, $tipoConsulta),
            'usa_complementaria' => in_array($especialidad, [
                'REFORMULACION', 'NUTRICIONISTA', 'PSICOLOGIA', 'NEFROLOGIA', 
                'INTERNISTA', 'FISIOTERAPIA', 'TRABAJO SOCIAL'
            ]),
            'especialidad' => $especialidad,
            'tipo_consulta' => $tipoConsulta
        ];

        $historiaPrevia = null;
        if ($tipoConsulta === 'CONTROL' && $especialidad === 'MEDICINA GENERAL') {
            $historiaPrevia = $this->obtenerUltimaHistoriaParaFormulario($pacienteUuid, $especialidad);
            Log::info('🔄 Historia previa offline para Medicina General', [
                'tiene_historia' => !empty($historiaPrevia)
            ]);
        }

        return $this->renderizarVistaEspecifica($vistaInfo, $cita, $historiaPrevia, $usuario, $isOffline);

    } catch (\Exception $e) {
        Log::error('❌ Error determinando vista de historia clínica', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid
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
 * ✅ OBTENER ESPECIALIDAD DESDE CITA UUID
 */
/**
 * ✅ OBTENER ESPECIALIDAD DESDE CITA UUID - CORREGIDO
 */
private function obtenerEspecialidadDesdeCita(string $citaUuid): ?string
{
    try {
        Log::info('🔍 Obteniendo especialidad desde cita', [
            'cita_uuid' => $citaUuid
        ]);
        
        $citaResult = $this->citaService->show($citaUuid);
        
        if (!$citaResult['success']) {
            Log::warning('⚠️ No se pudo obtener la cita', [
                'cita_uuid' => $citaUuid
            ]);
            return null;
        }
        
        $cita = $citaResult['data'];
        
        // ✅ INTENTAR OBTENER ESPECIALIDAD DE MÚLTIPLES FUENTES
        $especialidad = $cita['agenda']['medico']['especialidad']['nombre'] ?? 
                       $cita['agenda']['usuario_medico']['especialidad']['nombre'] ?? 
                       $cita['medico']['especialidad']['nombre'] ?? 
                       $cita['especialidad']['nombre'] ?? 
                       $cita['especialidad_nombre'] ?? 
                       null;
        
        Log::info('🔍 Especialidad detectada desde cita', [
            'cita_uuid' => $citaUuid,
            'especialidad' => $especialidad,
            'estructura_cita_keys' => array_keys($cita),
            'tiene_agenda' => isset($cita['agenda']),
            'tiene_medico' => isset($cita['medico'])
        ]);
        
        return $especialidad;
        
    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo especialidad desde cita', [
            'error' => $e->getMessage(),
            'cita_uuid' => $citaUuid
        ]);
        
        return null;
    }
}


/**
 * ✅ CREAR HISTORIA COMPLEMENTARIA
 */
private function crearHistoriaComplementaria(int $historiaId, Request $request): void
{
    try {
        $datosComplementarios = $request->only([
            // ✅ ANTECEDENTES PATOLÓGICOS
            'sistema_nervioso_nefro_inter', 'sistema_hemolinfatico', 'aparato_digestivo',
            'organo_sentido', 'endocrino_metabolico', 'inmunologico', 
            'cancer_tumores_radioterapia_quimio', 'glandula_mamaria', 
            'hipertension_diabetes_erc', 'reacciones_alergica', 'cardio_vasculares',
            'respiratorios', 'urinarias', 'osteoarticulares', 'infecciosos',
            'cirugia_trauma', 'tratamiento_medicacion', 'antecedente_quirurgico',
            'antecedentes_familiares', 'consumo_tabaco', 'antecedentes_alcohol',
            'sedentarismo', 'ginecologico', 'citologia_vaginal', 'menarquia',
            'gestaciones', 'parto', 'aborto', 'cesaria', 'metodo_conceptivo',
            'metodo_conceptivo_cual', 'antecedente_personal',
            
            // ✅ DESCRIPCIONES DETALLADAS
            'descripcion_sistema_nervioso', 'descripcion_sistema_hemolinfatico',
            'descripcion_aparato_digestivo', 'descripcion_organos_sentidos',
            'descripcion_endocrino_metabolico', 'descripcion_inmunologico',
            'descripcion_cancer_tumores_radio_quimioterapia', 'descripcion_glandulas_mamarias',
            'descripcion_hipertension_diabetes_erc', 'descripcion_reacion_alergica',
            'descripcion_cardio_vasculares', 'descripcion_respiratorios',
            'descripcion_urinarias', 'descripcion_osteoarticulares',
            'descripcion_infecciosos', 'descripcion_cirugias_traumas',
            'descripcion_tratamiento_medicacion', 'descripcion_antecedentes_quirurgicos',
            'descripcion_antecedentes_familiares', 'descripcion_consumo_tabaco',
            'descripcion_antecedentes_alcohol', 'descripcion_sedentarismo',
            'descripcion_ginecologicos', 'descripcion_citologia_vaginal',
            
            // ✅ NEUROLÓGICO Y ESTADO MENTAL
            'neurologico_estado_mental', 'obs_neurologico_estado_mental',
            
            // ✅ ESTRUCTURA FAMILIAR
            'estructura_familiar', 'cantidad_habitantes', 'cantidad_conforman_familia',
            'composicion_familiar',
            
            // ✅ VIVIENDA
            'tipo_vivienda', 'tenencia_vivienda', 'material_paredes', 'material_pisos',
            'espacios_sala', 'comedor', 'banio', 'cocina', 'patio', 'cantidad_dormitorios',
            'cantidad_personas_ocupan_cuarto',
            
            // ✅ SERVICIOS PÚBLICOS
            'energia_electrica', 'alcantarillado', 'gas_natural', 'centro_atencion',
            'acueducto', 'centro_culturales', 'ventilacion', 'organizacion',
            'centro_educacion', 'centro_recreacion_esparcimiento',
            
            // ✅ EVALUACIÓN PSICOSOCIAL
            'dinamica_familiar', 'diagnostico', 'acciones_seguir', 'motivo_consulta',
            'psicologia_descripcion_problema', 'psicologia_red_apoyo',
            'psicologia_plan_intervencion_recomendacion', 'psicologia_tratamiento_actual_adherencia',
            'analisis_conclusiones', 'psicologia_comportamiento_consulta',
            
            // ✅ SEGUIMIENTO
            'objetivo_visita', 'situacion_encontrada', 'compromiso', 'recomendaciones',
            'siguiente_seguimiento', 'enfermedad_diagnostica',
            
            // ✅ ANTECEDENTES ADICIONALES
            'habito_intestinal', 'quirurgicos', 'quirurgicos_observaciones',
            'alergicos', 'alergicos_observaciones', 'familiares', 'familiares_observaciones',
            'psa', 'psa_observaciones', 'farmacologicos', 'farmacologicos_observaciones',
            'sueno', 'sueno_observaciones', 'tabaquismo_observaciones',
            'ejercicio', 'ejercicio_observaciones',
            
            // ✅ GINECO-OBSTÉTRICOS
            'embarazo_actual', 'semanas_gestacion', 'climatero',
            
            // ✅ EVALUACIÓN NUTRICIONAL
            'tolerancia_via_oral', 'percepcion_apetito', 'percepcion_apetito_observacion',
            'alimentos_preferidos', 'alimentos_rechazados', 'suplemento_nutricionales',
            'dieta_especial', 'dieta_especial_cual',
            
            // ✅ HORARIOS DE COMIDA
            'desayuno_hora', 'desayuno_hora_observacion', 'media_manana_hora',
            'media_manana_hora_observacion', 'almuerzo_hora', 'almuerzo_hora_observacion',
            'media_tarde_hora', 'media_tarde_hora_observacion', 'cena_hora',
            'cena_hora_observacion', 'refrigerio_nocturno_hora', 'refrigerio_nocturno_hora_observacion',
            
            // ✅ EVALUACIÓN NUTRICIONAL DETALLADA
            'peso_ideal', 'interpretacion', 'meta_meses', 'analisis_nutricional',
            'plan_seguir', 'avance_paciente',
            
            // ✅ FRECUENCIA DE CONSUMO
            'comida_desayuno', 'comida_almuerzo', 'comida_medio_almuerzo',
            'comida_cena', 'comida_medio_desayuno',
            
            // ✅ GRUPOS DE ALIMENTOS
            'lacteo', 'lacteo_observacion', 'huevo', 'huevo_observacion',
            'embutido', 'embutido_observacion', 'carne_roja', 'carne_blanca',
            'carne_vicera', 'carne_observacion', 'leguminosas', 'leguminosas_observacion',
            'frutas_jugo', 'frutas_porcion', 'frutas_observacion', 'verduras_hortalizas',
            'vh_observacion', 'cereales', 'cereales_observacion', 'rtp', 'rtp_observacion',
            'azucar_dulce', 'ad_observacion',
            
            // ✅ DIAGNÓSTICO NUTRICIONAL
            'diagnostico_nutri', 'plan_seguir_nutri',
            
            // ✅ EVALUACIÓN FÍSICA Y TERAPÉUTICA
            'actitud', 'evaluacion_d', 'evaluacion_p', 'estado', 'evaluacion_dolor',
            'evaluacion_os', 'evaluacion_neu', 'comitante'
        ]);

        if (!empty(array_filter($datosComplementarios))) {
            \App\Models\HistoriaClinicaComplementaria::create([
                'historia_clinica_id' => $historiaId,
                ...$datosComplementarios
            ]);
            
            Log::info('✅ Historia complementaria creada', [
                'historia_id' => $historiaId,
                'campos_completados' => count(array_filter($datosComplementarios))
            ]);
        }
    } catch (\Exception $e) {
        Log::error('❌ Error creando historia complementaria', [
            'error' => $e->getMessage(),
            'historia_id' => $historiaId
        ]);
    }
}

/**
 * ✅ DETERMINAR VISTA OFFLINE
 */
private function determinarVistaOffline(string $especialidad, string $tipoConsulta): string
{
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
            'PRIMERA VEZ' => 'nefrologia.primera-vez',
            'CONTROL' => 'nefrologia.control'
        ],
        'INTERNISTA' => [
            'PRIMERA VEZ' => 'internista.primera-vez',
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
 * ✅ DETERMINAR TIPO CONSULTA OFFLINE
 */
private function determinarTipoConsultaOffline(string $pacienteUuid, ?string $especialidad = null): string
{
    try {
        Log::info('🔍 OFFLINE: Determinando tipo de consulta', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

        $especialidadFinal = $especialidad ?? 'MEDICINA GENERAL';
        
        // ✅ VERIFICAR HISTORIAS ANTERIORES OFFLINE
        $tieneHistoriasAnteriores = $this->verificarHistoriasAnterioresOffline($pacienteUuid, $especialidadFinal);
        
        $tipoConsulta = $tieneHistoriasAnteriores ? 'CONTROL' : 'PRIMERA VEZ';

        Log::info('✅ Tipo de consulta determinado offline', [
            'paciente_uuid' => $pacienteUuid,
            'especialidad_final' => $especialidadFinal,
            'tipo_consulta' => $tipoConsulta
        ]);

        return $tipoConsulta;

    } catch (\Exception $e) {
        Log::error('❌ Error en determinarTipoConsultaOffline', [
            'error' => $e->getMessage(),
            'paciente_uuid' => $pacienteUuid,
            'especialidad' => $especialidad
        ]);

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

}

