@extends('layouts.app')

@section('title', 'Dashboard - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header del Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt text-primary me-2"></i>
                        Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Bienvenido, <strong>{{ $usuario['nombre_completo'] ?? 'Usuario' }}</strong>
                    </p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Estado de Conexión -->
                    @if(($is_offline ?? false) || !($is_online ?? true))
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @else
                        <span class="badge bg-success me-2">
                            <i class="fas fa-wifi"></i> Conectado
                        </span>
                    @endif
                    
                    <!-- Verificar Conexión -->
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="checkConnection()">
                        <i class="fas fa-sync-alt"></i> Verificar
                    </button>

                    <!-- Botón de Logout -->
                    <div class="dropdown">
                        <button class="btn btn-outline-danger btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> {{ explode(' ', $usuario['nombre_completo'] ?? 'Usuario')[0] }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="fas fa-user me-2"></i>
                                    {{ $usuario['nombre_completo'] ?? 'Usuario' }}
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="showProfile()">
                                    <i class="fas fa-user-edit me-2"></i>Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="showSettings()">
                                    <i class="fas fa-cog me-2"></i>Configuración
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de Modo Offline -->
    @if(($is_offline ?? false) || !($is_online ?? true))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Modo Offline Activo</strong>
                    <p class="mb-1">{{ $offline_message ?? 'Sin conexión al servidor. Trabajando con datos locales.' }}</p>
                    @if(($pending_changes ?? 0) > 0)
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            {{ $pending_changes }} cambios pendientes de sincronizar
                        </small>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats Cards -->
   <div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
                <h5 class="card-title">Pacientes</h5>
                <p class="text-muted mb-3">Gestión de pacientes</p>
                <a href="{{ route('pacientes.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>Ver más
                </a>
            </div>
        </div>
    </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-calendar-alt fa-2x text-success"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Citas</h5>
                    <p class="text-muted mb-3">Agenda de citas</p>
                    <a href="{{ route('agendas.index') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ver más
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-file-medical fa-2x text-info"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Historias</h5>
                    <p class="text-muted mb-3">Historias clínicas</p>
                    <a href="#" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ver más
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-chart-bar fa-2x text-warning"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Reportes</h5>
                    <p class="text-muted mb-3">Estadísticas</p>
                    <a href="#" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ver más
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Usuario -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-id-card text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Documento</small>
                                    <div class="fw-semibold">{{ $usuario['documento'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Teléfono</small>
                                    <div class="fw-semibold">{{ $usuario['telefono'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Correo</small>
                                    <div class="fw-semibold">{{ $usuario['correo'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-tag text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Usuario</small>
                                    <div class="fw-semibold">{{ $usuario['login'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Información Laboral
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Sede</small>
                                    <div class="fw-semibold">{{ $usuario['sede']['nombre'] ?? 'No asignada' }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-toggle-on text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Estado</small>
                                    <div>
                                        <span class="badge bg-{{ (($usuario['estado']['id'] ?? 0) == 1) ? 'success' : 'danger' }}">
                                            {{ $usuario['estado']['nombre'] ?? 'No definido' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-shield text-muted me-2"></i>
                                <div>
                                    <small class="text-muted">Rol</small>
                                    <div class="fw-semibold">{{ $usuario['rol']['nombre'] ?? 'No asignado' }}</div>
                                </div>
                            </div>
                            @if(isset($usuario['especialidad']) && $usuario['especialidad'])
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-stethoscope text-muted me-2"></i>
                                    <div>
                                        <small class="text-muted">Especialidad</small>
                                        <div class="fw-semibold">{{ $usuario['especialidad']['nombre'] }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Logout Form -->
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</div>

@push('scripts')
<script>
function checkConnection() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    button.disabled = true;

    fetch('/check-connection', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.online) {
            showAlert('success', 'Conexión restablecida', '¡Conectado!');
                       setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('warning', 'Sin conexión al servidor', 'Modo Offline');
        }
    })
    .catch(error => {
        showAlert('error', 'Error verificando conexión: ' + error.message, 'Error de Conexión');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function confirmLogout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Está seguro que desea cerrar su sesión actual?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Sí, cerrar sesión',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'animated fadeIn faster',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Cerrando sesión...',
                text: 'Por favor espere',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit logout form
            setTimeout(() => {
                document.getElementById('logoutForm').submit();
            }, 1000);
        }
    });
}

function showProfile() {
    Swal.fire({
        title: '<i class="fas fa-user-edit text-primary"></i> Mi Perfil',
        html: `
            <div class="text-start">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-user me-2"></i>Información Personal</h6>
                                <p><strong>Nombre:</strong> {{ $usuario['nombre_completo'] ?? 'No especificado' }}</p>
                                <p><strong>Documento:</strong> {{ $usuario['documento'] ?? 'No especificado' }}</p>
                                <p><strong>Correo:</strong> {{ $usuario['correo'] ?? 'No especificado' }}</p>
                                <p><strong>Teléfono:</strong> {{ $usuario['telefono'] ?? 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-briefcase me-2"></i>Información Laboral</h6>
                                <p><strong>Rol:</strong> {{ $usuario['rol']['nombre'] ?? 'No asignado' }}</p>
                                <p><strong>Sede:</strong> {{ $usuario['sede']['nombre'] ?? 'No asignada' }}</p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge bg-{{ (($usuario['estado']['id'] ?? 0) == 1) ? 'success' : 'danger' }}">
                                        {{ $usuario['estado']['nombre'] ?? 'No definido' }}
                                    </span>
                                </p>
                                @if(isset($usuario['especialidad']) && $usuario['especialidad'])
                                <p><strong>Especialidad:</strong> {{ $usuario['especialidad']['nombre'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        width: '600px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'animated fadeIn faster'
        }
    });
}

function showSettings() {
    Swal.fire({
        title: '<i class="fas fa-cog text-primary"></i> Configuración',
        html: `
            <div class="text-start">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action" onclick="changeTheme()">
                        <i class="fas fa-palette me-2"></i>
                        Cambiar tema
                        <small class="text-muted d-block">Personalizar apariencia</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="changePassword()">
                        <i class="fas fa-key me-2"></i>
                        Cambiar contraseña
                        <small class="text-muted d-block">Actualizar credenciales</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="syncSettings()">
                        <i class="fas fa-sync-alt me-2"></i>
                        Sincronización
                        <small class="text-muted d-block">Configurar sincronización automática</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="clearCache()">
                        <i class="fas fa-trash-alt me-2"></i>
                        Limpiar caché
                        <small class="text-muted d-block">Eliminar datos temporales</small>
                    </a>
                </div>
            </div>
        `,
        width: '500px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'animated fadeIn faster'
        }
    });
}

function changeTheme() {
    Swal.close();
    Swal.fire({
        title: 'Cambiar Tema',
        text: 'Seleccione el tema de su preferencia',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Tema Oscuro',
        cancelButtonText: 'Tema Claro',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Aplicar tema oscuro
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            showAlert('success', 'Tema oscuro aplicado');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Aplicar tema claro
            document.body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
            showAlert('success', 'Tema claro aplicado');
        }
    });
}

function changePassword() {
    Swal.close();
    Swal.fire({
        title: 'Cambiar Contraseña',
        html: `
            <form id="passwordForm">
                <div class="mb-3">
                    <input type="password" class="form-control" id="currentPassword" placeholder="Contraseña actual" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" id="newPassword" placeholder="Nueva contraseña" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirmar contraseña" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Cambiar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (!current || !newPass || !confirm) {
                Swal.showValidationMessage('Todos los campos son obligatorios');
                return false;
            }
            
            if (newPass !== confirm) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPass.length < 6) {
                Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return { current, newPass };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí enviarías la petición para cambiar la contraseña
            showAlert('success', 'Contraseña actualizada correctamente');
        }
    });
}

function syncSettings() {
    Swal.close();
    Swal.fire({
        title: 'Configuración de Sincronización',
        html: `
            <div class="text-start">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="autoSync" checked>
                    <label class="form-check-label" for="autoSync">
                        Sincronización automática
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="syncOnWifi" checked>
                    <label class="form-check-label" for="syncOnWifi">
                        Solo sincronizar con WiFi
                    </label>
                </div>
                <div class="mb-3">
                    <label for="syncInterval" class="form-label">Intervalo de sincronización</label>
                    <select class="form-select" id="syncInterval">
                        <option value="5">5 minutos</option>
                        <option value="15" selected>15 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="60">1 hora</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            showAlert('success', 'Configuración de sincronización guardada');
        }
    });
}

function clearCache() {
    Swal.close();
    Swal.fire({
        title: '¿Limpiar caché?',
        text: 'Esto eliminará todos los datos temporales almacenados localmente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Limpiar localStorage y sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            showAlert('success', 'Caché limpiado correctamente');
        }
    });
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                alert.classList.add('fade');
            }
        });
    }, 5000);
});
</script>
@endpush
@endsection

