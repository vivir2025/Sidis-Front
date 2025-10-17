{{-- resources/views/cronograma/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Mi Cronograma - ' . ($usuario['nombre_completo'] ?? 'Profesional'))

@push('styles')
<style>
/* ✅ ESTILOS ESPECÍFICOS DEL CRONOGRAMA */
:root {
    --primary-gradient: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    --warning-gradient: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    --danger-gradient: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    --border-radius: 0.5rem;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-header.bg-primary {
    background: var(--primary-gradient) !important;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.card-stat {
    transition: var(--transition);
    cursor: pointer;
    border: none;
    box-shadow: var(--shadow-sm);
}
/* ✅ ESTILOS PARA CAMBIOS PENDIENTES */
.cambios-pendientes {
    position: relative;
    border-left: 4px solid #17a2b8 !important;
}

.cambios-pendientes::before {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background: #17a2b8;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.cambios-pendientes-text {
    font-size: 0.75rem;
    font-weight: 500;
}

.card-stat:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.agenda-card {
    transition: var(--transition);
    border-radius: var(--border-radius);
    overflow: hidden;
    border: none;
    box-shadow: var(--shadow-sm);
}

.agenda-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.cita-card {
    transition: var(--transition);
    border-radius: var(--border-radius);
    height: 100%;
    border: none;
    box-shadow: var(--shadow-sm);
}

.cita-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* ✅ ESTILOS PARA CAMBIOS PENDIENTES */
.cita-card.cambios-pendientes {
    border-left: 4px solid #17a2b8 !important;
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
}

.badge.cambios-pendientes {
    position: relative;
    overflow: visible;
}

.badge.cambios-pendientes::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background: #ffc107;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse-pending 2s infinite;
}

@keyframes pulse-pending {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.text-info.cambios-pendientes {
    font-weight: 600;
    animation: fade-in-out 3s infinite;
}

@keyframes fade-in-out {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

.border-start.border-4 {
    border-left-width: 4px !important;
}

.badge-sm {
    font-size: 0.75em;
    padding: 0.35em 0.6em;
    border-radius: 0.375rem;
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    max-height: 2.8em;
}

#loading-overlay {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.connection-indicator {
    transition: var(--transition);
}

.connection-indicator.online {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.connection-indicator.offline {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

.badge {
    animation: pulse 2s infinite;
}

@media (max-width: 768px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- ✅ HEADER DEL CRONOGRAMA -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-2 mb-md-0">
                    <h1 class="h3 mb-1">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Mi Cronograma
                    </h1>
                    <p class="text-muted mb-1">
                        <strong>{{ $usuario['nombre_completo'] ?? 'Profesional' }}</strong>
                        @if(isset($usuario['especialidad']['nombre']))
                            - {{ $usuario['especialidad']['nombre'] }}
                        @endif
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Última actualización: <span id="ultima-actualizacion">{{ now()->format('H:i:s') }}</span>
                    </small>
                </div>
                
                <!-- ✅ CONTROLES -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Estado de Conexión -->
                    @if($isOffline)
                        <span class="badge bg-warning connection-indicator offline" id="badge-conexion">
                            <i class="fas fa-database"></i> Datos Locales
                        </span>
                    @else
                        <span class="badge bg-success connection-indicator online" id="badge-conexion">
                            <i class="fas fa-wifi"></i> Conectado
                        </span>
                    @endif
                    
                    <!-- Selector de Fecha -->
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">
                            <i class="fas fa-calendar"></i>
                        </span>
                        <input type="date" 
                               class="form-control" 
                               id="fecha-selector" 
                               value="{{ $fechaSeleccionada }}"
                               min="{{ now()->subDays(7)->format('Y-m-d') }}"
                               max="{{ now()->addDays(30)->format('Y-m-d') }}">
                    </div>
                    
                    <!-- Botón Actualizar -->
                    <button type="button" class="btn btn-outline-primary" id="btn-actualizar">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ESTADÍSTICAS GLOBALES -->
    <div class="row mb-4" id="estadisticas-globales">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                            <i class="fas fa-calendar-alt text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="total-agendas">{{ $cronogramaData['estadisticas']['total_agendas'] ?? 0 }}</h4>
                    <small class="text-muted">Agendas Activas</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-info bg-opacity-10 p-2">
                            <i class="fas fa-users text-info"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="total-citas">{{ $cronogramaData['estadisticas']['total_citas'] ?? 0 }}</h4>
                    <small class="text-muted">Total Citas</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-success bg-opacity-10 p-2">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="citas-atendida">{{ $cronogramaData['estadisticas']['por_estado']['ATENDIDA'] ?? 0 }}</h4>
                    <small class="text-muted">Atendidas</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-2">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="citas-programada">{{ $cronogramaData['estadisticas']['por_estado']['PROGRAMADA'] ?? 0 }}</h4>
                    <small class="text-muted">Programadas</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-secondary bg-opacity-10 p-2">
                            <i class="fas fa-chair text-secondary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="cupos-disponibles">{{ $cronogramaData['estadisticas']['cupos_disponibles'] ?? 0 }}</h4>
                    <small class="text-muted">Cupos Libres</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 card-stat">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-dark bg-opacity-10 p-2">
                            <i class="fas fa-percentage text-dark"></i>
                        </div>
                    </div>
                    <h4 class="mb-1" id="porcentaje-ocupacion">{{ $cronogramaData['estadisticas']['porcentaje_ocupacion_global'] ?? 0 }}%</h4>
                    <small class="text-muted">Ocupación</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ AGENDAS DEL DÍA CON CITAS -->
    <div class="row" id="cronograma-content">
        @if(empty($cronogramaData['agendas']))
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay agendas programadas</h4>
                        <p class="text-muted">No tienes agendas asignadas para el {{ \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}</p>
                        @if(!$isOffline)
                            <a href="{{ route('agendas.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Crear Nueva Agenda
                            </a>
                        @else
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Modo Offline:</strong> Para crear nuevas agendas necesitas conexión a internet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            @foreach($cronogramaData['agendas'] as $agenda)
                <div class="col-12 mb-4" data-agenda-uuid="{{ $agenda['uuid'] }}">
                    <div class="card border-0 shadow-sm agenda-card">
                        <!-- ✅ HEADER DE AGENDA -->
                        <div class="card-header bg-primary text-white">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <i class="fas fa-clock me-2"></i>
                                        {{ $agenda['hora_inicio'] ?? 'N/A' }} - {{ $agenda['hora_fin'] ?? 'N/A' }}
                                        <span class="badge bg-light text-dark ms-2">
                                            {{ $agenda['modalidad'] ?? 'Presencial' }}
                                        </span>
                                        @if(isset($agenda['source']) && $agenda['source'] === 'offline')
                                            <span class="badge bg-warning ms-1">
                                                <i class="fas fa-database me-1"></i>Local
                                            </span>
                                        @endif
                                    </h5>
                                    <div class="d-flex align-items-center gap-3">
                                        <small class="opacity-75">
                                            <i class="fas fa-door-open me-1"></i>
                                            {{ $agenda['consultorio'] ?? 'Consultorio' }}
                                        </small>
                                        <small class="opacity-75">
                                            <i class="fas fa-tag me-1"></i>
                                            {{ $agenda['etiqueta'] ?? 'Consulta General' }}
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-users me-1"></i>
                                            <span class="total-citas">{{ count($agenda['citas'] ?? []) }}</span>/<span class="total-cupos">{{ $agenda['total_cupos'] ?? 0 }}</span>
                                        </span>
                                        @php
                                            $totalCupos = $agenda['total_cupos'] ?? 0;
                                            $totalCitas = count($agenda['citas'] ?? []);
                                            $porcentaje = $totalCupos > 0 ? round(($totalCitas / $totalCupos) * 100, 1) : 0;
                                        @endphp
                                        <span class="badge bg-success">
                                            <i class="fas fa-chart-pie me-1"></i>
                                            <span class="porcentaje-ocupacion">{{ $porcentaje }}</span>%
                                        </span>
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item btn-ver-citas-agenda" 
                                                       data-agenda-uuid="{{ $agenda['uuid'] }}">
                                                        <i class="fas fa-list me-2"></i>Ver Todas las Citas
                                                    </a>
                                                </li>
                                                @if(!$isOffline)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('agendas.show', $agenda['uuid']) }}">
                                                            <i class="fas fa-eye me-2"></i>Ver Agenda
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ✅ BARRA DE PROGRESO -->
                            <div class="mt-2">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-light" 
                                         role="progressbar" 
                                         style="width: {{ $porcentaje }}%"
                                         aria-valuenow="{{ $porcentaje }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ✅ CUERPO CON CITAS -->
                        <div class="card-body">
                            @if(empty($agenda['citas']))
                                <div class="text-center py-4">
                                    <i class="fas fa-user-times fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-2">No hay citas programadas para esta agenda</p>
                                    @if(!$isOffline)
                                        <a href="{{ route('citas.create') }}?agenda={{ $agenda['uuid'] }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Agendar Cita
                                        </a>
                                    @else
                                        <div class="alert alert-info mt-2">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <small>Modo offline: Las nuevas citas se sincronizarán cuando tengas conexión</small>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!-- ✅ ESTADÍSTICAS DE LA AGENDA -->
                                <div class="row g-2 mb-3">
                                    <div class="col-auto">
                                        <span class="badge bg-primary">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $agenda['estadisticas']['PROGRAMADA'] ?? 0 }} Programadas
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $agenda['estadisticas']['EN_ATENCION'] ?? 0 }} En Atención
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>
                                            {{ $agenda['estadisticas']['ATENDIDA'] ?? 0 }} Atendidas
                                        </span>
                                    </div>
                                    @if(($agenda['estadisticas']['CANCELADA'] ?? 0) > 0 || ($agenda['estadisticas']['NO_ASISTIO'] ?? 0) > 0)
                                        <div class="col-auto">
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>
                                                {{ ($agenda['estadisticas']['CANCELADA'] ?? 0) + ($agenda['estadisticas']['NO_ASISTIO'] ?? 0) }} Canceladas/No asistió
                                            </span>
                                        </div>
                                    @endif
                                    <div class="col-auto ms-auto">
                                        @php
                                            $citasActivas = array_filter($agenda['citas'], function($cita) {
                                                return !in_array($cita['estado'] ?? '', ['CANCELADA', 'NO_ASISTIO']);
                                            });
                                            $cuposLibres = max(0, ($agenda['total_cupos'] ?? 0) - count($citasActivas));
                                        @endphp
                                        <span class="badge bg-info">
                                            <i class="fas fa-chair me-1"></i>
                                            <span class="cupos-disponibles">{{ $cuposLibres }}</span> Cupos Libres
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- ✅ GRID DE CITAS -->
                                <div class="row g-3">
                                    @foreach($agenda['citas'] as $cita)
                                        @php
                                            // ✅ MEJORAR LA BÚSQUEDA DE DATOS DEL PACIENTE
                                            $paciente = $cita['paciente'] ?? [];
                                            $estado = $cita['estado'] ?? 'PROGRAMADA';
                                            $estadoInfo = [
                                                'PROGRAMADA' => ['color' => 'primary', 'icon' => 'calendar', 'label' => 'Programada'],
                                                'EN_ATENCION' => ['color' => 'warning', 'icon' => 'clock', 'label' => 'En Atención'],
                                                'ATENDIDA' => ['color' => 'success', 'icon' => 'check', 'label' => 'Atendida'],
                                                'CANCELADA' => ['color' => 'danger', 'icon' => 'times', 'label' => 'Cancelada'],
                                                'NO_ASISTIO' => ['color' => 'secondary', 'icon' => 'user-times', 'label' => 'No Asistió']
                                            ][$estado] ?? ['color' => 'secondary', 'icon' => 'question', 'label' => $estado];
                                            
                                            // ✅ BUSCAR NOMBRE EN MÚLTIPLES UBICACIONES CON MEJOR LÓGICA
                                            $nombrePaciente = '';
                                            if (!empty($paciente['nombre_completo'])) {
                                                $nombrePaciente = $paciente['nombre_completo'];
                                            } elseif (!empty($paciente['primer_nombre']) || !empty($paciente['primer_apellido'])) {
                                                $nombrePaciente = trim(($paciente['primer_nombre'] ?? '') . ' ' . ($paciente['primer_apellido'] ?? ''));
                                            } elseif (!empty($cita['paciente_nombre'])) {
                                                $nombrePaciente = $cita['paciente_nombre'];
                                            } elseif (!empty($cita['nombre_paciente'])) {
                                                $nombrePaciente = $cita['nombre_paciente'];
                                            } else {
                                                $nombrePaciente = 'Paciente no identificado';
                                            }
                                            
                                            // ✅ BUSCAR DOCUMENTO
                                            $documentoPaciente = $paciente['documento'] ?? 
                                                                $paciente['cedula'] ?? 
                                                                $cita['paciente_documento'] ?? 
                                                                $cita['documento_paciente'] ?? 
                                                                'Sin documento';
                                                                
                                            // ✅ BUSCAR TELÉFONO
                                            $telefonoPaciente = $paciente['telefono'] ?? 
                                                               $paciente['celular'] ?? 
                                                               $cita['paciente_telefono'] ?? 
                                                               $cita['telefono_paciente'] ?? 
                                                               null;
                                            
                                            // ✅ VERIFICAR SI HAY CAMBIOS PENDIENTES OFFLINE
                                            $tieneCambiosPendientes = false;
                                            if (isset($cita['offline_modificado']) && $cita['offline_modificado']) {
                                                $tieneCambiosPendientes = true;
                                            }
                                                           
                                            // ✅ DEBUG MEJORADO
                                            if ($loop->first) {
                                                \Log::info('🔍 DEBUG PACIENTE CRONOGRAMA MEJORADO:', [
                                                    'cita_uuid' => $cita['uuid'],
                                                    'paciente_raw' => $paciente,
                                                    'nombre_encontrado' => $nombrePaciente,
                                                    'documento_encontrado' => $documentoPaciente,
                                                    'telefono_encontrado' => $telefonoPaciente,
                                                    'tiene_cambios_pendientes' => $tieneCambiosPendientes,
                                                    'estado_actual' => $cita['estado'] ?? 'N/A'
                                                ]);
                                            }
                                        @endphp
                                        
                                        <div class="col-md-6 col-lg-4" data-cita-uuid="{{ $cita['uuid'] }}">
                                            <div class="card border-start border-4 border-{{ $estadoInfo['color'] }} h-100 cita-card {{ $tieneCambiosPendientes ? 'cambios-pendientes' : '' }}">
                                                <div class="card-body">
                                                    <!-- ✅ HEADER DE CITA MEJORADO -->
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0 text-truncate" style="max-width: 70%;">
                                                            <i class="fas fa-user me-1"></i>
                                                            {{ $nombrePaciente }}
                                                            @if($tieneCambiosPendientes)
                                                                <i class="fas fa-sync-alt text-info ms-1" title="Cambios pendientes de sincronización"></i>
                                                            @endif
                                                        </h6>
                                                        <span class="badge bg-{{ $estadoInfo['color'] }} badge-sm {{ $tieneCambiosPendientes ? 'cambios-pendientes' : '' }}">
                                                            <i class="fas fa-{{ $estadoInfo['icon'] }} me-1"></i>
                                                            {{ $estadoInfo['label'] }}
                                                            @if($tieneCambiosPendientes)
                                                                <i class="fas fa-clock ms-1" title="Pendiente de sincronizar"></i>
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <!-- ✅ INFORMACIÓN DE LA CITA MEJORADA -->
                                                    <div class="text-muted small mb-2">
                                                        <div class="mb-1">
                                                            <i class="fas fa-id-card me-1"></i> 
                                                            {{ $documentoPaciente }}
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock me-1"></i> 
                                                            {{ isset($cita['fecha_inicio']) ? \Carbon\Carbon::parse($cita['fecha_inicio'])->format('H:i') : ($cita['hora'] ?? 'N/A') }} - 
                                                            {{ isset($cita['fecha_final']) ? \Carbon\Carbon::parse($cita['fecha_final'])->format('H:i') : 'N/A' }}
                                                        </div>
                                                        @if($telefonoPaciente)
                                                            <div class="mb-1">
                                                                <i class="fas fa-phone me-1"></i>
                                                                                                                                {{ $telefonoPaciente }}
                                                            </div>
                                                        @endif
                                                        
                                                        <!-- ✅ INDICADORES DE ESTADO -->
                                                        @if(isset($cita['source']) && $cita['source'] === 'offline')
                                                            <div class="mb-1">
                                                                <i class="fas fa-database me-1"></i>
                                                                <span class="text-warning">Datos locales</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($tieneCambiosPendientes)
                                                            <div class="mb-1">
                                                                <i class="fas fa-sync-alt me-1"></i>
                                                                <span class="text-info cambios-pendientes">Cambios pendientes</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    
                                                    <!-- ✅ MOTIVO DE LA CITA -->
                                                    @if(!empty($cita['motivo']))
                                                        <p class="card-text small text-muted mb-2 text-truncate-2">
                                                            <i class="fas fa-notes-medical me-1"></i>
                                                            {{ $cita['motivo'] }}
                                                        </p>
                                                    @endif
                                                    
                                                <!-- ✅ BOTONES DE ACCIÓN MEJORADOS CON HISTORIA CLÍNICA -->
<div class="d-flex gap-1 mt-auto position-relative">
    <button type="button" 
            class="btn btn-outline-primary btn-sm flex-fill btn-detalle-cita"
            data-cita-uuid="{{ $cita['uuid'] }}">
        <i class="fas fa-eye"></i> Ver
    </button>
    
    <!-- ✅ BOTONES QUE FUNCIONAN TANTO ONLINE COMO OFFLINE -->
    @switch($estado)
        @case('PROGRAMADA')
            <button type="button" 
                    class="btn btn-success btn-sm btn-estado-cita"
                    data-cita-uuid="{{ $cita['uuid'] }}"
                    data-estado="EN_ATENCION"
                    title="Iniciar atención">
                <i class="fas fa-play"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                        data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item btn-estado-cita" 
                           data-cita-uuid="{{ $cita['uuid'] }}" 
                           data-estado="CANCELADA">
                            <i class="fas fa-times text-danger me-2"></i>Cancelar
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item btn-estado-cita" 
                           data-cita-uuid="{{ $cita['uuid'] }}" 
                           data-estado="NO_ASISTIO">
                            <i class="fas fa-user-times text-secondary me-2"></i>No Asistió
                        </a>
                    </li>
                </ul>
            </div>
            @break
            
        @case('EN_ATENCION')
            <!-- ✅ AGREGADO: Botón de Historia Clínica para EN_ATENCION -->
            <button type="button" 
                    class="btn btn-info btn-sm btn-historia-clinica"
                    data-cita-uuid="{{ $cita['uuid'] }}"
                    title="Crear Historia Clínica">
                <i class="fas fa-file-medical"></i> HC
            </button>
            <button type="button" 
                    class="btn btn-success btn-sm btn-estado-cita"
                    data-cita-uuid="{{ $cita['uuid'] }}"
                    data-estado="ATENDIDA"
                    title="Marcar como atendida">
                <i class="fas fa-check"></i>
            </button>
            @break
            
        @case('ATENDIDA')
            <!-- ✅ AGREGADO: Botón de Historia Clínica para ATENDIDA -->
            <button type="button" 
                    class="btn btn-success btn-sm btn-historia-clinica"
                    data-cita-uuid="{{ $cita['uuid'] }}"
                    title="Ver Historia Clínica">
                <i class="fas fa-file-medical"></i> HC
            </button>
            <span class="badge bg-success flex-fill text-center py-2 ms-1">
                <i class="fas fa-check-circle"></i> Completada
            </span>
            @break
            
        @default
            <button type="button" 
                    class="btn btn-outline-primary btn-sm btn-estado-cita"
                    data-cita-uuid="{{ $cita['uuid'] }}"
                    data-estado="PROGRAMADA"
                    title="Reprogramar">
                <i class="fas fa-redo"></i>
            </button>
    @endswitch
    
    <!-- ✅ INDICADOR DE MODO OFFLINE (OPCIONAL) -->
    @if($isOffline)
        <div class="position-absolute top-0 end-0 mt-1 me-1">
            <span class="badge bg-warning badge-sm" title="Modo offline - Los cambios se sincronizarán">
                <i class="fas fa-database"></i>
            </span>
        </div>
    @endif
</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- ✅ LOADING OVERLAY -->
    <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" 
         style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center text-white">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h5>Actualizando cronograma...</h5>
                <p class="mb-0">Por favor espere un momento</p>
            </div>
        </div>
    </div>
</div>

<!-- ✅ MODAL DETALLE DE CITA -->
<div class="modal fade" id="modal-detalle-cita" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-md me-2"></i>
                    Detalle de Cita
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-detalle-cita-body">
                <!-- Se carga dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
                <div class="btn-group" id="botones-estado-modal">
                    <!-- Se cargan dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ MODAL CITAS DE AGENDA -->
<div class="modal fade" id="modal-citas-agenda" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list me-2"></i>
                    Todas las Citas de la Agenda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabla-citas-agenda">
                        <thead class="table-dark">
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ✅ INTERCEPTAR TODAS LAS LLAMADAS FETCH PARA DEBUG
const originalFetch = window.fetch;
window.fetch = function(...args) {
    console.log('🌐 FETCH INTERCEPTADO:', args[0], args[1]);
    if (args[0].includes('/estado')) {
        console.log('🚨 LLAMADA A ESTADO DETECTADA:', {
            url: args[0],
            options: args[1]
        });
    }
    return originalFetch.apply(this, args);
};

// ✅ VARIABLES GLOBALES
let fechaActual = '{{ $fechaSeleccionada }}';
let cronogramaData = @json($cronogramaData ?? []);
let isOffline = {{ $isOffline ? 'true' : 'false' }};

console.log('🏥 Cronograma iniciado', {
    fecha: fechaActual,
    agendas: cronogramaData.agendas?.length || 0,
    offline: isOffline
});

// ✅ INICIALIZACIÓN INTELIGENTE
document.addEventListener('DOMContentLoaded', function() {
    initEventListeners();

    if (!isOffline) {
        setInterval(actualizarCronogramaAuto, 5 * 60 * 1000);
        
        // ✅ SINCRONIZACIÓN INTELIGENTE: Solo si hay cambios pendientes
        setTimeout(() => {
            verificarYSincronizarCambiosPendientes();
        }, 3000); // Esperar 3 segundos para que cargue todo
    }

    initDetectorConectividad();
});

// ✅ EVENT LISTENERS CORREGIDOS
function initEventListeners() {
    // Selector de fecha
    const fechaSelector = document.getElementById('fecha-selector');
    if (fechaSelector) {
        fechaSelector.addEventListener('change', function() {
            fechaActual = this.value;
            cambiarFecha(this.value);
        });
    }
    
    // Botón actualizar
    const btnActualizar = document.getElementById('btn-actualizar');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', function() {
            if (isOffline) {
                mostrarAlerta('warning', 'Modo offline: No se puede actualizar desde el servidor');
                return;
            }
            actualizarCronograma();
        });
    }
    
    // ✅ EVENT DELEGATION CORREGIDO CON DEBUG COMPLETO
    document.addEventListener('click', function(e) {
        // ✅ BOTÓN DE HISTORIA CLÍNICA
       if (e.target.classList.contains('btn-historia-clinica') || e.target.closest('.btn-historia-clinica')) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = e.target.classList.contains('btn-historia-clinica') ? e.target : e.target.closest('.btn-historia-clinica');
    const citaUuid = btn.dataset.citaUuid;
    
    console.log('📋 Abriendo Historia Clínica para cita:', citaUuid);
    
    if (!citaUuid) {
        mostrarAlerta('error', 'Error: ID de cita no válido para Historia Clínica');
        return;
    }
    
    // ✅ CAMBIAR ESTA LÍNEA:
    const url = `/historia-clinica/determinar-vista/${citaUuid}`; // ← NUEVA RUTA
    console.log('🔗 Redirigiendo a:', url);
    window.location.href = url;
    return;
}
        
        // Botones de estado de citas
        if (e.target.classList.contains('btn-estado-cita') || e.target.closest('.btn-estado-cita')) {
            e.preventDefault(); // ✅ PREVENIR COMPORTAMIENTO DEFAULT
            e.stopPropagation(); // ✅ EVITAR PROPAGACIÓN
            
            const btn = e.target.classList.contains('btn-estado-cita') ? e.target : e.target.closest('.btn-estado-cita');
            const citaUuid = btn.dataset.citaUuid;
            const nuevoEstado = btn.dataset.estado;
            
            // ✅ DEBUG CRÍTICO COMPLETO
            console.log('🔍 DEBUG COMPLETO: Botón clickeado', {
                citaUuid: citaUuid,
                nuevoEstado: nuevoEstado,
                citaUuidType: typeof citaUuid,
                citaUuidLength: citaUuid ? citaUuid.length : 0,
                btnElement: btn,
                btnDataset: btn.dataset,
                btnOuterHTML: btn.outerHTML.substring(0, 200) + '...',
                targetElement: e.target,
                targetOuterHTML: e.target.outerHTML.substring(0, 200) + '...'
            });
            
            // ✅ VALIDACIÓN ESTRICTA
            if (!citaUuid || citaUuid.trim() === '') {
                console.error('❌ ERROR: UUID vacío o inválido', {
                    citaUuid: citaUuid,
                    btn: btn,
                    dataset: btn.dataset
                });
                mostrarAlerta('error', 'Error: ID de cita no válido');
                return;
            }
            
            if (!nuevoEstado || nuevoEstado.trim() === '') {
                console.error('❌ ERROR: Estado vacío o inválido', {
                    nuevoEstado: nuevoEstado,
                    btn: btn
                });
                mostrarAlerta('error', 'Error: Estado no válido');
                return;
            }
            
            // ✅ LLAMAR A LA FUNCIÓN
            cambiarEstadoCita(citaUuid.trim(), nuevoEstado.trim());
            return; // ✅ SALIR AQUÍ
        }
        
        // Botones de detalle de cita
        if (e.target.classList.contains('btn-detalle-cita') || e.target.closest('.btn-detalle-cita')) {
            const btn = e.target.classList.contains('btn-detalle-cita') ? e.target : e.target.closest('.btn-detalle-cita');
            const citaUuid = btn.dataset.citaUuid;
            if (citaUuid) {
                verDetalleCita(citaUuid);
            }
        }
        
        // Botones de ver citas de agenda
        if (e.target.classList.contains('btn-ver-citas-agenda') || e.target.closest('.btn-ver-citas-agenda')) {
            const btn = e.target.classList.contains('btn-ver-citas-agenda') ? e.target : e.target.closest('.btn-ver-citas-agenda');
            const agendaUuid = btn.dataset.agendaUuid;
            if (agendaUuid) {
                verCitasAgenda(agendaUuid);
            }
        }
    });
}

// ✅ DEBUG: Verificar event listeners
console.log('🔍 DEBUG: Event listeners registrados:', {
    totalListeners: document.querySelectorAll('*').length,
    botonesEstado: document.querySelectorAll('.btn-estado-cita').length,
    botonesHistoria: document.querySelectorAll('.btn-historia-clinica').length
});

// ✅ Verificar si hay múltiples event listeners
document.querySelectorAll('.btn-estado-cita').forEach((btn, index) => {
    console.log(`🔍 Botón ${index}:`, {
        uuid: btn.dataset.citaUuid,
        estado: btn.dataset.estado,
        element: btn.outerHTML.substring(0, 100) + '...'
    });
});

// ✅ CAMBIAR FECHA
function cambiarFecha(fecha) {
    if (fecha === fechaActual) return;
    
    console.log('🔄 Cambiando a fecha:', fecha);
    mostrarLoading(true);
    
    window.location.href = `/cronograma?fecha=${fecha}`;
}

// ✅ ACTUALIZAR CRONOGRAMA
function actualizarCronograma() {
    if (isOffline) {
        mostrarAlerta('warning', 'Modo offline: No se puede actualizar desde el servidor');
        return;
    }
    
    console.log('🔄 Actualizando cronograma para fecha:', fechaActual);
    
    if (document.getElementById('btn-actualizar').disabled) {
        return;
    }
    
    mostrarLoading(true);
    setButtonLoading('btn-actualizar', true);
    
    fetch(`/cronograma/data/${fechaActual}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📥 Respuesta del servidor:', data);
        
        if (data.success) {
            cronogramaData = data.data;
            isOffline = data.offline || false;
            
            actualizarContenidoCronograma(data.data);
            mostrarAlerta('success', 'Cronograma actualizado correctamente');
            actualizarTimestamp();
            actualizarBadgeConexion(!isOffline);
        } else {
            throw new Error(data.error || 'Error cargando cronograma');
        }
    })
    .catch(error => {
        console.error('❌ Error actualizando cronograma:', error);
        mostrarAlerta('error', 'Error de conexión. Trabajando con datos locales.');
        manejarErrorConexion();
    })
    .finally(() => {
        mostrarLoading(false);
        setButtonLoading('btn-actualizar', false);
    });
}

// ✅ ACTUALIZACIÓN AUTOMÁTICA
function actualizarCronogramaAuto() {
    if (isOffline) {
        return;
    }
    
    console.log('🔄 Actualización automática iniciada');
    
    fetch(`/cronograma/refresh?fecha=${fechaActual}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Actualización automática exitosa');
            cronogramaData = data.data;
            isOffline = data.offline || false;
            actualizarContenidoCronograma(data.data);
            actualizarTimestamp();
            actualizarBadgeConexion(!isOffline);
        }
    })
    .catch(error => {
        console.warn('⚠️ Error en actualización automática:', error);
        manejarErrorConexion();
    });
}

// ✅ ACTUALIZAR CONTENIDO
function actualizarContenidoCronograma(data) {
    try {
        console.log('🔄 Actualizando contenido del cronograma');
        
        actualizarEstadisticasGlobales(data.estadisticas);
        actualizarAgendasExistentes(data.agendas);
        
        console.log('✅ Contenido actualizado correctamente');
        
    } catch (error) {
        console.error('❌ Error actualizando contenido:', error);
        mostrarAlerta('error', 'Error actualizando la interfaz');
    }
}

// ✅ ACTUALIZAR ESTADÍSTICAS
function actualizarEstadisticasGlobales(estadisticas) {
    const elementos = {
        'total-agendas': estadisticas.total_agendas || 0,
        'total-citas': estadisticas.total_citas || 0,
        'citas-atendida': estadisticas.por_estado?.ATENDIDA || 0,
        'citas-programada': estadisticas.por_estado?.PROGRAMADA || 0,
        'cupos-disponibles': estadisticas.cupos_disponibles || 0,
        'porcentaje-ocupacion': (estadisticas.porcentaje_ocupacion_global || 0) + '%'
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            animarCambioNumero(elemento, valor);
        }
    });
}

// ✅ ACTUALIZAR AGENDAS
function actualizarAgendasExistentes(agendas) {
    agendas.forEach(agenda => {
        const agendaCard = document.querySelector(`[data-agenda-uuid="${agenda.uuid}"]`);
        if (agendaCard) {
            actualizarTarjetaAgenda(agendaCard, agenda);
        }
    });
}

// ✅ ACTUALIZAR TARJETA DE AGENDA
function actualizarTarjetaAgenda(tarjeta, agenda) {
    try {
        const totalCitas = tarjeta.querySelector('.total-citas');
        if (totalCitas) totalCitas.textContent = agenda.citas?.length || 0;
        
        const totalCupos = tarjeta.querySelector('.total-cupos');
        if (totalCupos) totalCupos.textContent = agenda.total_cupos || 0;
        
        const cuposDisponibles = tarjeta.querySelector('.cupos-disponibles');
        if (cuposDisponibles) {
            const citasActivas = agenda.citas?.filter(c => !['CANCELADA', 'NO_ASISTIO'].includes(c.estado)) || [];
            const libres = Math.max(0, (agenda.total_cupos || 0) - citasActivas.length);
            cuposDisponibles.textContent = libres;
        }
        
        const porcentajeOcupacion = tarjeta.querySelector('.porcentaje-ocupacion');
        if (porcentajeOcupacion) {
            const totalCupos = agenda.total_cupos || 0;
            const totalCitas = agenda.citas?.length || 0;
            const porcentaje = totalCupos > 0 ? Math.round((totalCitas / totalCupos) * 100) : 0;
            porcentajeOcupacion.textContent = porcentaje;
        }
        
        const barraProgreso = tarjeta.querySelector('.progress-bar');
        if (barraProgreso && porcentajeOcupacion) {
            const porcentaje = parseInt(porcentajeOcupacion.textContent) || 0;
            barraProgreso.style.width = porcentaje + '%';
            barraProgreso.setAttribute('aria-valuenow', porcentaje);
        }
        
    } catch (error) {
        console.error('❌ Error actualizando tarjeta de agenda:', error);
    }
}

// ✅ VER DETALLE DE CITA
function verDetalleCita(citaUuid) {
    console.log('👁️ Viendo detalle de cita:', citaUuid);
    
    mostrarLoading(true);
    
    fetch(`/cronograma/cita/${citaUuid}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mostrarModalDetalleCita(data.data);
        } else {
            throw new Error(data.error || 'Error obteniendo detalle de cita');
        }
    })
    .catch(error => {
        console.error('❌ Error cargando detalle:', error);
        mostrarAlerta('error', 'Error cargando detalle de cita');
    })
    .finally(() => {
        mostrarLoading(false);
    });
}

// ✅ MOSTRAR MODAL DETALLE CON BOTÓN DE HISTORIA CLÍNICA
function mostrarModalDetalleCita(cita) {
    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-cita'));
    const modalBody = document.getElementById('modal-detalle-cita-body');
    const botonesModal = document.getElementById('botones-estado-modal');
    
    const paciente = cita.paciente || {};
    const agenda = cita.agenda || {};
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-user me-2"></i>Información del Paciente
                </h6>
                <div class="mb-2">
                    <strong>Nombre:</strong> ${paciente.nombre_completo || 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Documento:</strong> ${paciente.documento || 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Teléfono:</strong> ${paciente.telefono || 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Fecha Nacimiento:</strong> ${paciente.fecha_nacimiento || 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Sexo:</strong> ${paciente.sexo || 'N/A'}
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-calendar me-2"></i>Información de la Cita
                </h6>
                <div class="mb-2">
                    <strong>Fecha:</strong> ${cita.fecha_inicio ? new Date(cita.fecha_inicio).toLocaleDateString() : 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Hora:</strong> 
                    ${cita.fecha_inicio ? new Date(cita.fecha_inicio).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : 'N/A'} - 
                    ${cita.fecha_final ? new Date(cita.fecha_final).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Estado:</strong> 
                    <span class="badge bg-${getEstadoColor(cita.estado)}">${cita.estado || 'N/A'}</span>
                </div>
                <div class="mb-2">
                    <strong>Consultorio:</strong> ${agenda.consultorio || 'N/A'}
                </div>
                <div class="mb-2">
                    <strong>Modalidad:</strong> ${agenda.modalidad || 'N/A'}
                </div>
            </div>
        </div>
        
        ${cita.motivo ? `
            <div class="mt-3">
                <h6 class="text-primary mb-2">
                    <i class="fas fa-notes-medical me-2"></i>Motivo de Consulta
                </h6>
                <p class="mb-0">${cita.motivo}</p>
            </div>
        ` : ''}
        
        ${cita.nota ? `
            <div class="mt-3">
                <h6 class="text-primary mb-2">
                    <i class="fas fa-comment me-2"></i>Notas
                </h6>
                <p class="mb-0">${cita.nota}</p>
            </div>
        ` : ''}
        
        ${cita.offline ? `
            <div class="alert alert-warning mt-3">
                <i class="fas fa-database me-2"></i>
                Esta información proviene del almacenamiento local
            </div>
        ` : ''}
    `;
    
    // ✅ CONSTRUIR BOTONES DE ESTADO + HISTORIA CLÍNICA
    let botonesHtml = '';
    
    // Botón de Historia Clínica si la cita está en atención o atendida
    if (cita.estado === 'EN_ATENCION' || cita.estado === 'ATENDIDA') {
        botonesHtml += `
            <button type="button" class="btn btn-success btn-historia-clinica me-2" 
                    data-cita-uuid="${cita.uuid}" data-bs-dismiss="modal">
                <i class="fas fa-file-medical me-1"></i>Historia Clínica
            </button>
        `;
    }
    
    // Botones de estado si no está offline
    if (!isOffline && cita.estado) {
        botonesHtml += generarBotonesEstado(cita.uuid, cita.estado);
    }
    
    botonesModal.innerHTML = botonesHtml;
    
    modal.show();
}

// ✅ VER CITAS DE AGENDA CON BOTÓN DE HISTORIA CLÍNICA
function verCitasAgenda(agendaUuid) {
    console.log('📋 Viendo citas de agenda:', agendaUuid);
    
    const agenda = cronogramaData.agendas?.find(a => a.uuid === agendaUuid);
    if (!agenda) {
        mostrarAlerta('error', 'Agenda no encontrada');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modal-citas-agenda'));
    const tabla = document.getElementById('tabla-citas-agenda').getElementsByTagName('tbody')[0];
    
    tabla.innerHTML = '';
    
    document.querySelector('#modal-citas-agenda .modal-title').innerHTML = `
        <i class="fas fa-list me-2"></i>
        Citas - ${agenda.etiqueta || 'Agenda'} (${agenda.hora_inicio} - ${agenda.hora_fin})
    `;
    
    if (agenda.citas && agenda.citas.length > 0) {
        agenda.citas.forEach(cita => {
            const paciente = cita.paciente || {};
            const fila = tabla.insertRow();
            
            // ✅ GENERAR BOTONES INCLUYENDO HISTORIA CLÍNICA
            let botonesAccion = `
                <button class="btn btn-sm btn-outline-primary btn-detalle-cita" 
                        data-cita-uuid="${cita.uuid}" data-bs-dismiss="modal">
                    <i class="fas fa-eye"></i> Ver
                </button>
            `;
            
            // Botón de Historia Clínica
            if (cita.estado === 'EN_ATENCION' || cita.estado === 'ATENDIDA') {
                botonesAccion += `
                    <button class="btn btn-sm btn-success btn-historia-clinica ms-1" 
                            data-cita-uuid="${cita.uuid}" data-bs-dismiss="modal"
                            title="Crear Historia Clínica">
                        <i class="fas fa-file-medical"></i>
                    </button>
                `;
            }
            
            // Botón de iniciar atención
            if (!isOffline && cita.estado === 'PROGRAMADA') {
                botonesAccion += `
                    <button class="btn btn-sm btn-warning btn-estado-cita ms-1" 
                            data-cita-uuid="${cita.uuid}" 
                            data-estado="EN_ATENCION" 
                            data-bs-dismiss="modal">
                        <i class="fas fa-play"></i>
                    </button>
                `;
            }
            
            fila.innerHTML = `
                <td>${cita.fecha_inicio ? new Date(cita.fecha_inicio).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : 'N/A'}</td>
                <td>${paciente.nombre_completo || 'N/A'}</td>
                <td>${paciente.documento || 'N/A'}</td>
                <td>${paciente.telefono || 'N/A'}</td>
                <td>
                    <span class="badge bg-${getEstadoColor(cita.estado)}">${cita.estado || 'N/A'}</span>
                </td>
                <td>${botonesAccion}</td>
            `;
        });
    } else {
        tabla.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No hay citas programadas para esta agenda
                </td>
            </tr>
        `;
    }
    
    modal.show();
}

// ✅ FUNCIÓN CRÍTICA CORREGIDA PARA CAMBIAR ESTADO
function cambiarEstadoCita(citaUuid, nuevoEstado) {
    // ✅ VALIDACIÓN CRÍTICA DEL UUID
    if (!citaUuid || typeof citaUuid !== 'string' || citaUuid.trim() === '') {
        console.error('❌ ERROR CRÍTICO: UUID de cita inválido', {
            citaUuid: citaUuid,
            type: typeof citaUuid,
            length: citaUuid ? citaUuid.length : 0
        });
        mostrarAlerta('error', 'Error: ID de cita no válido');
        return;
    }
    
    // ✅ LIMPIAR UUID
    citaUuid = citaUuid.trim();
    
    // ✅ VALIDAR QUE EL UUID TENGA FORMATO CORRECTO
    const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
    if (!uuidRegex.test(citaUuid)) {
        console.error('❌ ERROR: UUID con formato inválido', {
            citaUuid: citaUuid,
            length: citaUuid.length
        });
        mostrarAlerta('error', 'Error: Formato de ID de cita inválido');
        return;
    }
    
    console.log('🔄 Cambiando estado de cita:', {
        citaUuid: citaUuid,
        nuevoEstado: nuevoEstado,
        uuidLength: citaUuid.length
    });
    
    mostrarLoading(true);
    
    // ✅ MODO OFFLINE
    if (isOffline || !navigator.onLine) {
        console.log('📱 Modo offline: Guardando cambio localmente');
        guardarCambioEstadoOffline(citaUuid, nuevoEstado);
        actualizarCitaEnInterfaz(citaUuid, nuevoEstado, {});
        mostrarAlerta('warning', `Estado cambiado a ${nuevoEstado.toLowerCase()} (se sincronizará cuando tengas conexión)`);
        mostrarLoading(false);
        return;
    }
    
    // ✅ CONSTRUIR URL CORRECTAMENTE
    const url = `/cronograma/cita/${citaUuid}/cambiar-estado`;
    console.log('🌐 URL construida:', url);
    
    // ✅ HACER PETICIÓN
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            estado: nuevoEstado
        })
    })
    .then(response => {
        console.log('🔍 DEBUG: Status de respuesta:', response.status);
        console.log('🔍 DEBUG: URL final:', response.url);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.log('🔍 DEBUG: Texto de error:', text);
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ DEBUG: Respuesta exitosa:', data);
        
        if (data.success) {
            console.log('✅ Estado cambiado correctamente');
            mostrarAlerta('success', `Cita marcada como ${nuevoEstado.toLowerCase()}`);
            
            actualizarCitaEnInterfaz(citaUuid, nuevoEstado, data);
            
            // Cerrar modales
            const modales = document.querySelectorAll('.modal.show');
            modales.forEach(modal => {
                bootstrap.Modal.getInstance(modal)?.hide();
            });
            
        } else {
            throw new Error(data.error || 'Error cambiando estado');
        }
    })
    .catch(error => {
        console.error('❌ Error cambiando estado:', error);
        
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            console.log('🔄 Error de red, guardando offline como fallback');
            guardarCambioEstadoOffline(citaUuid, nuevoEstado);
            actualizarCitaEnInterfaz(citaUuid, nuevoEstado, {});
            mostrarAlerta('warning', `Estado cambiado offline (se sincronizará cuando tengas conexión)`);
        } else {
            mostrarAlerta('error', 'Error cambiando estado de la cita: ' + error.message);
        }
    })
    .finally(() => {
        mostrarLoading(false);
            });
}

// ✅ GUARDAR CAMBIO OFFLINE MEJORADO
function guardarCambioEstadoOffline(citaUuid, nuevoEstado) {
    try {
        const cambiosExistentes = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
        
        // ✅ REMOVER CAMBIOS ANTERIORES DE LA MISMA CITA (evitar duplicados)
        const cambiosFiltrados = cambiosExistentes.filter(c => c.cita_uuid !== citaUuid);
        
        const nuevoCambio = {
            cita_uuid: citaUuid,
            nuevo_estado: nuevoEstado,
            timestamp: new Date().toISOString(),
            sincronizado: false,
            intentos_sincronizacion: 0
        };
        
        cambiosFiltrados.push(nuevoCambio);
        localStorage.setItem('cambios_estados_pendientes', JSON.stringify(cambiosFiltrados));
        
        console.log(`💾 Cambio offline guardado: ${citaUuid} -> ${nuevoEstado}`);
        
        // ✅ ACTUALIZAR INTERFAZ INMEDIATAMENTE
        actualizarCitaEnInterfaz(citaUuid, nuevoEstado, null);
        
        // ✅ APLICAR INDICADORES VISUALES
        aplicarIndicadoresCambiosPendientes([nuevoCambio]);
        
        return true;
        
    } catch (error) {
        console.error('❌ Error guardando cambio offline:', error);
        return false;
    }
}

// ✅ NUEVA FUNCIÓN: SINCRONIZAR CAMBIOS PENDIENTES
function sincronizarCambiosPendientes() {
    try {
        const cambiosPendientes = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
        const cambiosNoSincronizados = cambiosPendientes.filter(c => !c.sincronizado);
        
        if (cambiosNoSincronizados.length === 0) {
            console.log('✅ No hay cambios pendientes para sincronizar');
            return;
        }
        
        console.log('🔄 Sincronizando cambios pendientes:', cambiosNoSincronizados.length);
        
        cambiosNoSincronizados.forEach(cambio => {
            // ✅ USAR LA MISMA RUTA QUE FUNCIONA
            fetch(`/cronograma/cita/${cambio.cita_uuid}/cambiar-estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    estado: cambio.nuevo_estado
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ✅ MARCAR COMO SINCRONIZADO
                    cambio.sincronizado = true;
                    cambio.fecha_sincronizacion = new Date().toISOString();
                    
                    console.log('✅ Cambio sincronizado:', cambio.cita_uuid);
                }
            })
            .catch(error => {
                console.error('❌ Error sincronizando cambio:', error);
            });
        });
        
        // ✅ ACTUALIZAR LOCALSTORAGE
        localStorage.setItem('cambios_estados_pendientes', JSON.stringify(cambiosPendientes));
        
        // ✅ MOSTRAR NOTIFICACIÓN
        mostrarAlerta('success', `${cambiosNoSincronizados.length} cambios sincronizados con el servidor`);
        
    } catch (error) {
        console.error('❌ Error en sincronización:', error);
    }
}

function actualizarCitaEnInterfaz(citaUuid, nuevoEstado, datosActualizados) {
    const citaCard = document.querySelector(`[data-cita-uuid="${citaUuid}"]`);
    if (!citaCard) {
        console.log('⚠️ Tarjeta de cita no encontrada en interfaz:', citaUuid);
        return;
    }
    
    console.log('🔄 Actualizando interfaz de cita:', {
        citaUuid: citaUuid,
        nuevoEstado: nuevoEstado
    });
    
    // Actualizar badge de estado
    const badge = citaCard.querySelector('.badge');
    if (badge) {
        const estadoInfo = getEstadoInfo(nuevoEstado);
        badge.className = `badge bg-${estadoInfo.color} badge-sm`;
        badge.innerHTML = `<i class="fas fa-${estadoInfo.icon} me-1"></i>${estadoInfo.label}`;
    }
    
    // Actualizar borde de la tarjeta
    const card = citaCard.querySelector('.card');
    if (card) {
        const estadoInfo = getEstadoInfo(nuevoEstado);
        card.className = card.className.replace(/border-\w+/, `border-${estadoInfo.color}`);
    }
    
    // ✅ ACTUALIZAR BOTONES DE ACCIÓN
    const botonesContainer = citaCard.querySelector('.d-flex.gap-1');
    if (botonesContainer) {
        const nuevosBotones = generarBotonesAccion(citaUuid, nuevoEstado);
        const botonesDinamicos = botonesContainer.querySelectorAll('.btn-estado-cita, .dropdown, .btn-historia-clinica');
        botonesDinamicos.forEach(btn => btn.remove());
        botonesContainer.insertAdjacentHTML('beforeend', nuevosBotones);
    }
    
    // ✅ REMOVER INDICADORES DE CAMBIOS PENDIENTES SI LA CITA ESTÁ ATENDIDA
    if (nuevoEstado === 'ATENDIDA') {
        const iconoSync = citaCard.querySelector('.fa-sync-alt');
        if (iconoSync) {
            iconoSync.remove();
        }
        
        const textoPendiente = citaCard.querySelector('.cambios-pendientes-text');
        if (textoPendiente) {
            textoPendiente.remove();
        }
        
        if (card) {
            card.classList.remove('cambios-pendientes');
            card.style.borderLeft = '';
        }
    }
    
    // Actualizar estadísticas globales si vienen
    if (datosActualizados && datosActualizados.estadisticas_globales) {
        actualizarEstadisticasGlobales(datosActualizados.estadisticas_globales);
    }
    
    console.log('✅ Interfaz de cita actualizada correctamente');
}

// ✅ ESCUCHAR EVENTOS DE GUARDADO DE HISTORIA CLÍNICA
window.addEventListener('historiaClinicaGuardada', function(event) {
    const citaUuid = event.detail.cita_uuid;
    
    console.log('📋 Historia clínica guardada, actualizando cita:', citaUuid);
    
    // ✅ ACTUALIZAR INTERFAZ INMEDIATAMENTE
    actualizarCitaEnInterfaz(citaUuid, 'ATENDIDA', {});
    
    // ✅ MOSTRAR NOTIFICACIÓN
    mostrarAlerta('success', 'Historia clínica guardada. Cita marcada como atendida.');
    
    // ✅ ACTUALIZAR ESTADÍSTICAS GLOBALES
    setTimeout(() => {
        actualizarCronograma();
    }, 1000);
});

// ✅ DETECTOR DE CONECTIVIDAD MEJORADO
function initDetectorConectividad() {
   window.addEventListener('online', function() {
    console.log('🌐 Conexión restaurada');
    isOffline = false;
    actualizarBadgeConexion(true);
    mostrarAlerta('success', 'Conexión restaurada. Sincronizando datos...');

    // ✅ SINCRONIZAR CAMBIOS PENDIENTES DE FORMA INTELIGENTE
    setTimeout(() => {
        verificarYSincronizarCambiosPendientes();
        actualizarCronograma();
    }, 1000);
});
    
    window.addEventListener('offline', function() {
        console.log('📵 Conexión perdida');
        isOffline = true;
        actualizarBadgeConexion(false);
        mostrarAlerta('warning', 'Sin conexión. Los cambios se guardarán localmente.');
    });
    
    // Verificar conectividad cada 30 segundos
    setInterval(verificarConectividad, 30000);
}

// ✅ NUEVA FUNCIÓN: VERIFICAR Y SINCRONIZAR CAMBIOS PENDIENTES
function verificarYSincronizarCambiosPendientes() {
    try {
        // ✅ VERIFICAR SI HAY CAMBIOS PENDIENTES
        const cambiosPendientes = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
        const cambiosNoSincronizados = cambiosPendientes.filter(c => !c.sincronizado);
        
        if (cambiosNoSincronizados.length === 0) {
            console.log('✅ No hay cambios pendientes para sincronizar');
            return;
        }
        
        console.log(`🔄 Encontrados ${cambiosNoSincronizados.length} cambios pendientes para sincronizar`);
        
        // ✅ PROTECCIÓN CONTRA BUCLES: Verificar que no se esté ejecutando ya
        if (window.sincronizacionEnProceso) {
            console.log('⚠️ Sincronización ya en proceso, saltando...');
            return;
        }
        
        // ✅ MARCAR COMO EN PROCESO
        window.sincronizacionEnProceso = true;
        
        // ✅ EJECUTAR SINCRONIZACIÓN
        sincronizarCambiosPendientesInteligente(cambiosNoSincronizados);
        
    } catch (error) {
        console.error('❌ Error verificando cambios pendientes:', error);
        window.sincronizacionEnProceso = false;
    }
}

// ✅ SINCRONIZACIÓN INTELIGENTE CON PROTECCIÓN CONTRA BUCLES
function sincronizarCambiosPendientesInteligente(cambiosNoSincronizados) {
    let cambiosSincronizados = 0;
    let errores = 0;
    
    console.log(`🔄 Iniciando sincronización de ${cambiosNoSincronizados.length} cambios`);
    
    // ✅ PROCESAR CADA CAMBIO CON DELAY PARA EVITAR SATURAR EL SERVIDOR
    cambiosNoSincronizados.forEach((cambio, index) => {
        setTimeout(() => {
            console.log(`📤 Sincronizando cambio ${index + 1}/${cambiosNoSincronizados.length}:`, cambio.cita_uuid);
            
            fetch(`/cronograma/cita/${cambio.cita_uuid}/cambiar-estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    estado: cambio.nuevo_estado
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // ✅ MARCAR COMO SINCRONIZADO EN LOCALSTORAGE
                    cambio.sincronizado = true;
                    cambio.fecha_sincronizacion = new Date().toISOString();
                    cambiosSincronizados++;
                    
                    console.log(`✅ Cambio sincronizado: ${cambio.cita_uuid} -> ${cambio.nuevo_estado}`);
                    
                    // ✅ ACTUALIZAR LOCALSTORAGE
                    const todosCambios = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
                    const indice = todosCambios.findIndex(c => c.cita_uuid === cambio.cita_uuid && c.timestamp === cambio.timestamp);
                    if (indice >= 0) {
                        todosCambios[indice] = cambio;
                        localStorage.setItem('cambios_estados_pendientes', JSON.stringify(todosCambios));
                    }
                    
                    // ✅ REMOVER INDICADORES VISUALES DE CAMBIOS PENDIENTES
                    const citaCard = document.querySelector(`[data-cita-uuid="${cambio.cita_uuid}"]`);
                    if (citaCard) {
                        const card = citaCard.querySelector('.card');
                        if (card) {
                            card.classList.remove('cambios-pendientes');
                        }
                        
                        // Remover iconos de sincronización
                        const iconoSync = citaCard.querySelector('.fa-sync-alt');
                        if (iconoSync) {
                            iconoSync.remove();
                        }
                        
                        // Remover texto de cambios pendientes
                        const textoPendiente = citaCard.querySelector('.cambios-pendientes');
                        if (textoPendiente && textoPendiente.parentElement) {
                            textoPendiente.parentElement.remove();
                        }
                    }
                    
                } else {
                    throw new Error(data.error || 'Error en respuesta del servidor');
                }
            })
            .catch(error => {
                console.error(`❌ Error sincronizando cambio ${cambio.cita_uuid}:`, error);
                errores++;
            })
            .finally(() => {
                // ✅ VERIFICAR SI ES EL ÚLTIMO CAMBIO
                if (index === cambiosNoSincronizados.length - 1) {
                    // ✅ FINALIZAR PROCESO
                    setTimeout(() => {
                        window.sincronizacionEnProceso = false;
                        
                        if (cambiosSincronizados > 0) {
                            mostrarAlerta('success', `✅ ${cambiosSincronizados} cambios sincronizados correctamente`);
                        }
                        
                        if (errores > 0) {
                            mostrarAlerta('warning', `⚠️ ${errores} cambios no pudieron sincronizarse`);
                        }
                        
                        console.log(`🏁 Sincronización completada: ${cambiosSincronizados} exitosos, ${errores} errores`);
                        
                        // ✅ LIMPIAR CAMBIOS SINCRONIZADOS DEL LOCALSTORAGE
                        limpiarCambiosSincronizados();
                        
                    }, 500);
                }
            });
            
        }, index * 500); // ✅ DELAY DE 500ms ENTRE CADA PETICIÓN
    });
}

// ✅ LIMPIAR CAMBIOS SINCRONIZADOS DEL LOCALSTORAGE
function limpiarCambiosSincronizados() {
    try {
        const todosCambios = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
        const cambiosNoSincronizados = todosCambios.filter(c => !c.sincronizado);
        
        localStorage.setItem('cambios_estados_pendientes', JSON.stringify(cambiosNoSincronizados));
        
        console.log(`🧹 LocalStorage limpiado. Cambios restantes: ${cambiosNoSincronizados.length}`);
        
    } catch (error) {
        console.error('❌ Error limpiando localStorage:', error);
    }
}

// ✅ FUNCIÓN: RESOLVER CONFLICTOS ENTRE API Y CAMBIOS OFFLINE
function resolverConflictosDatos(citaApi, cambiosOffline) {
    // Si no hay cambios offline, usar datos de API
    if (!cambiosOffline || cambiosOffline.length === 0) {
        return citaApi;
    }
    
    // Buscar cambios pendientes para esta cita
    const cambiosPendientes = cambiosOffline.filter(c => 
        c.cita_uuid === citaApi.uuid && !c.sincronizado
    );
    
    if (cambiosPendientes.length === 0) {
        return citaApi;
    }
    
    // Obtener el cambio más reciente
    const cambioMasReciente = cambiosPendientes.sort((a, b) => 
        new Date(b.timestamp) - new Date(a.timestamp)
    )[0];
    
    console.log(`🔄 Aplicando cambio offline pendiente: ${citaApi.uuid} -> ${cambioMasReciente.nuevo_estado}`);
    
    // Aplicar el cambio offline sobre los datos de la API
    const citaConCambios = { ...citaApi };
    citaConCambios.estado = cambioMasReciente.nuevo_estado;
    citaConCambios._tiene_cambios_pendientes = true;
    citaConCambios._cambio_timestamp = cambioMasReciente.timestamp;
    
    return citaConCambios;
}

// ✅ APLICAR INDICADORES VISUALES A CAMBIOS PENDIENTES
function aplicarIndicadoresCambiosPendientes(cambiosPendientes) {
    cambiosPendientes.forEach(cambio => {
        const citaCard = document.querySelector(`[data-cita-uuid="${cambio.cita_uuid}"]`);
        if (citaCard) {
            console.log(`🎨 Aplicando indicadores visuales a: ${cambio.cita_uuid}`);
            
            // ✅ ACTUALIZAR ESTADO VISUAL
            actualizarCitaEnInterfaz(cambio.cita_uuid, cambio.nuevo_estado, null);
            
            // ✅ AGREGAR INDICADORES DE CAMBIOS PENDIENTES
            const card = citaCard.querySelector('.card');
            if (card) {
                card.classList.add('cambios-pendientes');
                
                // Agregar borde especial
                card.style.borderLeft = '4px solid #17a2b8';
                
                // Agregar badge de cambios pendientes
                const titulo = citaCard.querySelector('.card-title');
                if (titulo && !titulo.querySelector('.fa-sync-alt')) {
                    titulo.insertAdjacentHTML('beforeend', `
                        <i class="fas fa-sync-alt text-info ms-1" 
                           title="Cambios pendientes de sincronización"></i>
                    `);
                }
                
                // Agregar texto informativo
                const infoContainer = citaCard.querySelector('.text-muted.small');
                if (infoContainer && !infoContainer.querySelector('.cambios-pendientes-text')) {
                    infoContainer.insertAdjacentHTML('beforeend', `
                        <div class="mb-1 cambios-pendientes-text">
                            <i class="fas fa-sync-alt me-1 text-info"></i>
                            <span class="text-info">Cambios pendientes</span>
                        </div>
                    `);
                }
            }
        }
    });
}

function verificarConectividad() {
    if (!navigator.onLine) {
        if (!isOffline) {
            isOffline = true;
            actualizarBadgeConexion(false);
        }
        return;
    }
    
    fetch('/api/ping', {
        method: 'HEAD',
        cache: 'no-cache'
    })
    .then(response => {
        if (response.ok && isOffline) {
            isOffline = false;
            actualizarBadgeConexion(true);
        }
    })
    .catch(() => {
        if (!isOffline) {
            isOffline = true;
            actualizarBadgeConexion(false);
        }
    });
}

function actualizarBadgeConexion(online) {
    const badge = document.getElementById('badge-conexion');
    if (badge) {
        if (online) {
            badge.className = 'badge bg-success connection-indicator online';
            badge.innerHTML = '<i class="fas fa-wifi me-1"></i>Conectado';
        } else {
            badge.className = 'badge bg-warning connection-indicator offline';
            badge.innerHTML = '<i class="fas fa-database me-1"></i>Datos Locales';
        }
    }
}

function manejarErrorConexion() {
    isOffline = true;
    actualizarBadgeConexion(false);
}

// ✅ FUNCIONES AUXILIARES
function getEstadoColor(estado) {
    const colores = {
        'PROGRAMADA': 'primary',
        'EN_ATENCION': 'warning',
        'ATENDIDA': 'success',
        'CANCELADA': 'danger',
        'NO_ASISTIO': 'secondary'
    };
    return colores[estado] || 'secondary';
}

function getEstadoInfo(estado) {
    const info = {
        'PROGRAMADA': { color: 'primary', icon: 'calendar', label: 'Programada' },
        'EN_ATENCION': { color: 'warning', icon: 'clock', label: 'En Atención' },
        'ATENDIDA': { color: 'success', icon: 'check', label: 'Atendida' },
        'CANCELADA': { color: 'danger', icon: 'times', label: 'Cancelada' },
        'NO_ASISTIO': { color: 'secondary', icon: 'user-times', label: 'No Asistió' }
    };
    return info[estado] || { color: 'secondary', icon: 'question', label: estado };
}

function generarBotonesEstado(citaUuid, estadoActual) {
    if (isOffline) return '';
    
    let botones = '';
    
    switch (estadoActual) {
        case 'PROGRAMADA':
            botones = `
                <button type="button" class="btn btn-success btn-sm btn-estado-cita" 
                        data-cita-uuid="${citaUuid}" data-estado="EN_ATENCION">
                    <i class="fas fa-play me-1"></i>Iniciar Atención
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm btn-estado-cita" 
                        data-cita-uuid="${citaUuid}" data-estado="CANCELADA">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
            `;
            break;
            
        case 'EN_ATENCION':
            botones = `
                <button type="button" class="btn btn-success btn-sm btn-estado-cita" 
                        data-cita-uuid="${citaUuid}" data-estado="ATENDIDA">
                    <i class="fas fa-check me-1"></i>Marcar Atendida
                </button>
            `;
            break;
            
        case 'ATENDIDA':
            botones = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>Completada
                </span>
            `;
            break;
            
        default:
            botones = `
                <button type="button" class="btn btn-outline-primary btn-sm btn-estado-cita" 
                        data-cita-uuid="${citaUuid}" data-estado="PROGRAMADA">
                    <i class="fas fa-redo me-1"></i>Reprogramar
                </button>
            `;
    }
    
    return botones;
}


function generarBotonesAccion(citaUuid, estado) {
    if (isOffline) {
        return `
            <div class="alert alert-warning p-1 mb-0 flex-fill text-center">
                <small><i class="fas fa-database me-1"></i>Modo Offline</small>
            </div>
        `;
    }
    
    let botones = '';
    
    // ✅ BOTÓN DE HISTORIA CLÍNICA PARA ESTADOS APROPIADOS
    if (estado === 'EN_ATENCION' || estado === 'ATENDIDA') {
        const textoBoton = estado === 'ATENDIDA' ? 'Ver HC' : 'Crear HC';
        const colorBoton = estado === 'ATENDIDA' ? 'success' : 'info';
        
        botones += `
            <button type="button" class="btn btn-${colorBoton} btn-sm btn-historia-clinica"
                    data-cita-uuid="${citaUuid}" title="${textoBoton}">
                <i class="fas fa-file-medical"></i> ${textoBoton}
            </button>
        `;
    }
    
    switch (estado) {
        case 'PROGRAMADA':
            botones += `
                <button type="button" class="btn btn-warning btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}" data-estado="EN_ATENCION" title="Iniciar atención">
                    <i class="fas fa-play"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item btn-estado-cita" 
                               data-cita-uuid="${citaUuid}" data-estado="CANCELADA">
                                <i class="fas fa-times text-danger me-2"></i>Cancelar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-estado-cita" 
                               data-cita-uuid="${citaUuid}" data-estado="NO_ASISTIO">
                                <i class="fas fa-user-times text-secondary me-2"></i>No Asistió
                            </a>
                        </li>
                    </ul>
                </div>
            `;
            break;
            
        case 'EN_ATENCION':
            botones += `
                <button type="button" class="btn btn-success btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}" data-estado="ATENDIDA" title="Marcar como atendida">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;
            
        case 'ATENDIDA':
            botones += `
                <span class="badge bg-success flex-fill text-center py-2">
                    <i class="fas fa-check-circle"></i> Completada
                </span>
            `;
            break;
            
        default:
            botones += `
                <button type="button" class="btn btn-outline-primary btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}" data-estado="PROGRAMADA" title="Reprogramar">
                    <i class="fas fa-redo"></i>
                </button>
            `;
    }
    
    return botones;
}

// ✅ UTILIDADES DE INTERFAZ
function mostrarLoading(mostrar) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        if (mostrar) {
            overlay.classList.remove('d-none');
        } else {
            overlay.classList.add('d-none');
        }
    }
}

function setButtonLoading(buttonId, loading) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.dataset.originalText = originalText;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

function mostrarAlerta(tipo, mensaje) {
    const alertaId = 'alerta-' + Date.now();
    const alertaHtml = `
        <div id="${alertaId}" class="alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 10000; min-width: 300px;">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertaHtml);
    
    setTimeout(() => {
        const alerta = document.getElementById(alertaId);
        if (alerta) {
            alerta.remove();
        }
    }, 5000);
}

function actualizarTimestamp() {
    const timestamp = document.getElementById('ultima-actualizacion');
    if (timestamp) {
        const ahora = new Date();
        timestamp.textContent = ahora.toLocaleTimeString('es-ES');
    }
}

function animarCambioNumero(elemento, nuevoValor) {
    if (!elemento) return;
    
    const valorActual = parseInt(elemento.textContent) || 0;
    const valorNuevo = parseInt(nuevoValor) || 0;
    
    if (valorActual === valorNuevo) return;
    
    elemento.style.transform = 'scale(1.1)';
    elemento.style.transition = 'transform 0.2s ease';
    
    setTimeout(() => {
        elemento.textContent = nuevoValor;
        elemento.style.transform = 'scale(1)';
    }, 100);
}

// ✅ DEBUGGING CON HISTORIA CLÍNICA
window.cronogramaDebug = {
    cronogramaData,
    fechaActual,
    isOffline,
    actualizarCronograma,
    verDetalleCita,
    cambiarEstadoCita,
    verificarConectividad,
    sincronizarCambiosPendientes,
    guardarCambioEstadoOffline,
    // ✅ NUEVAS FUNCIONES DE DEBUG
    testHistoriaClinica: function(citaUuid) {
        console.log('🧪 Test Historia Clínica:', citaUuid);
        
        const url = `/historia-clinica/determinar-vista/${citaUuid}`; // ← NUEVA RUTA
        console.log('🔗 URL generada:', url);
        window.open(url, '_blank');
    }
};

console.log('✅ Cronograma JavaScript con Historia Clínica cargado completamente');
</script>
@endpush

    
