<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AgendaService, CitaService, AuthService, ApiService, OfflineService};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronogramaController extends Controller
{
    protected $agendaService;
    protected $citaService;
    protected $authService;
    protected $apiService;
    protected $offlineService;

    public function __construct(
        AgendaService $agendaService,
        CitaService $citaService,
        AuthService $authService,
        ApiService $apiService,
        OfflineService $offlineService
    ) {
        $this->agendaService = $agendaService;
        $this->citaService = $citaService;
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    /**
     * ✅ MÉTODO PRINCIPAL (tu ruta existente mantenida)
     */
    public function index(Request $request)
    {
        try {
            $fechaSeleccionada = $request->get('fecha', now()->format('Y-m-d'));
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();

            Log::info('🏥 Cronograma index', [
                'fecha' => $fechaSeleccionada,
                'usuario_id' => $usuario['id'] ?? null,
                'is_ajax' => $request->ajax()
            ]);

            // ✅ OBTENER DATOS COMPLETOS USANDO TUS SERVICIOS EXISTENTES
            $cronogramaData = $this->obtenerDatosCronogramaIntegrado($fechaSeleccionada, $usuario);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $cronogramaData,
                    'offline' => $isOffline
                ]);
            }

            return view('cronograma.index', [
                'fechaSeleccionada' => $fechaSeleccionada,
                'usuario' => $usuario,
                'cronogramaData' => $cronogramaData,
                'isOffline' => $isOffline
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en cronograma index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ]);
            }

            return view('cronograma.index', [
                'fechaSeleccionada' => $fechaSeleccionada,
                'usuario' => $usuario ?? [],
                'cronogramaData' => $this->getCronogramaVacio(),
                'isOffline' => true,
                'error' => 'Error cargando cronograma'
            ]);
        }
    }

    /**
     * ✅ DETALLE DE CITA (tu ruta existente mantenida)
     */
    public function getDetalleCita($uuid)
    {
        try {
            Log::info('👁️ Obteniendo detalle de cita', ['cita_uuid' => $uuid]);

            // ✅ USAR TU CitaService EXISTENTE
            $result = $this->citaService->show($uuid);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Cita no encontrada'
                ]);
            }

            $cita = $result['data'];
            
            // ✅ ENRIQUECER CON DATOS ADICIONALES PARA CRONOGRAMA
            $citaEnriquecida = $this->enriquecerCitaParaCronograma($cita);

            return response()->json([
                'success' => true,
                'data' => $citaEnriquecida
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo detalle de cita', [
                'error' => $e->getMessage(),
                'cita_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * ✅ CAMBIAR ESTADO DE CITA (tu ruta existente mantenida)
     */
    public function cambiarEstadoCita(Request $request, $uuid)
    {
        try {
            $nuevoEstado = $request->input('estado');
            
            Log::info('🔄 Cambiando estado de cita desde cronograma', [
                'cita_uuid' => $uuid,
                'estado' => $nuevoEstado
            ]);

            $request->validate([
                'estado' => 'required|in:PROGRAMADA,EN_ATENCION,ATENDIDA,CANCELADA,NO_ASISTIO'
            ]);

            // ✅ USAR TU CitaService EXISTENTE
            $result = $this->citaService->cambiarEstado($uuid, $nuevoEstado);

            if ($result['success']) {
                Log::info('✅ Estado de cita cambiado desde cronograma', [
                    'cita_uuid' => $uuid,
                    'estado' => $nuevoEstado
                ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('❌ Error cambiando estado de cita', [
                'error' => $e->getMessage(),
                'cita_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * ✅ NUEVA: Obtener citas de una agenda específica
     */
    public function getCitasAgenda(Request $request, $uuid)
    {
        try {
            $fecha = $request->get('fecha', now()->format('Y-m-d'));
            
            Log::info('🔍 Obteniendo citas de agenda para cronograma', [
                'agenda_uuid' => $uuid,
                'fecha' => $fecha
            ]);

            // ✅ USAR TU CitaService EXISTENTE
            $citasResult = $this->citaService->index([
                'agenda_uuid' => $uuid,
                'fecha' => $fecha
            ], 1, 100);

            if (!$citasResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $citasResult['error'] ?? 'Error obteniendo citas'
                ]);
            }

            $citas = $citasResult['data'] ?? [];

            // ✅ ENRIQUECER CITAS PARA CRONOGRAMA
            $citasEnriquecidas = array_map(function($cita) {
                return $this->enriquecerCitaParaCronograma($cita);
            }, $citas);

            return response()->json([
                'success' => true,
                'data' => $citasEnriquecidas,
                'total' => count($citasEnriquecidas),
                'agenda_uuid' => $uuid,
                'fecha' => $fecha
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo citas de agenda', [
                'error' => $e->getMessage(),
                'agenda_uuid' => $uuid
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ NUEVA: Obtener datos del cronograma (AJAX)
     */
    public function getData(Request $request, $fecha = null)
    {
        try {
            $fecha = $fecha ?? $request->get('fecha', now()->format('Y-m-d'));
            $usuario = $this->authService->usuario();

            Log::info('📊 Obteniendo datos de cronograma vía AJAX', [
                'fecha' => $fecha,
                'usuario_id' => $usuario['id'] ?? null
            ]);

            $cronogramaData = $this->obtenerDatosCronogramaIntegrado($fecha, $usuario);

            return response()->json([
                'success' => true,
                'data' => $cronogramaData,
                'fecha' => $fecha,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo datos de cronograma', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✅ NUEVA: Refrescar cronograma
     */
    public function refresh(Request $request)
    {
        try {
            $fecha = $request->get('fecha', now()->format('Y-m-d'));
            
            Log::info('🔄 Refrescando cronograma', ['fecha' => $fecha]);

            // ✅ LIMPIAR CACHÉ SI ES NECESARIO
            $this->limpiarCacheCronograma($fecha);

            // ✅ OBTENER DATOS FRESCOS
            $usuario = $this->authService->usuario();
            $cronogramaData = $this->obtenerDatosCronogramaIntegrado($fecha, $usuario);

            return response()->json([
                'success' => true,
                'data' => $cronogramaData,
                'message' => 'Cronograma actualizado correctamente',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error refrescando cronograma', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error actualizando cronograma'
            ], 500);
        }
    }

    /**
     * ✅ MÉTODO PRINCIPAL: Obtener datos integrados del cronograma
     */
    private function obtenerDatosCronogramaIntegrado($fecha, $usuario)
    {
        try {
            Log::info('📊 Obteniendo cronograma integrado', [
                'fecha' => $fecha,
                'usuario_id' => $usuario['id'] ?? null
            ]);

            // ✅ PASO 1: Obtener agendas del día usando TU AgendaService
            $agendasResult = $this->agendaService->index([
                'fecha_desde' => $fecha,
                'fecha_hasta' => $fecha,
                'estado' => 'ACTIVO'
            ], 1, 50);

            $agendas = $agendasResult['success'] ? ($agendasResult['data'] ?? []) : [];

            Log::info('📋 Agendas obtenidas para cronograma', [
                'total_agendas' => count($agendas),
                'success' => $agendasResult['success']
            ]);

            // ✅ PASO 2: Enriquecer cada agenda con sus citas usando TU CitaService
            $agendasEnriquecidas = [];
            foreach ($agendas as $agenda) {
                $agendaEnriquecida = $this->enriquecerAgendaConCitasIntegrada($agenda, $fecha);
                $agendasEnriquecidas[] = $agendaEnriquecida;
            }

            // ✅ PASO 3: Calcular estadísticas globales
            $estadisticas = $this->calcularEstadisticasGlobales($agendasEnriquecidas);

            // ✅ PASO 4: Obtener resumen de citas del día usando TU CitaService
            $resumenCitas = $this->obtenerResumenCitasDelDia($fecha);

            return [
                'agendas' => $agendasEnriquecidas,
                'estadisticas' => $estadisticas,
                'resumen_citas' => $resumenCitas,
                'fecha' => $fecha,
                'total_agendas' => count($agendasEnriquecidas),
                'isOffline' => $this->authService->isOffline(),
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo cronograma integrado', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            return $this->getCronogramaVacio();
        }
    }

    /**
     * ✅ ENRIQUECER AGENDA CON CITAS (usando tus servicios existentes)
     */
    private function enriquecerAgendaConCitasIntegrada($agenda, $fecha)
    {
        try {
            Log::info('🔍 Enriqueciendo agenda con citas integrada', [
                'agenda_uuid' => $agenda['uuid'] ?? 'unknown',
                'fecha' => $fecha
            ]);

            // ✅ OBTENER CITAS usando TU CitaService
            $citasResult = $this->citaService->index([
                'agenda_uuid' => $agenda['uuid'],
                'fecha' => $fecha
            ], 1, 100);

            $citas = $citasResult['success'] ? ($citasResult['data'] ?? []) : [];

            // ✅ ENRIQUECER CITAS PARA CRONOGRAMA
            $citasEnriquecidas = array_map(function($cita) {
                return $this->enriquecerCitaParaCronograma($cita);
            }, $citas);

            // ✅ CALCULAR CUPOS Y ESTADÍSTICAS
            $totalCupos = $this->calcularTotalCupos($agenda);
            $citasActivas = array_filter($citasEnriquecidas, function($cita) {
                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
            });

            // ✅ AGREGAR DATOS CALCULADOS A LA AGENDA
            $agenda['citas'] = $citasEnriquecidas;
            $agenda['total_citas'] = count($citasEnriquecidas);
            $agenda['citas_activas'] = count($citasActivas);
            $agenda['total_cupos'] = $totalCupos;
            $agenda['cupos_disponibles'] = max(0, $totalCupos - count($citasActivas));
            $agenda['porcentaje_ocupacion'] = $totalCupos > 0 ? 
                round((count($citasActivas) / $totalCupos) * 100, 1) : 0;

            // ✅ ESTADÍSTICAS POR ESTADO
            $agenda['estadisticas'] = $this->calcularEstadisticasPorEstado($citasEnriquecidas);

            Log::info('✅ Agenda enriquecida para cronograma', [
                'agenda_uuid' => $agenda['uuid'],
                'total_citas' => count($citasEnriquecidas),
                'cupos_disponibles' => $agenda['cupos_disponibles']
            ]);

            return $agenda;

        } catch (\Exception $e) {
            Log::error('❌ Error enriqueciendo agenda integrada', [
                'error' => $e->getMessage(),
                'agenda_uuid' => $agenda['uuid'] ?? 'unknown'
            ]);

            // ✅ VALORES POR DEFECTO EN CASO DE ERROR
            $agenda['citas'] = [];
            $agenda['total_citas'] = 0;
            $agenda['citas_activas'] = 0;
            $agenda['total_cupos'] = 0;
            $agenda['cupos_disponibles'] = 0;
            $agenda['porcentaje_ocupacion'] = 0;
            $agenda['estadisticas'] = $this->getEstadisticasVacias();

            return $agenda;
        }
    }

    /**
     * ✅ ENRIQUECER CITA PARA CRONOGRAMA
     */
    private function enriquecerCitaParaCronograma($cita)
    {
        try {
            // ✅ FORMATEAR FECHAS Y HORAS
            if (isset($cita['fecha_inicio'])) {
                $cita['hora_inicio'] = date('H:i', strtotime($cita['fecha_inicio']));
                $cita['fecha_formateada'] = date('d/m/Y', strtotime($cita['fecha_inicio']));
            }

            if (isset($cita['fecha_final'])) {
                $cita['hora_final'] = date('H:i', strtotime($cita['fecha_final']));
            }

            // ✅ INFORMACIÓN DE ESTADO CON COLORES
            $cita['estado_info'] = $this->getEstadoInfo($cita['estado'] ?? 'PROGRAMADA');

            // ✅ FORMATEAR INFORMACIÓN DEL PACIENTE
            if (isset($cita['paciente'])) {
                $cita['paciente_nombre'] = $cita['paciente']['nombre_completo'] ?? 'Sin nombre';
                $cita['paciente_documento'] = $cita['paciente']['documento'] ?? 'Sin documento';
                $cita['paciente_telefono'] = $cita['paciente']['telefono'] ?? '';
            }

            // ✅ TIEMPO TRANSCURRIDO/RESTANTE
            if (isset($cita['fecha_inicio'])) {
                $fechaCita = Carbon::parse($cita['fecha_inicio']);
                $ahora = now();
                
                if ($fechaCita->isPast()) {
                    $cita['tiempo_info'] = [
                        'tipo' => 'pasado',
                        'texto' => 'Hace ' . $fechaCita->diffForHumans($ahora, true)
                    ];
                } else {
                    $cita['tiempo_info'] = [
                        'tipo' => 'futuro',
                        'texto' => 'En ' . $ahora->diffForHumans($fechaCita, true)
                    ];
                }
            }

            return $cita;

        } catch (\Exception $e) {
            Log::warning('⚠️ Error enriqueciendo cita para cronograma', [
                'error' => $e->getMessage(),
                'cita_uuid' => $cita['uuid'] ?? 'unknown'
            ]);

            return $cita;
        }
    }

    // ✅ MÉTODOS AUXILIARES (mantenidos de la implementación anterior)
    private function calcularTotalCupos($agenda)
    {
        try {
            $horaInicio = $agenda['hora_inicio'] ?? '08:00';
            $horaFin = $agenda['hora_fin'] ?? '17:00';
            $intervalo = (int) ($agenda['intervalo'] ?? 15);

            $inicio = Carbon::createFromFormat('H:i', $horaInicio);
            $fin = Carbon::createFromFormat('H:i', $horaFin);
            
            $duracionMinutos = $fin->diffInMinutes($inicio);
            $totalCupos = floor($duracionMinutos / $intervalo);

            return max(1, $totalCupos);

        } catch (\Exception $e) {
            Log::warning('⚠️ Error calculando cupos', [
                'error' => $e->getMessage(),
                'agenda' => $agenda['uuid'] ?? 'unknown'
            ]);
            
            return 20; // Valor por defecto
        }
    }

    private function calcularEstadisticasPorEstado($citas)
    {
        $estadisticas = [
            'PROGRAMADA' => 0,
            'EN_ATENCION' => 0,
            'ATENDIDA' => 0,
            'CANCELADA' => 0,
            'NO_ASISTIO' => 0
        ];

        foreach ($citas as $cita) {
            $estado = strtoupper($cita['estado'] ?? 'PROGRAMADA');
            if (isset($estadisticas[$estado])) {
                $estadisticas[$estado]++;
            }
        }

        return $estadisticas;
    }

    private function calcularEstadisticasGlobales($agendas)
    {
        $totales = [
            'total_agendas' => count($agendas),
            'total_cupos' => 0,
            'total_citas' => 0,
            'citas_activas' => 0,
            'cupos_disponibles' => 0,
            'porcentaje_ocupacion_global' => 0,
            'por_estado' => [
                'PROGRAMADA' => 0,
                'EN_ATENCION' => 0,
                'ATENDIDA' => 0,
                'CANCELADA' => 0,
                'NO_ASISTIO' => 0
            ]
        ];

        foreach ($agendas as $agenda) {
            $totales['total_cupos'] += $agenda['total_cupos'] ?? 0;
            $totales['total_citas'] += $agenda['total_citas'] ?? 0;
            $totales['citas_activas'] += $agenda['citas_activas'] ?? 0;
            $totales['cupos_disponibles'] += $agenda['cupos_disponibles'] ?? 0;

            $estadisticas = $agenda['estadisticas'] ?? [];
            foreach ($totales['por_estado'] as $estado => $valor) {
                $totales['por_estado'][$estado] += $estadisticas[$estado] ?? 0;
            }
        }

        $totales['porcentaje_ocupacion_global'] = $totales['total_cupos'] > 0 ? 
            round(($totales['citas_activas'] / $totales['total_cupos']) * 100, 1) : 0;

        return $totales;
    }

    private function obtenerResumenCitasDelDia($fecha)
    {
        try {
            // ✅ USAR TU CitaService EXISTENTE
            $citasResult = $this->citaService->citasDelDia($fecha);
            
            if (!$citasResult['success']) {
                return $this->getResumenVacio();
            }

            $citas = $citasResult['data'] ?? [];
            
            return [
                'total' => count($citas),
                'proximas' => $this->contarCitasProximas($citas),
                'en_atencion' => $this->contarCitasPorEstado($citas, 'EN_ATENCION'),
                'atendidas' => $this->contarCitasPorEstado($citas, 'ATENDIDA'),
                'canceladas' => $this->contarCitasPorEstado($citas, 'CANCELADA')
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo resumen de citas', [
                'error' => $e->getMessage(),
                'fecha' => $fecha
            ]);

            return $this->getResumenVacio();
        }
    }

    private function getEstadoInfo($estado)
    {
        $estados = [
            'PROGRAMADA' => ['label' => 'Programada', 'color' => 'primary', 'icon' => 'calendar'],
            'EN_ATENCION' => ['label' => 'En Atención', 'color' => 'warning', 'icon' => 'clock'],
            'ATENDIDA' => ['label' => 'Atendida', 'color' => 'success', 'icon' => 'check'],
            'CANCELADA' => ['label' => 'Cancelada', 'color' => 'danger', 'icon' => 'x'],
            'NO_ASISTIO' => ['label' => 'No Asistió', 'color' => 'secondary', 'icon' => 'user-x']
        ];

        return $estados[$estado] ?? $estados['PROGRAMADA'];
    }

    private function limpiarCacheCronograma($fecha)
    {
        try {
            $usuario = $this->authService->usuario();
            $sedeId = $usuario['sede_id'] ?? null;
            
            if ($sedeId) {
                // ✅ LIMPIAR CACHÉ ESPECÍFICO DEL CRONOGRAMA
                $cacheKeys = [
                    "cronograma_{$sedeId}_{$fecha}",
                    "agendas_{$sedeId}_{$fecha}",
                    "citas_del_dia_{$sedeId}_{$fecha}"
                ];
                
                foreach ($cacheKeys as $key) {
                    cache()->forget($key);
                }
                
                Log::info('🧹 Caché de cronograma limpiado', [
                    'sede_id' => $sedeId,
                    'fecha' => $fecha,
                    'keys_cleared' => count($cacheKeys)
                ]);
            }
            
        } catch (\Exception $e) {
            Log::warning('⚠️ Error limpiando caché de cronograma', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ✅ MÉTODOS DE UTILIDAD
    private function getCronogramaVacio()
    {
        return [
            'agendas' => [],
            'estadisticas' => [
                'total_agendas' => 0,
                'total_cupos' => 0,
                'total_citas' => 0,
                'citas_activas' => 0,
                'cupos_disponibles' => 0,
                'porcentaje_ocupacion_global' => 0,
                'por_estado' => [
                    'PROGRAMADA' => 0,
                    'EN_ATENCION' => 0,
                    'ATENDIDA' => 0,
                    'CANCELADA' => 0,
                    'NO_ASISTIO' => 0
                ]
            ],
            'resumen_citas' => $this->getResumenVacio(),
            'fecha' => now()->format('Y-m-d'),
            'total_agendas' => 0,
            'isOffline' => true
        ];
    }

    private function getEstadisticasVacias()
    {
        return [
            'PROGRAMADA' => 0,
            'EN_ATENCION' => 0,
            'ATENDIDA' => 0,
            'CANCELADA' => 0,
            'NO_ASISTIO' => 0
        ];
    }

    private function getResumenVacio()
    {
        return [
            'total' => 0,
            'proximas' => 0,
            'en_atencion' => 0,
            'atendidas' => 0,
            'canceladas' => 0
        ];
    }

    private function contarCitasProximas($citas)
    {
        $ahora = now();
        $contador = 0;
        
        foreach ($citas as $cita) {
            if (isset($cita['fecha_inicio'])) {
                $fechaCita = Carbon::parse($cita['fecha_inicio']);
                if ($fechaCita->gt($ahora) && $cita['estado'] === 'PROGRAMADA') {
                    $contador++;
                }
            }
        }
        
        return $contador;
    }

    private function contarCitasPorEstado($citas, $estado)
    {
        return count(array_filter($citas, function($cita) use ($estado) {
                       return ($cita['estado'] ?? '') === $estado;
        }));
    }
}


