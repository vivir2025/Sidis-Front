<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    DashboardController,
    AdminController,
    PacienteController,
    OfflineController,
    CitaController,
    AgendaController,
    CronogramaController,
    CupsController,
    UsuarioController,
    FirmaQRController,
    HistoriaClinicaController,
    SyncController
};

// ====================================================================
// 🔥 RUTAS PÚBLICAS SIN MIDDLEWARE (DEBEN IR PRIMERO)
// ====================================================================

// ✅ FIRMA MÓVIL - ACCESIBLE SIN AUTENTICACIÓN
Route::get('/firma-movil/{token}', [FirmaQRController::class, 'mostrarPaginaMovil'])
    ->name('firma.movil')
    ->where('token', '[a-zA-Z0-9_-]+');

Route::post('/firma-movil/{token}', [FirmaQRController::class, 'guardarFirmaMovil'])
    ->name('firma.guardar-movil')
    ->where('token', '[a-zA-Z0-9_-]+');

// ✅ HEALTH CHECK - ACCESIBLE SIN AUTENTICACIÓN
Route::get('/health-check', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0')
    ]);
})->name('health-check');

Route::get('/check-connection', [AuthController::class, 'checkConnection'])
    ->name('check-connection');

// ✅ FIX TEMPORAL: Página para corregir UUID de agenda
Route::get('/fix-agenda-uuid-page', function() {
    return view('fix-agenda-uuid');
})->middleware('custom.auth')->name('fix-agenda-uuid-page');



// ====================================================================
// 🔐 RUTAS PARA USUARIOS NO AUTENTICADOS (GUEST)
// ====================================================================

Route::middleware('custom.guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/', [AuthController::class, 'login'])->name('login.post');
    Route::get('/remembered-data', [AuthController::class, 'getRememberedData'])
        ->name('remembered.data');
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
  Route::post('/cambiar-sede', [AuthController::class, 'cambiarSede'])->name('cambiar-sede');
    Route::get('/sedes-disponibles', [AuthController::class, 'getSedesDisponibles'])->name('sedes-disponibles');
    // ✅ AUTENTICACIÓN Y SESIÓN
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/sync', [AuthController::class, 'sync'])->name('sync');
    
    // ✅ NUEVA: Verificar estado de sesión
    Route::get('/session-status', [AuthController::class, 'sessionStatus'])->name('session.status');
    
    // ✅ FIX: Corregir UUID de agenda
    Route::post('/sync/fix-agenda-uuid', [SyncController::class, 'fixAgendaUuid'])->name('sync.fix-agenda-uuid');
    
    // ✅ DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity'])
        ->name('dashboard.activity');
    
    // ✅ FIRMA CON QR (DENTRO DE LA APLICACIÓN)
    Route::post('/usuarios/generar-qr-firma', [FirmaQRController::class, 'generarQR'])
        ->name('firma.generar-qr');
    Route::get('/usuarios/verificar-firma/{token}', [FirmaQRController::class, 'verificarFirma'])
        ->name('firma.verificar');
    
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
        Route::post('sync-all', [PacienteController::class, 'syncAllPendingChanges'])
            ->name('sync.all');
        Route::get('pending-count', [PacienteController::class, 'getPendingCount'])
            ->name('pending.count');
        Route::post('sync-pending', [PacienteController::class, 'syncPendingPacientes'])
            ->name('sync');
        Route::post('test-sync-manual', [PacienteController::class, 'testSyncManual'])
            ->name('test-sync');
    });
Route::post('/sync-agendas', [AgendaController::class, 'syncPendingAgendas'])
        ->name('sync-agendas');
    // ✅ AGENDAS
    Route::prefix('agendas')->name('agendas.')->group(function () {
        Route::get('/', [AgendaController::class, 'index'])->name('index');
        Route::get('/create', [AgendaController::class, 'create'])->name('create');
        Route::post('/', [AgendaController::class, 'store'])->name('store');
        Route::get('/disponibles', [AgendaController::class, 'disponibles'])->name('disponibles');
         
        Route::get('/test-sync', [AgendaController::class, 'testSyncManual'])
        ->name('test-sync');

         

         Route::get('/{uuid}/citas', [AgendaController::class, 'getCitas'])
        ->name('citas')
        ->where('uuid', '[0-9a-f-]{36}');
         Route::get('/{uuid}/citas/count', [AgendaController::class, 'getCitasCount'])
        ->name('citas.count')
        ->where('uuid', '[0-9a-f-]{36}');
        Route::get('/{uuid}', [AgendaController::class, 'show'])->name('show');
        Route::get('/{uuid}/edit', [AgendaController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [AgendaController::class, 'update'])->name('update');
         Route::delete('/{uuid}', [AgendaController::class, 'destroy'])->name('destroy');
       
    });
Route::delete('/cups/{cupsUuid}/cache', [CupsController::class, 'invalidarCacheCupsContratado'])
    ->name('cups.invalidar-cache');
    // routes/web.php - AGREGAR TEMPORALMENTE
Route::get('/agendas/diagnostic', function() {
    $offlineService = app(\App\Services\OfflineService::class);
    $result = $offlineService->diagnosticSync();
    return response()->json($result);
})->middleware('custom.auth');


    Route::get('/citas/determinar-tipo-consulta-previo', [CitaController::class, 'determinarTipoConsultaPrevio'])
        ->name('citas.determinar-tipo-consulta');
Route::get('/agendas/{uuid}/diagnostic', [AgendaController::class, 'diagnosticAgenda'])
    ->name('agendas.diagnostic');
    // Citas
    Route::prefix('citas')->name('citas.')->group(function () {
        Route::get('/', [CitaController::class, 'index'])->name('index');
        Route::get('/create', [CitaController::class, 'create'])->name('create');
        Route::post('/', [CitaController::class, 'store'])->name('store');
        Route::get('/del-dia', [CitaController::class, 'citasDelDia'])->name('del-dia');
        Route::get('/buscar-paciente', [CitaController::class, 'buscarPaciente'])->name('buscar-paciente');
        // ✅ NUEVAS RUTAS PARA HORARIOS
        Route::get('/agenda/{agenda}/horarios', [CitaController::class, 'getHorariosDisponibles'])->name('citas.horarios-disponibles');
        Route::get('/agenda/{agenda}/details', [CitaController::class, 'getAgendaDetails'])->name('citas.agenda-details');
         // ✅ NUEVAS RUTAS PARA SINCRONIZACIÓN DE CITAS
        Route::get('/pendientes-sync', [CitaController::class, 'getPendientesSync'])->name('citas.pendientes-sync');
        Route::post('/sincronizar', [CitaController::class, 'sincronizarPendientes'])->name('citas.sincronizar');
        Route::get('/sync-status', [CitaController::class, 'getSyncStatus'])->name('citas.sync-status');
        Route::get('/{uuid}', [CitaController::class, 'show'])->name('show');
        Route::get('/{uuid}/edit', [CitaController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [CitaController::class, 'update'])->name('update');
        Route::delete('/{uuid}', [CitaController::class, 'destroy'])->name('destroy');
        Route::patch('/{uuid}/estado', [CitaController::class, 'cambiarEstado'])->name('cambiar-estado');

    });
  
    Route::get('/historia-clinica/{uuid}/medicamentos', [HistoriaClinicaController::class, 'medicamentos'])
        ->name('historia-clinica.medicamentos');

    Route::get('/historia-clinica/{uuid}/remisiones', [HistoriaClinicaController::class, 'remisiones'])
        ->name('historia-clinica.remisiones');

    Route::get('/historia-clinica/{uuid}/ayudas-diagnosticas', [HistoriaClinicaController::class, 'ayudasDiagnosticas'])
        ->name('historia-clinica.ayudas-diagnosticas');



    //CUPS
      Route::prefix('cups')->name('cups.')->group(function () {
        Route::get('/buscar', [CupsController::class, 'buscar'])->name('buscar');
        Route::get('/codigo', [CupsController::class, 'obtenerPorCodigo'])->name('codigo');
        Route::post('/sincronizar', [CupsController::class, 'sincronizar'])->name('sincronizar');
        Route::get('/activos', [CupsController::class, 'activos'])->name('activos');
        
    });

     // ✅ CUPS CONTRATADOS - RUTAS SEPARADAS (FUERA DEL GRUPO CUPS)
    Route::prefix('cups-contratados')->name('cups-contratados.')->group(function () {
        Route::get('/por-cups/{cupsUuid}', [CupsController::class, 'getCupsContratadoPorCups'])
            ->name('por-cups')
            ->where('cupsUuid', '[0-9a-f-]{36}'); // ✅ VALIDAR UUID
    });
    
    Route::post('/cups-contratados/sincronizar', [CupsController::class, 'sincronizarCupsContratados'])
    ->name('cups-contratados.sincronizar');
    // ✅ SINCRONIZACIÓN UNIFICADA
    Route::post('/sync-all', [SyncController::class, 'syncAll'])
        ->name('sync.all');
    Route::get('/sync/pending-counts', [SyncController::class, 'getPendingCounts'])
        ->name('sync.pending-counts');

    // ✅ SINCRONIZACIÓN INDIVIDUAL (mantener para compatibilidad)
    Route::post('/sync-pacientes', [PacienteController::class, 'syncPendingPacientes'])
        ->name('pacientes.sync');
         Route::post('/test-sync-manual', [PacienteController::class, 'testSyncManual'])->name('pacientes.test-sync');

         // Sincronización de usuarios
    Route::get('/usuarios/sincronizar', [UsuarioController::class, 'sincronizar'])
        ->name('usuarios.sincronizar');
    Route::post('/usuarios/sincronizar/ejecutar', [UsuarioController::class, 'ejecutarSincronizacion'])
        ->name('usuarios.sincronizar.ejecutar');

    
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
        
        // En routes/web.php - agregar esta ruta
        Route::get('/api/health-check', function () {
            return response()->json(['status' => 'ok', 'timestamp' => now()]);
        })->name('health.check');

        // Configuración del sistema
        Route::get('/config', [AdminController::class, 'config'])->name('config');
        Route::post('/config', [AdminController::class, 'updateConfig'])->name('config.update');
        
        // Sincronización masiva
        Route::post('/sync-all', [AdminController::class, 'syncAll'])->name('sync-all');
    });

        // Sincronizar historias
    Route::post('/cronograma/sincronizar-historias', [CronogramaController::class, 'sincronizarHistorias'])
        ->name('cronograma.sincronizar-historias');
    
    // Verificar nuevas historias
    Route::get('/cronograma/verificar-nuevas-historias', [CronogramaController::class, 'verificarNuevasHistorias'])
        ->name('cronograma.verificar-nuevas-historias');
    
    // Obtener historias
    Route::get('/cronograma/historias-clinicas', [CronogramaController::class, 'getHistoriasClinicas'])
        ->name('cronograma.historias-clinicas');

    
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


Route::middleware(['custom.auth', 'role:admin,administrador'])->prefix('usuarios')->name('usuarios.')->group(function () {
    Route::get('/', [UsuarioController::class, 'index'])->name('index');
    Route::get('/create', [UsuarioController::class, 'create'])->name('create');
    Route::post('/', [UsuarioController::class, 'store'])->name('store');
    Route::get('/{uuid}', [UsuarioController::class, 'show'])->name('show');
    Route::get('/{uuid}/edit', [UsuarioController::class, 'edit'])->name('edit');
    Route::put('/{uuid}', [UsuarioController::class, 'update'])->name('update');
    Route::delete('/{uuid}', [UsuarioController::class, 'destroy'])->name('destroy');
    
    // Rutas adicionales
    Route::patch('/{uuid}/cambiar-estado', [UsuarioController::class, 'cambiarEstado'])->name('cambiar-estado');
    Route::post('/{uuid}/subir-firma', [UsuarioController::class, 'subirFirma'])->name('subir-firma');
    Route::delete('/{uuid}/eliminar-firma', [UsuarioController::class, 'eliminarFirma'])->name('eliminar-firma');
    });
Route::middleware(['custom.auth', 'profesional.salud'])->group(function () {
    // ✅ CRONOGRAMA - RUTAS COMPLETAS PARA PROFESIONALES DE SALUD
        Route::prefix('cronograma')->name('cronograma.')->group(function () {
        // Vista principal del cronograma
        Route::get('/', [CronogramaController::class, 'index'])->name('index');
        
        // Datos del cronograma vía AJAX
        Route::get('/data/{fecha}', [CronogramaController::class, 'getData'])->name('data');
        
        // Actualización rápida
        Route::get('/refresh', [CronogramaController::class, 'refresh'])->name('refresh');
        
        // Ver detalle de cita
        Route::get('/cita/{uuid}', [CronogramaController::class, 'verCita'])
              ->name('cita')
              ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');
              
        Route::get('/cita/{uuid}/detalle', [CronogramaController::class, 'getDetalleCita'])
              ->name('cita.detalle')
              ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');
        
        // ✅ RUTA ÚNICA PARA CAMBIAR ESTADO - DEBE COINCIDIR CON EL JAVASCRIPT
        Route::post('/cita/{uuid}/cambiar-estado', [CronogramaController::class, 'cambiarEstadoCita'])
              ->name('cita.cambiar-estado')
              ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');
        
        // ✅ RUTAS ADICIONALES ESPECÍFICAS PARA PROFESIONALES
        Route::get('/estadisticas/{fecha}', [CronogramaController::class, 'getEstadisticas'])
              ->name('estadisticas')
              ->where('fecha', '\d{4}-\d{2}-\d{2}');
              
        Route::get('/agenda/{uuid}/citas', [CronogramaController::class, 'getCitasAgenda'])
              ->name('agenda.citas')
              ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');
        
        // ✅ RUTAS DE DEBUG OFFLINE
        Route::get('/debug-agendas-offline', [CronogramaController::class, 'debugAgendasOffline'])
              ->name('debug-agendas-offline');
              
        Route::post('/sincronizar-agendas', [CronogramaController::class, 'sincronizarAgendasManual'])
              ->name('sincronizar-agendas');
              
        Route::post('/reparar-uuids-agendas', [CronogramaController::class, 'repararUUIDsAgendas'])
              ->name('reparar-uuids-agendas');
              
        Route::get('/mis-agendas/{fecha}', [CronogramaController::class, 'getMisAgendas'])
              ->name('mis-agendas')
              ->where('fecha', '\d{4}-\d{2}-\d{2}');
              
        Route::get('/resumen-dia/{fecha}', [CronogramaController::class, 'getResumenDia'])
              ->name('resumen-dia')
              ->where('fecha', '\d{4}-\d{2}-\d{2}');
              
        // ✅ RUTA PARA SINCRONIZACIÓN DE CAMBIOS
        Route::post('/sincronizar', [CronogramaController::class, 'sincronizarCambios'])
              ->name('sincronizar');
    });


        // ✅ RUTAS AJAX PARA BÚSQUEDAS
    Route::get('/historia-clinica/buscar-medicamentos', [HistoriaClinicaController::class, 'buscarMedicamentos'])
        ->name('historia-clinica.buscar-medicamentos');
    Route::get('/historia-clinica/buscar-diagnosticos', [HistoriaClinicaController::class, 'buscarDiagnosticos'])
        ->name('historia-clinica.buscar-diagnosticos');
    Route::get('/historia-clinica/buscar-remisiones', [HistoriaClinicaController::class, 'buscarRemisiones'])
        ->name('historia-clinica.buscar-remisiones');
    Route::get('/historia-clinica/buscar-cups', [HistoriaClinicaController::class, 'buscarCups'])
        ->name('historia-clinica.buscar-cups');
        // routes/web.php

        Route::get('/historia-clinica/determinar-vista/{citaUuid}', [HistoriaClinicaController::class, 'determinarVista'])
    ->name('historia-clinica.determinar-vista');




    // ✅ OTRAS RUTAS ESPECÍFICAS PARA PROFESIONALES DE SALUD
    Route::prefix('profesional')->name('profesional.')->group(function () {
        // Mis citas del día
        Route::get('/mis-citas', [CronogramaController::class, 'misCitas'])->name('mis-citas');
        
        // Historial de atenciones
        Route::get('/historial-atenciones', [CronogramaController::class, 'historialAtenciones'])->name('historial-atenciones');
        
        // Estadísticas personales
        Route::get('/estadisticas-personales', [CronogramaController::class, 'estadisticasPersonales'])->name('estadisticas-personales');
    });

    // ========================================
    // ✅ HISTORIA CLÍNICA - RUTAS COMPLETAS
    // ========================================
    Route::prefix('historia-clinica')->name('historia-clinica.')->group(function () {
        Route::get('/', [HistoriaClinicaController::class, 'index'])->name('index'); // ✅ ESTA ES LA PRINCIPAL
        Route::get('/buscar-por-documento', [HistoriaClinicaController::class, 'buscarPorDocumento'])->name('buscar-documento');
        // ... resto de rutas
    });
    // ✅ RUTA PARA VER HISTORIA CLÍNICA GUARDADA (SHOW)
    Route::get('/historia-clinica/{uuid}', [HistoriaClinicaController::class, 'show'])
        ->name('historia-clinica.show')
        ->middleware('custom.auth');


     // ✅ RUTAS DE HISTORIA CLÍNICA
    Route::prefix('historia-clinica')->name('historia-clinica.')->group(function () {
        Route::get('/crear/{citaUuid}', [HistoriaClinicaController::class, 'determinarVista'])
    ->name('historia-clinica.create');

        Route::post('/guardar', [HistoriaClinicaController::class, 'store'])->name('store');
        Route::get('/{uuid}', [HistoriaClinicaController::class, 'show'])->name('show');
        
        // ✅ RUTAS AJAX PARA BÚSQUEDAS
        Route::get('/ajax/medicamentos/buscar', [HistoriaClinicaController::class, 'buscarMedicamentos'])->name('buscar-medicamentos');
        Route::get('/ajax/diagnosticos/buscar', [HistoriaClinicaController::class, 'buscarDiagnosticos'])->name('buscar-diagnosticos');
        Route::get('/ajax/cups/buscar', [HistoriaClinicaController::class, 'buscarCups'])->name('buscar-cups');
        Route::get('/ajax/remisiones/buscar', [HistoriaClinicaController::class, 'buscarRemisiones'])->name('buscar-remisiones');
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

Route::get('/sync-cups-background', [App\Http\Controllers\CitaController::class, 'syncCupsBackground'])
    ->name('sync.cups.background');

    
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

// routes/web.php
Route::get('/fix-agendas-uuid', function() {
    try {
        $offlineService = app(\App\Services\OfflineService::class);
        
        // 1. Recrear tabla con campos UUID
        $offlineService->recreateAgendasTable();
        
        // 2. Obtener agendas de la API
        $apiService = app(\App\Services\ApiService::class);
        $response = $apiService->get('/agendas');
        
        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'error' => 'API devolvió success=false',
                'response' => $response
            ]);
        }
        
        // ✅ EXTRAER SOLO EL ARRAY 'data' DE LA PAGINACIÓN
        $responseData = $response['data'];
        
        // Verificar si tiene estructura de paginación Laravel
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            // ✅ CASO 1: Respuesta paginada
            $agendas = $responseData['data'];
            $totalPages = $responseData['last_page'] ?? 1;
            $currentPage = $responseData['current_page'] ?? 1;
            
            Log::info('📄 Respuesta paginada detectada', [
                'current_page' => $currentPage,
                'last_page' => $totalPages,
                'per_page' => $responseData['per_page'] ?? 'unknown',
                'total' => $responseData['total'] ?? 'unknown',
                'agendas_en_pagina' => count($agendas)
            ]);
            
            // ✅ OBTENER TODAS LAS PÁGINAS
            $todasLasAgendas = $agendas;
            
            for ($page = 2; $page <= $totalPages; $page++) {
                Log::info("📄 Obteniendo página {$page}/{$totalPages}");
                
                $pageResponse = $apiService->get("/agendas?page={$page}");
                
                if ($pageResponse['success'] && isset($pageResponse['data']['data'])) {
                    $todasLasAgendas = array_merge(
                        $todasLasAgendas, 
                        $pageResponse['data']['data']
                    );
                }
            }
            
            $agendas = $todasLasAgendas;
            
        } elseif (is_array($responseData)) {
            // ✅ CASO 2: Respuesta simple (array directo)
            $agendas = $responseData;
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Estructura de respuesta no reconocida',
                'data_type' => gettype($responseData)
            ]);
        }
        
        // ✅ VERIFICAR QUE HAY AGENDAS
        if (empty($agendas)) {
            return response()->json([
                'success' => false,
                'error' => 'No hay agendas para sincronizar',
                'total' => 0
            ]);
        }
        
        // 3. Guardar cada agenda
        $guardadas = 0;
        $errores = [];
        
        foreach ($agendas as $index => $agenda) {
            try {
                if (!is_array($agenda)) {
                    $errores[] = [
                        'index' => $index,
                        'error' => 'Item no es array',
                        'type' => gettype($agenda)
                    ];
                    continue;
                }
                
                $offlineService->storeAgendaOffline($agenda, false);
                $guardadas++;
                
            } catch (\Exception $e) {
                $errores[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'agenda_uuid' => $agenda['uuid'] ?? 'sin-uuid'
                ];
                
                Log::error('❌ Error guardando agenda', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'agenda' => $agenda
                ]);
            }
        }
        
        // 4. Verificar resultado
        $totalEnDB = DB::connection('offline')->table('agendas')->count();
        
        Log::info('✅ Sincronización completada', [
            'total_api' => count($agendas),
            'guardadas' => $guardadas,
            'errores' => count($errores),
            'total_en_db' => $totalEnDB
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Agendas sincronizadas exitosamente',
            'total_api' => count($agendas),
            'guardadas' => $guardadas,
            'errores_count' => count($errores),
            'errores' => $errores,
            'total_en_db' => $totalEnDB
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error en fix-agendas-uuid', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
});
// routes/web.php
Route::get('/test-sqlite', function() {
    try {
        $results = [];
        
        // 1. Verificar configuración
        $dbPath = database_path('offline.sqlite');
        $results['db_path'] = $dbPath;
        $results['db_exists'] = file_exists($dbPath);
        $results['db_writable'] = is_writable($dbPath);
        $results['db_size'] = file_exists($dbPath) ? filesize($dbPath) : 0;
        
        // 2. Verificar conexión
        try {
            DB::connection('offline')->getPdo();
            $results['connection'] = 'OK';
        } catch (\Exception $e) {
            $results['connection'] = 'ERROR: ' . $e->getMessage();
        }
        
        // 3. Verificar tablas
        $tables = DB::connection('offline')
            ->select("SELECT name FROM sqlite_master WHERE type='table'");
        $results['tables'] = array_map(fn($t) => $t->name, $tables);
        
        // 4. Contar registros
        if (in_array('agendas', $results['tables'])) {
            $results['agendas_count'] = DB::connection('offline')
                ->table('agendas')
                ->count();
        }
        
        // 5. Intentar insertar un registro de prueba
        try {
            DB::connection('offline')->table('agendas')->insert([
                'uuid' => 'test-' . uniqid(),
                'sede_id' => 1,
                'modalidad' => 'TEST',
                'fecha' => now()->format('Y-m-d'),
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
                'estado' => 'ACTIVO',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $results['insert_test'] = 'OK';
            $results['agendas_count_after'] = DB::connection('offline')
                ->table('agendas')
                ->count();
                
        } catch (\Exception $e) {
            $results['insert_test'] = 'ERROR: ' . $e->getMessage();
        }
        
        return response()->json($results);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});
// routes/web.php
Route::get('/init-sqlite', function() {
    try {
        $dbPath = database_path('offline.sqlite');
        
        // Crear archivo si no existe
        if (!file_exists($dbPath)) {
            touch($dbPath);
            chmod($dbPath, 0666);
        }
        
        // Crear tablas
        $offlineService = app(\App\Services\OfflineService::class);
        $offlineService->ensureDatabaseExists();
        
        // Verificar tablas creadas
        $tables = DB::connection('offline')
            ->select("SELECT name FROM sqlite_master WHERE type='table'");
        
        $tableNames = array_map(fn($t) => $t->name, $tables);
        
        // Contar registros en agendas
        $agendasCount = 0;
        if (in_array('agendas', $tableNames)) {
            $agendasCount = DB::connection('offline')->table('agendas')->count();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Base de datos SQLite inicializada',
            'db_path' => $dbPath,
            'db_exists' => file_exists($dbPath),
            'db_writable' => is_writable($dbPath),
            'db_size' => filesize($dbPath),
            'tables' => $tableNames,
            'agendas_count' => $agendasCount
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});
