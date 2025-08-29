<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    DashboardController,
    AdminController,
    PacienteController,
    OfflineController
};

// Rutas públicas (para usuarios no autenticados)
Route::middleware('custom.guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/', [AuthController::class, 'login'])->name('login.post'); // ✅ AGREGAR nombre
    
    // ✅ NUEVA: Ruta para obtener datos recordados
    Route::get('/remembered-data', [AuthController::class, 'getRememberedData'])->name('remembered.data');
});

// ✅ NUEVAS: Rutas de verificación sin autenticación (accesibles siempre)
Route::get('/check-connection', [AuthController::class, 'checkConnection'])->name('check-connection');
Route::get('/health-check', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0')
    ]);
})->name('health-check');

// Rutas protegidas (requieren autenticación)
Route::middleware('custom.auth')->group(function () {
    
    // ✅ AUTENTICACIÓN Y SESIÓN
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/sync', [AuthController::class, 'sync'])->name('sync');
    
    // ✅ NUEVA: Verificar estado de sesión
    Route::get('/session-status', [AuthController::class, 'sessionStatus'])->name('session.status');
    
    // ✅ DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity'])->name('dashboard.activity');
    
    // ✅ NUEVAS: Rutas de datos maestros
    Route::prefix('master-data')->name('master.')->group(function () {
        Route::get('/sedes', [AuthController::class, 'getSedes'])->name('sedes');
        Route::get('/usuarios', [AdminController::class, 'getUsuarios'])->name('usuarios');
    });
    
    // ✅ NUEVAS: Rutas de perfil de usuario
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [AuthController::class, 'showProfile'])->name('show');
        Route::post('/update', [AuthController::class, 'updateProfile'])->name('update');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
    });
       // ✅ NUEVAS: Rutas de paciente
     Route::resource('pacientes', PacienteController::class);
    
    // ✅ RUTAS ADICIONALES DE PACIENTES
    Route::prefix('pacientes')->name('pacientes.')->group(function () {
        Route::get('search/document', [PacienteController::class, 'searchByDocument'])
            ->name('search.document');
        Route::post('search', [PacienteController::class, 'search'])
            ->name('search');
        Route::get('stats', [PacienteController::class, 'stats'])
            ->name('stats');
    });
    
    // ✅ SINCRONIZACIÓN
    Route::post('/sync-pacientes', [PacienteController::class, 'syncPendingPacientes'])
        ->name('pacientes.sync');
         Route::post('/test-sync-manual', [PacienteController::class, 'testSyncManual'])->name('pacientes.test-sync');
    
    // ✅ RUTAS DE ADMINISTRACIÓN (requieren rol admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        
        // Gestión de usuarios
        Route::resource('usuarios', AdminController::class)->except(['show']);
        Route::post('/usuarios/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('usuarios.toggle-status');
        
        // Gestión de sedes
        Route::get('/sedes', [AdminController::class, 'sedes'])->name('sedes');
        Route::post('/sedes', [AdminController::class, 'storeSede'])->name('sedes.store');
        Route::put('/sedes/{id}', [AdminController::class, 'updateSede'])->name('sedes.update');
        
        // Logs del sistema
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
        Route::get('/logs/download', [AdminController::class, 'downloadLogs'])->name('logs.download');
        
        // Configuración del sistema
        Route::get('/config', [AdminController::class, 'config'])->name('config');
        Route::post('/config', [AdminController::class, 'updateConfig'])->name('config.update');
        
        // Sincronización masiva
        Route::post('/sync-all', [AdminController::class, 'syncAll'])->name('sync-all');
    });

    
    // ✅ NUEVAS: Rutas de reportes
    Route::middleware('role:admin,supervisor')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminController::class, 'reportsIndex'])->name('index');
        Route::get('/users-activity', [AdminController::class, 'usersActivityReport'])->name('users-activity');
        Route::get('/system-usage', [AdminController::class, 'systemUsageReport'])->name('system-usage');
        Route::post('/export/{type}', [AdminController::class, 'exportReport'])->name('export');
    });

         Route::prefix('offline')->name('offline.')->group(function () {
        Route::post('/sync-master-data', [OfflineController::class, 'syncMasterData'])
            ->name('sync-master-data');
        Route::get('/sync-status', [OfflineController::class, 'getSyncStatus'])
            ->name('sync-status');
        Route::delete('/clear-data', [OfflineController::class, 'clearOfflineData'])
            ->name('clear-data');
        Route::post('/force-sync-all', [OfflineController::class, 'forceSyncAll'])
            ->name('force-sync-all');
        Route::get('/detailed-stats', [OfflineController::class, 'getDetailedStats'])
            ->name('detailed-stats');
    });
});

// ✅ NUEVA: Ruta de fallback para SPA (si usas Vue/React)
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'error' => 'Endpoint no encontrado',
            'message' => 'La ruta solicitada no existe'
        ], 404);
    }
    
    // Si no está autenticado, redirigir al login
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    // Si está autenticado, redirigir al dashboard
    return redirect()->route('dashboard');
});


Route::get('/debug-sync', function() {
    try {
        $authService = app(\App\Services\AuthService::class);
        $offlineService = app(\App\Services\OfflineService::class);
        
        $user = $authService->usuario();
        if (!$user) {
            return response()->json(['error' => 'No authenticated']);
        }
        
        $sedeId = $user['sede_id'];
        
        // Obtener todos los pacientes offline
        $pacientesPath = $offlineService->getStoragePath() . '/pacientes';
        $allPacientes = [];
        $pendingPacientes = [];
        
        if (is_dir($pacientesPath)) {
            $files = glob($pacientesPath . '/*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if ($data && $data['sede_id'] == $sedeId) {
                    $allPacientes[] = [
                        'uuid' => $data['uuid'],
                        'documento' => $data['documento'],
                        'nombre' => ($data['primer_nombre'] ?? '') . ' ' . ($data['primer_apellido'] ?? ''),
                        'sync_status' => $data['sync_status'] ?? 'synced',
                        'stored_at' => $data['stored_at'] ?? null,
                        'file' => basename($file)
                    ];
                    
                    if (($data['sync_status'] ?? 'synced') === 'pending') {
                        $pendingPacientes[] = $data;
                    }
                }
            }
        }
        
        return response()->json([
            'debug_info' => [
                'user_sede_id' => $sedeId,
                'pacientes_path' => $pacientesPath,
                'path_exists' => is_dir($pacientesPath),
                'total_files' => is_dir($pacientesPath) ? count(glob($pacientesPath . '/*.json')) : 0,
                'api_online' => app(\App\Services\ApiService::class)->isOnline(),
                'api_base_url' => config('api.base_url')
            ],
            'all_pacientes' => $allPacientes,
            'pending_count' => count($pendingPacientes),
            'pending_pacientes' => array_map(function($p) {
                return [
                    'uuid' => $p['uuid'],
                    'documento' => $p['documento'],
                    'nombre' => ($p['primer_nombre'] ?? '') . ' ' . ($p['primer_apellido'] ?? ''),
                    'sync_status' => $p['sync_status'] ?? 'synced'
                ];
            }, $pendingPacientes)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('custom.auth');
Route::post('/test-sync-manual', function() {
    try {
        $pacienteService = app(\App\Services\PacienteService::class);
        $result = $pacienteService->syncPendingPacientes();
        
        return response()->json([
            'test_result' => $result,
            'timestamp' => now()->toISOString()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('custom.auth');
Route::get('/debug-pacientes-pending', function() {
    $authService = app(\App\Services\AuthService::class);
    $offlineService = app(\App\Services\OfflineService::class);
    
    $user = $authService->usuario();
    $sedeId = $user['sede_id'];
    
    $pacientesPath = $offlineService->getStoragePath() . '/pacientes';
    $pendingFiles = [];
    
    if (is_dir($pacientesPath)) {
        $files = glob($pacientesPath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['sede_id'] == $sedeId && ($data['sync_status'] ?? 'synced') === 'pending') {
                $pendingFiles[] = [
                    'file' => basename($file),
                    'uuid' => $data['uuid'],
                    'documento' => $data['documento'],
                    'sync_status' => $data['sync_status'],
                    'data_keys' => array_keys($data)
                ];
            }
        }
    }
    
    return response()->json([
        'sede_id' => $sedeId,
        'pacientes_path' => $pacientesPath,
        'pending_files' => $pendingFiles,
        'api_online' => app(\App\Services\ApiService::class)->isOnline()
    ]);
})->middleware('custom.auth');