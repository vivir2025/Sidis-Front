<?php
// app/Http/Controllers/AgendaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AgendaService, AuthService, ApiService, OfflineService};
use Illuminate\Support\Facades\Log;

class AgendaController extends Controller
{
    protected $agendaService;
    protected $authService;
    protected $apiService;
    protected $offlineService;

    public function __construct(AgendaService $agendaService, AuthService $authService, ApiService $apiService, OfflineService $offlineService)
    {
        $this->middleware('custom.auth');
        $this->agendaService = $agendaService;
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'fecha_desde', 'fecha_hasta', 'estado', 'modalidad', 'consultorio'
            ]);
            
            $page = $request->get('page', 1);
            
            Log::info('AgendaController@index', [
                'filters' => $filters,
                'page' => $page,
                'is_ajax' => $request->ajax()
            ]);

            $result = $this->agendaService->index($filters, $page);

            if ($request->ajax()) {
                return response()->json($result);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();

            return view('agendas.index', compact('usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ], 500);
            }

            return back()->with('error', 'Error cargando agendas');
        }
    }

    public function create()
    {
        try {
            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $masterData = $this->getMasterData();

            return view('agendas.create', compact('usuario', 'isOffline', 'masterData'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@create', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error cargando formulario de creación');
        }
    }

  public function store(Request $request)
{
    try {
        Log::info('🔍 AgendaController@store - Datos RAW recibidos', [
            'all_data' => $request->all(),
            'proceso_id_raw' => $request->input('proceso_id'),
            'brigada_id_raw' => $request->input('brigada_id'),
            'usuario_medico_id_raw' => $request->input('usuario_medico_id')
        ]);

        // ✅ OBTENER USUARIO AUTENTICADO PRIMERO
        $user = $this->authService->usuario();
        
        Log::info('✅ Usuario autenticado obtenido', [
            'usuario_id' => $user['id'],
            'sede_id' => $user['sede_id'],
            'nombre' => $user['nombre_completo'] ?? 'Sin nombre'
        ]);

        // ✅ VALIDACIÓN BÁSICA
        $validatedData = $request->validate([
            'modalidad' => 'required|in:Telemedicina,Ambulatoria',
            'fecha' => 'required|date|after_or_equal:today',
            'consultorio' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo' => 'required|string|in:15,20,30,45,60', 
            'etiqueta' => 'required|string|max:50',
            'proceso_id' => 'nullable|string|max:100',
            'brigada_id' => 'nullable|string|max:100',
            'usuario_medico_id' => 'nullable|string|max:100',
        ]);

        // ✅ AGREGAR CAMPOS REQUERIDOS DEL USUARIO
        $validatedData['sede_id'] = $user['sede_id'];
        $validatedData['usuario_id'] = $user['id'];
        
        Log::info('✅ Campos de usuario agregados', [
            'sede_id' => $validatedData['sede_id'],
            'usuario_id' => $validatedData['usuario_id']
        ]);

        // ✅ PROCESAR IDs CORRECTAMENTE (tu código existente)
        $masterData = $this->getMasterData();
        
        // ✅ RESOLVER proceso_id
        if (!empty($validatedData['proceso_id']) && $validatedData['proceso_id'] !== '') {
            $resolvedProcesoId = $this->resolveProcesoId($validatedData['proceso_id'], $masterData);
            $validatedData['proceso_id'] = $resolvedProcesoId;
            
            Log::info('✅ proceso_id procesado', [
                'original' => $request->input('proceso_id'),
                'resolved' => $resolvedProcesoId
            ]);
        } else {
            $validatedData['proceso_id'] = null;
        }

        // ✅ RESOLVER brigada_id
        if (!empty($validatedData['brigada_id']) && $validatedData['brigada_id'] !== '') {
            $resolvedBrigadaId = $this->resolveBrigadaId($validatedData['brigada_id'], $masterData);
            $validatedData['brigada_id'] = $resolvedBrigadaId;
            
            Log::info('✅ brigada_id procesado', [
                'original' => $request->input('brigada_id'),
                'resolved' => $resolvedBrigadaId
            ]);
        } else {
            $validatedData['brigada_id'] = null;
        }

         if (!empty($validatedData['usuario_medico_id']) && $validatedData['usuario_medico_id'] !== '') {
    $resolvedUsuarioMedicoUuid = $this->resolveUsuarioMedicoId($validatedData['usuario_medico_id'], $masterData);
    $validatedData['usuario_medico_uuid'] = $resolvedUsuarioMedicoUuid; 
    unset($validatedData['usuario_medico_id']); 
} else {
    $validatedData['usuario_medico_uuid'] = null; 
}

        // ✅ ASEGURAR TIPOS CORRECTOS
        $validatedData['intervalo'] = (string) $validatedData['intervalo'];
        
        Log::info('📤 Datos finales para guardar', [
            'sede_id' => $validatedData['sede_id'],          
            'usuario_id' => $validatedData['usuario_id'],
            'proceso_id' => $validatedData['proceso_id'],
            'brigada_id' => $validatedData['brigada_id'],
            'intervalo' => $validatedData['intervalo'],
            'intervalo_type' => gettype($validatedData['intervalo'])
        ]);

        $result = $this->agendaService->store($validatedData);

        if ($request->ajax()) {
            $response = $result;
            if ($result['success']) {
                $response['redirect_url'] = route('agendas.index');
            }
            return response()->json($response);
        }

        if ($result['success']) {
            return redirect()->route('agendas.index')
                ->with('success', $result['message'] ?? 'Agenda creada exitosamente');
        }

        return back()
            ->withErrors(['error' => $result['error']])
            ->withInput();
            
    } catch (\Exception $e) {
        Log::error('💥 Error crítico en AgendaController@store', [
            'error' => $e->getMessage(),
            'data' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }

        return back()->with('error', 'Error interno del servidor')->withInput();
    }
}

private function resolveUsuarioMedicoId($usuarioValue, array $masterData): mixed
{
    if (empty($usuarioValue) || $usuarioValue === 'null' || $usuarioValue === '') {
        return null;
    }

    Log::info('🔍 Resolviendo usuario_medico_id', [
        'input_value' => $usuarioValue,
        'input_type' => gettype($usuarioValue),
        'has_usuarios' => isset($masterData['usuarios_con_especialidad']),
        'usuarios_count' => isset($masterData['usuarios_con_especialidad']) ? count($masterData['usuarios_con_especialidad']) : 0
    ]);

    // Si no hay usuarios en datos maestros, devolver null
    if (!isset($masterData['usuarios_con_especialidad']) || empty($masterData['usuarios_con_especialidad'])) {
        Log::warning('⚠️ No hay usuarios con especialidad en datos maestros');
        return null;
    }

    // Buscar en datos maestros
    foreach ($masterData['usuarios_con_especialidad'] as $usuario) {
        // ✅ CAMBIO: Buscar por ID y devolver UUID
        if (isset($usuario['id']) && (string)$usuario['id'] === (string)$usuarioValue) {
            if (isset($usuario['uuid']) && !empty($usuario['uuid'])) {
                Log::info('✅ Usuario médico encontrado por ID, devolviendo UUID', [
                    'id' => $usuarioValue,
                    'uuid_resuelto' => $usuario['uuid']
                ]);
                return $usuario['uuid']; // ✅ DEVOLVER UUID EN LUGAR DE ID
            }
        }
        
        // Coincidencia por UUID (mantener)
        if (isset($usuario['uuid']) && $usuario['uuid'] === $usuarioValue) {
            Log::info('✅ Usuario médico encontrado por UUID', [
                'uuid' => $usuarioValue
            ]);
            return $usuario['uuid'];
        }
    }

    Log::warning('⚠️ usuario_medico_id no encontrado en datos maestros', [
        'usuario_value' => $usuarioValue
    ]);

    return null;
}

// ✅ REEMPLAZAR en AgendaController
private function resolveProcesoId($procesoValue, array $masterData): mixed
{
    if (empty($procesoValue) || $procesoValue === 'null' || $procesoValue === '') {
        return null;
    }

    Log::info('🔍 Resolviendo proceso_id', [
        'input_value' => $procesoValue,
        'input_type' => gettype($procesoValue)
    ]);

    // ✅ SI YA ES UN UUID VÁLIDO, DEVOLVERLO DIRECTAMENTE
    if (is_string($procesoValue) && $this->isValidUuid($procesoValue)) {
        Log::info('✅ proceso_id ya es UUID válido', ['uuid' => $procesoValue]);
        return $procesoValue;
    }

    // Si ya es numérico, devolverlo como entero
    if (is_numeric($procesoValue)) {
        return (int) $procesoValue;
    }

    // Si no hay procesos en datos maestros, devolver null
    if (!isset($masterData['procesos']) || empty($masterData['procesos'])) {
        Log::warning('⚠️ No hay procesos en datos maestros');
        return null;
    }

    // Buscar en datos maestros
    foreach ($masterData['procesos'] as $proceso) {
        // Coincidencia por UUID - DEVOLVER UUID
        if (isset($proceso['uuid']) && $proceso['uuid'] === $procesoValue) {
            Log::info('✅ Proceso encontrado por UUID', ['uuid' => $procesoValue]);
            return $procesoValue; // ✅ DEVOLVER UUID, NO ID
        }
        
        // Coincidencia por ID - DEVOLVER UUID SI ESTÁ DISPONIBLE
        if (isset($proceso['id']) && (string)$proceso['id'] === (string)$procesoValue) {
            if (isset($proceso['uuid']) && !empty($proceso['uuid'])) {
                Log::info('✅ Proceso encontrado por ID, devolviendo UUID', [
                    'id' => $procesoValue,
                    'uuid' => $proceso['uuid']
                ]);
                return $proceso['uuid']; // ✅ DEVOLVER UUID
            } else {
                Log::info('✅ Proceso encontrado por ID (sin UUID)', ['id' => $procesoValue]);
                return (int) $proceso['id'];
            }
        }
    }

    Log::warning('⚠️ proceso_id no encontrado', ['value' => $procesoValue]);
    return null;
}

private function resolveBrigadaId($brigadaValue, array $masterData): mixed
{
    if (empty($brigadaValue) || $brigadaValue === 'null' || $brigadaValue === '') {
        return null;
    }

    Log::info('🔍 Resolviendo brigada_id', [
        'input_value' => $brigadaValue,
        'input_type' => gettype($brigadaValue)
    ]);

    // ✅ SI YA ES UN UUID VÁLIDO, DEVOLVERLO DIRECTAMENTE
    if (is_string($brigadaValue) && $this->isValidUuid($brigadaValue)) {
        Log::info('✅ brigada_id ya es UUID válido', ['uuid' => $brigadaValue]);
        return $brigadaValue;
    }

    // Si ya es numérico, devolverlo como entero
    if (is_numeric($brigadaValue)) {
        return (int) $brigadaValue;
    }

    // Si no hay brigadas en datos maestros, devolver null
    if (!isset($masterData['brigadas']) || empty($masterData['brigadas'])) {
        Log::warning('⚠️ No hay brigadas en datos maestros');
        return null;
    }

    // Buscar en datos maestros
    foreach ($masterData['brigadas'] as $brigada) {
        // Coincidencia por UUID - DEVOLVER UUID
        if (isset($brigada['uuid']) && $brigada['uuid'] === $brigadaValue) {
            Log::info('✅ Brigada encontrada por UUID', ['uuid' => $brigadaValue]);
            return $brigadaValue; // ✅ DEVOLVER UUID, NO ID
        }
        
        // Coincidencia por ID - DEVOLVER UUID SI ESTÁ DISPONIBLE
        if (isset($brigada['id']) && (string)$brigada['id'] === (string)$brigadaValue) {
            if (isset($brigada['uuid']) && !empty($brigada['uuid'])) {
                Log::info('✅ Brigada encontrada por ID, devolviendo UUID', [
                    'id' => $brigadaValue,
                    'uuid' => $brigada['uuid']
                ]);
                return $brigada['uuid']; // ✅ DEVOLVER UUID
            } else {
                Log::info('✅ Brigada encontrada por ID (sin UUID)', ['id' => $brigadaValue]);
                return (int) $brigada['id'];
            }
        }
    }

    Log::warning('⚠️ brigada_id no encontrado', ['value' => $brigadaValue]);
    return null;
}

// ✅ AGREGAR ESTA FUNCIÓN AL CONTROLLER
private function isValidUuid(string $uuid): bool
{
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
}

    // ... resto de métodos sin cambios

   public function show(string $uuid)
{
    try {
        $result = $this->agendaService->show($uuid);

        if (!$result['success']) {
            abort(404, $result['error']);
        }

        $usuario = $this->authService->usuario();
        $isOffline = $this->authService->isOffline();
        $agenda = $result['data'];

        // ✅ ENRIQUECER DATOS SI ESTAMOS OFFLINE
        if ($isOffline || $result['offline']) {
            $agenda = $this->enrichAgendaDataForView($agenda);
        }

        // ✅ CALCULAR CUPOS Y CITAS ADICIONALES
        $agenda = $this->enrichAgendaData($agenda);

        return view('agendas.show', compact('agenda', 'usuario', 'isOffline'));
        
    } catch (\Exception $e) {
        Log::error('Error en AgendaController@show', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);

        abort(500, 'Error interno del servidor');
    }
}

/**
 * ✅ NUEVO: Enriquecer datos de agenda para la vista
 */
private function enrichAgendaDataForView(array $agenda): array
{
    try {
        // Obtener datos maestros
        $masterData = $this->getMasterData();
        
        // ✅ ENRIQUECER PROCESO
        if (!empty($agenda['proceso_id']) && !isset($agenda['proceso'])) {
            foreach ($masterData['procesos'] ?? [] as $proceso) {
                if ($proceso['id'] == $agenda['proceso_id'] || 
                    $proceso['uuid'] == $agenda['proceso_id']) {
                    $agenda['proceso'] = $proceso;
                    break;
                }
            }
        }
        
        // ✅ ENRIQUECER BRIGADA
        if (!empty($agenda['brigada_id']) && !isset($agenda['brigada'])) {
            foreach ($masterData['brigadas'] ?? [] as $brigada) {
                if ($brigada['id'] == $agenda['brigada_id'] || 
                    $brigada['uuid'] == $agenda['brigada_id']) {
                    $agenda['brigada'] = $brigada;
                    break;
                }
            }
        }
        
        // ✅ ENRIQUECER USUARIO (desde sesión actual si no está disponible)
        if (!isset($agenda['usuario']) || empty($agenda['usuario']['nombre_completo'])) {
            $currentUser = $this->authService->usuario();
            $agenda['usuario'] = [
                'nombre_completo' => $currentUser['nombre_completo'] ?? 'Usuario del Sistema',
                'id' => $agenda['usuario_id'] ?? $currentUser['id'] ?? null
            ];
        }
        
        // ✅ ENRIQUECER SEDE (desde sesión actual si no está disponible)
        if (!isset($agenda['sede']) || empty($agenda['sede']['nombre'])) {
            $currentUser = $this->authService->usuario();
            $agenda['sede'] = [
                'nombre' => $currentUser['sede']['nombre'] ?? 'Sede Principal',
                'id' => $agenda['sede_id'] ?? $currentUser['sede_id'] ?? null
            ];
        }
        
        // ✅ OBTENER CITAS OFFLINE SI NO ESTÁN PRESENTES
        if (!isset($agenda['citas']) || !is_array($agenda['citas'])) {
            $agenda['citas'] = $this->getCitasForAgendaOffline($agenda['uuid']);
        }
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo datos de agenda para vista', [
            'error' => $e->getMessage(),
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown'
        ]);
        
        return $agenda;
    }
}

/**
 * ✅ NUEVO: Obtener citas de una agenda en modo offline
 */
private function getCitasForAgendaOffline(string $agendaUuid): array
{
    try {
        $citas = [];
        
        // Intentar desde SQLite primero
        if ($this->offlineService->isSQLiteAvailable()) {
            $citasDb = DB::connection('offline')
                ->table('citas')
                ->where('agenda_uuid', $agendaUuid)
                ->whereNull('deleted_at')
                ->orderBy('fecha_inicio')
                ->get();
                
            foreach ($citasDb as $cita) {
                $citaArray = (array) $cita;
                
                // Enriquecer con datos del paciente si está disponible
                if (!empty($cita->paciente_uuid)) {
                    $citaArray['paciente'] = $this->getPacienteDataOffline($cita->paciente_uuid);
                }
                
                $citas[] = $citaArray;
            }
        } else {
            // Fallback a archivos JSON
            $citasPath = $this->offlineService->getStoragePath() . '/citas';
            if (is_dir($citasPath)) {
                $files = glob($citasPath . '/*.json');
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['agenda_uuid'] == $agendaUuid && empty($data['deleted_at'])) {
                        // Enriquecer con datos del paciente
                        if (!empty($data['paciente_uuid'])) {
                            $data['paciente'] = $this->getPacienteDataOffline($data['paciente_uuid']);
                        }
                        $citas[] = $data;
                    }
                }
                
                // Ordenar por hora
                usort($citas, function($a, $b) {
                    return strcmp($a['fecha_inicio'] ?? '', $b['fecha_inicio'] ?? '');
                });
            }
        }
        
        return $citas;
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo citas offline para agenda', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

/**
 * ✅ NUEVO: Obtener datos de paciente offline
 */
private function getPacienteDataOffline(string $pacienteUuid): ?array
{
    try {
        // Intentar desde SQLite
        if ($this->offlineService->isSQLiteAvailable()) {
            $paciente = DB::connection('offline')
                ->table('pacientes')
                ->where('uuid', $pacienteUuid)
                ->first();
                
            if ($paciente) {
                return [
                    'uuid' => $paciente->uuid,
                    'documento' => $paciente->documento,
                    'nombre_completo' => trim(
                        ($paciente->primer_nombre ?? '') . ' ' . 
                        ($paciente->segundo_nombre ?? '') . ' ' . 
                        ($paciente->primer_apellido ?? '') . ' ' . 
                        ($paciente->segundo_apellido ?? '')
                    ),
                    'nombre' => $paciente->primer_nombre,
                    'apellido' => $paciente->primer_apellido
                ];
            }
        }
        
        // Fallback a JSON
        $pacienteData = $this->offlineService->getData('pacientes/' . $pacienteUuid . '.json');
        if ($pacienteData) {
            return [
                'uuid' => $pacienteData['uuid'],
                'documento' => $pacienteData['documento'],
                'nombre_completo' => trim(
                    ($pacienteData['primer_nombre'] ?? '') . ' ' . 
                    ($pacienteData['segundo_nombre'] ?? '') . ' ' . 
                    ($pacienteData['primer_apellido'] ?? '') . ' ' . 
                    ($pacienteData['segundo_apellido'] ?? '')
                ),
                'nombre' => $pacienteData['primer_nombre'],
                'apellido' => $pacienteData['primer_apellido']
            ];
        }
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo paciente offline', [
            'uuid' => $pacienteUuid,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}

/**
 * ✅ NUEVO: Endpoint para obtener citas de una agenda
 */
public function getCitas(string $uuid)
{
    try {
        $result = $this->agendaService->getCitasForAgenda($uuid);
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo citas de agenda', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Error interno del servidor'
        ], 500);
    }
}

/**
 * ✅ NUEVO: Endpoint para obtener conteo de citas
 */
public function getCitasCount(string $uuid)
{
    try {
        $result = $this->agendaService->getCitasCountForAgenda($uuid);
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo conteo de citas', [
            'uuid' => $uuid,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Error interno del servidor'
        ], 500);
    }
}

/**
 * ✅ NUEVO: Enriquecer datos de agenda
 */
private function enrichAgendaData(array $agenda): array
{
    try {
        // Calcular cupos totales
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $totalCupos = floor($duracionMinutos / $intervalo);
        
        // Obtener número de citas (si está disponible)
        $citasCount = 0;
        if (isset($agenda['citas']) && is_array($agenda['citas'])) {
            $citasCount = count(array_filter($agenda['citas'], function($cita) {
                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
            }));
        }
        
        // Calcular cupos disponibles
        $cuposDisponibles = max(0, $totalCupos - $citasCount);
        
        // Agregar datos calculados
        $agenda['total_cupos'] = $totalCupos;
        $agenda['citas_count'] = $citasCount;
        $agenda['cupos_disponibles'] = $cuposDisponibles;
        
        return $agenda;
        
    } catch (\Exception $e) {
        Log::error('Error enriqueciendo datos de agenda', [
            'error' => $e->getMessage(),
            'agenda_uuid' => $agenda['uuid'] ?? 'unknown'
        ]);
        
        // Valores por defecto en caso de error
        $agenda['total_cupos'] = 0;
        $agenda['citas_count'] = 0;
        $agenda['cupos_disponibles'] = 0;
        
        return $agenda;
    }
}

    public function edit(string $uuid)
    {
        try {
            $result = $this->agendaService->show($uuid);

            if (!$result['success']) {
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $agenda = $result['data'];
            $masterData = $this->getMasterData();

            return view('agendas.edit', compact('agenda', 'usuario', 'isOffline', 'masterData'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@edit', [
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
                'modalidad' => 'required|in:Telemedicina,Ambulatoria',
                'fecha' => 'required|date|after_or_equal:today',
                'consultorio' => 'required|string|max:50',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'intervalo' => 'required|string|max:10',
                'etiqueta' => 'required|string|max:50',
                'estado' => 'nullable|in:ACTIVO,ANULADA,LLENA',
                'proceso_id' => 'nullable|string|max:100',
                'brigada_id' => 'nullable|string|max:100',
            ]);

            $result = $this->agendaService->update($uuid, $validatedData);

            if ($request->ajax()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('agendas.show', $uuid)
                    ->with('success', $result['message'] ?? 'Agenda actualizada exitosamente');
            }

            return back()
                ->withErrors(['error' => $result['error']])
                ->withInput();
                
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@update', [
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
            $result = $this->agendaService->destroy($uuid);
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@destroy', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function disponibles(Request $request)
    {
        try {
            $filters = $request->only(['modalidad', 'proceso_id', 'fecha_desde']);
            $result = $this->agendaService->disponibles($filters);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@disponibles', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

  private function getMasterData(): array
{
    try {
        Log::info('🔍 Obteniendo datos maestros para formulario');
        
        // ✅ SIEMPRE INTENTAR ACTUALIZAR DESDE API SI HAY CONEXIÓN
        if ($this->apiService->isOnline()) {
            try {
                Log::info('🌐 Intentando obtener datos maestros desde API');
                
                $response = $this->apiService->get('/master-data/all');
                
                if ($response['success'] && isset($response['data'])) {
                    Log::info('✅ Datos maestros obtenidos desde API', [
                        'tables_count' => count($response['data']),
                        'procesos_count' => count($response['data']['procesos'] ?? []),
                        'brigadas_count' => count($response['data']['brigadas'] ?? [])
                    ]);
                    
                    // ✅ SINCRONIZAR OFFLINE INMEDIATAMENTE
                    $this->offlineService->syncMasterDataFromApi($response['data']);
                    
                    return $response['data'];
                } else {
                    Log::warning('⚠️ Respuesta de API inválida para datos maestros');
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo datos maestros desde API', [
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::info('📱 Sin conexión, usando datos offline');
        }
        
        // ✅ USAR DATOS OFFLINE
        if ($this->offlineService->hasMasterDataOffline()) {
            $offlineData = $this->offlineService->getMasterDataOffline();
            
            Log::info('📱 Usando datos maestros offline', [
                'tables_count' => count($offlineData),
                'procesos_count' => count($offlineData['procesos'] ?? []),
                'brigadas_count' => count($offlineData['brigadas'] ?? [])
            ]);
            
            return $offlineData;
        }
        
        Log::warning('⚠️ No hay datos maestros disponibles, usando defaults');
        return $this->getDefaultMasterData();
        
    } catch (\Exception $e) {
        Log::error('❌ Error crítico obteniendo datos maestros', [
            'error' => $e->getMessage()
        ]);
        
        return $this->getDefaultMasterData();
    }
}

// ✅ MEJORAR DATOS POR DEFECTO
private function getDefaultMasterData(): array
{
    return [
        'procesos' => [
            ['id' => 1, 'uuid' => 'proc-consulta-general', 'nombre' => 'Consulta General', 'n_cups' => '890201'],
            ['id' => 2, 'uuid' => 'proc-control-prenatal', 'nombre' => 'Control Prenatal', 'n_cups' => '890301'],
            ['id' => 3, 'uuid' => 'proc-planificacion', 'nombre' => 'Planificación Familiar', 'n_cups' => '890401']
        ],
        'brigadas' => [
            ['id' => 1, 'uuid' => 'bri-general', 'nombre' => 'Brigada General'],
            ['id' => 2, 'uuid' => 'bri-especializada', 'nombre' => 'Brigada Especializada'],
            ['id' => 3, 'uuid' => 'bri-rural', 'nombre' => 'Brigada Rural']
        ],
        'modalidades' => [
            'Telemedicina' => 'Telemedicina',
            'Ambulatoria' => 'Ambulatoria'
        ],
        'estados' => [
            'ACTIVO' => 'Activo',
            'ANULADA' => 'Anulada',
            'LLENA' => 'Llena'
        ]
    ];
}

    // ... resto de métodos sin cambios (syncPending, pendingCount, etc.)

    public function syncPending(Request $request)
    {
        try {
            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay conexión a internet para sincronizar'
                ]);
            }

            // Sincronizar agendas
            $agendasResult = $this->offlineService->syncPendingAgendas();
            
            // Sincronizar citas
            $citasResult = $this->offlineService->syncPendingCitas();

            $totalSuccess = $agendasResult['success'] + $citasResult['success'];
            $totalErrors = $agendasResult['errors'] + $citasResult['errors'];

            $message = "Sincronización completada: {$totalSuccess} exitosos";
            if ($totalErrors > 0) {
                $message .= ", {$totalErrors} errores";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'agendas' => $agendasResult,
                    'citas' => $citasResult,
                    'totals' => [
                        'success' => $totalSuccess,
                        'errors' => $totalErrors
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en sincronización manual', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pendingCount(Request $request)
    {
        try {
            $counts = $this->offlineService->getPendingSyncCount();
            
            return response()->json([
                'success' => true,
                'data' => $counts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function syncPendingAgendas(Request $request)
    {
        try {
            Log::info('🔄 Iniciando sincronización de agendas desde controlador');

            if (!$this->apiService->isOnline()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin conexión al servidor',
                    'synced_count' => 0,
                    'failed_count' => 0
                ]);
            }

            $result = $this->offlineService->syncPendingAgendas();

            Log::info('🏁 Resultado de sincronización de agendas', [
                'synced_count' => $result['synced_count'] ?? 0,
                'failed_count' => $result['failed_count'] ?? 0
            ]);

            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en sincronización de agendas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en sincronización: ' . $e->getMessage(),
                'synced_count' => 0,
                'failed_count' => 0
            ], 500);
        }
    }

    public function testSyncManual(Request $request)
    {
        try {
            Log::info('🧪 Test de sincronización de agendas');

            $result = $this->offlineService->getTestSyncData(10);

            return response()->json([
                'success' => $result['success'],
                'pending_count' => $result['pending_count'] ?? 0,
                'total_count' => $result['total_count'] ?? 0,
                'error_count' => $result['error_count'] ?? 0,
                'data' => $result['data'] ?? [],
                'message' => $result['message'] ?? 'Test completado'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en test de agendas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'pending_count' => 0,
                'total_count' => 0
            ], 500);
        }
    }
}
