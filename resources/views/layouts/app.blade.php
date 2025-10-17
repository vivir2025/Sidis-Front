<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIDIS - Sistema Médico')</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --primary-dark: #1e3d6f;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-bg: #1a1a1a;
            --dark-card: #2d3748;
            --dark-border: #4b5563;
            --border-radius: 12px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            line-height: 1.6;
            transition: var(--transition);
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: linear-gradient(135deg, #ffffff, #f8f9fa) !important;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .navbar-brand i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* ===== CONNECTION STATUS ===== */
        .connection-status {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .connection-online {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
        }

        .connection-offline {
            background: linear-gradient(135deg, var(--danger-color), #e74c3c);
            color: white;
        }

        .connection-syncing {
            background: linear-gradient(135deg, var(--warning-color), #f39c12);
            color: #212529;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            min-height: calc(100vh - 76px);
            background: linear-gradient(180deg, #ffffff, #f8f9fa);
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            border-right: 1px solid #e9ecef;
        }

        .sidebar .nav-link {
            color: #495057;
            padding: 14px 20px;
            margin: 4px 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            padding: 25px;
            background-color: var(--secondary-color);
            min-height: calc(100vh - 76px);
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* ===== BUTTONS ===== */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #0f2347);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.4);
        }

        /* ===== SEDE SELECTOR ===== */
        .sede-selector-container {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .sede-selector-container::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(44, 90, 160, 0.1), transparent);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .sede-actual-info {
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .sede-actual-info:hover {
            transform: translateY(-2px);
        }

        #btnCambiarSede {
            border-radius: 20px;
            transition: var(--transition);
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        #btnCambiarSede:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
        }

        /* ===== MODALS ===== */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border: none;
            padding: 1.5rem 2rem;
            background-color: #f8f9fa;
        }

        /* ===== FORMS ===== */
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(44, 90, 160, 0.15);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
        }

        /* ===== ALERTS ===== */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left: 4px solid var(--info-color);
        }

        .offline-indicator {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--warning-color);
            box-shadow: var(--box-shadow);
        }

        /* ===== SYNC BUTTON ===== */
        .sync-button {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1040;
            border-radius: 50%;
            width: 65px;
            height: 65px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: none;
            background: linear-gradient(135deg, var(--warning-color), #f39c12);
            color: #212529;
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .sync-button:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }

        .sync-button.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== USER INFO ===== */
        .user-info {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }

        /* ===== DROPDOWN ===== */
        .dropdown-menu {
            border: none;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.75rem 1.25rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            transform: translateX(5px);
        }

        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
        }

        /* ===== DARK THEME ===== */
        .dark-theme {
            background-color: var(--dark-bg) !important;
            color: #e9ecef !important;
        }

        .dark-theme .navbar {
            background: linear-gradient(135deg, var(--dark-card), #374151) !important;
            border-bottom-color: var(--dark-border);
        }

        .dark-theme .sidebar {
            background: linear-gradient(180deg, var(--dark-card), #374151);
            border-right-color: var(--dark-border);
        }

        .dark-theme .sidebar .nav-link {
            color: #e9ecef !important;
        }

        .dark-theme .sidebar .nav-link:hover,
        .dark-theme .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
            color: white !important;
        }

        .dark-theme .card {
            background-color: var(--dark-card) !important;
            color: #e9ecef !important;
        }

        .dark-theme .form-control,
        .dark-theme .form-select {
            background-color: #374151 !important;
            border-color: var(--dark-border) !important;
            color: #e9ecef !important;
        }

        .dark-theme .form-control:focus,
        .dark-theme .form-select:focus {
            background-color: #374151 !important;
            border-color: var(--primary-color) !important;
            color: #e9ecef !important;
        }

        .dark-theme .sede-selector-container {
            background: linear-gradient(135deg, var(--dark-card), #374151);
        }

        .dark-theme .modal-content {
            background-color: var(--dark-card);
            color: #e9ecef;
        }

        .dark-theme .modal-footer {
            background-color: #374151;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
            
            .connection-status {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 15px;
                display: inline-block;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .sede-selector-container {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .sync-button {
                width: 55px;
                height: 55px;
                bottom: 20px;
                right: 20px;
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .sede-selector-container {
                padding: 12px;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }

        /* ===== ANIMATIONS ===== */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* ===== LOADING STATES ===== */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>

    @push('styles')
        <link href="{{ asset('css/cups-autocomplete.css') }}" rel="stylesheet">
    @endpush

    @stack('styles')
</head>
<body class="fade-in">
    <!-- Connection Status -->
    <div id="connectionStatus" class="connection-status {{ session('is_online', true) ? 'connection-online' : 'connection-offline' }}">
        <i class="fas fa-wifi"></i> 
        <span id="statusText">{{ session('is_online', true) ? 'Conectado' : 'Sin conexión' }}</span>
    </div>
 @unless(request()->routeIs('login'))
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-heartbeat"></i> SIDS
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i> 
                                {{ session('usuario')['nombre_completo'] ?? 'Usuario' }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end slide-in-right">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
@endunless


    <div class="container-fluid">
        <div class="row">
            @auth
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar">
                    @include('layouts.partials.sidebar')
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10 main-content">
                    <!-- Selector de Sede -->
                    <div class="sede-selector-container">
                        <div class="sede-actual-info">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-building text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold" id="sedeActualNombre">
                                        {{ session('usuario')['sede']['nombre'] ?? 'Cargando...' }}
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Sede actual
                                    </small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" 
                                        id="btnCambiarSede"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalCambiarSede">
                                    <i class="fas fa-exchange-alt me-1"></i>
                                    Cambiar
                                </button>
                            </div>
                        </div>
                    </div>

                    @if(($is_offline ?? false) || !($is_online ?? true))
                        <div class="offline-indicator">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                                <div>
                                    <strong>Modo Offline:</strong> Trabajando sin conexión. Los cambios se sincronizarán automáticamente cuando se restablezca la conexión.
                                </div>
                            </div>
                        </div>
                    @endif

                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>
            @else
                <div class="col-12">
                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>
            @endauth
        </div>
    </div>

    <!-- Modal para Cambiar Sede -->
    @auth
    <div class="modal fade" id="modalCambiarSede" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>
                        Cambiar Sede
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-list me-2"></i>
                            Seleccionar nueva sede:
                        </label>
                        <select class="form-select" id="nuevaSedeSelect">
                            <option value="">Cargando sedes...</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-3 mt-1"></i>
                            <div>
                                <strong>Información importante:</strong>
                                <p class="mb-0 mt-1">Al cambiar de sede, tendrás acceso completo a todos los datos y funcionalidades de la sede seleccionada.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCambio">
                        <i class="fas fa-check me-1"></i>
                        Cambiar Sede
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <!-- Sync Button -->
    @auth
        @if(($is_offline ?? false) || !($is_online ?? true))
            <button id="syncButton" class="btn sync-button" title="Sincronizar datos">
                <i class="fas fa-sync-alt"></i>
            </button>
        @endif
    @endauth

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        // Tu JavaScript existente aquí (mantener exactamente igual)
        let isOnline = {{ ($is_online ?? true) ? 'true' : 'false' }};
        let checkInterval;
        let wasOfflinePacientes = false;
        let syncInProgress = false;

        // Todas tus funciones JavaScript existentes...
        // (Copio todo tu JavaScript tal como está)

        function updateConnectionStatus(online) {
            const statusEl = document.getElementById('connectionStatus');
            const textEl = document.getElementById('statusText');
            
            if (online) {
                statusEl.className = 'connection-status connection-online';
                textEl.innerHTML = 'Conectado';
            } else {
                statusEl.className = 'connection-status connection-offline';
                textEl.innerHTML = 'Sin conexión';
            }
            
            isOnline = online;
        }

        function checkConnection() {
            fetch('/check-connection', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.online !== isOnline) {
                        updateConnectionStatus(data.online);
                        
                        if (data.online && !isOnline) {
                            Swal.fire({
                                title: '¡Conexión restablecida!',
                                text: '¿Desea sincronizar los datos pendientes?',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Sincronizar',
                                cancelButtonText: 'Más tarde'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    syncData();
                                }
                            });
                        }
                    }
                })
                .catch(() => {
                    updateConnectionStatus(false);
                });
        }

        function syncData() {
            const syncBtn = document.getElementById('syncButton');
            const statusEl = document.getElementById('connectionStatus');
            
            if (syncBtn) {
                syncBtn.classList.add('spinning');
            }
            
            statusEl.className = 'connection-status connection-syncing';
            document.getElementById('statusText').innerHTML = 'Sincronizando...';

            fetch('/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Sincronizado!', 'Los datos se han sincronizado correctamente', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', 'No se pudo sincronizar: ' + data.error, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Error de conexión durante la sincronización', 'error');
            })
            .finally(() => {
                if (syncBtn) {
                    syncBtn.classList.remove('spinning');
                }
                updateConnectionStatus(isOnline);
            });
        }

        function autoSyncPacientes() {
            if (syncInProgress) {
                              console.log('🔄 Sincronización ya en progreso...');
                return;
            }

            fetch('/check-connection', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const currentlyOnline = data.success && data.online;
                
                if (currentlyOnline && wasOfflinePacientes) {
                    console.log('🔄 Conexión restaurada, sincronizando pacientes...');
                    
                    syncInProgress = true;
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Sincronizando',
                            text: 'Sincronizando pacientes pendientes...',
                            icon: 'info',
                            timer: 2000,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false
                        });
                    }
                    
                    fetch('/sync-pacientes', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(syncData => {
                        if (syncData.success && syncData.synced_count > 0) {
                            console.log(`✅ ${syncData.synced_count} pacientes sincronizados`);
                            
                            Swal.fire({
                                title: 'Sincronización Completada',
                                text: `${syncData.synced_count} pacientes sincronizados automáticamente`,
                                icon: 'success',
                                timer: 3000,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false
                            });
                            
                            if (window.location.pathname.includes('pacientes')) {
                                setTimeout(() => window.location.reload(), 2000);
                            }
                        } else {
                            console.log('ℹ️ No hay pacientes pendientes para sincronizar');
                        }
                    })
                    .catch(error => {
                        console.log('❌ Error sincronizando pacientes:', error);
                    })
                    .finally(() => {
                        syncInProgress = false;
                    });
                }
                
                wasOfflinePacientes = !currentlyOnline;
            })
            .catch(error => {
                wasOfflinePacientes = true;
            });
        }

        function cargarSedesDisponibles() {
            const nuevaSedeSelect = document.getElementById('nuevaSedeSelect');
            nuevaSedeSelect.innerHTML = '<option value="">Cargando...</option>';
            nuevaSedeSelect.classList.add('loading');
            
            fetch('/sedes-disponibles', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    nuevaSedeSelect.innerHTML = '<option value="">Selecciona una sede...</option>';
                    
                    data.data.forEach(sede => {
                        const option = document.createElement('option');
                        option.value = sede.id;
                        option.textContent = sede.nombre;
                        
                        if (sede.id === data.sede_actual) {
                            option.textContent += ' (Actual)';
                            option.disabled = true;
                            option.selected = true;
                        }
                        
                        nuevaSedeSelect.appendChild(option);
                    });
                } else {
                    nuevaSedeSelect.innerHTML = '<option value="">Error cargando sedes</option>';
                    mostrarAlerta('Error cargando sedes disponibles', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                nuevaSedeSelect.innerHTML = '<option value="">Error de conexión</option>';
                mostrarAlerta('Error de conexión', 'error');
            })
            .finally(() => {
                nuevaSedeSelect.classList.remove('loading');
            });
        }

        function cambiarSede(nuevaSedeId) {
            const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
            const originalText = btnConfirmarCambio.innerHTML;
            
            btnConfirmarCambio.disabled = true;
            btnConfirmarCambio.classList.add('loading');
            btnConfirmarCambio.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cambiando...';

            fetch('/cambiar-sede', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    sede_id: parseInt(nuevaSedeId)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sedeActualNombre').textContent = data.usuario.sede.nombre;
                    
                    const modalCambiarSede = bootstrap.Modal.getInstance(document.getElementById('modalCambiarSede'));
                    modalCambiarSede.hide();
                    
                    Swal.fire({
                        title: '¡Sede cambiada!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                } else {
                    mostrarAlerta(data.error || 'Error cambiando sede', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error de conexión al cambiar sede', 'error');
            })
            .finally(() => {
                btnConfirmarCambio.disabled = false;
                btnConfirmarCambio.classList.remove('loading');
                btnConfirmarCambio.innerHTML = originalText;
            });
        }

        function mostrarAlerta(mensaje, tipo) {
            const alertaDiv = document.createElement('div');
            alertaDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed`;
            alertaDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 350px; max-width: 400px;';
            
            const iconClass = tipo === 'success' ? 'check-circle' : 
                             tipo === 'warning' ? 'exclamation-triangle' : 
                             tipo === 'info' ? 'info-circle' : 'times-circle';
            
            alertaDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${iconClass} me-2 fs-5"></i>
                    <div class="flex-grow-1">${mensaje}</div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(alertaDiv);
            
            // Animación de entrada
            setTimeout(() => alertaDiv.classList.add('slide-in-right'), 10);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertaDiv.parentNode) {
                    alertaDiv.classList.add('fade');
                    setTimeout(() => alertaDiv.remove(), 300);
                }
            }, 5000);
        }

        function testSyncNow() {
            console.log('🧪 Iniciando test de sincronización manual...');
            
            Swal.fire({
                title: 'Ejecutando Test',
                text: 'Verificando sistema de sincronización...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('/test-sync-manual', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('🧪 Resultado del test:', data);
                
                Swal.fire({
                    title: 'Resultado del Test',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <strong>Estado:</strong> 
                                <span class="badge bg-${data.success ? 'success' : 'danger'} ms-2">
                                    ${data.success ? '✅ Exitoso' : '❌ Error'}
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Conexión:</strong> 
                                <span class="badge bg-${data.connection ? 'success' : 'danger'} ms-2">
                                    ${data.connection ? '🟢 Online' : '🔴 Offline'}
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Pacientes pendientes:</strong> 
                                <span class="badge bg-info ms-2">${data.pending_count || 0}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Mensaje:</strong> 
                                <div class="mt-1 p-2 bg-light rounded">${data.message || 'Sin mensaje'}</div>
                            </div>
                            ${data.error ? `
                                <div class="mb-3">
                                    <strong>Error:</strong> 
                                    <div class="mt-1 p-2 bg-danger bg-opacity-10 text-danger rounded">${data.error}</div>
                                </div>
                            ` : ''}
                        </div>
                    `,
                    icon: data.success ? 'success' : 'error',
                    width: '600px',
                    confirmButtonText: 'Entendido'
                });
            })
            .catch(error => {
                console.error('❌ Error en test:', error);
                Swal.fire({
                    title: 'Error en Test',
                    text: 'No se pudo ejecutar el test de sincronización',
                    icon: 'error'
                });
            });
        }

        function syncAllPendingData() {
            if (syncInProgress) {
                Swal.fire({
                    title: 'Sincronización en Progreso',
                    text: 'Ya hay una sincronización ejecutándose',
                    icon: 'warning'
                });
                return;
            }

            console.log('🚀 Forzando sincronización manual de pacientes...');
            
            Swal.fire({
                title: 'Sincronizando',
                text: 'Sincronizando todos los pacientes pendientes...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            syncInProgress = true;
            
            const statusEl = document.getElementById('connectionStatus');
            if (statusEl) {
                statusEl.className = 'connection-status connection-syncing';
                document.getElementById('statusText').innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Sincronizando...';
            }

            fetch('/sync-pacientes', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('📥 Respuesta del servidor:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('📊 Resultado de sincronización forzada:', data);
                
                if (data.success) {
                    const syncedCount = data.synced_count || 0;
                    const failedCount = data.failed_count || 0;
                    
                    let message = '';
                    let icon = 'success';
                    
                    if (syncedCount > 0) {
                        message = `${syncedCount} pacientes sincronizados correctamente`;
                        if (failedCount > 0) {
                            message += `\n${failedCount} pacientes fallaron`;
                            icon = 'warning';
                        }
                    } else if (failedCount > 0) {
                        message = `${failedCount} pacientes fallaron al sincronizar`;
                        icon = 'error';
                    } else {
                        message = 'No hay pacientes pendientes para sincronizar';
                        icon = 'info';
                    }
                    
                    Swal.fire({
                        title: 'Sincronización Completada',
                        text: message,
                        icon: icon,
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        if (syncedCount > 0 && window.location.pathname.includes('pacientes')) {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error de Sincronización',
                        text: data.error || 'Error desconocido en la sincronización',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('💥 Error de conexión en sincronización forzada:', error);
                
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor para sincronizar',
                    icon: 'error'
                });
            })
            .finally(() => {
                syncInProgress = false;
                
                setTimeout(() => {
                    updateConnectionStatus(isOnline);
                }, 2000);
                
                console.log('🏁 Sincronización forzada finalizada');
            });
        }

        // Utility functions
        function showAlert(type, message, title = '') {
            Swal.fire({
                icon: type,
                title: title || (type === 'success' ? '¡Éxito!' : type === 'error' ? '¡Error!' : '¡Información!'),
                text: message,
                timer: type === 'success' ? 3000 : undefined,
                showConfirmButton: type !== 'success',
                toast: type === 'success',
                position: type === 'success' ? 'top-end' : 'center'
            });
        }

        function confirmAction(message, callback) {
            Swal.fire({
                title: '¿Está seguro?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--danger-color)',
                cancelButtonColor: 'var(--primary-color)',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips de Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Check connection every 30 seconds
            checkInterval = setInterval(checkConnection, 30000);
            
            // Verificar sincronización de pacientes cada 30 segundos
            setInterval(autoSyncPacientes, 30000);
            setTimeout(autoSyncPacientes, 3000);

            // Sync button click
            const syncBtn = document.getElementById('syncButton');
            if (syncBtn) {
                syncBtn.addEventListener('click', syncData);
            }

            // Event listeners para cambio de sede
            const btnCambiarSede = document.getElementById('btnCambiarSede');
            if (btnCambiarSede) {
                btnCambiarSede.addEventListener('click', function() {
                    cargarSedesDisponibles();
                });
            }

            const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
            if (btnConfirmarCambio) {
                btnConfirmarCambio.addEventListener('click', function() {
                    const nuevaSedeId = document.getElementById('nuevaSedeSelect').value;
                    
                    if (!nuevaSedeId) {
                        mostrarAlerta('Por favor selecciona una sede', 'warning');
                        return;
                    }

                    cambiarSede(nuevaSedeId);
                });
            }

            // Handle online/offline events
            window.addEventListener('online', () => {
                updateConnectionStatus(true);
                checkConnection();
                setTimeout(autoSyncPacientes, 1000);
                mostrarAlerta('Conexión restablecida', 'success');
            });

            window.addEventListener('offline', () => {
                updateConnectionStatus(false);
                wasOfflinePacientes = true;
                mostrarAlerta('Conexión perdida - Trabajando offline', 'warning');
            });

            // Smooth scroll para enlaces internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(alert => {
                    if (!alert.classList.contains('show')) return;
                    
                    setTimeout(() => {
                        alert.classList.add('fade');
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.remove();
                            }
                        }, 300);
                    }, 5000);
                });
            }, 100);
        });

        // Prevenir envío múltiple de formularios
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                const originalText = submitBtn.innerHTML || submitBtn.value;
                
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
                } else {
                    submitBtn.value = 'Procesando...';
                }
                
                // Re-habilitar después de 5 segundos como fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    if (submitBtn.tagName === 'BUTTON') {
                        submitBtn.innerHTML = originalText;
                    } else {
                        submitBtn.value = originalText;
                    }
                }, 5000);
            }
        });

        // Manejo global de errores AJAX
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Error no manejado:', event.reason);
            mostrarAlerta('Ha ocurrido un error inesperado', 'error');
        });
    </script>

    @push('scripts')
        <script src="{{ asset('js/cups-autocomplete.js') }}"></script>
    @endpush

    @stack('scripts')
</body>
</html>
