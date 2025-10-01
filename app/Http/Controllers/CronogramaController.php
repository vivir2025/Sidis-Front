<?php
// app/Http/Controllers/CronogramaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService, OfflineService, AgendaService, CitaService , PacienteService};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronogramaController extends Controller
{
    protected $authService;
    protected $apiService;
    protected $offlineService;
    protected $agendaService;
    protected $citaService;
    protected $pacienteService;


    public function __construct(
        AuthService $authService,
        ApiService $apiService,
        OfflineService $offlineService,
        AgendaService $agendaService,
        CitaService $citaService,
        PacienteService $pacienteService
    ) {
        $this->middleware('custom.auth');
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
        $this->agendaService = $agendaService;
        $this->citaService = $citaService;
        $this->pacienteService = $pacienteService;
    }

    /**
     * ✅ VISTA PRINCIPAL DEL CRONOGRAMA
     */
    public function index(Request $request)
    {
        try {
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            
            // ✅ OBTENER FECHA SELECCIONADA
            $fechaSeleccionada = $request->get('fecha', now()->format('Y-m-d'));
            
            // ✅ VALIDAR FECHA
            if (!$this->isValidDate($fechaSeleccionada)) {
                $fechaSeleccionada = now()->format('Y-m-d');
            }

            Log::info('🏥 CronogramaController@index iniciado', [
                'usuario_uuid' => $usuario['uuid'] ?? 'N/A',
                'usuario_nombre' => $usuario['nombre_completo'] ?? 'N/A',
                'fecha_seleccionada' => $fechaSeleccionada,
                'is_offline' => $isOffline
            ]);

            // ✅ OBTENER DATOS DEL CRONOGRAMA CON FALLBACK
            $cronogramaData = $this->obtenerDatosCronogramaConFallback($fechaSeleccionada, $usuario);

            Log::info('📊 Datos del cronograma obtenidos', [
                'total_agendas' => count($cronogramaData['agendas'] ?? []),
                'total_citas' => $cronogramaData['estadisticas']['total_citas'] ?? 0,
                'offline' => $cronogramaData['offline'] ?? false,
                'es_prueba' => $cronogramaData['es_prueba'] ?? false
            ]);

            return view('cronograma.index', compact(
                'usuario',
                'isOffline',
                'fechaSeleccionada',
                'cronogramaData'
            ));

        } catch (\Exception $e) {
            Log::error('❌ Error en CronogramaController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error cargando cronograma: ' . $e->getMessage());
        }
    }
// En app/Http/Controllers/CronogramaController.php

public function sincronizarCambios(Request $request)
{
    try {
        $cambios = $request->validate([
            'cambios' => 'required|array',
            'cambios.*.cita_uuid' => 'required|string',
            'cambios.*.nuevo_estado' => 'required|string|in:PROGRAMADA,ATENDIDA,CANCELADA,NO_ASISTIO',
            'cambios.*.timestamp' => 'required|string'
        ]);

        $resultados = [];
        
        foreach ($cambios['cambios'] as $cambio) {
            try {
                $result = $this->citaService->cambiarEstado(
                    $cambio['cita_uuid'], 
                    $cambio['nuevo_estado']
                );
                
                $resultados[] = [
                    'cita_uuid' => $cambio['cita_uuid'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
                
            } catch (\Exception $e) {
                $resultados[] = [
                    'cita_uuid' => $cambio['cita_uuid'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'message' => 'Sincronización completada'
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error en sincronización', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Error en sincronización: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * ✅ OBTENER DATOS VÍA AJAX
     */
    public function getData(Request $request, string $fecha)
    {
        try {
            $usuario = $this->authService->usuario();
            
            if (!$this->isValidDate($fecha)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Fecha inválida'
                ], 400);
            }

            $cronogramaData = $this->obtenerDatosCronogramaConFallback($fecha, $usuario);

            return response()->json([
                'success' => true,
                'data' => $cronogramaData,
                'offline' => $cronogramaData['offline'] ?? false,
                'es_prueba' => $cronogramaData['es_prueba'] ?? false,
                'message' => $this->getMensajeEstado($cronogramaData)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en CronogramaController@getData', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ACTUALIZACIÓN RÁPIDA VÍA AJAX
     */
    public function refresh(Request $request)
    {
        try {
            $fecha = $request->get('fecha', now()->format('Y-m-d'));
            $usuario = $this->authService->usuario();

            if (!$this->isValidDate($fecha)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Fecha inválida'
                ], 400);
            }

            $cronogramaData = $this->obtenerDatosCronogramaConFallback($fecha, $usuario, true);

            return response()->json([
                'success' => true,
                'data' => $cronogramaData,
                'offline' => $cronogramaData['offline'] ?? false,
                'es_prueba' => $cronogramaData['es_prueba'] ?? false,
                'message' => $this->getMensajeEstado($cronogramaData),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en refresh', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error actualizando: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ VER DETALLE DE CITA
     */
    public function verCita(Request $request, string $uuid)
    {
        try {
            Log::info('👁️ Viendo detalle de cita', [
                'cita_uuid' => $uuid,
                'is_ajax' => $request->ajax()
            ]);

            $result = $this->citaService->show($uuid);

            if (!$result['success']) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Cita no encontrada'
                    ], 404);
                }
                
                abort(404, $result['error'] ?? 'Cita no encontrada');
            }

            $cita = $result['data'];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $cita,
                    'offline' => $result['offline'] ?? false
                ]);
            }

            return view('cronograma.cita', compact('cita'));

        } catch (\Exception $e) {
            Log::error('❌ Error viendo cita', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }

            abort(500, 'Error interno del servidor');
        }
    }

    /**
     * ✅ CAMBIAR ESTADO DE CITA
     */
   public function cambiarEstadoCita(Request $request, string $uuid)
{
    try {
        // ✅ VALIDACIÓN CRÍTICA DEL UUID ANTES DE TODO
        if (empty($uuid) || !is_string($uuid)) {
            Log::error('❌ UUID vacío o inválido en controlador', [
                'uuid_recibido' => $uuid,
                'tipo' => gettype($uuid),
                'request_route' => $request->route()->getName(),
                'request_url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'UUID de cita no válido'
            ], 400);
        }
        
        // ✅ LIMPIAR Y VALIDAR UUID
        $uuid = trim($uuid);
        
        if (strlen($uuid) !== 36) {
            Log::error('❌ UUID con longitud incorrecta en controlador', [
                'uuid' => $uuid,
                'longitud' => strlen($uuid)
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'UUID con formato incorrecto'
            ], 400);
        }

        $request->validate([
            'estado' => 'required|in:PROGRAMADA,EN_ATENCION,ATENDIDA,CANCELADA,NO_ASISTIO'
        ]);

        $nuevoEstado = $request->estado;
        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        Log::info('🔄 CronogramaController - Cambiando estado de cita', [
            'cita_uuid' => $uuid,
            'estado_nuevo' => $nuevoEstado,
            'fecha' => $fecha,
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl()
        ]);

        $result = $this->citaService->cambiarEstado($uuid, $nuevoEstado);

        if (!$result['success']) {
            Log::error('❌ CitaService devolvió error', [
                'error' => $result['error'] ?? 'Error desconocido',
                'uuid' => $uuid
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Error cambiando estado'
            ], 400);
        }

        // ✅ OBTENER ESTADÍSTICAS ACTUALIZADAS
        $usuario = $this->authService->usuario();
        $estadisticasActualizadas = $this->calcularEstadisticasGlobales($fecha, $usuario);

        Log::info('✅ Estado cambiado exitosamente en controlador', [
            'cita_uuid' => $uuid,
            'nuevo_estado' => $nuevoEstado,
            'offline' => $result['offline'] ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'message' => "Cita marcada como {$nuevoEstado}",
            'estadisticas_globales' => $estadisticasActualizadas,
            'offline' => $result['offline'] ?? false
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('❌ Error de validación en controlador', [
            'errors' => $e->errors(),
            'uuid' => $uuid ?? 'N/A'
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Datos de entrada inválidos',
            'validation_errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        Log::error('❌ Error crítico en controlador', [
            'uuid' => $uuid ?? 'N/A',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Error interno del servidor'
        ], 500);
    }
}

    /**
     * ✅ OBTENER DATOS COMPLETOS DEL CRONOGRAMA
     */
    private function obtenerDatosCronograma(string $fecha, array $usuario, bool $refresh = false): array
    {
        try {
            Log::info('📊 Obteniendo datos del cronograma', [
                'fecha' => $fecha,
                'usuario_uuid' => $usuario['uuid'] ?? 'N/A',
                'refresh' => $refresh
            ]);

            $usuarioUuid = $usuario['uuid'] ?? null;
            $sedeId = $usuario['sede_id'] ?? 1;
            
            if (!$usuarioUuid) {
                throw new \Exception('Usuario sin UUID válido');
            }

            // ✅ OBTENER AGENDAS DEL PROFESIONAL
            $agendas = $this->obtenerAgendasProfesional($usuarioUuid, $fecha, $sedeId, $refresh);
            
            Log::info('📅 Agendas obtenidas', [
                'total_agendas' => count($agendas['data']),
                'offline' => $agendas['offline']
            ]);

            // ✅ ENRIQUECER AGENDAS CON CITAS
            $agendasEnriquecidas = $this->enriquecerAgendasConCitas($agendas['data'], $fecha);

            // ✅ CALCULAR ESTADÍSTICAS
            $estadisticas = $this->calcularEstadisticasGlobales($fecha, $usuario, $agendasEnriquecidas);

            return [
                'agendas' => $agendasEnriquecidas,
                'estadisticas' => $estadisticas,
                'fecha' => $fecha,
                'offline' => $agendas['offline'],
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo datos del cronograma', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            throw $e; // Re-lanzar para que lo maneje el método con fallback
        }
    }

    /**
     * ✅ OBTENER DATOS CON FALLBACK A PRUEBA
     */
    private function obtenerDatosCronogramaConFallback(string $fecha, array $usuario, bool $refresh = false): array
    {
        try {
            // Intentar obtener datos normalmente
            return $this->obtenerDatosCronograma($fecha, $usuario, $refresh);
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo datos normales, intentando fallback', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            // Si no hay agendas y estamos en desarrollo, generar datos de prueba
            if (app()->environment('local')) {
                Log::info('🧪 Generando datos de prueba para desarrollo');
                
                $agendasPrueba = $this->generarDatosPrueba($fecha, $usuario['uuid']);
                $estadisticasPrueba = $this->calcularEstadisticasGlobales($fecha, $usuario, $agendasPrueba);
                
                return [
                    'agendas' => $agendasPrueba,
                    'estadisticas' => $estadisticasPrueba,
                    'fecha' => $fecha,
                    'offline' => true,
                    'es_prueba' => true,
                    'timestamp' => now()->toISOString()
                ];
            }

            // En producción, devolver datos vacíos
            return [
                'agendas' => [],
                'estadisticas' => $this->getEstadisticasVacias(),
                'fecha' => $fecha,
                'offline' => true,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ OBTENER AGENDAS DEL PROFESIONAL - MÉTODO CORREGIDO
     */
    private function obtenerAgendasProfesional(string $usuarioUuid, string $fecha, int $sedeId, bool $refresh = false): array
    {
        try {
            $isOffline = $this->authService->isOffline();
            
            if (!$isOffline && $this->apiService->isOnline()) {
                try {
                    Log::info('🌐 Intentando obtener agendas desde API', [
                        'usuario_uuid' => $usuarioUuid,
                        'fecha' => $fecha,
                        'sede_id' => $sedeId,
                        'refresh' => $refresh
                    ]);

                    // ✅ CAMBIO PRINCIPAL: Usar ruta de agendas existente
                    $response = $this->apiService->get('/agendas/disponibles', [
                        'usuario_medico_uuid' => $usuarioUuid,
                        'fecha' => $fecha,
                        'sede_id' => $sedeId
                    ]);

                    if (isset($response['success']) && $response['success'] && !empty($response['data'])) {
                        Log::info('✅ Agendas obtenidas desde API', [
                            'total' => count($response['data'])
                        ]);

                        // ✅ GUARDAR OFFLINE PARA CACHE
                        foreach ($response['data'] as $agenda) {
                            $this->offlineService->storeAgendaOffline($agenda, false);
                        }

                        return [
                            'data' => $response['data'],
                            'offline' => false
                        ];
                    } else {
                        Log::warning('⚠️ Respuesta API vacía o sin éxito', [
                            'response' => $response
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error API agendas, usando offline', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ✅ OBTENER DESDE OFFLINE
            Log::info('📱 Obteniendo agendas desde offline');
            
            $agendasOffline = $this->offlineService->getAgendasDelDia($usuarioUuid, $fecha);
            
            Log::info('📱 Agendas offline obtenidas', [
                'total' => count($agendasOffline)
            ]);

            return [
                'data' => $agendasOffline,
                'offline' => true
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo agendas del profesional', [
                'error' => $e->getMessage(),
                'usuario_uuid' => $usuarioUuid,
                'fecha' => $fecha
            ]);

            return [
                'data' => [],
                'offline' => true
            ];
        }
    }

    /**
     * ✅ ENRIQUECER AGENDAS CON CITAS
     */
    private function enriquecerAgendasConCitas(array $agendas, string $fecha): array
    {
        try {
            $agendasEnriquecidas = [];

            foreach ($agendas as $agenda) {
                Log::info('🔍 Enriqueciendo agenda con citas', [
                    'agenda_uuid' => $agenda['uuid'],
                    'fecha' => $fecha
                ]);

                // ✅ OBTENER CITAS DE LA AGENDA
                $citas = $this->obtenerCitasAgenda($agenda['uuid'], $fecha);
                
                // ✅ CALCULAR CUPOS Y ESTADÍSTICAS
                $agenda = $this->calcularCuposAgenda($agenda);
                $agenda['citas'] = $citas['data'];
                $agenda['estadisticas'] = $this->calcularEstadisticasAgenda($citas['data']);
                $agenda['source'] = $citas['offline'] ? 'offline' : 'api';

                // ✅ ACTUALIZAR CUPOS DISPONIBLES BASADO EN CITAS ACTIVAS
                $citasActivas = array_filter($citas['data'], function($cita) {
                    return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
                });
                $agenda['cupos_disponibles'] = max(0, ($agenda['total_cupos'] ?? 0) - count($citasActivas));

                Log::info('✅ Agenda enriquecida', [
                    'agenda_uuid' => $agenda['uuid'],
                    'total_citas' => count($citas['data']),
                    'citas_activas' => count($citasActivas),
                    'cupos_disponibles' => $agenda['cupos_disponibles']
                ]);

                $agendasEnriquecidas[] = $agenda;
            }

            return $agendasEnriquecidas;

        } catch (\Exception $e) {
            Log::error('❌ Error enriqueciendo agendas', [
                'error' => $e->getMessage()
            ]);

            return $agendas;
        }
    }

  private function obtenerCitasAgenda(string $agendaUuid, string $fecha): array
{
    try {
        $isOffline = $this->authService->isOffline();
        
        if (!$isOffline && $this->apiService->isOnline()) {
            try {
                Log::info('🌐 Obteniendo citas desde API', [
                    'agenda_uuid' => $agendaUuid,
                    'fecha' => $fecha
                ]);

                $response = $this->apiService->get("/agendas/{$agendaUuid}/citas", [
                    'fecha' => $fecha
                ]);

                if (isset($response['success']) && $response['success']) {
                    $citasApi = $response['data'] ?? [];
                    
                    Log::info('✅ Citas obtenidas desde API', [
                        'total' => count($citasApi)
                    ]);

                    // ✅ CAMBIO CRÍTICO: Solo usar API si realmente hay citas O si offline también está vacío
                    if (!empty($citasApi)) {
                        // ✅ GUARDAR CITAS OFFLINE PARA CACHE
                        foreach ($citasApi as $cita) {
                            $this->offlineService->storeCitaOffline($cita, false);
                        }

                        return [
                            'data' => $citasApi,
                            'offline' => false
                        ];
                    } else {
                        // ✅ API VACÍA: Verificar si offline tiene datos antes de decidir
                        Log::info('⚠️ API devolvió 0 citas, verificando offline como fallback');
                        
                        // Verificar offline primero
                        $usuario = $this->authService->usuario();
                        $sedeId = $usuario['sede_id'];
                        
                        $citasOffline = $this->offlineService->getCitasOffline($sedeId, [
                            'agenda_uuid' => $agendaUuid,
                            'fecha' => $fecha
                        ]);
                        
                        if (!empty($citasOffline)) {
                            Log::info('✅ Encontradas citas offline, usando como fallback', [
                                'total_offline' => count($citasOffline)
                            ]);
                            
                            return [
                                'data' => $citasOffline,
                                'offline' => true
                            ];
                        } else {
                            Log::info('📋 Tanto API como offline están vacíos');
                            
                            return [
                                'data' => [],
                                'offline' => false
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error API citas, usando offline', [
                    'error' => $e->getMessage(),
                    'agenda_uuid' => $agendaUuid
                ]);
            }
        }

        // ✅ OBTENER DESDE OFFLINE (cuando estamos offline o API falló)
        Log::info('📱 Obteniendo citas desde offline', [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha,
            'motivo' => $isOffline ? 'modo_offline' : 'api_error'
        ]);
        
        // ✅ OBTENER SEDE DEL USUARIO
        $usuario = $this->authService->usuario();
        $sedeId = $usuario['sede_id'];
        
        // ✅ USAR EL MÉTODO CORRECTO CON PARÁMETROS CORRECTOS
        $citasOffline = $this->offlineService->getCitasOffline($sedeId, [
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha
        ]);
        
        Log::info('📱 Citas offline obtenidas', [
            'total' => count($citasOffline),
            'sede_id' => $sedeId,
            'agenda_uuid' => $agendaUuid,
            'fecha' => $fecha
        ]);
        
        return [
            'data' => $citasOffline,
            'offline' => true
        ];

    } catch (\Exception $e) {
        Log::error('❌ Error obteniendo citas de agenda', [
            'error' => $e->getMessage(),
            'agenda_uuid' => $agendaUuid,
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'data' => [],
            'offline' => true
        ];
    }
}
    /**
     * ✅ GENERAR DATOS DE PRUEBA PARA DESARROLLO
     */
    private function generarDatosPrueba(string $fecha, string $usuarioUuid): array
    {
        Log::info('🧪 Generando datos de prueba para cronograma', [
            'fecha' => $fecha,
            'usuario_uuid' => $usuarioUuid
        ]);

        return [
            [
                'uuid' => 'test-agenda-' . uniqid(),
                'nombre' => 'Consulta Medicina General - PRUEBA',
                'medico_uuid' => $usuarioUuid,
                'medico_nombre' => 'Dr. Juan Pérez (PRUEBA)',
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
                'intervalo' => 30,
                'fecha' => $fecha,
                'total_cupos' => 8,
                'cupos_disponibles' => 3,
                'citas' => [
                    [
                        'uuid' => 'test-cita-1',
                        'hora' => '08:00',
                        'paciente_nombre' => 'María González',
                        'paciente_documento' => '12345678',
                        'estado' => 'PROGRAMADA',
                        'cups_nombre' => 'Consulta Medicina General',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-' . uniqid()
                    ],
                    [
                        'uuid' => 'test-cita-2',
                        'hora' => '08:30',
                        'paciente_nombre' => 'Carlos Rodríguez',
                        'paciente_documento' => '87654321',
                        'estado' => 'ATENDIDA',
                        'cups_nombre' => 'Consulta Medicina General',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-' . uniqid()
                    ],
                    [
                        'uuid' => 'test-cita-3',
                        'hora' => '09:00',
                        'paciente_nombre' => 'Ana Martínez',
                        'paciente_documento' => '11223344',
                        'estado' => 'EN_ATENCION',
                        'cups_nombre' => 'Consulta Medicina General',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-' . uniqid()
                    ],
                    [
                        'uuid' => 'test-cita-4',
                        'hora' => '09:30',
                        'paciente_nombre' => 'Luis Fernández',
                        'paciente_documento' => '55667788',
                        'estado' => 'PROGRAMADA',
                        'cups_nombre' => 'Consulta Medicina General',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-' . uniqid()
                    ],
                    [
                        'uuid' => 'test-cita-5',
                        'hora' => '10:00',
                        'paciente_nombre' => 'Carmen López',
                        'paciente_documento' => '99887766',
                        'estado' => 'CANCELADA',
                        'cups_nombre' => 'Consulta Medicina General',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-' . uniqid()
                    ]
                ],
                'estadisticas' => [
                    'PROGRAMADA' => 2,
                    'EN_ATENCION' => 1,
                    'ATENDIDA' => 1,
                    'CANCELADA' => 1,
                    'NO_ASISTIO' => 0
                ],
                'source' => 'prueba'
            ],
            [
                'uuid' => 'test-agenda-2-' . uniqid(),
                'nombre' => 'Consulta Especializada - PRUEBA',
                'medico_uuid' => $usuarioUuid,
                'medico_nombre' => 'Dr. Juan Pérez (PRUEBA)',
                'hora_inicio' => '14:00',
                'hora_fin' => '17:00',
                'intervalo' => 45,
                'fecha' => $fecha,
                'total_cupos' => 4,
                'cupos_disponibles' => 2,
                'citas' => [
                    [
                        'uuid' => 'test-cita-6',
                        'hora' => '14:00',
                        'paciente_nombre' => 'Pedro Sánchez',
                        'paciente_documento' => '33445566',
                        'estado' => 'ATENDIDA',
                        'cups_nombre' => 'Consulta Especializada',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-2-' . uniqid()
                    ],
                    [
                        'uuid' => 'test-cita-7',
                        'hora' => '14:45',
                        'paciente_nombre' => 'Isabel Torres',
                        'paciente_documento' => '77889900',
                        'estado' => 'PROGRAMADA',
                        'cups_nombre' => 'Consulta Especializada',
                        'fecha' => $fecha,
                        'agenda_uuid' => 'test-agenda-2-' . uniqid()
                    ]
                ],
                'estadisticas' => [
                    'PROGRAMADA' => 1,
                    'EN_ATENCION' => 0,
                    'ATENDIDA' => 1,
                    'CANCELADA' => 0,
                    'NO_ASISTIO' => 0
                ],
                'source' => 'prueba'
            ]
        ];
    }

    /**
     * ✅ CALCULAR CUPOS DE AGENDA
     */
    private function calcularCuposAgenda(array $agenda): array
    {
        try {
            $horaInicio = $agenda['hora_inicio'] ?? '08:00';
            $horaFin = $agenda['hora_fin'] ?? '17:00';
            $intervalo = (int) ($agenda['intervalo'] ?? 15);

            if ($intervalo <= 0) $intervalo = 15;

            $inicio = Carbon::createFromFormat('H:i', $horaInicio);
            $fin = Carbon::createFromFormat('H:i', $horaFin);
            
            $duracionMinutos = $fin->diffInMinutes($inicio);
            $totalCupos = floor($duracionMinutos / $intervalo);

            $agenda['total_cupos'] = $totalCupos;
            $agenda['cupos_disponibles'] = $totalCupos; // Se actualizará con las citas

            return $agenda;

        } catch (\Exception $e) {
            Log::error('❌ Error calculando cupos', [
                'error' => $e->getMessage(),
                'agenda_uuid' => $agenda['uuid'] ?? 'N/A'
            ]);

            $agenda['total_cupos'] = 0;
            $agenda['cupos_disponibles'] = 0;
            return $agenda;
        }
    }

    /**
     * ✅ CALCULAR ESTADÍSTICAS DE AGENDA
     */
    private function calcularEstadisticasAgenda(array $citas): array
    {
        $estadisticas = [
            'PROGRAMADA' => 0,
            'EN_ATENCION' => 0,
            'ATENDIDA' => 0,
            'CANCELADA' => 0,
            'NO_ASISTIO' => 0
        ];

        foreach ($citas as $cita) {
            $estado = $cita['estado'] ?? 'PROGRAMADA';
            if (isset($estadisticas[$estado])) {
                $estadisticas[$estado]++;
            }
        }

        return $estadisticas;
    }

    /**
     * ✅ CALCULAR ESTADÍSTICAS GLOBALES
     */
    private function calcularEstadisticasGlobales(string $fecha, array $usuario, array $agendas = null): array
    {
        try {
            if ($agendas === null) {
                $usuarioUuid = $usuario['uuid'] ?? null;
                $sedeId = $usuario['sede_id'] ?? 1;
                $agendasResult = $this->obtenerAgendasProfesional($usuarioUuid, $fecha, $sedeId);
                $agendas = $this->enriquecerAgendasConCitas($agendasResult['data'], $fecha);
            }

            $estadisticas = [
                'total_agendas' => count($agendas),
                'total_citas' => 0,
                'cupos_disponibles' => 0,
                'total_cupos' => 0,
                'por_estado' => [
                    'PROGRAMADA' => 0,
                    'EN_ATENCION' => 0,
                    'ATENDIDA' => 0,
                    'CANCELADA' => 0,
                    'NO_ASISTIO' => 0
                ]
            ];

            foreach ($agendas as $agenda) {
                $estadisticas['total_cupos'] += $agenda['total_cupos'] ?? 0;
                
                if (isset($agenda['citas'])) {
                    $citasActivas = array_filter($agenda['citas'], function($cita) {
                        return !in_array($cita['estado'] ?? 'PROGRAMADA', ['CANCELADA', 'NO_ASISTIO']);
                    });
                    
                    $estadisticas['total_citas'] += count($agenda['citas']);
                    $estadisticas['cupos_disponibles'] += max(0, ($agenda['total_cupos'] ?? 0) - count($citasActivas));

                    foreach ($agenda['citas'] as $cita) {
                        $estado = $cita['estado'] ?? 'PROGRAMADA';
                        if (isset($estadisticas['por_estado'][$estado])) {
                            $estadisticas['por_estado'][$estado]++;
                        }
                    }
                }
            }

            // ✅ CALCULAR PORCENTAJE DE OCUPACIÓN
            if ($estadisticas['total_cupos'] > 0) {
                $cuposOcupados = $estadisticas['total_cupos'] - $estadisticas['cupos_disponibles'];
                $estadisticas['porcentaje_ocupacion_global'] = round(($cuposOcupados / $estadisticas['total_cupos']) * 100, 1);
            } else {
                $estadisticas['porcentaje_ocupacion_global'] = 0;
            }

            return $estadisticas;

        } catch (\Exception $e) {
            Log::error('❌ Error calculando estadísticas globales', [
                'error' => $e->getMessage()
            ]);

            return $this->getEstadisticasVacias();
        }
    }

    /**
     * ✅ ESTADÍSTICAS VACÍAS POR DEFECTO
     */
    private function getEstadisticasVacias(): array
    {
        return [
            'total_agendas' => 0,
            'total_citas' => 0,
            'cupos_disponibles' => 0,
            'total_cupos' => 0,
            'porcentaje_ocupacion_global' => 0,
            'por_estado' => [
                'PROGRAMADA' => 0,
                'EN_ATENCION' => 0,
                'ATENDIDA' => 0,
                'CANCELADA' => 0,
                'NO_ASISTIO' => 0
            ]
        ];
    }

    /**
     * ✅ OBTENER MENSAJE DE ESTADO
     */
    private function getMensajeEstado(array $cronogramaData): string
    {
        if (isset($cronogramaData['es_prueba']) && $cronogramaData['es_prueba']) {
            return 'Datos de prueba - Desarrollo';
        }
        
        if ($cronogramaData['offline'] ?? false) {
            return 'Datos locales - Sin conexión';
        }
        
        return 'Datos actualizados desde servidor';
    }

    /**
     * ✅ VALIDAR FECHA
     */
    private function isValidDate(string $fecha): bool
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $fecha);
            return $date && $date->format('Y-m-d') === $fecha;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: OBTENER RESUMEN DEL DÍA
     */
    public function resumenDia(Request $request, string $fecha)
    {
        try {
            $usuario = $this->authService->usuario();
            
            if (!$this->isValidDate($fecha)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Fecha inválida'
                ], 400);
            }

            $cronogramaData = $this->obtenerDatosCronogramaConFallback($fecha, $usuario);
            
            // ✅ CREAR RESUMEN EJECUTIVO
            $resumen = [
                'fecha' => $fecha,
                'total_agendas' => count($cronogramaData['agendas']),
                'total_citas' => $cronogramaData['estadisticas']['total_citas'],
                'citas_atendidas' => $cronogramaData['estadisticas']['por_estado']['ATENDIDA'],
                'citas_pendientes' => $cronogramaData['estadisticas']['por_estado']['PROGRAMADA'] + 
                                   $cronogramaData['estadisticas']['por_estado']['EN_ATENCION'],
                'citas_canceladas' => $cronogramaData['estadisticas']['por_estado']['CANCELADA'] + 
                                    $cronogramaData['estadisticas']['por_estado']['NO_ASISTIO'],
                'porcentaje_ocupacion' => $cronogramaData['estadisticas']['porcentaje_ocupacion_global'],
                'cupos_disponibles' => $cronogramaData['estadisticas']['cupos_disponibles'],
                'offline' => $cronogramaData['offline'] ?? false,
                'es_prueba' => $cronogramaData['es_prueba'] ?? false
            ];

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => $this->getMensajeEstado($cronogramaData)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo resumen del día', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo resumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: OBTENER PRÓXIMAS CITAS
     */
    public function proximasCitas(Request $request)
    {
        try {
            $usuario = $this->authService->usuario();
            $fecha = now()->format('Y-m-d');
            $horaActual = now()->format('H:i');

            $cronogramaData = $this->obtenerDatosCronogramaConFallback($fecha, $usuario);
            
            $proximasCitas = [];
            
            foreach ($cronogramaData['agendas'] as $agenda) {
                foreach ($agenda['citas'] ?? [] as $cita) {
                    if (($cita['estado'] ?? '') === 'PROGRAMADA' && 
                        ($cita['hora'] ?? '') >= $horaActual) {
                        $proximasCitas[] = [
                            'cita_uuid' => $cita['uuid'],
                            'hora' => $cita['hora'],
                            'paciente_nombre' => $cita['paciente_nombre'] ?? 'Sin nombre',
                            'paciente_documento' => $cita['paciente_documento'] ?? 'Sin documento',
                            'agenda_nombre' => $agenda['nombre'] ?? 'Sin agenda',
                            'cups_nombre' => $cita['cups_nombre'] ?? 'Sin procedimiento'
                        ];
                    }
                }
            }

            // ✅ ORDENAR POR HORA
            usort($proximasCitas, function($a, $b) {
                return strcmp($a['hora'], $b['hora']);
            });

            // ✅ TOMAR SOLO LAS PRÓXIMAS 5
            $proximasCitas = array_slice($proximasCitas, 0, 5);

            return response()->json([
                'success' => true,
                'data' => $proximasCitas,
                'total' => count($proximasCitas),
                'offline' => $cronogramaData['offline'] ?? false,
                'es_prueba' => $cronogramaData['es_prueba'] ?? false
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo próximas citas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo próximas citas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MÉTODO ADICIONAL: ESTADÍSTICAS SEMANALES
     */
    public function estadisticasSemanales(Request $request)
    {
        try {
            $usuario = $this->authService->usuario();
            $fechaInicio = $request->get('fecha_inicio', now()->startOfWeek()->format('Y-m-d'));
            $fechaFin = $request->get('fecha_fin', now()->endOfWeek()->format('Y-m-d'));

            if (!$this->isValidDate($fechaInicio) || !$this->isValidDate($fechaFin)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Fechas inválidas'
                ], 400);
            }

            $estadisticasSemanales = [];
            $fechaActual = Carbon::createFromFormat('Y-m-d', $fechaInicio);
            $fechaLimite = Carbon::createFromFormat('Y-m-d', $fechaFin);

            while ($fechaActual <= $fechaLimite) {
                $fechaStr = $fechaActual->format('Y-m-d');
                
                try {
                    $cronogramaData = $this->obtenerDatosCronogramaConFallback($fechaStr, $usuario);
                    
                    $estadisticasSemanales[] = [
                        'fecha' => $fechaStr,
                        'dia_semana' => $fechaActual->locale('es')->isoFormat('dddd'),
                        'total_citas' => $cronogramaData['estadisticas']['total_citas'],
                        'citas_atendidas' => $cronogramaData['estadisticas']['por_estado']['ATENDIDA'],
                        'porcentaje_ocupacion' => $cronogramaData['estadisticas']['porcentaje_ocupacion_global']
                    ];
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error obteniendo datos para fecha', [
                        'fecha' => $fechaStr,
                        'error' => $e->getMessage()
                    ]);
                    
                    $estadisticasSemanales[] = [
                        'fecha' => $fechaStr,
                        'dia_semana' => $fechaActual->locale('es')->isoFormat('dddd'),
                        'total_citas' => 0,
                        'citas_atendidas' => 0,
                        'porcentaje_ocupacion' => 0
                    ];
                }

                $fechaActual->addDay();
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticasSemanales,
                'periodo' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo estadísticas semanales', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estadísticas semanales: ' . $e->getMessage()
            ], 500);
        }
    }
}

                        
