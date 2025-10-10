{{-- resources/views/usuarios/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle de Usuario')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-user text-primary me-2"></i>
                                Detalle de Usuario
                            </h4>
                            <p class="text-muted mb-0">
                                Información completa del usuario
                            </p>
                        </div>
                        <div>
                            @if($isOnline)
                                <a href="{{ route('usuarios.edit', $usuario['uuid']) }}" class="btn btn-warning me-2">
                                    <i class="fas fa-edit me-1"></i>
                                    Editar
                                </a>
                            @endif
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Columna Izquierda -->
                <div class="col-lg-8">
                    <!-- Datos Personales -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-id-card me-2"></i>
                                Datos Personales
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small">Documento</label>
                                    <p class="mb-0">
                                        <i class="fas fa-id-badge text-primary me-1"></i>
                                        <strong>{{ $usuario['documento'] }}</strong>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Nombre Completo</label>
                                    <p class="mb-0">
                                        <i class="fas fa-user text-primary me-1"></i>
                                        <strong>{{ $usuario['nombre_completo'] }}</strong>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Teléfono</label>
                                    <p class="mb-0">
                                        <i class="fas fa-phone text-primary me-1"></i>
                                        {{ $usuario['telefono'] }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Correo Electrónico</label>
                                    <p class="mb-0">
                                        <i class="fas fa-envelope text-primary me-1"></i>
                                        {{ $usuario['correo'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Acceso -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-key me-2"></i>
                                Datos de Acceso
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small">Usuario (Login)</label>
                                    <p class="mb-0">
                                        <i class="fas fa-user-circle text-success me-1"></i>
                                        <code class="fs-6">{{ $usuario['login'] }}</code>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Último Acceso</label>
                                    <p class="mb-0">
                                        <i class="fas fa-clock text-success me-1"></i>
                                        {{ $usuario['ultimo_acceso'] ?? 'Nunca' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ Datos Profesionales (MEJORADO) -->
                    @if($usuario['es_medico'] ?? false)
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-stethoscope me-2"></i>
                                    Datos Profesionales
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- ✅ Especialidad -->
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block mb-2">Especialidad</label>
                                        @if(isset($usuario['especialidad']) && !empty($usuario['especialidad']))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-briefcase-medical text-warning me-2 fs-5"></i>
                                                <div>
                                                    <strong class="d-block">{{ $usuario['especialidad']['nombre'] }}</strong>
                                                    @if(isset($usuario['especialidad']['uuid']))
                                                        <small class="text-muted">
                                                            <i class="fas fa-fingerprint me-1"></i>
                                                            ID: {{ substr($usuario['especialidad']['uuid'], 0, 8) }}...
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning py-2 mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <small>No especificada</small>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- ✅ Registro Profesional -->
                                    <div class="col-md-6">
                                        <label class="text-muted small d-block mb-2">Registro Profesional</label>
                                        @if(!empty($usuario['registro_profesional']))
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-certificate text-warning me-2 fs-5"></i>
                                                <div>
                                                    <strong class="d-block">{{ $usuario['registro_profesional'] }}</strong>
                                                    <small class="text-muted">Número de registro médico</small>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning py-2 mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <small>No especificado</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- ✅ Información adicional si es médico -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex align-items-center text-success">
                                        <i class="fas fa-user-md me-2"></i>
                                        <small><strong>Usuario habilitado como médico</strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- ✅ Mensaje cuando NO es médico -->
                        <div class="card shadow-sm mb-4 border-secondary">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-info-circle text-secondary fs-3 mb-2"></i>
                                <p class="text-muted mb-0">
                                    Este usuario no tiene rol de médico
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Columna Derecha -->
                <div class="col-lg-4">
                    <!-- Rol y Estado -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tag me-2"></i>
                                Rol y Estado
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted small">Rol</label>
                                <p class="mb-0">
                                    <span class="badge bg-primary fs-6">
                                        <i class="fas fa-user-shield me-1"></i>
                                        {{ $usuario['rol']['nombre'] ?? 'N/A' }}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Estado</label>
                                <p class="mb-0">
                                    @if(($usuario['estado']['nombre'] ?? '') === 'ACTIVO')
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary fs-6">
                                            <i class="fas fa-times-circle me-1"></i>
                                            Inactivo
                                        </span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-muted small">Sede</label>
                                <p class="mb-0">
                                    <i class="fas fa-building text-info me-1"></i>
                                    {{ $usuario['sede']['nombre'] ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ Firma Digital (MEJORADO) -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-signature me-2"></i>
                                Firma Digital
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($usuario['tiene_firma'] ?? false)
                                <!-- ✅ Mostrar firma si existe -->
                                <div class="text-center">
                                    @if(isset($usuario['firma']) && !empty($usuario['firma']))
                                        <div class="border rounded p-3 bg-light mb-3">
                                            <img src="{{ $usuario['firma'] }}" 
                                                 alt="Firma Digital" 
                                                 class="img-fluid"
                                                 style="max-height: 150px; max-width: 100%;">
                                        </div>
                                        
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- Botón Ver en Grande -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary"
                                                    onclick="verFirmaGrande()">
                                                <i class="fas fa-search-plus me-1"></i>
                                                Ver Grande
                                            </button>
                                            
                                            <!-- Botón Eliminar (solo si está online) -->
                                            @if($isOnline)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmarEliminarFirma()">
                                                    <i class="fas fa-trash me-1"></i>
                                                    Eliminar
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <!-- Tiene firma pero no se puede mostrar -->
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <small>Firma registrada en el sistema</small>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!-- ✅ No tiene firma -->
                                <div class="text-center py-4">
                                    <i class="fas fa-signature text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted mb-0">
                                        <small>Este usuario no tiene firma digital registrada</small>
                                    </p>
                                    @if($isOnline && ($usuario['es_medico'] ?? false))
                                        <a href="{{ route('usuarios.edit', $usuario['uuid']) }}" 
                                           class="btn btn-sm btn-outline-primary mt-3">
                                            <i class="fas fa-plus me-1"></i>
                                            Agregar Firma
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información de Registro -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Información de Registro
                            </h6>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-calendar-plus me-1"></i>
                                <strong>Creado:</strong> 
                                @if(isset($usuario['created_at']))
                                    {{ \Carbon\Carbon::parse($usuario['created_at'])->format('d/m/Y H:i') }}
                                @else
                                    N/A
                                @endif
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-calendar-check me-1"></i>
                                <strong>Actualizado:</strong> 
                                @if(isset($usuario['updated_at']))
                                    {{ \Carbon\Carbon::parse($usuario['updated_at'])->format('d/m/Y H:i') }}
                                @else
                                    N/A
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal Ver Firma en Grande -->
<div class="modal fade" id="modalVerFirma" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-signature me-2"></i>
                    Firma Digital - {{ $usuario['nombre_completo'] }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center bg-light">
                @if(isset($usuario['firma']) && !empty($usuario['firma']))
                    <img src="{{ $usuario['firma'] }}" 
                         alt="Firma Digital" 
                         class="img-fluid"
                         style="max-height: 400px;">
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal Eliminar Firma -->
<div class="modal fade" id="modalEliminarFirma" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Eliminar Firma Digital
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center mb-3">
                    ¿Está seguro de eliminar la firma digital de <strong>{{ $usuario['nombre_completo'] }}</strong>?
                </p>
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <small><strong>Advertencia:</strong> Esta acción no se puede deshacer.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <form action="{{ route('usuarios.eliminar-firma', $usuario['uuid']) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Sí, Eliminar Firma
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * ✅ Mostrar firma en grande
 */
function verFirmaGrande() {
    const modal = new bootstrap.Modal(document.getElementById('modalVerFirma'));
    modal.show();
}

/**
 * ✅ Confirmar eliminación de firma
 */
function confirmarEliminarFirma() {
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarFirma'));
    modal.show();
}
</script>
@endpush

@push('styles')
<style>
/* ✅ Estilos adicionales para la vista de detalle */
.card-header h5,
.card-header h6 {
    font-weight: 600;
}

.badge.fs-6 {
    padding: 0.5rem 1rem;
}

code.fs-6 {
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

.border-top {
    border-top: 1px solid #dee2e6 !important;
}

/* Animación para las tarjetas */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Estilo para la imagen de firma */
.card-body img {
    transition: transform 0.3s ease;
}

.card-body img:hover {
    transform: scale(1.05);
    cursor: pointer;
}
</style>
@endpush
