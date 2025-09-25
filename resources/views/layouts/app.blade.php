<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIDIS - Sistema Médico')</title>
    @push('styles')
<link href="{{ asset('css/cups-autocomplete.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('js/cups-autocomplete.js') }}"></script>
@endpush
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        .connection-status {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1050;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .connection-online {
            background-color: var(--success-color);
            color: white;
        }

        .connection-offline {
            background-color: var(--danger-color);
            color: white;
        }

        .connection-syncing {
            background-color: var(--warning-color);
            color: white;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            padding: 20px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1e3d6f;
            border-color: #1e3d6f;
        }

        .sync-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1040;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .sync-button.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .offline-indicator {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .user-info {
            background: linear-gradient(135deg, var(--primary-color), #1e3d6f);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        /* ✅ NUEVOS ESTILOS PARA SELECTOR DE SEDE */
        .sede-selector-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sede-actual-info {
            transition: all 0.3s ease;
        }

        .sede-actual-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        #btnCambiarSede {
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        #btnCambiarSede:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #1e3d6f);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(44, 90, 160, 0.15);
        }

        .alert-info {
            border-radius: 10px;
            border-left: 4px solid #0dcaf0;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
            
            .connection-status {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 10px;
                display: inline-block;
            }
        }
        /* Dark Theme Styles */
.dark-theme {
    background-color: #1a1a1a !important;
    color: #e9ecef !important;
}

.dark-theme .card {
    background-color: #2d3748 !important;
    color: #e9ecef !important;
}

.dark-theme .navbar {
    background-color: #2d3748 !important;
}

.dark-theme .sidebar {
    background-color: #2d3748 !important;
}

.dark-theme .sidebar .nav-link {
    color: #e9ecef !important;
}

.dark-theme .sidebar .nav-link:hover,
.dark-theme .sidebar .nav-link.active {
    background-color: var(--primary-color) !important;
    color: white !important;
}

.dark-theme .form-control,
.dark-theme .form-select {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: #e9ecef !important;
}

.dark-theme .form-control:focus,
.dark-theme .form-select:focus {
    background-color: #374151 !important;
    border-color: var(--primary-color) !important;
    color: #e9ecef !important;
}

    </style>

    @stack('styles')
</head>
<body>
    <!-- Connection Status -->
    <div id="connectionStatus" class="connection-status {{ session('is_online', true) ? 'connection-online' : 'connection-offline' }}">
        <i class="fas fa-wifi"></i> 
        <span id="statusText">{{ session('is_online', true) ? 'Conectado' : 'Sin conexión' }}</span>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-heartbeat"></i> SIDIS
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> 
                                {{ session('usuario')['nombre_completo'] ?? 'Usuario' }}
                            </a>
                            <ul class="dropdown-menu">
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

    <div class="container-fluid">
        <div class="row">
            @auth
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar">
                    @include('layouts.partials.sidebar')
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10 main-content">
                    <!-- ✅ SELECTOR DE SEDE -->
                    <div class="sede-selector-container">
                        <div class="sede-actual-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-building text-primary me-2"></i>
                                <div>
                                    <strong id="sedeActualNombre">{{ session('usuario')['sede']['nombre'] ?? 'Cargando...' }}</strong>
                                    <br>
                                    <small class="text-muted">Sede actual</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm ms-auto" 
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
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Modo Offline:</strong> Trabajando sin conexión. Los cambios se sincronizarán automáticamente cuando se restablezca la conexión.
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

    <!-- ✅ MODAL PARA CAMBIAR SEDE -->
    @auth
    <div class="modal fade" id="modalCambiarSede" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>
                        Cambiar Sede
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar nueva sede:</label>
                        <select class="form-select" id="nuevaSedeSelect">
                            <option value="">Cargando sedes...</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Al cambiar de sede, tendrás acceso completo a todos los datos y funcionalidades de la sede seleccionada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
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

    <!-- Sync Button (solo si está autenticado y offline) -->
    @auth
        @if(($is_offline ?? false) || !($is_online ?? true))
            <button id="syncButton" class="btn btn-warning sync-button" title="Sincronizar datos">
                <i class="fas fa-sync-alt"></i>
            </button>
        @endif
    @endauth

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        // ✅ TU SCRIPT EXISTENTE DE CONNECTION MONITORING (MANTENER EXACTAMENTE IGUAL)
        let isOnline = {{ ($is_online ?? true) ? 'true' : 'false' }};
        let checkInterval;

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
                            // Reconectado - mostrar opción de sincronizar
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
                    // Recargar página para actualizar estado
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

        // 🆕 SOLO ESTAS VARIABLES Y FUNCIONES PARA SINCRONIZACIÓN AUTOMÁTICA
        let wasOfflinePacientes = false;
        let syncInProgress = false;

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
                
                // Si acabamos de volver online después de estar offline
                if (currentlyOnline && wasOfflinePacientes) {
                    console.log('🔄 Conexión restaurada, sincronizando pacientes...');
                    
                    syncInProgress = true;
                    
                    // Mostrar notificación discreta
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
                    
                    // Sincronizar pacientes automáticamente
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
                            
                            // Recargar si estamos en la página de pacientes
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

        // ✅ FUNCIONES PARA CAMBIO DE SEDE
        function cargarSedesDisponibles() {
            const nuevaSedeSelect = document.getElementById('nuevaSedeSelect');
            nuevaSedeSelect.innerHTML = '<option value="">Cargando...</option>';
            
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
                        
                        // Marcar la sede actual
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
            });
        }

        function cambiarSede(nuevaSedeId) {
            const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
            btnConfirmarCambio.disabled = true;
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
                    // Actualizar nombre de sede actual
                    document.getElementById('sedeActualNombre').textContent = data.usuario.sede.nombre;
                    
                    // Cerrar modal
                    const modalCambiarSede = bootstrap.Modal.getInstance(document.getElementById('modalCambiarSede'));
                    modalCambiarSede.hide();
                    
                    // Mostrar mensaje de éxito
                    mostrarAlerta(data.message, 'success');
                    
                    // Recargar página después de un momento para actualizar todo
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
                btnConfirmarCambio.innerHTML = '<i class="fas fa-check me-1"></i> Cambiar Sede';
            });
        }

        function mostrarAlerta(mensaje, tipo) {
            // Crear alerta dinámica
            const alertaDiv = document.createElement('div');
            alertaDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed`;
            alertaDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            
            alertaDiv.innerHTML = `
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'times-circle'} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertaDiv);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertaDiv.parentNode) {
                    alertaDiv.remove();
                }
            }, 5000);
        }

        // Event listeners (TU CÓDIGO EXISTENTE + PEQUEÑAS ADICIONES)
        document.addEventListener('DOMContentLoaded', function() {
            // Check connection every 30 seconds (tu código existente)
            checkInterval = setInterval(checkConnection, 30000);
            
            // 🆕 SOLO AGREGAR ESTO: Verificar sincronización de pacientes cada 30 segundos
            setInterval(autoSyncPacientes, 30000);
            setTimeout(autoSyncPacientes, 3000); // Verificar al cargar

            // Sync button click (tu código existente)
            const syncBtn = document.getElementById('syncButton');
            if (syncBtn) {
                syncBtn.addEventListener('click', syncData);
            }

            // ✅ EVENT LISTENERS PARA CAMBIO DE SEDE
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

            // Handle online/offline events (tu código existente + pequeña adición)
            window.addEventListener('online', () => {
                updateConnectionStatus(true);
                checkConnection();
                // 🆕 SOLO AGREGAR ESTO: También verificar sincronización de pacientes
                setTimeout(autoSyncPacientes, 1000);
            });

            window.addEventListener('offline', () => {
                updateConnectionStatus(false);
                // 🆕 SOLO AGREGAR ESTO: Marcar como offline para pacientes
                wasOfflinePacientes = true;
            });
        });

        // Utility functions (tu código existente - MANTENER IGUAL)
        function showAlert(type, message, title = '') {
            Swal.fire({
                icon: type,
                title: title || (type === 'success' ? '¡Éxito!' : type === 'error' ? '¡Error!' : '¡Información!'),
                text: message,
                timer: type === 'success' ? 3000 : undefined,
                showConfirmButton: type !== 'success'
            });
        }

        function confirmAction(message, callback) {
            Swal.fire({
                title: '¿Está seguro?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        }
        // 🆕 AGREGAR ESTAS FUNCIONES PARA LOS BOTONES DE TEST
// Función para test manual de sincronización
function testSyncNow() {
    console.log('🧪 Iniciando test de sincronización manual...');
    
    Swal.fire({
        title: 'Ejecutando Test',
        text: 'Verificando sistema de sincronización...',
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false
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
                <div style="text-align: left;">
                    <p><strong>Estado:</strong> ${data.success ? '✅ Exitoso' : '❌ Error'}</p>
                    <p><strong>Conexión:</strong> ${data.connection ? '🟢 Online' : '🔴 Offline'}</p>
                    <p><strong>Pacientes pendientes:</strong> ${data.pending_count || 0}</p>
                    <p><strong>Mensaje:</strong> ${data.message || 'Sin mensaje'}</p>
                    ${data.error ? `<p><strong>Error:</strong> ${data.error}</p>` : ''}
                </div>
            `,
            icon: data.success ? 'success' : 'error',
            width: '500px'
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

// Función para forzar sincronización manual
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
        allowOutsideClick: false
    });

    syncInProgress = true;
    
    // Actualizar indicador visual
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
                icon: icon
            }).then(() => {
                // Recargar la página si estamos en pacientes y se sincronizó algo
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
        
        // Restaurar indicador de conexión después de 2 segundos
        setTimeout(() => {
            updateConnectionStatus(isOnline);
        }, 2000);
        
        console.log('🏁 Sincronización forzada finalizada');
    });
}

    </script>

    @stack('scripts')
</body>
</html>
