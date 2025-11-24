<?php
// app/Http/Controllers/CitaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{CitaService, AuthService, ApiService, OfflineService, PacienteService, AgendaService};
use Illuminate\Support\Facades\Log;

class CitaController extends Controller
    {
        protected $citaService;
        protected $authService;
        protected $apiService;
        protected $offlineService;
        protected $pacienteService;
        protected $agendaService;

        public function __construct(
            CitaService $citaService, 
            AuthService $authService, 
            ApiService $apiService, 
            OfflineService $offlineService,
            PacienteService $pacienteService,
            AgendaService $agendaService
        ) {
            $this->middleware('custom.auth');
            $this->citaService = $citaService;
            $this->authService = $authService;
            $this->apiService = $apiService;
            $this->offlineService = $offlineService;
            $this->pacienteService = $pacienteService;
            $this->agendaService = $agendaService;
        }

        public function index(Request $request)
        {
            try {
                $filters = $request->only([
                    'fecha', 'estado', 'paciente_documento', 'fecha_inicio', 'fecha_fin'
                ]);
                
                $page = $request->get('page', 1);
                
                $result = $this->citaService->index($filters, $page);

                if ($request->ajax()) {
                    return response()->json($result);
                }

                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();

                return view('citas.index', compact('usuario', 'isOffline'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@index', [
                    'error' => $e->getMessage()
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error interno del servidor'
                    ], 500);
                }

                return back()->with('error', 'Error cargando citas');
            }
        }

        public function create()
        {
            try {
                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                
                // Obtener agendas disponibles
                $agendasResult = $this->agendaService->disponibles();
                $agendas = $agendasResult['success'] ? $agendasResult['data'] : [];

                return view('citas.create', compact('usuario', 'isOffline', 'agendas'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@create', [
                    'error' => $e->getMessage()
                ]);

                return back()->with('error', 'Error cargando formulario de creación');
            }
        }

        public function store(Request $request)
        {
            try {
                $validatedData = $request->validate([
                    'fecha' => 'required|date',
                    'fecha_inicio' => 'required|date',
                    'fecha_final' => 'required|date|after:fecha_inicio',
                    'fecha_deseada' => 'nullable|date',
                    'motivo' => 'nullable|string|max:200',
                    'nota' => 'required|string|max:200',
                    'estado' => 'nullable|string|max:50',
                    'patologia' => 'nullable|string|max:50',
                    'paciente_uuid' => 'required|string|max:100',
                    'agenda_uuid' => 'required|string|max:100',
                    'cups_contratado_uuid' => 'nullable|string|max:100',
                ]);

                $result = $this->citaService->store($validatedData);

                if ($request->ajax()) {
                    $response = $result;
                    if ($result['success']) {
                        $response['redirect_url'] = route('citas.index');
                    }
                    return response()->json($response);
                }

                if ($result['success']) {
                    return redirect()->route('citas.index')
                        ->with('success', $result['message'] ?? 'Cita creada exitosamente');
                }

                return back()
                    ->withErrors(['error' => $result['error']])
                    ->withInput();
                    
            } catch (\Exception $e) {
                Log::error('Error en CitaController@store', [
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

        public function show(string $uuid)
        {
            try {
                $result = $this->citaService->show($uuid);

                if (!$result['success']) {
                    abort(404, $result['error']);
                }

                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                $cita = $result['data'];

                return view('citas.show', compact('cita', 'usuario', 'isOffline'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@show', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                abort(500, 'Error interno del servidor');
            }
        }

        public function edit(string $uuid)
        {
            try {
                $result = $this->citaService->show($uuid);

                if (!$result['success']) {
                    abort(404, $result['error']);
                }

                $usuario = $this->authService->usuario();
                $isOffline = $this->authService->isOffline();
                $cita = $result['data'];
                
                // Obtener agendas disponibles
                $agendasResult = $this->agendaService->disponibles();
                $agendas = $agendasResult['success'] ? $agendasResult['data'] : [];

                return view('citas.edit', compact('cita', 'usuario', 'isOffline', 'agendas'));
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@edit', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return back()->with('error', 'Error cargando formulario de edición');
            }
        }

        public function update(Request $request, string $uuid)
        {
            try {
                $validatedData = $request->validate([
                    'fecha' => 'required|date',
                    'fecha_inicio' => 'required|date',
                    'fecha_final' => 'required|date|after:fecha_inicio',
                    'fecha_deseada' => 'nullable|date',
                    'motivo' => 'nullable|string|max:200',
                    'nota' => 'required|string|max:200',
                    'estado' => 'nullable|string|max:50',
                    'patologia' => 'nullable|string|max:50',
                    'paciente_uuid' => 'required|string|max:100',
                    'agenda_uuid' => 'required|string|max:100',
                    'cups_contratado_id' => 'nullable|string|max:100',
                ]);

                $result = $this->citaService->update($uuid, $validatedData);

                if ($request->ajax()) {
                    return response()->json($result);
                }

                if ($result['success']) {
                    return redirect()->route('citas.show', $uuid)
                        ->with('success', $result['message'] ?? 'Cita actualizada exitosamente');
                }

                return back()
                    ->withErrors(['error' => $result['error']])
                    ->withInput();
                    
            } catch (\Exception $e) {
                Log::error('Error en CitaController@update', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
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
                $result = $this->citaService->destroy($uuid);
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@destroy', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        public function citasDelDia(Request $request)
        {
            try {
                $fecha = $request->get('fecha', now()->format('Y-m-d'));
                $result = $this->citaService->citasDelDia($fecha);
                
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@citasDelDia', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        public function cambiarEstado(Request $request, string $uuid)
        {
            try {
                $request->validate([
                    'estado' => 'required|in:PROGRAMADA,EN_ATENCION,ATENDIDA,CANCELADA,NO_ASISTIO'
                ]);

                $result = $this->citaService->cambiarEstado($uuid, $request->estado);
                return response()->json($result);
                
            } catch (\Exception $e) {
                Log::error('Error en CitaController@cambiarEstado', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }
    public function buscarPaciente(Request $request)
    {
        try {
            $request->validate([
                'documento' => 'required|string|min:3'
            ]);

            Log::info('🔍 CitaController::buscarPaciente iniciado', [
                'documento' => $request->documento
            ]);

            $result = $this->pacienteService->searchByDocument($request->documento);
            
            // ✅ VALIDACIÓN ADICIONAL SI SE ENCUENTRA EL PACIENTE
            if ($result['success'] && isset($result['data']) && !empty($result['data'])) {
                $pacientes = $result['data'];
                $paciente = is_array($pacientes) ? $pacientes[0] : $pacientes;
                
                // ✅ VALIDAR UUID DEL PACIENTE ENCONTRADO
                if (!isset($paciente['uuid']) || empty($paciente['uuid'])) {
                    Log::error('❌ Paciente encontrado sin UUID válido', [
                        'documento' => $request->documento,
                        'paciente_keys' => array_keys($paciente),
                        'sede_id' => $paciente['sede_id'] ?? 'NO_DEFINIDA'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'El paciente encontrado no tiene un identificador válido'
                    ]);
                }

                // ✅ VALIDAR FORMATO DE UUID
                if (!$this->isValidUuid($paciente['uuid'])) {
                    Log::error('❌ UUID con formato inválido', [
                        'documento' => $request->documento,
                        'uuid' => $paciente['uuid']
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'El identificador del paciente tiene formato inválido'
                    ]);
                }

                Log::info('✅ Paciente encontrado correctamente', [
                    'documento' => $request->documento,
                    'uuid' => $paciente['uuid'],
                    'nombre' => ($paciente['primer_nombre'] ?? '') . ' ' . ($paciente['primer_apellido'] ?? ''),
                    'sede_id' => $paciente['sede_id'] ?? 'NO_DEFINIDA',
                    'offline' => $result['offline'] ?? false
                ]);

                // ✅ ASEGURAR QUE RETORNAMOS EL PACIENTE INDIVIDUAL
                $result['data'] = $paciente;
            }
            
            return response()->json($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Validación fallida en búsqueda de paciente', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en CitaController@buscarPaciente', [
                'documento' => $request->documento ?? 'NO_DEFINIDO',
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
     * ✅ CORREGIDO: Validar formato de UUID
     */
    private function isValidUuid($uuid): bool
    {
        if (empty($uuid) || !is_string($uuid)) {
            return false;
        }
        
        // ✅ PATRÓN UUID CORREGIDO (acepta cualquier versión de UUID)
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        
        return preg_match($pattern, $uuid) === 1;
    }


    /**
     * ✅ CORREGIDO: Obtener horarios disponibles de una agenda
     */
    public function getHorariosDisponibles(Request $request, string $agendaUuid)
    {
        try {
            $fecha = $request->get('fecha');
            
            Log::info('🔍 Controlador: Obteniendo horarios disponibles', [
                'agenda_uuid' => $agendaUuid,
                'fecha_solicitada' => $fecha
            ]);
            
            $result = $this->citaService->getHorariosDisponibles($agendaUuid, $fecha);
            
            // ✅ AGREGAR LOGGING DETALLADO
            if ($result['success'] && isset($result['data'])) {
                $disponibles = count(array_filter($result['data'], fn($h) => $h['disponible']));
                $ocupados = count(array_filter($result['data'], fn($h) => !$h['disponible']));
                
                Log::info('✅ Horarios obtenidos correctamente', [
                    'agenda_uuid' => $agendaUuid,
                    'total_horarios' => count($result['data']),
                    'disponibles' => $disponibles,
                    'ocupados' => $ocupados
                ]);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo horarios disponibles', [
                'agenda_uuid' => $agendaUuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

        // ✅ NUEVO: Obtener detalles de agenda
        public function getAgendaDetails(string $agendaUuid)
        {
            try {
                $result = $this->agendaService->show($agendaUuid);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Agenda no encontrada'
                    ]);
                }

                $agenda = $result['data'];
                
                // Calcular cupos y horarios
                $horarios = $this->calcularHorariosDisponibles($agenda);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'agenda' => $agenda,
                        'horarios_disponibles' => $horarios
                    ]
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error obteniendo detalles de agenda', [
                    'agenda_uuid' => $agendaUuid,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }
        }

        // ✅ NUEVO: Calcular horarios disponibles
        private function calcularHorariosDisponibles(array $agenda): array
        {
            try {
                $horarios = [];
                
                $fecha = $agenda['fecha'];
                $horaInicio = $agenda['hora_inicio'];
                $horaFin = $agenda['hora_fin'];
                $intervalo = (int) ($agenda['intervalo'] ?? 15);
                
                // Obtener citas existentes para esta agenda
                $citasExistentes = $this->obtenerCitasExistentes($agenda['uuid'], $fecha);
                $horariosOcupados = array_map(function($cita) {
                    return date('H:i', strtotime($cita['fecha_inicio']));
                }, $citasExistentes);
                
                // Generar todos los horarios posibles
                $inicio = \Carbon\Carbon::createFromFormat('H:i', $horaInicio);
                $fin = \Carbon\Carbon::createFromFormat('H:i', $horaFin);
                
                while ($inicio->lt($fin)) {
                    $horarioStr = $inicio->format('H:i');
                    $finHorario = $inicio->copy()->addMinutes($intervalo);
                    
                    // Verificar si el horario está disponible
                    $disponible = !in_array($horarioStr, $horariosOcupados);
                    
                    $horarios[] = [
                        'hora_inicio' => $horarioStr,
                        'hora_fin' => $finHorario->format('H:i'),
                        'fecha_inicio' => $fecha . 'T' . $horarioStr,
                        'fecha_final' => $fecha . 'T' . $finHorario->format('H:i'),
                        'disponible' => $disponible,
                        'ocupado_por' => $disponible ? null : $this->obtenerPacienteEnHorario($citasExistentes, $horarioStr)
                    ];
                    
                    $inicio->addMinutes($intervalo);
                }
                
                return $horarios;
                
            } catch (\Exception $e) {
                Log::error('Error calculando horarios disponibles', [
                    'error' => $e->getMessage()
                ]);
                
                return [];
            }
        }

        /**
     * ✅ NUEVO: Endpoint para determinar tipo de consulta ANTES de crear cita
     */
    public function determinarTipoConsultaPrevio(Request $request)
    {
        try {
            $request->validate([
                'paciente_uuid' => 'required|string',
                'agenda_uuid' => 'required|string'
            ]);

            Log::info('🔍 Frontend: Determinando tipo de consulta previo', [
                'paciente_uuid' => $request->paciente_uuid,
                'agenda_uuid' => $request->agenda_uuid
            ]);

            // ✅ LLAMAR A LA API BACKEND QUE YA TIENE LA LÓGICA
            $response = $this->apiService->post('/citas/determinar-tipo-consulta', [
                'paciente_uuid' => $request->paciente_uuid,
                'agenda_uuid' => $request->agenda_uuid
            ]);

            if ($response['success']) {
                Log::info('✅ Tipo de consulta determinado desde API', $response['data']);
                return response()->json($response);
            }

            return response()->json([
                'success' => false,
                'error' => $response['error'] ?? 'Error desconocido'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error determinando tipo de consulta previo', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
    /**
     * ✅ CORREGIDO: Obtener citas existentes CON SEDE DE LA AGENDA
     */
    private function obtenerCitasExistentes(string $agendaUuid, string $fecha): array
    {
        try {
            Log::info('🔍 Obteniendo citas existentes para agenda (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fecha
            ]);

            // ✅ PASO 1: OBTENER LA AGENDA PRIMERO PARA SABER SU SEDE
            $agenda = $this->offlineService->getAgendaOffline($agendaUuid);
            
            if (!$agenda) {
                Log::warning('⚠️ Agenda no encontrada offline, intentando desde API');
                
                // Si no está offline, intentar desde API
                if ($this->apiService->isOnline()) {
                    $response = $this->apiService->get("/agendas/{$agendaUuid}");
                    if ($response['success']) {
                        $agenda = $response['data'];
                    }
                }
            }
            
            if (!$agenda) {
                Log::error('❌ No se pudo obtener la agenda para determinar la sede', [
                    'agenda_uuid' => $agendaUuid
                ]);
                return [];
            }
            
            // ✅ PASO 2: USAR LA SEDE DE LA AGENDA (NO DEL USUARIO)
            $sedeAgenda = $agenda['sede_id'];
            
            Log::info('✅ Agenda encontrada, usando su sede (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'sede_agenda' => $sedeAgenda,
                'usuario_sede' => $this->authService->usuario()['sede_id'] ?? 'N/A' // Solo para comparar
            ]);
            
            // ✅ PASO 3: EXTRAER FECHA LIMPIA
            $fechaLimpia = $fecha;
            if (strpos($fecha, 'T') !== false) {
                $fechaLimpia = explode('T', $fecha)[0];
            }
            
            // ✅ PASO 4: OBTENER CITAS CON LA SEDE CORRECTA
            $filters = [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fechaLimpia
            ];
            
            $citas = $this->offlineService->getCitasOffline($sedeAgenda, $filters); // ← CAMBIO CRÍTICO
            
            // ✅ PASO 5: SI ESTAMOS ONLINE, TAMBIÉN VERIFICAR API
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get("/agendas/{$agendaUuid}/citas", [
                        'fecha' => $fechaLimpia
                    ]);
                    
                    if ($response['success'] && isset($response['data'])) {
                        $citasApi = $response['data'];
                        $uuidsOffline = array_column($citas, 'uuid');
                        
                        foreach ($citasApi as $citaApi) {
                            if (!in_array($citaApi['uuid'], $uuidsOffline)) {
                                $citas[] = $citaApi;
                                // También guardar offline
                                $this->offlineService->storeCitaOffline($citaApi, false);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error sincronizando citas desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // ✅ PASO 6: FILTRAR SOLO CITAS ACTIVAS
            $citasActivas = array_filter($citas, function($cita) {
                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
            });
            
            Log::info('📊 Citas existentes obtenidas (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'sede_agenda' => $sedeAgenda,
                'fecha_consulta' => $fechaLimpia,
                'total_citas' => count($citas),
                'citas_activas' => count($citasActivas)
            ]);
            
            return $citasActivas;
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo citas existentes (Controlador)', [
                'agenda_uuid' => $agendaUuid,
                'fecha' => $fecha,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

        // ✅ NUEVO: Obtener paciente en horario específico
        private function obtenerPacienteEnHorario(array $citas, string $hora): ?string
        {
            foreach ($citas as $cita) {
                $horaCita = date('H:i', strtotime($cita['fecha_inicio']));
                if ($horaCita === $hora) {
                    return $cita['paciente']['nombre_completo'] ?? 'Paciente no identificado';
                }
            }
            
            return null;
        }

        // ✅ NUEVO: Obtener citas pendientes de sincronización
    public function getPendientesSync(Request $request)
    {
        try {
            Log::info('🔍 Obteniendo citas pendientes de sincronización');
            
            $pendingCount = $this->offlineService->getPendingSyncCount();
            
            Log::info('📊 Conteo de pendientes obtenido', [
                'citas_pendientes' => $pendingCount['citas'] ?? 0,
                'total_pendientes' => $pendingCount['total'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'pending_count' => $pendingCount['citas'] ?? 0,
                'total_pending' => $pendingCount['total'] ?? 0,
                'details' => $pendingCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo citas pendientes', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo citas pendientes: ' . $e->getMessage(),
                'pending_count' => 0
            ], 500);
        }
    }

    // ✅ NUEVO: Sincronizar citas pendientes CON MANEJO ESPECÍFICO DE CUPS
    public function sincronizarPendientes(Request $request)
    {
        try {
            Log::info('🔄 Iniciando sincronización de citas pendientes');
            
            // ✅ VERIFICAR CONEXIÓN
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin conexión al servidor',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }
            
            // ✅ VERIFICAR TOKEN
            if (!$this->authService->hasValidToken()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token de autenticación inválido',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }
            
            // ✅ EJECUTAR SINCRONIZACIÓN
            $result = $this->offlineService->syncPendingCitas();
            
            Log::info('✅ Sincronización de citas completada', [
                'success' => $result['success'] ?? 0,
                'errors' => $result['errors'] ?? 0
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada',
                'synced_count' => $result['success'] ?? 0,
                'failed_count' => $result['errors'] ?? 0,
                'details' => $result['details'] ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error sincronizando citas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
                'synced_count' => 0,
                'failed_count' => 0
            ], 500);
        }
    }

    // ✅ NUEVO: Obtener estado de sincronización
    public function getSyncStatus(Request $request)
    {
        try {
            $stats = $this->offlineService->getPendingSyncCount();
            $isOnline = $this->apiService->isOnline();
            $hasToken = $this->authService->hasValidToken();
            
            return response()->json([
                'success' => true,
                'is_online' => $isOnline,
                'has_valid_token' => $hasToken,
                'can_sync' => $isOnline && $hasToken,
                'pending_stats' => $stats,
                'last_check' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo estado de sync', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estado: ' . $e->getMessage()
            ], 500);
        }
    }
    }
