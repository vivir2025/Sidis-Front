{{-- resources/views/pacientes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Paciente - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Editar Paciente
                    </h1>
                    <p class="text-muted mb-0">
                        Modificar información de {{ $paciente['primer_nombre'] }} {{ $paciente['primer_apellido'] }}
                        <span class="badge bg-secondary ms-2">{{ $paciente['documento'] }}</span>
                    </p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @endif
                    
                    <a href="{{ route('pacientes.show', $paciente['uuid']) }}" class="btn btn-info">
                        <i class="fas fa-eye me-1"></i>Ver Paciente
                    </a>
                    
                    <a href="{{ route('pacientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Offline -->
    @if($isOffline)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Modo Offline</strong>
                    <p class="mb-0">Los cambios se guardarán localmente y se sincronizarán cuando vuelva la conexión.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Formulario -->
    <form id="pacienteEditForm" method="POST" action="{{ route('pacientes.update', $paciente['uuid']) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Información Personal -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Información Personal
                        </h5>
                        <small class="text-muted">Campos obligatorios marcados con *</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Nombres -->
                            <div class="col-md-6">
                                <label for="primer_nombre" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_nombre') is-invalid @enderror" 
                                       id="primer_nombre" name="primer_nombre" 
                                       value="{{ old('primer_nombre', $paciente['primer_nombre']) }}" required>
                                @error('primer_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control @error('segundo_nombre') is-invalid @enderror" 
                                       id="segundo_nombre" name="segundo_nombre" 
                                       value="{{ old('segundo_nombre', $paciente['segundo_nombre']) }}">
                                @error('segundo_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Apellidos -->
                            <div class="col-md-6">
                                <label for="primer_apellido" class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_apellido') is-invalid @enderror" 
                                       id="primer_apellido" name="primer_apellido" 
                                       value="{{ old('primer_apellido', $paciente['primer_apellido']) }}" required>
                                @error('primer_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                                <input type="text" class="form-control @error('segundo_apellido') is-invalid @enderror" 
                                       id="segundo_apellido" name="segundo_apellido" 
                                       value="{{ old('segundo_apellido', $paciente['segundo_apellido']) }}">
                                @error('segundo_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Documento -->
                            <div class="col-md-6">
                                <label for="documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('documento') is-invalid @enderror" 
                                           id="documento" name="documento" 
                                           value="{{ old('documento', $paciente['documento']) }}" required>
                                    <button type="button" class="btn btn-outline-info" onclick="validateDocument()" title="Validar documento">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @error('documento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Verifique que el documento sea único</small>
                            </div>
                            
                            <!-- Fecha Nacimiento -->
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror" 
                                       id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="{{ old('fecha_nacimiento', \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->format('Y-m-d')) }}" required>
                                @error('fecha_nacimiento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted" id="edadCalculada"></small>
                            </div>
                            
                            <!-- Sexo -->
                            <div class="col-md-6">
                                <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                                <select class="form-select @error('sexo') is-invalid @enderror" id="sexo" name="sexo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="M" {{ old('sexo', $paciente['sexo']) == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('sexo', $paciente['sexo']) == 'F' ? 'selected' : '' }}>Femenino</option>
                                </select>
                                @error('sexo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Estado Civil -->
                            <div class="col-md-6">
                                <label for="estado_civil" class="form-label">Estado Civil</label>
                                <select class="form-select @error('estado_civil') is-invalid @enderror" id="estado_civil" name="estado_civil">
                                    <option value="">Seleccione...</option>
                                    <option value="SOLTERO" {{ old('estado_civil', $paciente['estado_civil']) == 'SOLTERO' ? 'selected' : '' }}>Soltero(a)</option>
                                    <option value="CASADO" {{ old('estado_civil', $paciente['estado_civil']) == 'CASADO' ? 'selected' : '' }}>Casado(a)</option>
                                    <option value="UNION_LIBRE" {{ old('estado_civil', $paciente['estado_civil']) == 'UNION_LIBRE' ? 'selected' : '' }}>Unión Libre</option>
                                    <option value="DIVORCIADO" {{ old('estado_civil', $paciente['estado_civil']) == 'DIVORCIADO' ? 'selected' : '' }}>Divorciado(a)</option>
                                    <option value="VIUDO" {{ old('estado_civil', $paciente['estado_civil']) == 'VIUDO' ? 'selected' : '' }}>Viudo(a)</option>
                                </select>
                                @error('estado_civil')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                            <div class="col-md-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror" 
                                       id="direccion" name="direccion" 
                                       value="{{ old('direccion', $paciente['direccion']) }}">
                                @error('direccion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                       id="telefono" name="telefono" 
                                       value="{{ old('telefono', $paciente['telefono']) }}">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control @error('correo') is-invalid @enderror" 
                                       id="correo" name="correo" 
                                       value="{{ old('correo', $paciente['correo']) }}">
                                @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Observaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observaciones Generales</label>
                            <textarea class="form-control @error('observacion') is-invalid @enderror" 
                                      id="observacion" name="observacion" rows="3">{{ old('observacion', $paciente['observacion']) }}</textarea>
                            @error('observacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Lateral -->
            <div class="col-lg-4">
                <!-- Información del Sistema -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Información del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">UUID</label>
                            <input type="text" class="form-control font-monospace" value="{{ $paciente['uuid'] }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sede</label>
                            <input type="text" class="form-control" value="{{ $usuario['sede']['nombre'] ?? 'No asignada' }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" 
                                   value="{{ \Carbon\Carbon::parse($paciente['fecha_registro'])->format('d/m/Y') }}" readonly>
                        </div>
                        
                        @if($paciente['fecha_actualizacion'])
                        <div class="mb-3">
                            <label class="form-label">Última Actualización</label>
                            <input type="text" class="form-control" 
                                   value="{{ \Carbon\Carbon::parse($paciente['fecha_actualizacion'])->format('d/m/Y H:i') }}" readonly>
                        </div>
                        @endif
                        
                        <div class="mb-3">
                            <label class="form-label">Estado Actual</label>
                            <div>
                                <span class="badge bg-{{ $paciente['estado'] === 'ACTIVO' ? 'success' : 'danger' }} fs-6">
                                    {{ $paciente['estado'] }}
                                </span>
                            </div>
                        </div>
                        
                        @if($isOffline)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>Los cambios se sincronizarán automáticamente cuando vuelva la conexión.</small>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Historial de Cambios -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Historial de Cambios
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="historialCambios">
                            <div class="text-center py-3">
                                <i class="fas fa-clock text-muted"></i>
                                <p class="text-muted mb-0 mt-2">Sin cambios recientes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                            
                            <button type="button" class="btn btn-warning" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Deshacer Cambios
                            </button>
                            
                            <hr>
                            
                            <a href="{{ route('pacientes.show', $paciente['uuid']) }}" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Ver Paciente
                            </a>
                            
                            <a href="{{ route('pacientes.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Variables originales para detectar cambios
const originalData = @json($paciente);
let hasChanges = false;

// Manejar envío del formulario
document.getElementById('pacienteEditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    
    // Obtener datos del formulario
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message || 'Paciente actualizado exitosamente');
            
            if (data.offline) {
                showAlert('info', 'Cambios guardados localmente. Se sincronizarán cuando vuelva la conexión.', 'Modo Offline');
            }
            
            // Actualizar historial de cambios
            addToHistorial('Información actualizada', 'success');
            
            // Redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = `/pacientes/{{ $paciente['uuid'] }}`;
            }, 2000);
        } else {
            showAlert('error', data.error || 'Error al actualizar paciente');
            
            // Mostrar errores de validación si existen
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback') || 
                                       document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = data.errors[field][0];
                        if (!input.parentNode.querySelector('.invalid-feedback')) {
                            input.parentNode.appendChild(feedback);
                        }
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión al actualizar paciente');
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Detectar cambios en el formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pacienteEditForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            detectChanges();
        });
        
        input.addEventListener('input', function() {
            detectChanges();
        });
    });
    
    // Calcular edad inicial
    calculateAge();
});

// Detectar cambios en el formulario
function detectChanges() {
    const form = document.getElementById('pacienteEditForm');
    const formData = new FormData(form);
    const currentData = {};
    
    for (let [key, value] of formData.entries()) {
        currentData[key] = value;
    }
    
    // Comparar con datos originales
    const changed = Object.keys(currentData).some(key => {
        return currentData[key] !== (originalData[key] || '');
    });
    
    hasChanges = changed;
    
    // Actualizar UI si hay cambios
    const submitBtn = document.getElementById('submitBtn');
    if (hasChanges) {
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-warning');
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Guardar Cambios';
    } else {
        submitBtn.classList.remove('btn-warning');
        submitBtn.classList.add('btn-primary');
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Cambios';
    }
}

// Validar documento
function validateDocument() {
    const documento = document.getElementById('documento').value.trim();
    
    if (!documento) {
        showAlert('warning', 'Debe ingresar un documento para validar');
        return;
    }
    
    if (documento === originalData.documento) {
        showAlert('info', 'Este es el documento actual del paciente');
        return;
    }
    
    fetch(`{{ route('pacientes.search.document') }}?documento=${documento}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Documento ya existe
            showAlert('error', 'Este documento ya está registrado en otro paciente', 'Documento Duplicado');
            document.getElementById('documento').classList.add('is-invalid');
        } else {
            // Documento disponible
            showAlert('success', 'Documento disponible para usar', 'Documento Válido');
            document.getElementById('documento').classList.remove('is-invalid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('warning', 'No se pudo validar el documento. Verifique manualmente.');
    });
}

// Calcular edad
function calculateAge() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
    const edadElement = document.getElementById('edadCalculada');
    
    if (fechaNacimiento) {
        const birth = new Date(fechaNacimiento);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        edadElement.textContent = `Edad: ${age} años`;
        
        if (age > 120) {
            edadElement.classList.add('text-warning');
            edadElement.textContent += ' (Verifique la fecha)';
        } else {
            edadElement.classList.remove('text-warning');
        }
    } else {
        edadElement.textContent = '';
    }
}

// Event listener para calcular edad
document.getElementById('fecha_nacimiento').addEventListener('change', calculateAge);

// Resetear formulario
function resetForm() {
    if (hasChanges) {
        Swal.fire({
            title: '¿Deshacer Cambios?',
            text: 'Se perderán todas las modificaciones realizadas',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, deshacer',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Restaurar valores originales
                Object.keys(originalData).forEach(key => {
                    const input = document.getElementById(key);
                    if (input) {
                        input.value = originalData[key] || '';
                        input.classList.remove('is-invalid');
                    }
                });
                
                // Limpiar errores de validación
                document.querySelectorAll('.invalid-feedback').forEach(element => {
                    element.remove();
                });
                
                hasChanges = false;
                detectChanges();
                calculateAge();
                
                showAlert('info', 'Cambios deshecho correctamente');
            }
        });
    } else {
        showAlert('info', 'No hay cambios para deshacer');
    }
}

// Agregar al historial de cambios
function addToHistorial(mensaje, tipo = 'info') {
    const historial = document.getElementById('historialCambios');
    const now = new Date().toLocaleString();
    
    const iconos = {
        'success': 'fas fa-check-circle text-success',
        'warning': 'fas fa-exclamation-triangle text-warning',
        'error': 'fas fa-times-circle text-danger',
        'info': 'fas fa-info-circle text-info'
    };
    
    const newEntry = document.createElement('div');
    newEntry.className = 'border-start border-3 border-primary ps-3 mb-3';
    newEntry.innerHTML = `
        <div class="d-flex align-items-start">
            <i class="${iconos[tipo]} me-2 mt-1"></i>
            <div class="flex-grow-1">
                <p class="mb-1">${mensaje}</p>
                <small class="text-muted">${now}</small>
            </div>
        </div>
    `;
    
    // Si es el primer cambio, limpiar el mensaje de "sin cambios"
    if (historial.querySelector('.text-muted')) {
        historial.innerHTML = '';
    }
    
    historial.insertBefore(newEntry, historial.firstChild);
    
    // Mantener solo los últimos 5 cambios
    const entries = historial.querySelectorAll('.border-start');
    if (entries.length > 5) {
        entries[entries.length - 1].remove();
    }
}

// Advertir antes de salir si hay cambios no guardados
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Formatear teléfono
document.getElementById('telefono').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    this.value = value;
});

// Validar correo en tiempo real
document.getElementById('correo').addEventListener('blur', function() {
    const email = this.value;
    if (email && !isValidEmail(email)) {
        this.classList.add('is-invalid');
        let feedback = this.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            this.parentNode.appendChild(feedback);
        }
        feedback.textContent = 'Formato de correo inválido';
    } else {
        this.classList.remove('is-invalid');
        const feedback = this.parentNode.querySelector('.invalid-feedback');
        if (feedback) feedback.remove();
    }
});

// Función auxiliar para validar email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>
@endpush
@endsection
