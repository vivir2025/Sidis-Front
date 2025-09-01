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
                'cups_contratado_id' => 'nullable|string|max:100',
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

            $result = $this->pacienteService->searchByDocument($request->documento);
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error en CitaController@buscarPaciente', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
}

