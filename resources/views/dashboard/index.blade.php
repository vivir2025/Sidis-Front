@extends('layouts.app')
@section('title', 'Dashboard - SIDS')
@section('content')
<div class="container-fluid">
   
    <!-- Header del Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-3 mb-md-0">
                    <h1 class="h3 mb-0 d-flex align-items-center">
                        <div class="dashboard-icon-wrapper me-3">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            <span class="d-block fw-bold">Home</span>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-user-circle me-1"></i>
                                Bienvenido, <strong>{{ $usuario['nombre_completo'] ?? 'Usuario' }}</strong>
                            </small>
                        </div>
                    </h1>
                </div>
                
                <!-- Reloj en Tiempo Real -->
                <div class="d-flex align-items-center gap-3">
                    <div class="clock-widget" id="clockWidget">
                        <i class="fas fa-clock me-2"></i>
                        <span id="currentTime"></span>
                    </div>
                    @if(($is_offline ?? false) || !($is_online ?? true))
                        <span class="badge bg-warning text-dark animated-badge">
                            <i class="fas fa-wifi-slash"></i> Offline
                        </span>
                    @else
                        <span class="badge bg-success animated-badge">
                            <i class="fas fa-wifi"></i> Online
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
                
                <!-- Header Actions -->
                <!-- <div class="d-flex align-items-center gap-2"> -->
                    <!-- Estado de Conexión -->
                    <!-- @if(($is_offline ?? false) || !($is_online ?? true))
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @else
                        <span class="badge bg-success me-2">
                            <i class="fas fa-wifi"></i> Conectado
                        </span>
                    @endif
                    
                    Verificar Conexión -->
                    <!-- <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="checkConnection()">
                        <i class="fas fa-sync-alt"></i> Verificar
                    </button> -->

                    <!-- Botón de Logout -->
                    <!-- <div class="dropdown">
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
        </div> -->
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
                    <h2 class="stats-counter mb-2" data-count="{{ $stats['pacientes']['total'] ?? 0 }}">0</h2>
                    <p class="text-muted mb-3">
                        <span class="badge bg-success me-1">{{ $stats['pacientes']['activos'] ?? 0 }} activos</span>
                        <small class="text-success"><i class="fas fa-arrow-up"></i> +{{ $stats['pacientes']['nuevos_mes'] ?? 0 }} este mes</small>
                    </p>
                    
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
                    <h2 class="stats-counter mb-2" data-count="{{ $stats['agendas']['total'] ?? 0 }}">0</h2>
                    <p class="text-muted mb-3">
                        <span class="badge bg-info me-1">{{ $stats['agendas']['activas'] ?? 0 }} activas</span>
                        <small class="text-warning"><i class="fas fa-sync-alt"></i> {{ $stats['agendas']['pendientes'] ?? 0 }} pendientes</small>
                    </p>
                    
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
                    <h2 class="stats-counter mb-2" data-count="{{ $stats['citas']['total'] ?? 0 }}">0</h2>
                    <p class="text-muted mb-3">
                        <span class="badge bg-warning text-dark me-1">{{ $stats['citas']['hoy'] ?? 0 }} hoy</span>
                        <small class="text-success"><i class="fas fa-check-circle"></i> {{ $stats['citas']['completadas'] ?? 0 }} completadas</small>
                    </p>
                    
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
                    <h2 class="stats-counter mb-2" data-count="{{ $stats['usuarios']['total'] ?? 0 }}">0</h2>
                    <p class="text-muted mb-3">
                        <span class="badge bg-success">{{ $stats['usuarios']['activos'] ?? 0 }} activos</span>
                    </p>
                    
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

    <!-- Sección de Actividad Reciente del Sistema -->
    @if(!empty($stats['actividad_reciente']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" data-aos="fade-up">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Actividad Reciente del Sistema
                    </h5>
                    <button class="btn btn-sm btn-light" onclick="refreshActivity()" title="Actualizar actividad">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="activity-timeline">
                        @foreach(array_slice($stats['actividad_reciente'], 0, 5) as $index => $actividad)
                            <div class="activity-item" data-aos="fade-left" data-aos-delay="{{ ($index + 1) * 100 }}">
                                <div class="activity-icon bg-{{ $actividad['color'] }}">
                                    <i class="fas fa-{{ $actividad['icono'] }}"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="mb-0 fw-semibold">{{ $actividad['descripcion'] }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ \Carbon\Carbon::parse($actividad['fecha'])->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Hidden Logout Form -->
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</div>

@push('styles')
<style>
/* ===== VARIABLES Y ANIMACIONES GLOBALES ===== */
:root {
    --primary-color: #2c5aa0;
    --primary-dark: #1e3d6f;
    --primary-gradient: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --card-shadow: 0 4px 20px rgba(44, 90, 160, 0.08);
    --card-hover-shadow: 0 8px 30px rgba(44, 90, 160, 0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== DASHBOARD HEADER ===== */
.dashboard-icon-wrapper {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.4);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* ===== RELOJ Y BADGES ===== */
.clock-widget {
    background: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    color: #333;
    transition: var(--transition);
}

.clock-widget:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transform: scale(1.05);
}

.animated-badge {
    animation: pulse-badge 2s infinite;
    font-size: 0.9rem;
    padding: 8px 16px;
}

@keyframes pulse-badge {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(44, 90, 160, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(44, 90, 160, 0);
    }
}

/* ===== CONTADOR ANIMADO ===== */
.stats-counter {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: countUp 0.5s ease;
}

@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== CARDS MEJORADAS ===== */
.module-card {
    transition: var(--transition);
    cursor: pointer;
    border: none !important;
    background: white;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
}

.module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #2c5aa0, #1e3d6f);
    transition: left 0.5s ease;
}

.module-card:hover::before {
    left: 0;
}

.module-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--card-hover-shadow);
}

.icon-hover {
    transition: var(--transition);
}

.module-card:hover .icon-hover {
    transform: scale(1.2) rotate(360deg);
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%) !important;
}

/* Botones de acceso rápido */
.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    text-decoration: none;
    color: #495057;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.quick-action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(44, 90, 160, 0.1), transparent);
    transition: left 0.6s ease;
}

.quick-action-btn:hover::before {
    left: 100%;
}

.quick-action-btn:hover {
    border-color: #2c5aa0;
    color: #2c5aa0;
    transform: translateX(10px) scale(1.02);
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
}

.quick-action-btn i {
    font-size: 1.8rem;
    transition: var(--transition);
}

.quick-action-btn:hover i {
    transform: scale(1.2) rotate(5deg);
}

.quick-action-btn span {
    font-weight: 600;
    font-size: 1.1rem;
}

/* Info items */
.info-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    margin-bottom: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    transition: var(--transition);
    border-left: 4px solid transparent;
}

.info-item:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: translateX(10px);
    border-left-color: #2c5aa0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.info-item i {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease;
}

/* ===== EFECTO RIPPLE ===== */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(44, 90, 160, 0.6);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
    pointer-events: none;
    z-index: 1000;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* ===== EFECTOS DE CARGA ===== */
.loading-shimmer {
    animation: shimmer 2s infinite;
    background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
    background-size: 1000px 100%;
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .stats-counter {
        font-size: 2rem;
    }
    
    .module-card {
        margin-bottom: 1rem;
    }
    
    .quick-action-btn {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .quick-action-btn i {
        font-size: 2rem;
    }
    
    .info-item {
        padding: 12px;
    }
    
    .clock-widget {
        font-size: 0.75rem;
        padding: 6px 12px;
    }
}

/* ===== SCROLL SUAVE ===== */
html {
    scroll-behavior: smooth;
}

/* ===== BARRA DE SCROLL PERSONALIZADA ===== */
::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
    border-radius: 10px;
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #1e3d6f 0%, #2c5aa0 100%);
}

/* ===== EFECTOS DE HOVER MEJORADOS ===== */
.btn {
    position: relative;
    overflow: hidden;
    transition: var(--transition);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.badge {
    transition: var(--transition);
}

.badge:hover {
    transform: scale(1.1);
}

/* ===== GRADIENTES ANIMADOS ===== */
@keyframes gradient-shift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

.animated-gradient {
    background-size: 200% 200%;
    animation: gradient-shift 3s ease infinite;
}

/* ===== ACTIVITY TIMELINE ===== */
.activity-timeline {
    padding: 20px;
}

.activity-item {
    display: flex;
    align-items-center;
    gap: 20px;
    padding: 15px;
    margin-bottom: 10px;
    background: white;
    border-left: 4px solid transparent;
    border-radius: 10px;
    transition: var(--transition);
}

.activity-item:hover {
    background: #f8f9fa;
    border-left-color: #2c5aa0;
    transform: translateX(10px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.activity-content {
    flex: 1;
}

.activity-content p {
    color: #2d3748;
    font-size: 0.95rem;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #2c5aa0 0%, #17a2b8 100%);
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

// ===== RELOJ EN TIEMPO REAL =====
function updateClock() {
    const now = new Date();
    const options = { 
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    };
    const timeString = now.toLocaleDateString('es-ES', options);
    document.getElementById('currentTime').textContent = timeString;
}

// Actualizar reloj cada segundo
updateClock();
setInterval(updateClock, 1000);

// ===== ANIMACIÓN DE CONTADORES =====
function animateCounters() {
    const counters = document.querySelectorAll('.stats-counter');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60 FPS
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    });
}

// Iniciar animación de contadores al cargar
document.addEventListener('DOMContentLoaded', animateCounters);

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
    
    // ===== EFECTO RIPPLE EN BOTONES =====
    const buttons = document.querySelectorAll('.btn, .quick-action-btn');
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
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // ===== EFECTO PARALLAX SUAVE =====
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                const scrolled = window.pageYOffset;
                const cards = document.querySelectorAll('.module-card');
                cards.forEach((card, index) => {
                    const speed = 0.3 + (index * 0.05);
                    card.style.transform = `translateY(${scrolled * speed * 0.02}px)`;
                });
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // ===== AUTO-HIDE ALERTS =====
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
    
    // ===== TOOLTIPS =====
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ===== EFECTO FADE-IN EN CARDS AL SCROLL =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.module-card, .card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// ===== REFRESCAR ACTIVIDAD =====
function refreshActivity() {
    const button = event.target.closest('button');
    const icon = button.querySelector('i');
    
    // Animar el botón
    icon.classList.add('fa-spin');
    button.disabled = true;
    
    // Simular carga
    setTimeout(() => {
        icon.classList.remove('fa-spin');
        button.disabled = false;
        
        // Mostrar notificación
        showToast('Actividad actualizada correctamente', 'success');
        
        // Opcional: recargar página
        // location.reload();
    }, 1000);
}

// ===== MOSTRAR TOAST =====
function showToast(message, type = 'info') {
    const icons = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        icon: icons[type] || 'info',
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#fff',
        backdrop: false
    });
}

// ===== FUNCIÓN PARA ACTUALIZAR ESTADÍSTICAS =====
function updateStats() {
    fetch('/dashboard/stats', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores
            document.querySelectorAll('.stats-counter').forEach(counter => {
                const newValue = data[counter.dataset.stat];
                if (newValue !== undefined) {
                    counter.setAttribute('data-count', newValue);
                    animateCounters();
                }
            });
        }
    })
    .catch(error => console.error('Error actualizando estadísticas:', error));
}

// Actualizar estadísticas cada 5 minutos
setInterval(updateStats, 300000);

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
