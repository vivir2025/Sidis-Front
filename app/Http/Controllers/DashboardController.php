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

        // Datos para el dashboard
        $data = [
            'usuario' => $user,
            'is_offline' => $isOffline,
            'is_online' => $isOnline,
            'connection_status' => $isOnline ? 'online' : 'offline',
            'pending_changes' => count($this->offlineService->getPendingChanges()),
        ];

        // Si está offline, mostrar mensaje
        if ($isOffline || !$isOnline) {
            $data['offline_message'] = 'Conectado en modo offline. Algunas funciones pueden estar limitadas.';
        }

        return view('dashboard.index', $data);
    }
}
