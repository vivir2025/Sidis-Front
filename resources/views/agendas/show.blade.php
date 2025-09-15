{{-- resources/views/agendas/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalles de Agenda - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('agendas.index') }}">
                                    <i class="fas fa-calendar-alt"></i> Agendas
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Detalles</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Detalles de Agenda
                    </h1>
                    <p class="text-muted mb-0">Información completa de la agenda médica</p>
                </div>
                
                <!-- Header Actions -->
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
                    
                    <!-- Botones de Acción -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarAgenda('{{ $agenda['uuid'] }}', '{{ $agenda['fecha'] }} - {{ $agenda['consultorio'] }}')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                    
                    <a href="{{ route('agendas.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <!-- Datos Básicos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Básica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Fecha y Horario -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-calendar text-primary me-2"></i>Fecha
                                </label>
                                <div class="info-value">
                                    <span class="fw-bold fs-5" id="fechaAgenda">{{ $agenda['fecha'] ?? 'No disponible' }}</span>
                                    <div class="text-muted small" id="diaSemana">
                                        <!-- Se llena con JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-clock text-primary me-2"></i>Horario
                                </label>
                                <div class="info-value">
                                    <span class="fw-bold fs-5">
                                        {{ $agenda['hora_inicio'] ?? '--:--' }} - {{ $agenda['hora_fin'] ?? '--:--' }}
                                    </span>
                                    <div class="text-muted small">
                                        Intervalo: {{ $agenda['intervalo'] ?? '15' }} minutos
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Consultorio y Modalidad -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-door-open text-primary me-2"></i>Consultorio
                                </label>
                                <div class="info-value">
                                    <span class="fw-bold">{{ $agenda['consultorio'] ?? 'Sin asignar' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-laptop-medical text-primary me-2"></i>Modalidad
                                </label>
                                <div class="info-value">
                                    <span class="badge {{ ($agenda['modalidad'] ?? '') === 'Telemedicina' ? 'bg-info' : 'bg-secondary' }} fs-6">
                                        {{ $agenda['modalidad'] ?? 'Ambulatoria' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Etiqueta y Estado -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-tag text-primary me-2"></i>Etiqueta
                                </label>
                                <div class="info-value">
                                    <span class="fw-bold">{{ $agenda['etiqueta'] ?? 'Sin etiqueta' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-flag text-primary me-2"></i>Estado
                                </label>
                                <div class="info-value">
                                    @php
                                        $estado = $agenda['estado'] ?? 'ACTIVO';
                                        $badgeClass = match($estado) {
                                            'ACTIVO' => 'bg-success',
                                            'ANULADA' => 'bg-danger',
                                            'LLENA' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} fs-6">{{ $estado }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Información Adicional
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Proceso -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-cogs text-primary me-2"></i>Proceso
                                </label>
                                <div class="info-value">
                                    @if(!empty($agenda['proceso']['nombre']))
                                        <span class="fw-bold">{{ $agenda['proceso']['nombre'] }}</span>
                                        @if(!empty($agenda['proceso']['n_cups']))
                                            <div class="text-muted small">CUPS: {{ $agenda['proceso']['n_cups'] }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">No asignado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Brigada -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-users text-primary me-2"></i>Brigada
                                </label>
                                <div class="info-value">
                                    @if(!empty($agenda['brigada']['nombre']))
                                        <span class="fw-bold">{{ $agenda['brigada']['nombre'] }}</span>
                                    @else
                                        <span class="text-muted">No asignada</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Usuario Médico -->
<div class="col-md-6">
    <div class="info-item">
        <label class="info-label">
            <i class="fas fa-user-md text-primary me-2"></i>Usuario Médico
        </label>
        <div class="info-value">
            @if(!empty($agenda['usuario_medico']['nombre_completo']))
                <span class="fw-bold">{{ $agenda['usuario_medico']['nombre_completo'] }}</span>
                @if(!empty($agenda['usuario_medico']['especialidad']['nombre']))
                    <div class="text-muted small">{{ $agenda['usuario_medico']['especialidad']['nombre'] }}</div>
                @endif
            @else
                <span class="text-muted">No asignado</span>
            @endif
        </div>
    </div>
</div>

                        
                        <!-- Usuario Creador -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-user text-primary me-2"></i>Creado por
                                </label>
                                <div class="info-value">
                                    @if(!empty($agenda['usuario']['nombre_completo']))
                                        <span class="fw-bold">{{ $agenda['usuario']['nombre_completo'] }}</span>
                                    @else
                                        <span class="text-muted">Usuario no disponible</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sede -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-building text-primary me-2"></i>Sede
                                </label>
                                <div class="info-value">
                                    @if(!empty($agenda['sede']['nombre']))
                                        <span class="fw-bold">{{ $agenda['sede']['nombre'] }}</span>
                                    @else
                                        <span class="text-muted">Sede no disponible</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Citas de la Agenda -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Citas Programadas
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="refreshCitas()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <!-- Loading Citas -->
                    <div id="loadingCitas" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando citas...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando citas...</p>
                    </div>

                    <!-- Lista de Citas -->
                    <div id="citasContainer">
                        <!-- Se llena dinámicamente -->
                    </div>

                    <!-- Estado vacío -->
                    <div id="citasVacio" class="text-center py-4" style="display: none;">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No hay citas programadas</h6>
                        <p class="text-muted small">Esta agenda aún no tiene citas asignadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Resumen de Cupos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Resumen de Cupos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <canvas id="cuposChart" width="150" height="150"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div class="fw-bold fs-4" id="cuposDisponiblesNum">0</div>
                                <div class="small text-muted">Disponibles</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-primary fs-5" id="totalCupos">0</div>
                                <div class="small text-muted">Total</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-danger fs-5" id="cuposOcupados">0</div>
                                <div class="small text-muted">Ocupados</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-success fs-5" id="cuposLibres">0</div>
                                <div class="small text-muted">Libres</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="progress" style="height: 20px;">
                            <div id="progressOcupados" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            <div id="progressDisponibles" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>Ocupación: <span id="porcentajeOcupacion">0%</span></span>
                            <span>Disponibilidad: <span id="porcentajeDisponibilidad">100%</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/citas/create?agenda={{ $agenda['uuid'] }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nueva Cita
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="exportarAgenda()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info me-2"></i>
                        Información del Sistema
                    </h6>
                </div>
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-12">
                            <strong>UUID:</strong>
                            <code class="small">{{ $agenda['uuid'] ?? 'No disponible' }}</code>
                        </div>
                        <div class="col-6">
                            <strong>Creado:</strong><br>
                            <span class="text-muted" id="fechaCreacion">
                                {{ isset($agenda['created_at']) ? \Carbon\Carbon::parse($agenda['created_at'])->format('d/m/Y H:i') : 'No disponible' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <strong>Actualizado:</strong><br>
                            <span class="text-muted" id="fechaActualizacion">
                                {{ isset($agenda['updated_at']) ? \Carbon\Carbon::parse($agenda['updated_at'])->format('d/m/Y H:i') : 'No disponible' }}
                            </span>
                        </div>
                        @if($isOffline)
                        <div class="col-12 mt-2">
                            <div class="alert alert-warning alert-sm mb-0">
                                <i class="fas fa-wifi-slash me-1"></i>
                                <small>Datos desde almacenamiento local</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.info-item {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
    height: 100%;
}

.info-label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.info-value {
    font-size: 1rem;
    color: #212529;
}

.cita-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background-color: #ffffff;
    transition: all 0.2s ease;
}

.cita-item:hover {
    border-color: #007bff;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 123, 255, 0.075);
}

.estado-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

@media print {
    .btn, .card-header .btn, .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        break-inside: avoid;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
}

.chart-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto;
}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
const agendaUuid = '{{ $agenda["uuid"] }}';
const agendaFecha = '{{ $agenda["fecha"] ?? "" }}';
let cuposChart = null;
let citasData = [];

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando vista de agenda');
    
    // Formatear fecha y día de la semana
    formatearFechaAgenda();
    
    // ✅ CARGAR CUPOS REALES USANDO EL MISMO MÉTODO QUE EN INDEX
    cargarCuposRealesAgenda();
    
    // Inicializar gráfico
    initCuposChart();
    
    // Cargar citas
    loadCitas();
});

// ✅ NUEVA FUNCIÓN: Cargar cupos reales usando el mismo método que en index
async function cargarCuposRealesAgenda() {
    try {
        console.log('🔍 Cargando cupos reales para agenda:', agendaUuid);
        
        // ✅ USAR LA MISMA FUNCIÓN QUE EN EL INDEX Y CREATE
        const horariosReales = await obtenerHorariosRealesAgenda(agendaUuid, agendaFecha);
        
        const cuposDisponibles = horariosReales.disponibles;
        const cuposTotales = horariosReales.total;
        const cuposOcupados = horariosReales.ocupados;
        
        console.log('✅ Cupos reales obtenidos:', {
            disponibles: cuposDisponibles,
            total: cuposTotales,
            ocupados: cuposOcupados
        });
        
        // ✅ CREAR OBJETO DE DATOS COMPATIBLE
        const cuposData = {
            total_cupos: cuposTotales,
            citas_count: cuposOcupados,
            cupos_disponibles: cuposDisponibles
        };
        
        // Actualizar displays
        updateCuposDisplay(cuposData);
        updateCuposChart(cuposData);
        
    } catch (error) {
        console.error('❌ Error cargando cupos reales:', error);
        
        // ✅ FALLBACK A DATOS DEL BACKEND COMO ANTES
        const defaultData = {
            citas_count: {{ $agenda['citas_count'] ?? 0 }},
            total_cupos: {{ $agenda['total_cupos'] ?? 0 }},
            cupos_disponibles: {{ $agenda['cupos_disponibles'] ?? 0 }}
        };
        
        console.log('📊 Usando datos de fallback:', defaultData);
        updateCuposDisplay(defaultData);
        updateCuposChart(defaultData);
        
        if (defaultData.total_cupos > 0) {
            console.log('✅ Usando datos locales válidos');
        } else {
            showAlert('warning', 'No se pudieron cargar los datos de cupos actualizados.', 'Advertencia');
        }
    }
}

// ✅ FUNCIÓN REUTILIZADA: Obtener horarios reales (misma que en index y create)
async function obtenerHorariosRealesAgenda(agendaUuid, fecha) {
    try {
        console.log('🔍 Obteniendo horarios reales para agenda:', agendaUuid, 'fecha:', fecha);
        
        const response = await fetch(`/citas/agenda/${agendaUuid}/horarios?fecha=${fecha}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success && data.data) {
            const disponibles = data.data.filter(h => h.disponible).length;
            const total = data.data.length;
            
            console.log('✅ Horarios reales obtenidos:', {
                agenda_uuid: agendaUuid,
                disponibles,
                total,
                ocupados: total - disponibles
            });
            
            return {
                disponibles,
                total,
                ocupados: total - disponibles
            };
        }
        
        console.warn('⚠️ No se pudieron obtener horarios reales');
        return { disponibles: 0, total: 0, ocupados: 0 };
        
    } catch (error) {
        console.error('❌ Error obteniendo horarios reales:', error);
        return { disponibles: 0, total: 0, ocupados: 0 };
    }
}

// Formatear fecha de la agenda
function formatearFechaAgenda() {
    const fechaElement = document.getElementById('fechaAgenda');
    const diaSemanaElement = document.getElementById('diaSemana');
    
    if (!fechaElement) return;
    
    const fechaStr = '{{ $agenda["fecha"] ?? "" }}';
    if (!fechaStr) return;
    
    try {
        const partes = fechaStr.split('-');
        const fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        
        // Formatear fecha
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
        
        // Día de la semana
        const diaSemana = fecha.toLocaleDateString('es-ES', { weekday: 'long' });
        
        fechaElement.textContent = fechaFormateada;
        if (diaSemanaElement) {
            diaSemanaElement.textContent = diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1);
        }
        
    } catch (error) {
        console.error('Error formateando fecha:', error);
    }
}

// ✅ FUNCIÓN MODIFICADA: Actualizar display de cupos con datos reales
function updateCuposDisplay(data) {
    const totalCupos = data.total_cupos || 0;
    const cuposOcupados = data.citas_count || 0;
    const cuposDisponibles = data.cupos_disponibles || (totalCupos - cuposOcupados);
    
    console.log('📊 Actualizando display de cupos:', {
        total: totalCupos,
        ocupados: cuposOcupados,
        disponibles: cuposDisponibles
    });
    
    // Actualizar números
    document.getElementById('totalCupos').textContent = totalCupos;
    document.getElementById('cuposOcupados').textContent = cuposOcupados;
    document.getElementById('cuposLibres').textContent = cuposDisponibles;
    document.getElementById('cuposDisponiblesNum').textContent = cuposDisponibles;
    
    // Calcular porcentajes
    const porcentajeOcupacion = totalCupos > 0 ? Math.round((cuposOcupados / totalCupos) * 100) : 0;
    const porcentajeDisponibilidad = 100 - porcentajeOcupacion;
    
    document.getElementById('porcentajeOcupacion').textContent = porcentajeOcupacion + '%';
    document.getElementById('porcentajeDisponibilidad').textContent = porcentajeDisponibilidad + '%';
    
    // Actualizar barra de progreso
    document.getElementById('progressOcupados').style.width = porcentajeOcupacion + '%';
    document.getElementById('progressDisponibles').style.width = porcentajeDisponibilidad + '%';
    
    // ✅ ACTUALIZAR COLORES SEGÚN DISPONIBILIDAD
    const cuposDisponiblesElement = document.getElementById('cuposDisponiblesNum');
    const cuposLibresElement = document.getElementById('cuposLibres');
    
    if (cuposDisponibles <= 0) {
        cuposDisponiblesElement.className = 'fw-bold fs-4 text-danger';
        cuposLibresElement.className = 'fw-bold text-danger fs-5';
    } else if (cuposDisponibles <= 3) {
        cuposDisponiblesElement.className = 'fw-bold fs-4 text-warning';
        cuposLibresElement.className = 'fw-bold text-warning fs-5';
    } else {
        cuposDisponiblesElement.className = 'fw-bold fs-4 text-success';
        cuposLibresElement.className = 'fw-bold text-success fs-5';
    }
    
    console.log('✅ Display de cupos actualizado:', {
        total: totalCupos,
        ocupados: cuposOcupados,
        disponibles: cuposDisponibles,
        porcentajeOcupacion: porcentajeOcupacion
    });
}

// Inicializar gráfico de cupos
function initCuposChart() {
    const ctx = document.getElementById('cuposChart');
    if (!ctx) return;
    
    cuposChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ocupados', 'Disponibles'],
            datasets: [{
                data: [0, 100],
                backgroundColor: ['#dc3545', '#28a745'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return `${label}: ${value}%`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// Actualizar gráfico de cupos
function updateCuposChart(data) {
    if (!cuposChart) return;
    
    const totalCupos = data.total_cupos || 0;
    const cuposOcupados = data.citas_count || 0;
    
    const porcentajeOcupacion = totalCupos > 0 ? Math.round((cuposOcupados / totalCupos) * 100) : 0;
    const porcentajeDisponibilidad = 100 - porcentajeOcupacion;
    
    cuposChart.data.datasets[0].data = [porcentajeOcupacion, porcentajeDisponibilidad];
    cuposChart.update();
    
    console.log('📊 Gráfico actualizado:', {
        ocupacion: porcentajeOcupacion + '%',
        disponibilidad: porcentajeDisponibilidad + '%'
    });
}

async function loadCitas() {
    const loadingElement = document.getElementById('loadingCitas');
    const containerElement = document.getElementById('citasContainer');
    const vacioElement = document.getElementById('citasVacio');
    
    try {
        // Mostrar loading
        if (loadingElement) loadingElement.style.display = 'block';
        if (containerElement) containerElement.style.display = 'none';
        if (vacioElement) vacioElement.style.display = 'none';
        
        console.log('📋 Cargando citas para agenda:', agendaUuid);
        
        const response = await fetch(`/agendas/${agendaUuid}/citas`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        console.log('📋 Respuesta de citas - Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('📋 Datos de citas recibidos:', data);
        
        // ✅ VERIFICAR ESTRUCTURA DE RESPUESTA CORRECTAMENTE
        if (data.success) {
            citasData = data.data || [];
            
            // ✅ VERIFICAR SI HAY CITAS
            if (Array.isArray(citasData) && citasData.length > 0) {
                console.log('✅ Citas encontradas:', citasData.length);
                displayCitas(citasData);
            } else {
                console.log('ℹ️ No hay citas para mostrar');
                showCitasEmpty();
            }
        } else {
            throw new Error(data.message || 'Error desconocido cargando citas');
        }
        
    } catch (error) {
        console.error('❌ Error cargando citas:', error);
        
        try {
            console.log('🔄 Intentando usar citas desde datos locales...');
            
            @if(isset($agenda['citas']) && is_array($agenda['citas']))
                const citasLocales = @json($agenda['citas']);
                console.log('📋 Usando citas locales:', citasLocales);
                
                if (Array.isArray(citasLocales) && citasLocales.length > 0) {
                    citasData = citasLocales;
                    displayCitas(citasData);
                } else {
                    console.log('📋 No hay citas locales');
                    showCitasEmpty();
                }
            @else
                console.log('📋 No hay citas locales disponibles');
                showCitasEmpty();
            @endif
            
        } catch (localError) {
            console.error('❌ Error cargando citas locales:', localError);
            showCitasError('Error cargando citas: ' + error.message);
        }
        
    } finally {
        if (loadingElement) loadingElement.style.display = 'none';
    }
}

// ✅ FUNCIÓN MODIFICADA: Actualizar datos con cupos reales
function refreshCitas() {
    console.log('🔄 Refrescando datos de agenda...');
    
    // ✅ RECARGAR CUPOS REALES
    cargarCuposRealesAgenda();
    
    // Recargar citas
    loadCitas();
}

// Mostrar estado vacío sin error
function showCitasEmpty() {
    const container = document.getElementById('citasContainer');
    const vacio = document.getElementById('citasVacio');
    
    if (container) container.style.display = 'none';
    if (vacio) vacio.style.display = 'block';
}

// Mostrar citas en el contenedor
function displayCitas(citas) {
    const container = document.getElementById('citasContainer');
    const vacio = document.getElementById('citasVacio');
    
    if (!citas || citas.length === 0) {
        container.style.display = 'none';
        vacio.style.display = 'block';
        return;
    }
    
    container.innerHTML = '';
    
    // Ordenar citas por hora
    citas.sort((a, b) => {
        const horaA = a.fecha_inicio || a.hora_cita || '';
        const horaB = b.fecha_inicio || b.hora_cita || '';
        return horaA.localeCompare(horaB);
    });
    
    citas.forEach(cita => {
        const citaElement = createCitaElement(cita);
        container.appendChild(citaElement);
    });
    
    container.style.display = 'block';
    vacio.style.display = 'none';
    
    console.log(`✅ ${citas.length} citas mostradas`);
}

// Crear elemento de cita
function createCitaElement(cita) {
    const div = document.createElement('div');
    div.className = 'cita-item';
    div.setAttribute('data-cita-uuid', cita.uuid);
    
    // Formatear hora
    let hora = 'Sin hora';
    try {
        if (cita.fecha_inicio) {
            const fechaInicio = new Date(cita.fecha_inicio);
            hora = fechaInicio.toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        } else if (cita.hora_cita) {
            hora = cita.hora_cita;
        }
    } catch (error) {
        console.error('Error formateando hora de cita:', error);
    }
    
    // Estado de la cita
    const estadoBadge = getCitaEstadoBadge(cita.estado);
    
    // Información del paciente
    const pacienteInfo = getPacienteInfo(cita);
    
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <div class="me-3">
                        <i class="fas fa-clock text-primary me-1"></i>
                        <strong>${hora}</strong>
                    </div>
                    <div>
                        ${estadoBadge}
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="fw-semibold">
                        <i class="fas fa-user text-muted me-1"></i>
                        ${pacienteInfo.nombre}
                    </div>
                    ${pacienteInfo.documento ? `<small class="text-muted">Doc: ${pacienteInfo.documento}</small>` : ''}
                </div>
                
                ${cita.motivo ? `
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-comment-medical me-1"></i>
                            ${cita.motivo}
                        </small>
                    </div>
                ` : ''}
                
                ${cita.nota ? `
                    <div class="mb-2">
                        <small class="text-info">
                            <i class="fas fa-sticky-note me-1"></i>
                            ${cita.nota}
                        </small>
                    </div>
                ` : ''}
            </div>
            
            <div class="ms-3">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-info" onclick="verCita('${cita.uuid}')" title="Ver cita">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="editarCita('${cita.uuid}')" title="Editar cita">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return div;
}

// Obtener badge de estado de cita
function getCitaEstadoBadge(estado) {
    const badges = {
        'PROGRAMADA': '<span class="badge bg-primary estado-badge">Programada</span>',
        'EN_ATENCION': '<span class="badge bg-warning estado-badge">En Atención</span>',
        'ATENDIDA': '<span class="badge bg-success estado-badge">Atendida</span>',
        'CANCELADA': '<span class="badge bg-danger estado-badge">Cancelada</span>',
        'NO_ASISTIO': '<span class="badge bg-secondary estado-badge">No Asistió</span>',
        'REAGENDADA': '<span class="badge bg-info estado-badge">Reagendada</span>'
    };
    
    return badges[estado] || '<span class="badge bg-secondary estado-badge">Desconocido</span>';
}

// Obtener información del paciente
function getPacienteInfo(cita) {
    let nombre = 'Paciente no identificado';
    let documento = '';
    
    if (cita.paciente) {
        if (cita.paciente.nombre_completo) {
            nombre = cita.paciente.nombre_completo;
        } else if (cita.paciente.nombre && cita.paciente.apellido) {
            nombre = `${cita.paciente.nombre} ${cita.paciente.apellido}`;
        }
        
        if (cita.paciente.documento) {
            documento = cita.paciente.documento;
        }
    }
    
    return { nombre, documento };
}

// Mostrar error en citas
function showCitasError(message) {
    const container = document.getElementById('citasContainer');
    const vacio = document.getElementById('citasVacio');
    
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
    
    container.style.display = 'block';
    vacio.style.display = 'none';
}

// Acciones de citas
function verCita(uuid) {
    window.location.href = `/citas/${uuid}`;
}

function editarCita(uuid) {
    window.location.href = `/citas/${uuid}/edit`;
}

// Acciones de agenda
async function eliminarAgenda(uuid, descripcion) {
    const result = await Swal.fire({
        title: '¿Eliminar Agenda?',
        html: `¿Está seguro que desea eliminar la agenda:<br><strong>${descripcion}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`/agendas/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('¡Eliminado!', data.message, 'success').then(() => {
                    window.location.href = '/agendas';
                });
            } else {
                throw new Error(data.error);
            }

        } catch (error) {
            console.error('Error eliminando agenda:', error);
            Swal.fire('Error', 'Error eliminando agenda: ' + error.message, 'error');
        }
    }
}

function exportarAgenda() {
    Swal.fire({
        title: 'Exportar Agenda',
        text: 'Seleccione el formato de exportación',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'PDF',
        cancelButtonText: 'Excel',
        showDenyButton: true,
        denyButtonText: 'Cancelar',
        denyButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(`/agendas/${agendaUuid}/export/pdf`, '_blank');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            window.open(`/agendas/${agendaUuid}/export/excel`, '_blank');
        }
    });
}

// Utilidades
function showAlert(type, message, title = '') {
    const iconMap = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    Swal.fire({
        icon: iconMap[type] || 'info',
        title: title || (type === 'error' ? 'Error' : 'Información'),
        text: message,
        timer: type === 'success' ? 3000 : undefined,
        showConfirmButton: type !== 'success'
    });
}

// ✅ FUNCIONES DE DEBUG PARA DESARROLLO
window.debugAgenda = function() {
    console.log('=== 🔍 DEBUG AGENDA SHOW ===');
    console.log('Agenda UUID:', agendaUuid);
    console.log('Agenda Fecha:', agendaFecha);
    console.log('Citas cargadas:', citasData.length);
    console.log('=== FIN DEBUG ===');
};

window.refreshCuposManual = function() {
    console.log('🔄 Refresh manual de cupos...');
    cargarCuposRealesAgenda();
};

console.log('✅ Dashboard de agenda inicializado');
console.log('🔧 Funciones de debug disponibles:');
console.log('  - debugAgenda() - Información general');
console.log('  - refreshCuposManual() - Refrescar cupos manualmente');
</script>
@endpush
@endsection
