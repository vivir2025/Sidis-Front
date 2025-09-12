{{-- resources/views/cronograma/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Cronograma - Profesional en Salud')

@section('content')
<div class="container-fluid">
    <!-- Header del Cronograma -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Mi Cronograma
                    </h1>
                    <p class="text-muted mb-0">
                        Dr(a). <strong>{{ $usuario['nombre_completo'] ?? 'Profesional' }}</strong>
                        - {{ $usuario['especialidad']['nombre'] ?? 'Medicina General' }}
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Última actualización: <span id="ultima-actualizacion">{{ now()->format('H:i:s') }}</span>
                    </small>
                </div>
                
                <!-- Controles de Fecha -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Estado de Conexión -->
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @else
                        <span class="badge bg-success me-2">
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

    <!-- ✅ DEBUGGING INFO -->
    @if(config('app.debug'))
    <div class="alert alert-info">
        <strong>🔍 Debug Info:</strong><br>
        - Fecha: {{ $fechaSeleccionada }}<br>
        - Total Agendas: {{ count($cronogramaData['agendas'] ?? []) }}<br>
        - Total Citas: {{ $cronogramaData['estadisticas']['total_citas'] ?? 0 }}<br>
        - Modo Offline: {{ $isOffline ? 'Sí' : 'No' }}<br>
        - Timestamp: {{ $cronogramaData['timestamp'] ?? 'N/A' }}<br>
        - <a href="/cronograma/data/{{ $fechaSeleccionada }}" target="_blank">Ver JSON</a> |
        - <a href="/cronograma/refresh?fecha={{ $fechaSeleccionada }}" target="_blank">Refrescar</a>
    </div>
    @endif

    <!-- ✅ ESTADÍSTICAS GLOBALES MEJORADAS -->
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

    <!-- ✅ AGENDAS DEL DÍA CON CITAS INTEGRADAS -->
    <div class="row" id="cronograma-content">
        @if(empty($cronogramaData['agendas']))
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay agendas programadas</h4>
                        <p class="text-muted">No tienes agendas asignadas para el {{ \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}</p>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='/agendas/create'">
                            <i class="fas fa-plus me-1"></i>Crear Nueva Agenda
                        </button>
                    </div>
                </div>
            </div>
        @else
            @foreach($cronogramaData['agendas'] as $agenda)
                <div class="col-12 mb-4" data-agenda-uuid="{{ $agenda['uuid'] }}">
                    <div class="card border-0 shadow-sm agenda-card">
                        <!-- ✅ HEADER DE AGENDA MEJORADO -->
                        <div class="card-header bg-primary text-white">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <i class="fas fa-clock me-2"></i>
                                        {{ $agenda['hora_inicio'] ?? 'N/A' }} - {{ $agenda['hora_fin'] ?? 'N/A' }}
                                        <span class="badge bg-light text-dark ms-2">
                                            {{ $agenda['modalidad'] ?? 'Presencial' }}
                                        </span>
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
                                            <span class="total-citas">{{ $agenda['total_citas'] ?? 0 }}</span>/<span class="total-cupos">{{ $agenda['total_cupos'] ?? 0 }}</span>
                                        </span>
                                        <span class="badge bg-success">
                                            <i class="fas fa-chart-pie me-1"></i>
                                            <span class="porcentaje-ocupacion">{{ $agenda['porcentaje_ocupacion'] ?? 0 }}</span>%
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
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="/agendas/{{ $agenda['uuid'] }}/edit">
                                                        <i class="fas fa-edit me-2"></i>Editar Agenda
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ✅ BARRA DE PROGRESO DE OCUPACIÓN -->
                            <div class="mt-2">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-light" 
                                         role="progressbar" 
                                         style="width: {{ $agenda['porcentaje_ocupacion'] ?? 0 }}%"
                                         aria-valuenow="{{ $agenda['porcentaje_ocupacion'] ?? 0 }}" 
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
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="window.location.href='/citas/create?agenda={{ $agenda['uuid'] }}'">
                                        <i class="fas fa-plus me-1"></i>Agendar Cita
                                    </button>
                                </div>
                            @else
                                <!-- ✅ ESTADÍSTICAS RÁPIDAS DE LA AGENDA -->
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
                                        <span class="badge bg-info">
                                            <i class="fas fa-chair me-1"></i>
                                            <span class="cupos-disponibles">{{ $agenda['cupos_disponibles'] ?? 0 }}</span> Cupos Libres
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- ✅ GRID DE CITAS MEJORADO -->
                                <div class="row g-3">
                                    @foreach($agenda['citas'] as $cita)
                                        @php
                                            $paciente = $cita['paciente'] ?? [];
                                            $estado = $cita['estado'] ?? 'PROGRAMADA';
                                            $estadoInfo = [
                                                'PROGRAMADA' => ['color' => 'primary', 'icon' => 'calendar', 'label' => 'Programada'],
                                                'EN_ATENCION' => ['color' => 'warning', 'icon' => 'clock', 'label' => 'En Atención'],
                                                'ATENDIDA' => ['color' => 'success', 'icon' => 'check', 'label' => 'Atendida'],
                                                'CANCELADA' => ['color' => 'danger', 'icon' => 'times', 'label' => 'Cancelada'],
                                                'NO_ASISTIO' => ['color' => 'secondary', 'icon' => 'user-times', 'label' => 'No Asistió']
                                            ][$estado] ?? ['color' => 'secondary', 'icon' => 'question', 'label' => $estado];
                                        @endphp
                                        
                                        <div class="col-md-6 col-lg-4" data-cita-uuid="{{ $cita['uuid'] }}">
                                            <div class="card border-start border-4 border-{{ $estadoInfo['color'] }} h-100 cita-card">
                                                <div class="card-body">
                                                    <!-- ✅ HEADER DE CITA -->
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0 text-truncate" style="max-width: 70%;">
                                                            <i class="fas fa-user me-1"></i>
                                                            {{ $paciente['nombre_completo'] ?? 'Paciente no identificado' }}
                                                        </h6>
                                                        <span class="badge bg-{{ $estadoInfo['color'] }} badge-sm">
                                                            <i class="fas fa-{{ $estadoInfo['icon'] }} me-1"></i>
                                                            {{ $estadoInfo['label'] }}
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- ✅ INFORMACIÓN DE LA CITA -->
                                                    <div class="text-muted small mb-2">
                                                        <div class="mb-1">
                                                            <i class="fas fa-id-card me-1"></i> 
                                                            {{ $paciente['documento'] ?? 'Sin documento' }}
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock me-1"></i> 
                                                            {{ isset($cita['fecha_inicio']) ? \Carbon\Carbon::parse($cita['fecha_inicio'])->format('H:i') : 'N/A' }} - 
                                                            {{ isset($cita['fecha_final']) ? \Carbon\Carbon::parse($cita['fecha_final'])->format('H:i') : 'N/A' }}
                                                        </div>
                                                        @if(!empty($paciente['telefono']))
                                                            <div class="mb-1">
                                                                <i class="fas fa-phone me-1"></i> 
                                                                {{ $paciente['telefono'] }}
                                                            </div>
                                                        @endif
                                                        @if(isset($cita['tiempo_info']))
                                                            <div class="mb-1">
                                                                <i class="fas fa-hourglass-half me-1"></i>
                                                                <span class="text-{{ $cita['tiempo_info']['tipo'] === 'pasado' ? 'danger' : 'info' }}">
                                                                    {{ $cita['tiempo_info']['texto'] }}
                                                                </span>
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
                                                    
                                                    <!-- ✅ BOTONES DE ACCIÓN -->
                                                    <div class="d-flex gap-1 mt-auto">
                                                        <button type="button" 
                                                                class="btn btn-outline-primary btn-sm flex-fill btn-detalle-cita"
                                                                data-cita-uuid="{{ $cita['uuid'] }}">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                        
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
                                                                <button type="button" 
                                                                        class="btn btn-success btn-sm btn-estado-cita"
                                                                        data-cita-uuid="{{ $cita['uuid'] }}"
                                                                        data-estado="ATENDIDA"
                                                                        title="Marcar como atendida">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                @break
                                                                
                                                            @case('ATENDIDA')
                                                                <span class="badge bg-success flex-fill text-center py-2">
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

    <!-- ✅ LOADING OVERLAY MEJORADO -->
    <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" 
         style="background: rgba(0,0,0,0.5); z-index: 9999; backdrop-filter: blur(2px);">
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

<!-- ✅ MODAL DETALLE DE CITA MEJORADO -->
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

@push('scripts')
<script>
// ✅ VARIABLES GLOBALES INTEGRADAS
let fechaActual = '{{ $fechaSeleccionada }}';
let cronogramaData = @json($cronogramaData ?? []);
let isOffline = {{ $isOffline ? 'true' : 'false' }};

// ✅ DEBUGGING INICIAL
console.log('🏥 Iniciando cronograma profesional integrado');
console.log('📊 Datos del cronograma:', cronogramaData);
console.log('📅 Fecha actual:', fechaActual);
console.log('🌐 Estado offline:', isOffline);

// ✅ INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, inicializando cronograma integrado...');
    
    // Verificar datos
    if (!cronogramaData || !cronogramaData.estadisticas) {
        console.error('❌ No hay datos de cronograma disponibles');
        mostrarAlerta('error', 'No se pudieron cargar los datos del cronograma');
        return;
    }
    
    // Event listeners
    initEventListeners();
    
    // Auto-actualizar cada 5 minutos
    setInterval(actualizarCronogramaAuto, 5 * 60 * 1000);
    
    // Inicializar detector de conectividad
    initDetectorConectividad();
    
    console.log('✅ Cronograma integrado inicializado correctamente');
});
// ✅ INICIALIZAR EVENT LISTENERS
function initEventListeners() {
    // Selector de fecha
    const fechaSelector = document.getElementById('fecha-selector');
    if (fechaSelector) {
        fechaSelector.addEventListener('change', function() {
            console.log('📅 Fecha cambiada de', fechaActual, 'a', this.value);
            fechaActual = this.value;
            cambiarFecha(this.value);
        });
    }
    
    // Botón actualizar
    const btnActualizar = document.getElementById('btn-actualizar');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', function() {
            actualizarCronograma();
        });
    }
    
    // Event delegation para botones dinámicos
    document.addEventListener('click', function(e) {
        // Botones de estado de citas
        if (e.target.classList.contains('btn-estado-cita') || e.target.closest('.btn-estado-cita')) {
            const btn = e.target.classList.contains('btn-estado-cita') ? e.target : e.target.closest('.btn-estado-cita');
            const citaUuid = btn.dataset.citaUuid;
            const nuevoEstado = btn.dataset.estado;
            if (citaUuid && nuevoEstado) {
                cambiarEstadoCita(citaUuid, nuevoEstado);
            }
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

// ✅ CAMBIAR FECHA (REDIRIGIR A NUEVA FECHA)
function cambiarFecha(fecha) {
    if (fecha === fechaActual) return;
    
    console.log('🔄 Cambiando a fecha:', fecha);
    mostrarLoading(true);
    
    // Redirigir con la nueva fecha
    window.location.href = `/cronograma?fecha=${fecha}`;
}

// ✅ ACTUALIZAR CRONOGRAMA VÍA AJAX
function actualizarCronograma() {
    console.log('🔄 Actualizando cronograma para fecha:', fechaActual);
    
    if (document.getElementById('btn-actualizar').disabled) {
        console.log('⏳ Actualización ya en progreso...');
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
            console.log('✅ Datos actualizados correctamente');
            
            // Actualizar datos globales
            cronogramaData = data.data;
            
            // Actualizar contenido
            actualizarContenidoCronograma(data.data);
            
            // Mostrar mensaje de éxito
            mostrarAlerta('success', 'Cronograma actualizado correctamente');
            
            // Actualizar timestamp
            actualizarTimestamp();
            
        } else {
            console.error('❌ Error en la respuesta:', data.error);
            mostrarAlerta('error', data.error || 'Error cargando cronograma');
        }
    })
    .catch(error => {
        console.error('❌ Error actualizando cronograma:', error);
        mostrarAlerta('error', 'Error de conexión. Verifique su internet.');
        manejarErrorConexion();
    })
    .finally(() => {
        mostrarLoading(false);
        setButtonLoading('btn-actualizar', false);
    });
}

// ✅ ACTUALIZACIÓN AUTOMÁTICA SILENCIOSA
function actualizarCronogramaAuto() {
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
            actualizarContenidoCronograma(data.data);
            actualizarTimestamp();
        }
    })
    .catch(error => {
        console.warn('⚠️ Error en actualización automática:', error);
    });
}

// ✅ ACTUALIZAR CONTENIDO DEL CRONOGRAMA
function actualizarContenidoCronograma(data) {
    try {
        console.log('🔄 Actualizando contenido del cronograma');
        
        // Actualizar estadísticas globales
        actualizarEstadisticasGlobales(data.estadisticas);
        
        // Actualizar agendas existentes
        actualizarAgendasExistentes(data.agendas);
        
        console.log('✅ Contenido actualizado correctamente');
        
    } catch (error) {
        console.error('❌ Error actualizando contenido:', error);
        mostrarAlerta('error', 'Error actualizando la interfaz');
    }
}

// ✅ ACTUALIZAR ESTADÍSTICAS GLOBALES
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
            // Animación de cambio de número
            animarCambioNumero(elemento, valor);
        }
    });
}

// ✅ ACTUALIZAR AGENDAS EXISTENTES
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
        // Actualizar contadores en el header
        const totalCitas = tarjeta.querySelector('.total-citas');
        if (totalCitas) totalCitas.textContent = agenda.total_citas || 0;
        
        const totalCupos = tarjeta.querySelector('.total-cupos');
        if (totalCupos) totalCupos.textContent = agenda.total_cupos || 0;
        
        const cuposDisponibles = tarjeta.querySelector('.cupos-disponibles');
        if (cuposDisponibles) cuposDisponibles.textContent = agenda.cupos_disponibles || 0;
        
        const porcentajeOcupacion = tarjeta.querySelector('.porcentaje-ocupacion');
        if (porcentajeOcupacion) porcentajeOcupacion.textContent = agenda.porcentaje_ocupacion || 0;
        
        // Actualizar barra de progreso
        const barraProgreso = tarjeta.querySelector('.progress-bar');
        if (barraProgreso) {
            barraProgreso.style.width = (agenda.porcentaje_ocupacion || 0) + '%';
            barraProgreso.setAttribute('aria-valuenow', agenda.porcentaje_ocupacion || 0);
        }
        
        // Actualizar badges de estadísticas
        const estadisticas = agenda.estadisticas || {};
        Object.entries(estadisticas).forEach(([estado, cantidad]) => {
            const badge = tarjeta.querySelector(`[data-estado="${estado}"]`);
            if (badge) {
                badge.textContent = cantidad;
            }
        });
        
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

// ✅ MOSTRAR MODAL CON DETALLE DE CITA
function mostrarModalDetalleCita(cita) {
    const paciente = cita.paciente || {};
    const agenda = cita.agenda || {};
    const estadoInfo = getEstadoInfo(cita.estado);
    
    const modalBody = document.getElementById('modal-detalle-cita-body');
    modalBody.innerHTML = `
        <div class="row g-4">
            <!-- Información del Paciente -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-user text-primary me-2"></i>
                            Información del Paciente
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Nombre Completo:</label>
                                <p class="mb-0">${paciente.nombre_completo || 'No especificado'}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Documento:</label>
                                <p class="mb-0">${paciente.documento || 'No especificado'}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Teléfono:</label>
                                <p class="mb-0">
                                    ${paciente.telefono ? `<a href="tel:${paciente.telefono}">${paciente.telefono}</a>` : 'No especificado'}
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Correo Electrónico:</label>
                                <p class="mb-0">
                                    ${paciente.correo ? `<a href="mailto:${paciente.correo}">${paciente.correo}</a>` : 'No especificado'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información de la Cita -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar-alt text-info me-2"></i>
                            Información de la Cita
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Fecha:</label>
                                <p class="mb-0">${formatearFecha(cita.fecha_inicio)}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Hora Inicio:</label>
                                <p class="mb-0">${formatearHora(cita.fecha_inicio)}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Hora Fin:</label>
                                <p class="mb-0">${formatearHora(cita.fecha_final)}</p>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Estado Actual:</label>
                                <p class="mb-0">
                                    <span class="badge bg-${estadoInfo.color} fs-6">
                                        <i class="fas fa-${estadoInfo.icon} me-1"></i>
                                        ${estadoInfo.label}
                                    </span>
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Modalidad:</label>
                                <p class="mb-0">${agenda.modalidad || 'No especificado'}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold">Consultorio:</label>
                                <p class="mb-0">${agenda.consultorio || 'No especificado'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Observaciones y Motivo -->
            ${(cita.motivo || cita.nota || cita.observaciones) ? `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-notes-medical text-success me-2"></i>
                                Observaciones y Motivo
                            </h6>
                        </div>
                        <div class="card-body">
                            ${cita.motivo ? `
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Motivo de la Consulta:</label>
                                    <p class="mb-0">${cita.motivo}</p>
                                </div>
                            ` : ''}
                            ${cita.nota ? `
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nota:</label>
                                    <p class="mb-0">${cita.nota}</p>
                                </div>
                            ` : ''}
                            ${cita.observaciones ? `
                                <div class="mb-0">
                                    <label class="form-label fw-bold">Observaciones:</label>
                                    <p class="mb-0">${cita.observaciones}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            ` : ''}
            
            <!-- Información de Tiempo -->
            ${cita.tiempo_info ? `
                <div class="col-12">
                    <div class="alert alert-${cita.tiempo_info.tipo === 'pasado' ? 'warning' : 'info'} mb-0">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Tiempo:</strong> ${cita.tiempo_info.texto}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    // Configurar botones de estado
    const botonesContainer = document.getElementById('botones-estado-modal');
    botonesContainer.innerHTML = generarBotonesEstadoModal(cita);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-cita'));
    modal.show();
}

// ✅ GENERAR BOTONES DE ESTADO PARA MODAL
function generarBotonesEstadoModal(cita) {
    const estado = cita.estado?.toUpperCase() || 'PROGRAMADA';
    let botones = '';
    
    switch (estado) {
        case 'PROGRAMADA':
            botones = `
                <button type="button" class="btn btn-success me-2" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'EN_ATENCION')">
                    <i class="fas fa-play me-1"></i>Iniciar Atención
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-exclamation-triangle me-1"></i>Otras Acciones
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'CANCELADA')">
                                <i class="fas fa-times text-danger me-2"></i>Cancelar Cita
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'NO_ASISTIO')">
                                <i class="fas fa-user-times text-secondary me-2"></i>Marcar No Asistió
                            </a>
                        </li>
                    </ul>
                </div>
            `;
            break;
            
        case 'EN_ATENCION':
            botones = `
                <button type="button" class="btn btn-success me-2" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'ATENDIDA')">
                    <i class="fas fa-check me-1"></i>Marcar como Atendida
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'PROGRAMADA')">
                    <i class="fas fa-undo me-1"></i>Volver a Programada
                </button>
            `;
            break;
            
        case 'ATENDIDA':
            botones = `
                <div class="alert alert-success mb-0 d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Cita completada exitosamente</strong>
                </div>
            `;
            break;
            
        case 'CANCELADA':
        case 'NO_ASISTIO':
            botones = `
                <button type="button" class="btn btn-outline-primary" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'PROGRAMADA')">
                    <i class="fas fa-redo me-1"></i>Reprogramar Cita
                </button>
                <div class="alert alert-warning mb-0 ms-3 d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Esta cita fue ${estado === 'CANCELADA' ? 'cancelada' : 'marcada como no asistió'}</small>
                </div>
            `;
            break;
            
        default:
            botones = `
                <button type="button" class="btn btn-outline-primary" onclick="cambiarEstadoCitaModal('${cita.uuid}', 'PROGRAMADA')">
                    <i class="fas fa-calendar me-1"></i>Programar
                </button>
            `;
    }
    
    return botones;
}

// ✅ VER CITAS DE AGENDA
function verCitasAgenda(agendaUuid) {
    console.log('📋 Viendo citas de agenda:', agendaUuid);
    
    mostrarLoading(true);
    
    fetch(`/cronograma/agenda/${agendaUuid}/citas?fecha=${fechaActual}`, {
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
            mostrarModalCitasAgenda(data.data, agendaUuid);
        } else {
            throw new Error(data.error || 'Error obteniendo citas de agenda');
        }
    })
    .catch(error => {
        console.error('❌ Error obteniendo citas de agenda:', error);
        mostrarAlerta('error', 'Error cargando citas de la agenda');
    })
    .finally(() => {
        mostrarLoading(false);
    });
}

// ✅ MOSTRAR MODAL DE CITAS DE AGENDA
function mostrarModalCitasAgenda(citas, agendaUuid) {
    const tbody = document.querySelector('#tabla-citas-agenda tbody');
    if (!tbody) return;
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    if (!citas || citas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                    <br>No hay citas programadas para esta agenda
                </td>
            </tr>
        `;
    } else {
        // Ordenar citas por hora
        citas.sort((a, b) => {
            const horaA = new Date(a.fecha_inicio || 0);
            const horaB = new Date(b.fecha_inicio || 0);
            return horaA - horaB;
        });
        
        // Llenar tabla con citas
        citas.forEach(cita => {
            const fila = crearFilaCitaTabla(cita);
            tbody.appendChild(fila);
        });
    }
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modal-citas-agenda'));
    modal.show();
}

// ✅ CREAR FILA DE CITA PARA TABLA
function crearFilaCitaTabla(cita) {
    const fila = document.createElement('tr');
    const paciente = cita.paciente || {};
    const estadoInfo = getEstadoInfo(cita.estado);
    
    fila.innerHTML = `
        <td>
            <strong>${formatearHora(cita.fecha_inicio)}</strong>
            <br>
            <small class="text-muted">${formatearHora(cita.fecha_final)}</small>
        </td>
        <td>
            <strong>${paciente.nombre_completo || 'Sin nombre'}</strong>
            ${cita.motivo ? `<br><small class="text-muted">${cita.motivo}</small>` : ''}
        </td>
        <td>${paciente.documento || 'Sin documento'}</td>
        <td>
            ${paciente.telefono ? `<a href="tel:${paciente.telefono}">${paciente.telefono}</a>` : 'Sin teléfono'}
        </td>
        <td>
            <span class="badge bg-${estadoInfo.color}">
                <i class="fas fa-${estadoInfo.icon} me-1"></i>
                ${estadoInfo.label}
            </span>
            ${cita.tiempo_info ? `<br><small class="text-${cita.tiempo_info.tipo === 'pasado' ? 'danger' : 'info'}">${cita.tiempo_info.texto}</small>` : ''}
        </td>
        <td>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary btn-detalle-cita" 
                        data-cita-uuid="${cita.uuid}"
                        title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </button>
                ${generarBotonesAccionTabla(cita)}
            </div>
        </td>
    `;
    
    return fila;
}

// ✅ GENERAR BOTONES DE ACCIÓN PARA TABLA
function generarBotonesAccionTabla(cita) {
    const estado = cita.estado?.toUpperCase() || 'PROGRAMADA';
    let botones = '';
    
    switch (estado) {
        case 'PROGRAMADA':
            botones = `
                <button class="btn btn-success btn-sm btn-estado-cita" 
                        data-cita-uuid="${cita.uuid}" 
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
                               data-cita-uuid="${cita.uuid}" 
                               data-estado="CANCELADA">
                                <i class="fas fa-times text-danger me-2"></i>Cancelar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-estado-cita" 
                               data-cita-uuid="${cita.uuid}" 
                               data-estado="NO_ASISTIO">
                                <i class="fas fa-user-times text-secondary me-2"></i>No Asistió
                            </a>
                        </li>
                    </ul>
                </div>
            `;
            break;
            
        case 'EN_ATENCION':
            botones = `
                <button class="btn btn-success btn-sm btn-estado-cita" 
                        data-cita-uuid="${cita.uuid}" 
                        data-estado="ATENDIDA"
                        title="Marcar como atendida">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;
            
        case 'ATENDIDA':
            botones = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> Completada
                </span>
            `;
            break;
            
        default:
            botones = `
                <button class="btn btn-outline-primary btn-sm btn-estado-cita" 
                        data-cita-uuid="${cita.uuid}" 
                        data-estado="PROGRAMADA"
                        title="Reprogramar">
                    <i class="fas fa-redo"></i>
                </button>
            `;
    }
    
    return botones;
}

// ✅ CAMBIAR ESTADO DE CITA
function cambiarEstadoCita(citaUuid, nuevoEstado) {
    console.log('🔄 Cambiando estado de cita:', citaUuid, 'a', nuevoEstado);
    
    // Confirmación para estados críticos
    if (['CANCELADA', 'NO_ASISTIO'].includes(nuevoEstado)) {
        const mensajes = {
            'CANCELADA': '¿Está seguro de cancelar esta cita?',
            'NO_ASISTIO': '¿Confirma que el paciente no asistió a la cita?'
        };
        
        if (!confirm(mensajes[nuevoEstado])) {
            return;
        }
    }
    
    mostrarLoading(true);
    
    fetch(`/cronograma/cita/${citaUuid}/estado`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({ estado: nuevoEstado })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const estadoInfo = getEstadoInfo(nuevoEstado);
            mostrarAlerta('success', `Estado cambiado a ${estadoInfo.label}`);
            
            // Actualizar la vista sin recargar completamente
            actualizarEstadoCitaEnVista(citaUuid, nuevoEstado);
            
            // Actualizar cronograma después de un breve delay
            setTimeout(() => {
                actualizarCronograma();
            }, 1000);
            
        } else {
            throw new Error(data.error || 'Error actualizando estado');
        }
    })
    .catch(error => {
        console.error('❌ Error cambiando estado:', error);
        mostrarAlerta('error', 'Error actualizando estado de cita');
    })
    .finally(() => {
        mostrarLoading(false);
    });
}

// ✅ CAMBIAR ESTADO DESDE MODAL
function cambiarEstadoCitaModal(citaUuid, nuevoEstado) {
    cambiarEstadoCita(citaUuid, nuevoEstado);
    
    // Cerrar modal después de cambiar estado
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modal-detalle-cita'));
        if (modal) {
            modal.hide();
        }
    }, 1500);
}

// ✅ ACTUALIZAR ESTADO DE CITA EN VISTA
function actualizarEstadoCitaEnVista(citaUuid, nuevoEstado) {
    const citaCard = document.querySelector(`[data-cita-uuid="${citaUuid}"]`);
    if (!citaCard) return;
    
    const estadoInfo = getEstadoInfo(nuevoEstado);
    
    // Actualizar badge de estado
    const badge = citaCard.querySelector('.badge');
    if (badge) {
        badge.className = `badge bg-${estadoInfo.color} badge-sm`;
        badge.innerHTML = `<i class="fas fa-${estadoInfo.icon} me-1"></i>${estadoInfo.label}`;
    }
    
    // Actualizar borde de la tarjeta
    const card = citaCard.querySelector('.card');
    if (card) {
        // Remover clases de borde anteriores
        card.classList.remove('border-primary', 'border-warning', 'border-success', 'border-danger', 'border-secondary');
        // Agregar nueva clase de borde
        card.classList.add(`border-${estadoInfo.color}`);
    }
    
    // Actualizar botones de acción
    const botonesContainer = citaCard.querySelector('.d-flex.gap-1');
    if (botonesContainer) {
        botonesContainer.innerHTML = generarBotonesAccionActualizados(citaUuid, nuevoEstado);
    }
    
    // Animación de actualización
    citaCard.style.transform = 'scale(1.02)';
    citaCard.style.transition = 'transform 0.3s ease';
    setTimeout(() => {
        citaCard.style.transform = 'scale(1)';
    }, 300);
}

// ✅ GENERAR BOTONES DE ACCIÓN ACTUALIZADOS
function generarBotonesAccionActualizados(citaUuid, estado) {
    let botones = `
        <button type="button" 
                class="btn btn-outline-primary btn-sm flex-fill btn-detalle-cita"
                data-cita-uuid="${citaUuid}">
            <i class="fas fa-eye"></i> Ver
        </button>
    `;
    
    switch (estado.toUpperCase()) {
        case 'PROGRAMADA':
            botones += `
                <button type="button" 
                        class="btn btn-success btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}"
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
                               data-cita-uuid="${citaUuid}" 
                               data-estado="CANCELADA">
                                <i class="fas fa-times text-danger me-2"></i>Cancelar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-estado-cita" 
                               data-cita-uuid="${citaUuid}" 
                               data-estado="NO_ASISTIO">
                                <i class="fas fa-user-times text-secondary me-2"></i>No Asistió
                            </a>
                        </li>
                    </ul>
                </div>
            `;
            break;
            
        case 'EN_ATENCION':
            botones += `
                <button type="button" 
                        class="btn btn-success btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}"
                        data-estado="ATENDIDA"
                        title="Marcar como atendida">
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
                <button type="button" 
                        class="btn btn-outline-primary btn-sm btn-estado-cita"
                        data-cita-uuid="${citaUuid}"
                        data-estado="PROGRAMADA"
                        title="Reprogramar">
                    <i class="fas fa-redo"></i>
                </button>
            `;
    }
    
    return botones;
}

// ✅ FUNCIONES DE UTILIDAD MEJORADAS
function formatearFecha(fecha) {
    if (!fecha) return 'No especificada';
    
    try {
        const fechaObj = new Date(fecha);
        const opciones = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        
        return fechaObj.toLocaleDateString('es-ES', opciones);
    } catch (error) {
        console.error('Error formateando fecha:', error);
        return 'Fecha inválida';
    }
}

function formatearHora(fechaHora) {
    if (!fechaHora) return 'No especificada';
    
    try {
        const fechaObj = new Date(fechaHora);
        return fechaObj.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Error formateando hora:', error);
        return 'Hora inválida';
    }
}

function getEstadoInfo(estado) {
    const estados = {
        'PROGRAMADA': { label: 'Programada', color: 'primary', icon: 'calendar' },
        'EN_ATENCION': { label: 'En Atención', color: 'warning', icon: 'clock' },
        'ATENDIDA': { label: 'Atendida', color: 'success', icon: 'check' },
        'CANCELADA': { label: 'Cancelada', color: 'danger', icon: 'times' },
        'NO_ASISTIO': { label: 'No Asistió', color: 'secondary', icon: 'user-times' }
    };
    
    return estados[estado?.toUpperCase()] || estados['PROGRAMADA'];
}

// ✅ FUNCIONES DE UI MEJORADAS
function mostrarLoading(mostrar) {
    const overlay = document.getElementById('loading-overlay');
    if (!overlay) return;
    
    if (mostrar) {
        overlay.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    } else {
        overlay.classList.add('d-none');
        document.body.style.overflow = '';
    }
}

function setButtonLoading(buttonId, loading) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cargando...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

function mostrarAlerta(tipo, mensaje) {
    // Usar SweetAlert2 si está disponible
    if (typeof Swal !== 'undefined') {
        const iconos = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };
        
        Swal.fire({
            icon: iconos[tipo] || 'info',
            title: mensaje,
            showConfirmButton: false,
            timer: 3000,
            toast: true,
            position: 'top-end',
            timerProgressBar: true
        });
        return;
    }
    
    // Fallback con Bootstrap Toast
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${getToastClass(tipo)} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${getToastIcon(tipo)} me-2"></i>
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Crear contenedor de toasts si no existe
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Agregar toast
    const toastElement = document.createElement('div');
    toastElement.innerHTML = toastHtml;
    const toast = toastElement.firstElementChild;
    toastContainer.appendChild(toast);
    
    // Mostrar toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
    
    // Remover después de que se oculte
    toast.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

function getToastClass(tipo) {
    const clases = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'info'
    };
    return clases[tipo] || 'info';
}

function getToastIcon(tipo) {
    const iconos = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return iconos[tipo] || 'info-circle';
}

// ✅ ANIMACIONES Y EFECTOS VISUALES
function animarCambioNumero(elemento, nuevoValor) {
    if (!elemento) return;
    
    const valorActual = elemento.textContent.replace(/[^\d]/g, '');
    const valorNuevo = nuevoValor.toString().replace(/[^\d]/g, '');
    
    if (valorActual !== valorNuevo) {
        elemento.style.transform = 'scale(1.1)';
        elemento.style.transition = 'transform 0.3s ease';
        
        setTimeout(() => {
            elemento.textContent = nuevoValor;
            elemento.style.transform = 'scale(1)';
        }, 150);
    }
}

function actualizarTimestamp() {
    const timestampElement = document.getElementById('ultima-actualizacion');
    if (timestampElement) {
        const ahora = new Date();
        timestampElement.textContent = ahora.toLocaleTimeString('es-ES');
        
        // Efecto visual de actualización
        timestampElement.style.color = '#28a745';
        setTimeout(() => {
            timestampElement.style.color = '';
        }, 2000);
    }
}

// ✅ DETECTOR DE CONECTIVIDAD
function initDetectorConectividad() {
    // Detectar cambios en la conectividad
    window.addEventListener('online', function() {
        console.log('🌐 Conexión restaurada');
        mostrarAlerta('success', 'Conexión restaurada - Sincronizando datos');
        actualizarCronograma();
        
        // Actualizar badge de conexión
        actualizarBadgeConexion(true);
    });
    
    window.addEventListener('offline', function() {
        console.log('📱 Modo offline activado');
        mostrarAlerta('warning', 'Sin conexión - Trabajando en modo offline');
        
        // Actualizar badge de conexión
        actualizarBadgeConexion(false);
    });
    
    // Verificar conectividad inicial
    verificarConectividad();
}

function actualizarBadgeConexion(online) {
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        if (badge.textContent.includes('Conectado') || badge.textContent.includes('Modo Offline')) {
            if (online) {
                badge.className = 'badge bg-success me-2';
                badge.innerHTML = '<i class="fas fa-wifi"></i> Conectado';
            } else {
                badge.className = 'badge bg-warning me-2';
                badge.innerHTML = '<i class="fas fa-wifi-slash"></i> Modo Offline';
            }
        }
    });
}

function verificarConectividad() {
    if (!navigator.onLine) {
        actualizarBadgeConexion(false);
        isOffline = true;
    }
}

function manejarErrorConexion() {
    console.warn('⚠️ Error de conexión detectado');
    isOffline = true;
    actualizarBadgeConexion(false);
}

// ✅ FUNCIONES DE LIMPIEZA Y OPTIMIZACIÓN
function limpiarEventListeners() {
    // Limpiar event listeners para evitar memory leaks
    const elementos = document.querySelectorAll('[data-cita-uuid], [data-agenda-uuid]');
    elementos.forEach(elemento => {
        elemento.replaceWith(elemento.cloneNode(true));
    });
}

// ✅ MANEJO DE ERRORES GLOBAL
window.addEventListener('error', function(event) {
    console.error('❌ Error global capturado:', event.error);
    
    if (event.error && event.error.message && event.error.message.includes('fetch')) {
        manejarErrorConexion();
    }
});

// ✅ PREVENIR PÉRDIDA DE DATOS
window.addEventListener('beforeunload', function(event) {
    // Si hay operaciones pendientes, advertir al usuario
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay && !loadingOverlay.classList.contains('d-none')) {
        event.preventDefault();
        event.returnValue = 'Hay operaciones en progreso. ¿Está seguro de salir?';
        return event.returnValue;
    }
});

// ✅ OPTIMIZACIÓN DE RENDIMIENTO
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced version of actualizarCronograma
const actualizarCronogramaDebounced = debounce(actualizarCronograma, 1000);

// ✅ FUNCIONES DE ACCESIBILIDAD
function mejorarAccesibilidad() {
    // Agregar roles ARIA
    const botones = document.querySelectorAll('.btn-estado-cita');
    botones.forEach(boton => {
        boton.setAttribute('role', 'button');
        boton.setAttribute('aria-label', `Cambiar estado de cita a ${boton.dataset.estado}`);
    });
    
    // Mejorar navegación por teclado
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Cerrar modales abiertos
            const modales = document.querySelectorAll('.modal.show');
            modales.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        }
    });
}

// ✅ INICIALIZACIÓN FINAL
document.addEventListener('DOMContentLoaded', function() {
    // Mejorar accesibilidad
    mejorarAccesibilidad();
    
    // Log de inicialización completa
    console.log('✅ Cronograma profesional completamente inicializado');
    console.log('📊 Estadísticas:', cronogramaData?.estadisticas);
    console.log('📅 Agendas cargadas:', cronogramaData?.agendas?.length || 0);
    console.log('🎯 Funcionalidades activas: actualización automática, gestión de estados, modo offline');
});
</script>
@endpush

@push('styles')
<style>
/* ✅ ESTILOS MEJORADOS Y OPTIMIZADOS */

/* Variables CSS personalizadas */
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

/* Header del cronograma */
.card-header.bg-primary {
    background: var(--primary-gradient) !important;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

/* Tarjetas de estadísticas */
.card-stat {
    transition: var(--transition);
    cursor: pointer;
}

.card-stat:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.card-stat .rounded-circle {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.card-stat:hover .rounded-circle {
    transform: scale(1.1);
}

/* Tarjetas de agendas */
.agenda-card {
    transition: var(--transition);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.agenda-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Tarjetas de citas */
.cita-card {
    transition: var(--transition);
    border-radius: var(--border-radius);
    height: 100%;
}

.cita-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Bordes de estado */
.border-start.border-4 {
    border-left-width: 4px !important;
}

.border-primary { border-color: #007bff !important; }
.border-warning { border-color: #ffc107 !important; }
.border-success { border-color: #28a745 !important; }
.border-danger { border-color: #dc3545 !important; }
.border-secondary { border-color: #6c757d !important; }

/* Badges mejorados */
.badge-sm {
    font-size: 0.75em;
    padding: 0.35em 0.6em;
    border-radius: 0.375rem;
}

.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Botones mejorados */
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25;
    border-radius: 0.375rem;
    transition: var(--transition);
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

/* Barras de progreso */
.progress {
    height: 6px;
    border-radius: 3px;
    background-color: rgba(255, 255, 255, 0.2);
}

.progress-bar {
    border-radius: 3px;
    transition: width 0.6s ease;
}

/* Loading overlay mejorado */
#loading-overlay {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

#loading-overlay .spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: 0.3em;
}

/* Modales mejorados */
.modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.modal-body .card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: var(--border-radius);
}

.modal-body .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

/* Tablas mejoradas */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.05em;
}

/* Alertas de debug */
.alert-info {
    border-left: 4px solid #0dcaf0;
    background-color: rgba(13, 202, 240, 0.1);
}

.alert-info a {
    color: #0dcaf0;
    text-decoration: underline;
    font-weight: 500;
}

.alert-info a:hover {
    color: #0a97b0;
    text-decoration: none;
}

/* Toasts mejorados */
.toast-container {
    z-index: 9999;
}

.toast {
    min-width: 350px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
}

/* Animaciones */
@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes slideInRight {
    from { 
        opacity: 0; 
        transform: translateX(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

.toast {
    animation: slideInRight 0.3s ease-out;
}

.badge {
    animation: pulse 2s infinite;
}

/* Responsive design mejorado */
@media (max-width: 1200px) {
    .col-lg-4 {
        margin-bottom: 1rem;
    }
}

@media (max-width: 992px) {
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.75rem !important;
    }
    
    .input-group {
        width: 100% !important;
    }
    
    .card-header .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-header .col-md-4 {
        text-align: left !important;
    }
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
    
    .badge {
        font-size: 0.7em;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .col-md-2 {
        margin-bottom: 0.75rem;
    }
    
    .card-title {
        font-size: 0.9rem;
    }
    
    .text-truncate-2 {
        -webkit-line-clamp: 1;
    }
    
    .d-flex.gap-1 {
        gap: 0.25rem !important;
    }
    
    .btn-group-sm > .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
}

/* Mejoras de accesibilidad */
.btn:focus,
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

.btn:focus-visible {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Estados de carga */
.btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Mejoras visuales adicionales */
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    max-height: 2.8em;
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.shadow-sm {
    box-shadow: var(--shadow-sm) !important;
}

/* Efectos de hover mejorados */
.card-stat:hover .rounded-circle {
    background-color: rgba(var(--bs-primary-rgb), 0.2) !important;
}

.cita-card:hover .border-start {
    border-left-width: 6px !important;
}

/* Indicadores de tiempo */
.text-danger {
    color: #dc3545 !important;
    font-weight: 500;
}

.text-info {
    color: #0dcaf0 !important;
    font-weight: 500;
}

/* Estilos para elementos vacíos */
.text-center.py-5 {
    padding: 3rem 1rem !important;
}

.text-center.py-4 {
    padding: 2rem 1rem !important;
}

/* Optimizaciones de rendimiento */
* {
    box-sizing: border-box;
}

.card,
.btn,
.badge {
    will-change: transform;
}

/* Print styles */
@media print {
    .btn,
    .dropdown,
    #loading-overlay,
    .toast-container,
    .modal,
    .navbar,
    .sidebar {
        display: none !important;
    }
    
    .container-fluid {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 1rem !important;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
    }
    
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
    }
    
    .text-primary,
    .text-success,
    .text-warning,
    .text-danger {
        color: #000 !important;
    }
    
    .page-break {
        page-break-before: always;
    }
}

/* Optimizaciones finales */
.gpu-accelerated {
    transform: translateZ(0);
    backface-visibility: hidden;
    perspective: 1000px;
}

/* Estados de conectividad */
.connection-indicator {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1050;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 500;
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

/* Mejoras de performance */
.card-body {
    contain: layout style;
}

.table-responsive {
    contain: layout;
}

/* Estados de loading específicos */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Estilos para elementos específicos del cronograma médico */
.agenda-profesional {
    border-left: 4px solid var(--bs-primary);
    background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 123, 255, 0.02) 100%);
}

.cita-urgente {
    border-left: 4px solid var(--bs-danger);
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.05) 0%, rgba(220, 53, 69, 0.02) 100%);
}

.cita-completada {
    border-left: 4px solid var(--bs-success);
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(40, 167, 69, 0.02) 100%);
}

/* Indicadores de tiempo real */
.tiempo-real {
    position: relative;
}

.tiempo-real::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
}

/* Estilos para diferentes tipos de modalidad médica */
.modalidad-presencial {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
}

.modalidad-virtual {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    border-left: 4px solid #9c27b0;
}

.modalidad-domicilio {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    border-left: 4px solid #4caf50;
}

/* Tooltips mejorados */
.tooltip-inner {
    background-color: rgba(0, 0, 0, 0.9);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

/* Scrollbars personalizados */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
@endpush

