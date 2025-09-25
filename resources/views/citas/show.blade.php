{{-- resources/views/citas/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle de Cita - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Detalle de Cita
                    </h1>
                    <p class="text-muted mb-0">Información completa de la cita médica</p>
                </div>
                
                <div>
                   
                    <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información de la Cita
                    </h5>
                    <span class="badge bg-{{ $cita['estado'] === 'PROGRAMADA' ? 'primary' : ($cita['estado'] === 'ATENDIDA' ? 'success' : ($cita['estado'] === 'CANCELADA' ? 'danger' : 'warning')) }} fs-6">
                        {{ ucfirst(str_replace('_', ' ', strtolower($cita['estado'] ?? 'PROGRAMADA'))) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Información del Paciente -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Paciente
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre Completo:</label>
                                <p class="mb-1">{{ $cita['paciente']['nombre_completo'] ?? 'No disponible' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Documento:</label>
                                <p class="mb-1">{{ $cita['paciente']['documento'] ?? 'No disponible' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Teléfono:</label>
                                <p class="mb-1">{{ $cita['paciente']['telefono'] ?? 'No registrado' }}</p>
                            </div>
                        </div>

                        <!-- Información de la Agenda -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Agenda
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Consultorio:</label>
                                <p class="mb-1">{{ $cita['agenda']['consultorio'] ?? 'No disponible' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Modalidad:</label>
                                <p class="mb-1">
                                    <span class="badge bg-{{ ($cita['agenda']['modalidad'] ?? '') === 'Telemedicina' ? 'info' : 'secondary' }}">
                                        {{ $cita['agenda']['modalidad'] ?? 'No disponible' }}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Etiqueta:</label>
                                <p class="mb-1">{{ $cita['agenda']['etiqueta'] ?? 'No disponible' }}</p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Información de Fechas y Horarios -->
                    <div class="row g-4">
                        <div class="col-md-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clock me-2"></i>Horarios
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha:</label>
                                <p class="mb-1">{{ \Carbon\Carbon::parse($cita['fecha'])->format('d/m/Y') }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hora Inicio:</label>
                                <p class="mb-1">{{ \Carbon\Carbon::parse($cita['fecha_inicio'])->format('H:i') }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hora Fin:</label>
                                <p class="mb-1">{{ \Carbon\Carbon::parse($cita['fecha_final'])->format('H:i') }}</p>
                            </div>
                            @if(!empty($cita['fecha_deseada']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha Deseada:</label>
                                <p class="mb-1">{{ \Carbon\Carbon::parse($cita['fecha_deseada'])->format('d/m/Y') }}</p>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clipboard-list me-2"></i>Detalles Médicos
                            </h6>
                            @if(!empty($cita['motivo']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Motivo:</label>
                                <p class="mb-1">{{ $cita['motivo'] }}</p>
                            </div>
                            @endif
                            @if(!empty($cita['patologia']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Patología:</label>
                                <p class="mb-1">{{ $cita['patologia'] }}</p>
                            </div>
                            @endif
                            @if(!empty($cita['cups_contratado_id']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">CUPS Contratado:</label>
                                <p class="mb-1">{{ $cita['cups_contratado_id'] }}</p>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info me-2"></i>Información Adicional
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Estado:</label>
                                <p class="mb-1">
                                    <span class="badge bg-{{ $cita['estado'] === 'PROGRAMADA' ? 'primary' : ($cita['estado'] === 'ATENDIDA' ? 'success' : ($cita['estado'] === 'CANCELADA' ? 'danger' : 'warning')) }}">
                                        {{ ucfirst(str_replace('_', ' ', strtolower($cita['estado'] ?? 'PROGRAMADA'))) }}
                                    </span>
                                </p>
                            </div>
                          
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sede:</label>
                                <p class="mb-1">{{ $cita['sede']['nombre'] ?? 'Cajibio' }}</p>
                            </div>
                        </div>
                    </div>

                    @if(!empty($cita['nota']))
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-sticky-note me-2"></i>Notas
                            </h6>
                            <div class="alert alert-light">
                                {{ $cita['nota'] }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Panel de Acciones -->
        <div class="col-lg-4">
          
            <!-- Información del Sistema -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Información del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">UUID:</label>
                        <p class="mb-1 font-monospace small">{{ $cita['uuid'] }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Creado:</label>
                        <p class="mb-1">{{ \Carbon\Carbon::parse($cita['created_at'])->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Actualizado:</label>
                        <p class="mb-1">{{ \Carbon\Carbon::parse($cita['updated_at'])->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($isOffline)
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-wifi-slash me-1"></i>
                        Trabajando en modo offline
                    </div>
                    @endif
                </div>
            </div>

          
        </div>
    </div>
</div>

@push('scripts')
<script>


</script>

<style>
@media print {
    .btn, .card-header, nav, .navbar {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
}
</style>
@endpush
@endsection
