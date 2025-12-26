<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\{AuthService, ApiService, OfflineService};

class DashboardController extends Controller
{
    protected $authService;
    protected $apiService;
    protected $offlineService;

    public function __construct(AuthService $authService, ApiService $apiService, OfflineService $offlineService)
    {
        $this->authService = $authService;
        $this->apiService = $apiService;
        $this->offlineService = $offlineService;
    }

    public function index(Request $request)
    {
        // ✅ CORREGIDO: Cambiar user() por usuario()
        $user = $this->authService->usuario(); // ← ESTA ERA LA LÍNEA 23 CON ERROR
        $isOffline = $this->authService->isOffline();
        $isOnline = $this->apiService->isOnline();

        // Obtener estadísticas del sistema
        $stats = $this->getSystemStats();

        // Datos para el dashboard
        $data = [
            'usuario' => $user,
            'is_offline' => $isOffline,
            'is_online' => $isOnline,
            'connection_status' => $isOnline ? 'online' : 'offline',
            'pending_changes' => count($this->offlineService->getPendingChanges()),
            'stats' => $stats,
        ];

        // Si está offline, mostrar mensaje
        if ($isOffline || !$isOnline) {
            $data['offline_message'] = 'Conectado en modo offline. Algunas funciones pueden estar limitadas.';
        }

        return view('dashboard.index', $data);
    }

    /**
     * Obtener estadísticas del sistema
     */
    private function getSystemStats()
    {
        try {
            \Log::info('🔍 Obteniendo estadísticas del dashboard');
            
            // Leer pacientes de archivos JSON en storage/app/offline/pacientes
            $pacientesPath = storage_path('app/offline/pacientes');
            $totalPacientes = 0;
            $activosPacientes = 0;
            $nuevosMes = 0;
            
            if (is_dir($pacientesPath)) {
                $archivos = glob($pacientesPath . '/*.json');
                $totalPacientes = count($archivos);
                
                \Log::info('📊 Archivos de pacientes encontrados: ' . $totalPacientes);
                
                $mesActual = date('m');
                $anioActual = date('Y');
                
                foreach ($archivos as $archivo) {
                    try {
                        $contenido = file_get_contents($archivo);
                        $paciente = json_decode($contenido, true);
                        
                        if ($paciente) {
                            // Contar activos
                            if (isset($paciente['estado_id']) && $paciente['estado_id'] == 1) {
                                $activosPacientes++;
                            }
                            
                            // Contar nuevos del mes
                            if (isset($paciente['created_at'])) {
                                $fechaCreacion = strtotime($paciente['created_at']);
                                if ($fechaCreacion && 
                                    date('m', $fechaCreacion) == $mesActual && 
                                    date('Y', $fechaCreacion) == $anioActual) {
                                    $nuevosMes++;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Continuar con el siguiente archivo
                        continue;
                    }
                }
                
                \Log::info('✅ Total: ' . $totalPacientes . ' | Activos: ' . $activosPacientes . ' | Nuevos mes: ' . $nuevosMes);
            } else {
                \Log::warning('⚠️ Carpeta de pacientes no existe: ' . $pacientesPath);
            }
            
            // Para agendas, citas y usuarios, usar SQLite si existe
            $db = \DB::connection('offline');
            
            $totalAgendas = $db->table('agendas')->count();
            $activasAgendas = $db->table('agendas')
                ->where('fecha', '>=', date('Y-m-d'))
                ->count();
            $pendientesAgendas = $db->table('agendas')
                ->where('sync_status', 'pending')
                ->count();
            
            $totalCitas = $db->table('citas')->count();
            $citasHoy = $db->table('citas')
                ->where('fecha', date('Y-m-d'))
                ->count();
            $citasPendientes = $db->table('citas')
                ->where('estado_cita_id', 1)
                ->where('fecha', '>=', date('Y-m-d'))
                ->count();
            $citasCompletadas = $db->table('citas')
                ->where('estado_cita_id', 2)
                ->whereRaw("strftime('%m', fecha) = ?", [date('m')])
                ->count();
            
            $totalUsuarios = $db->table('usuarios')->count();
            $activosUsuarios = $db->table('usuarios')
                ->where('estado_id', 1)
                ->count();
            
            \Log::info('✅ Estadísticas obtenidas correctamente');
            
            return [
                'pacientes' => [
                    'total' => $totalPacientes,
                    'activos' => $activosPacientes,
                    'nuevos_mes' => $nuevosMes,
                ],
                'agendas' => [
                    'total' => $totalAgendas,
                    'activas' => $activasAgendas,
                    'pendientes' => $pendientesAgendas,
                ],
                'citas' => [
                    'total' => $totalCitas,
                    'hoy' => $citasHoy,
                    'pendientes' => $citasPendientes,
                    'completadas' => $citasCompletadas,
                ],
                'usuarios' => [
                    'total' => $totalUsuarios,
                    'activos' => $activosUsuarios,
                ],
                'actividad_reciente' => $this->getRecentActivity(),
            ];
        } catch (\Exception $e) {
            \Log::error('❌ Error obteniendo estadísticas: ' . $e->getMessage());
            \Log::error('📍 Archivo: ' . $e->getFile() . ' Línea: ' . $e->getLine());
            
            return [
                'pacientes' => ['total' => 0, 'activos' => 0, 'nuevos_mes' => 0],
                'agendas' => ['total' => 0, 'activas' => 0, 'pendientes' => 0],
                'citas' => ['total' => 0, 'hoy' => 0, 'pendientes' => 0, 'completadas' => 0],
                'usuarios' => ['total' => 0, 'activos' => 0],
                'actividad_reciente' => [],
            ];
        }
    }

    /**
     * Obtener actividad reciente del sistema
     */
    private function getRecentActivity()
    {
        try {
            $db = \DB::connection('offline');
            $actividades = [];

            // Últimas citas creadas
            $citas = $db->table('citas')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($citas as $cita) {
                $actividades[] = [
                    'tipo' => 'cita',
                    'descripcion' => 'Nueva cita agendada',
                    'fecha' => $cita->created_at,
                    'icono' => 'calendar-check',
                    'color' => 'success',
                ];
            }

            // Últimos pacientes registrados
            $pacientes = $db->table('pacientes')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($pacientes as $paciente) {
                $actividades[] = [
                    'tipo' => 'paciente',
                    'descripcion' => 'Nuevo paciente registrado',
                    'fecha' => $paciente->created_at,
                    'icono' => 'user-plus',
                    'color' => 'info',
                ];
            }

            // Ordenar por fecha descendente
            usort($actividades, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            return array_slice($actividades, 0, 10);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo actividad reciente: ' . $e->getMessage());
            return [];
        }
    }
}
