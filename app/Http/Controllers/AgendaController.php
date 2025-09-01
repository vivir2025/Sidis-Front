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
            Log::info('AgendaController@store - Datos recibidos', [
                'data' => $request->all()
            ]);

            $validatedData = $request->validate([
                'modalidad' => 'required|in:Telemedicina,Ambulatoria',
                'fecha' => 'required|date|after_or_equal:today',
                'consultorio' => 'required|string|max:50',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'intervalo' => 'required|string|max:10',
                'etiqueta' => 'required|string|max:50',
                'proceso_id' => 'nullable|string|max:100',
                'brigada_id' => 'nullable|string|max:100',
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
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Errores de validación en AgendaController@store', [
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
            Log::error('Error en AgendaController@store', [
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
            $result = $this->agendaService->show($uuid);

            if (!$result['success']) {
                abort(404, $result['error']);
            }

            $usuario = $this->authService->usuario();
            $isOffline = $this->authService->isOffline();
            $agenda = $result['data'];

            return view('agendas.show', compact('agenda', 'usuario', 'isOffline'));
            
        } catch (\Exception $e) {
            Log::error('Error en AgendaController@show', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error interno del servidor');
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
            if ($this->apiService->isOnline()) {
                try {
                    $response = $this->apiService->get('/master-data/all');
                    
                    if ($response['success'] && isset($response['data'])) {
                        $this->offlineService->syncMasterDataFromApi($response['data']);
                        return $response['data'];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error obteniendo datos maestros desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if ($this->offlineService->hasMasterDataOffline()) {
                return $this->offlineService->getMasterDataOffline();
            }
            
            return $this->getDefaultMasterData();
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos maestros', [
                'error' => $e->getMessage()
            ]);
            
            return $this->getDefaultMasterData();
        }
    }

    private function getDefaultMasterData(): array
    {
        return [
            'procesos' => [
                ['uuid' => 'proc-consulta', 'nombre' => 'Consulta General']
            ],
            'brigadas' => [
                ['uuid' => 'bri-general', 'nombre' => 'Brigada General']
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

/**
 * ✅ OBTENER CONTEO DE PENDIENTES
 */
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
}
