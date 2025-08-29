{{-- resources/views/pacientes/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Ver Paciente - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user text-primary me-2"></i>
                        {{ $paciente['primer_nombre'] }} {{ $paciente['segundo_nombre'] }} 
                        {{ $paciente['primer_apellido'] }} {{ $paciente['segundo_apellido'] }}
                    </h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-id-card me-1"></i>{{ $paciente['documento'] }}
                        @if($isOffline)
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-wifi-slash"></i> Offline
                            </span>
                        @endif
                    </p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('pacientes.edit', $paciente['uuid']) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-plus me-1"></i>Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="createCita()">
                                    <i class="fas fa-calendar-plus me-2"></i>Nueva Cita
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="createHistoria()">
                                    <i class="fas fa-file-medical me-2"></i>Nueva Historia
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="printPaciente()">
                                    <i class="fas fa-print me-2"></i>Imprimir
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <a href="{{ route('pacientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <!-- Datos Personales -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Primer Nombre</label>
                                <div class="info-value">{{ $paciente['primer_nombre'] }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Segundo Nombre</label>
                                <div class="info-value">{{ $paciente['segundo_nombre'] ?? '-' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Primer Apellido</label>
                                <div class="info-value">{{ $paciente['primer_apellido'] }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Segundo Apellido</label>
                                <div class="info-value">{{ $paciente['segundo_apellido'] ?? '-' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Documento</label>
                                <div class="info-value">
                                    <strong>{{ $paciente['documento'] }}</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Fecha de Nacimiento</label>
                                <div class="info-value">
                                    {{ \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->format('d/m/Y') }}
                                    <small class="text-muted">
                                        ({{ \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->age }} años)
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Sexo</label>
                                <div class="info-value">
                                    <span class="badge bg-{{ $paciente['sexo'] === 'M' ? 'primary' : 'pink' }}">
                                        {{ $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        @if($paciente['estado_civil'])
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Estado Civil</label>
                                <div class="info-value">{{ ucfirst(strtolower(str_replace('_', ' ', $paciente['estado_civil']))) }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Información de Contacto -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-address-book me-2"></i>Información de Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="info-item">
                                <label class="info-label">Dirección</label>
                                <div class="info-value">{{ $paciente['direccion'] ?? 'No especificada' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Teléfono</label>
                                <div class="info-value">
                                    @if($paciente['telefono'])
                                        <a href="tel:{{ $paciente['telefono'] }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $paciente['telefono'] }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Correo Electrónico</label>
                                <div class="info-value">
                                    @if($paciente['correo'])
                                        <a href="mailto:{{ $paciente['correo'] }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $paciente['correo'] }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Observaciones -->
            @if($paciente['observacion'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Observaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-value">{{ $paciente['observacion'] }}</div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Historial de Citas -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Historial de Citas
                    </h5>
                    <button class="btn btn-sm btn-primary" onclick="loadCitas()">
                        <i class="fas fa-sync-alt me-1"></i>Cargar
                    </button>
                </div>
                <div class="card-body">
                    <div id="citasContainer">
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Haga clic en "Cargar" para ver las citas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Estado del Paciente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Estado del Paciente
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="badge bg-{{ $paciente['estado'] === 'ACTIVO' ? 'success' : 'danger' }} fs-6 px-3 py-2">
                            <i class="fas fa-{{ $paciente['estado'] === 'ACTIVO' ? 'check-circle' : 'times-circle' }} me-2"></i>
                            {{ $paciente['estado'] }}
                        </span>
                    </div>
                    
                    @if($paciente['estado'] === 'INACTIVO')
                        <button class="btn btn-success btn-sm" onclick="activatePaciente()">
                            <i class="fas fa-check me-1"></i>Activar Paciente
                        </button>
                    @else
                        <button class="btn btn-warning btn-sm" onclick="deactivatePaciente()">
                            <i class="fas fa-pause me-1"></i>Desactivar Paciente
                        </button>
                    @endif
                </div>
            </div>
            
            <!-- Información del Sistema -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <label class="info-label">UUID</label>
                        <div class="info-value">
                            <small class="font-monospace">{{ $paciente['uuid'] }}</small>
                        </div>
                    </div>
                    
                    <div class="info-item mb-3">
                        <label class="info-label">Fecha de Registro</label>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse($paciente['fecha_registro'])->format('d/m/Y') }}
                        </div>
                    </div>
                    
                    @if($paciente['fecha_actualizacion'])
                    <div class="info-item mb-3">
                        <label class="info-label">Última Actualización</label>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse($paciente['fecha_actualizacion'])->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($paciente['sync_status']))
                    <div class="info-item">
                        <label class="info-label">Estado de Sincronización</label>
                        <div class="info-value">
                            <span class="badge bg-{{ $paciente['sync_status'] === 'synced' ? 'success' : ($paciente['sync_status'] === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($paciente['sync_status']) }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Acciones Rápidas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="createCita()">
                            <i class="fas fa-calendar-plus me-2"></i>Agendar Cita
                        </button>
                        
                        <button class="btn btn-outline-info" onclick="createHistoria()">
                            <i class="fas fa-file-medical me-2"></i>Nueva Historia
                        </button>
                        
                        <button class="btn btn-outline-success" onclick="generateReport()">
                            <i class="fas fa-chart-line me-2"></i>Generar Reporte
                        </button>
                        
                        <hr>
                        
                        <button class="btn btn-outline-danger" onclick="deletePaciente()">
                            <i class="fas fa-trash me-2"></i>Eliminar Paciente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.info-item {
    margin-bottom: 1rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}

.info-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.bg-pink {
    background-color: #e91e63 !important;
}
</style>
@endpush

@push('scripts')
<script>
// Cargar citas del paciente
function loadCitas() {
    const container = document.getElementById('citasContainer');
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando citas...</p></div>';
    
    // Simular carga de citas (aquí harías la petición real)
    setTimeout(() => {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay citas registradas</p>
                <button class="btn btn-primary btn-sm" onclick="createCita()">
                    <i class="fas fa-plus me-1"></i>Crear Primera Cita
                </button>
            </div>
        `;
    }, 1500);
}

// Crear nueva cita
function createCita() {
    showAlert('info', 'Función de citas en desarrollo', 'Próximamente');
}

// Crear nueva historia clínica
function createHistoria() {
    showAlert('info', 'Función de historias clínicas en desarrollo', 'Próximamente');
}

// Generar reporte
function generateReport() {
    showAlert('info', 'Función de reportes en desarrollo', 'Próximamente');
}

// Imprimir paciente
function printPaciente() {
    window.print();
}

// Activar paciente
function activatePaciente() {
    Swal.fire({
        title: '¿Activar Paciente?',
        text: 'El paciente volverá a estar disponible en el sistema',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            updatePacienteStatus('ACTIVO');
        }
    });
}

// Desactivar paciente
function deactivatePaciente() {
    Swal.fire({
        title: '¿Desactivar Paciente?',
        text: 'El paciente no aparecerá en búsquedas regulares',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            updatePacienteStatus('INACTIVO');
        }
    });
}

// Actualizar estado del paciente
function updatePacienteStatus(estado) {
    fetch(`/pacientes/{{ $paciente['uuid'] }}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Paciente ${estado.toLowerCase()} exitosamente`);
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('error', data.error || 'Error actualizando estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
    });
}

// Eliminar paciente
function deletePaciente() {
    Swal.fire({
        title: '¿Eliminar Paciente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/pacientes/{{ $paciente['uuid'] }}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = '{{ route("pacientes.index") }}';
                    }, 2000);
                } else {
                    showAlert('error', data.error || 'Error eliminando paciente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error de conexión');
            });
        }
    });
}
</script>
@endpush
@endsection
