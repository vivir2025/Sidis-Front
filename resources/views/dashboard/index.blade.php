@extends('layouts.app')
@include('layouts.partials.sidebar')
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

    <!-- Quick Stats Cards - Mejorado con animaciones -->
    <div class="row mb-4 g-4">
        <!-- Pacientes Card -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 module-card" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 icon-hover">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="card-title fw-bold">Pacientes</h5>
                    <p class="text-muted mb-3">Gestión de pacientes del sistema</p>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('pacientes.index') }}" class="btn btn-primary btn-sm flex-fill" title="Ver todos los pacientes">
                            <i class="fas fa-list me-1"></i>Listar
                        </a>
                        <a href="{{ route('pacientes.create') }}" class="btn btn-outline-primary btn-sm flex-fill" title="Crear nuevo paciente">
                            <i class="fas fa-plus me-1"></i>Crear
                        </a>
                    </div>
                    
                    <!-- Stats rápidos -->
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-chart-line me-1"></i>
                            Acceso rápido
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Agendas Card -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 module-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 icon-hover">
                            <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="card-title fw-bold">Agendas</h5>
                    <p class="text-muted mb-3">Gestión de agendas médicas</p>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('agendas.index') }}" class="btn btn-primary btn-sm flex-fill" title="Ver todas las agendas">
                            <i class="fas fa-list me-1"></i>Listar
                        </a>
                        <a href="{{ route('agendas.create') }}" class="btn btn-outline-primary btn-sm flex-fill" title="Crear nueva agenda">
                            <i class="fas fa-plus me-1"></i>Crear
                        </a>
                    </div>
                    
                    <!-- Stats rápidos -->
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Programación de horarios
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Citas Card -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 module-card" data-aos="fade-up" data-aos-delay="300">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 icon-hover">
                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="card-title fw-bold">Citas</h5>
                    <p class="text-muted mb-3">Gestión de citas médicas</p>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('citas.index') }}" class="btn btn-primary btn-sm flex-fill" title="Ver todas las citas">
                            <i class="fas fa-list me-1"></i>Listar
                        </a>
                        <a href="{{ route('citas.create') }}" class="btn btn-outline-primary btn-sm flex-fill" title="Agendar nueva cita">
                            <i class="fas fa-plus me-1"></i>Crear
                        </a>
                    </div>
                    
                    <!-- Stats rápidos -->
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-user-md me-1"></i>
                            Reserva de consultas
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Usuarios Card -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 module-card" data-aos="fade-up" data-aos-delay="400">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 icon-hover">
                            <i class="fas fa-user-shield fa-2x text-primary"></i>
                        </div>
                    </div>
                    <h5 class="card-title fw-bold">Usuarios</h5>
                    <p class="text-muted mb-3">Gestión de usuarios del sistema</p>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-primary btn-sm flex-fill" title="Ver todos los usuarios">
                            <i class="fas fa-list me-1"></i>Listar
                        </a>
                        <a href="{{ route('usuarios.create') }}" class="btn btn-outline-primary btn-sm flex-fill" title="Crear nuevo usuario">
                            <i class="fas fa-plus me-1"></i>Crear
                        </a>
                    </div>
                    
                    <!-- Stats rápidos -->
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-users-cog me-1"></i>
                            Administración de accesos
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Accesos Rápidos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" data-aos="fade-up">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('pacientes.create') }}" class="quick-action-btn">
                                <i class="fas fa-user-plus text-primary"></i>
                                <span>Nuevo Paciente</span>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('citas.create') }}" class="quick-action-btn">
                                <i class="fas fa-calendar-plus text-primary"></i>
                                <span>Agendar Cita</span>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('agendas.create') }}" class="quick-action-btn">
                                <i class="fas fa-clock text-primary"></i>
                                <span>Nueva Agenda</span>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('usuarios.create') }}" class="quick-action-btn">
                                <i class="fas fa-user-cog text-primary"></i>
                                <span>Nuevo Usuario</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Usuario -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" data-aos="fade-right">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="info-item">
                                <i class="fas fa-id-card text-primary"></i>
                                <div>
                                    <small class="text-muted">Documento</small>
                                    <div class="fw-semibold">{{ $usuario['documento'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone text-primary"></i>
                                <div>
                                    <small class="text-muted">Teléfono</small>
                                    <div class="fw-semibold">{{ $usuario['telefono'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-item">
                                <i class="fas fa-envelope text-primary"></i>
                                <div>
                                    <small class="text-muted">Correo</small>
                                    <div class="fw-semibold">{{ $usuario['correo'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user-tag text-primary"></i>
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
            <div class="card border-0 shadow-sm h-100" data-aos="fade-left">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Información Laboral
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <div>
                                    <small class="text-muted">Sede</small>
                                    <div class="fw-semibold">{{ $usuario['sede']['nombre'] ?? 'No asignada' }}</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-toggle-on text-primary"></i>
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
                            <div class="info-item">
                                <i class="fas fa-user-shield text-primary"></i>
                                <div>
                                    <small class="text-muted">Rol</small>
                                    <div class="fw-semibold">{{ $usuario['rol']['nombre'] ?? 'No asignado' }}</div>
                                </div>
                            </div>
                            @if(isset($usuario['especialidad']) && $usuario['especialidad'])
                                <div class="info-item">
                                    <i class="fas fa-stethoscope text-primary"></i>
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

@push('styles')
<style>
/* Animaciones y efectos hover para las cards */
.module-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.module-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.icon-hover {
    transition: all 0.3s ease;
}

.module-card:hover .icon-hover {
    transform: scale(1.1) rotate(5deg);
}

/* Botones de acceso rápido */
.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    border-color: #007bff;
    background: #f8f9fa;
    transform: translateX(5px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.quick-action-btn i {
    font-size: 1.5rem;
}

.quick-action-btn span {
    font-weight: 500;
}

/* Info items */
.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.info-item i {
    font-size: 1.2rem;
    width: 30px;
    text-align: center;
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .module-card {
        margin-bottom: 1rem;
    }
    
    .quick-action-btn {
        flex-direction: column;
        text-align: center;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<script>
// Inicializar AOS (Animate On Scroll)
AOS.init({
    duration: 800,
    once: true,
    offset: 100
});

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
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
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
        showConfirmButton: false
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
        showConfirmButton: false
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
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            showAlert('success', 'Tema oscuro aplicado');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
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
            localStorage.clear();
            sessionStorage.clear();
            showAlert('success', 'Caché limpiado correctamente');
        }
    });
}

// Función helper para mostrar alertas
function showAlert(type, message, title = '') {
    const icons = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        icon: icons[type] || 'info',
        title: title || message,
        text: title ? message : '',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Efecto de clic en las cards de módulos
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tema guardado
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
    // Auto-hide alerts después de 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                alert.classList.add('fade');
            }
        });
    }, 5000);
    
    // Agregar efecto ripple a los botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Tooltip de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Efecto parallax suave en scroll
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const parallax = document.querySelectorAll('.module-card');
        parallax.forEach((element, index) => {
            const speed = 0.5 + (index * 0.1);
            element.style.transform = `translateY(${scrolled * speed * 0.01}px)`;
        });
    });
});

// Función para actualizar el estado de conexión en tiempo real
function updateConnectionStatus() {
    fetch('/check-connection', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.badge');
        if (data.online) {
            badge.className = 'badge bg-success me-2';
            badge.innerHTML = '<i class="fas fa-wifi"></i> Conectado';
        } else {
            badge.className = 'badge bg-warning me-2';
            badge.innerHTML = '<i class="fas fa-wifi-slash"></i> Modo Offline';
        }
    })
    .catch(error => {
        console.error('Error checking connection:', error);
    });
}

// Verificar conexión cada 30 segundos
setInterval(updateConnectionStatus, 30000);

// Atajos de teclado (sin mostrar notificación)
document.addEventListener('keydown', function(e) {
    // Ctrl + Alt + P = Ir a Pacientes
    if (e.ctrlKey && e.altKey && e.key === 'p') {
        e.preventDefault();
        window.location.href = "{{ route('pacientes.index') }}";
    }
    
    // Ctrl + Alt + A = Ir a Agendas
    if (e.ctrlKey && e.altKey && e.key === 'a') {
        e.preventDefault();
        window.location.href = "{{ route('agendas.index') }}";
    }
    
    // Ctrl + Alt + C = Ir a Citas
    if (e.ctrlKey && e.altKey && e.key === 'c') {
        e.preventDefault();
        window.location.href = "{{ route('citas.index') }}";
    }
    
    // Ctrl + Alt + U = Ir a Usuarios
    if (e.ctrlKey && e.altKey && e.key === 'u') {
        e.preventDefault();
        window.location.href = "{{ route('usuarios.index') }}";
    }
    
    // Ctrl + Alt + L = Logout
    if (e.ctrlKey && e.altKey && e.key === 'l') {
        e.preventDefault();
        confirmLogout();
    }
});
</script>

<style>
/* Estilos adicionales para efectos ripple */
.btn {
    position: relative;
    overflow: hidden;
}

.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Estilos para tema oscuro */
body.dark-theme {
    background-color: #1a1a1a;
    color: #e0e0e0;
}

body.dark-theme .card {
    background-color: #2d2d2d;
    color: #e0e0e0;
    border-color: #404040;
}

body.dark-theme .card-header {
    background-color: #404040;
    border-color: #505050;
}

body.dark-theme .text-muted {
    color: #b0b0b0 !important;
}

body.dark-theme .quick-action-btn {
    background-color: #2d2d2d;
    border-color: #404040;
    color: #e0e0e0;
}

body.dark-theme .quick-action-btn:hover {
    background-color: #404040;
    border-color: #007bff;
}

body.dark-theme .info-item {
    background-color: #404040;
}

body.dark-theme .info-item:hover {
    background-color: #505050;
}

/* Transiciones suaves para todos los elementos interactivos */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Scroll suave */
html {
    scroll-behavior: smooth;
}

/* Barra de scroll personalizada */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

body.dark-theme ::-webkit-scrollbar-track {
    background: #2d2d2d;
}

body.dark-theme ::-webkit-scrollbar-thumb {
    background: #555;
}

body.dark-theme ::-webkit-scrollbar-thumb:hover {
    background: #777;
}

/* Animación de pulso para notificaciones */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

.pulse {
    animation: pulse 2s infinite;
}

/* Loading spinner personalizado */
.spinner-custom {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Estilos responsivos mejorados */
@media (max-width: 768px) {
    .module-card {
        margin-bottom: 1rem;
    }
    
    .quick-action-btn {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .quick-action-btn i {
        margin-bottom: 10px;
    }
    
    .info-item {
        flex-direction: column;
        text-align: center;
    }
    
    .info-item i {
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 10px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    h1.h3 {
        font-size: 1.5rem;
    }
}
</style>
@endpush

@endsection
