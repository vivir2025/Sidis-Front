{{-- resources/views/agendas/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Agenda - SIDS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Editar Agenda
                    </h1>
                    <p class="text-muted mb-0">Modificar información de la agenda médica</p>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('agendas.show', $agenda['uuid']) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                    <a href="{{ route('agendas.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información Actual -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle me-3"></i>
                <div class="flex-grow-1">
                    <strong>Agenda Actual:</strong> 
                    {{ formatearFecha($agenda['fecha']) }} - {{ $agenda['consultorio'] }} 
                    ({{ $agenda['hora_inicio'] }} - {{ $agenda['hora_fin'] }})
                    <span class="badge bg-{{ $agenda['estado'] === 'ACTIVO' ? 'success' : ($agenda['estado'] === 'LLENA' ? 'warning' : 'danger') }} ms-2">
                        {{ $agenda['estado'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-form me-2"></i>Formulario de Edición
                    </h5>
                </div>
                <div class="card-body">
                    <form id="agendaEditForm" method="POST" action="{{ route('agendas.update', $agenda['uuid']) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <!-- Modalidad -->
                            <div class="col-md-6">
                                <label for="modalidad" class="form-label">
                                    <i class="fas fa-laptop-medical me-1"></i>Modalidad <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('modalidad') is-invalid @enderror" 
                                        id="modalidad" name="modalidad" required>
                                    <option value="">Seleccionar modalidad</option>
                                    <option value="Ambulatoria" {{ (old('modalidad', $agenda['modalidad']) == 'Ambulatoria') ? 'selected' : '' }}>
                                        Ambulatoria
                                    </option>
                                    <option value="Telemedicina" {{ (old('modalidad', $agenda['modalidad']) == 'Telemedicina') ? 'selected' : '' }}>
                                        Telemedicina
                                    </option>
                                </select>
                                @error('modalidad')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6">
                                <label for="estado" class="form-label">
                                    <i class="fas fa-flag me-1"></i>Estado <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('estado') is-invalid @enderror" 
                                        id="estado" name="estado" required>
                                    <option value="ACTIVO" {{ (old('estado', $agenda['estado']) == 'ACTIVO') ? 'selected' : '' }}>
                                        Activo
                                    </option>
                                    <option value="LLENA" {{ (old('estado', $agenda['estado']) == 'LLENA') ? 'selected' : '' }}>
                                        Llena
                                    </option>
                                    <option value="ANULADA" {{ (old('estado', $agenda['estado']) == 'ANULADA') ? 'selected' : '' }}>
                                        Anulada
                                    </option>
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <strong>Activo:</strong> Disponible para citas | 
                                    <strong>Llena:</strong> Sin cupos | 
                                    <strong>Anulada:</strong> Cancelada
                                </div>
                            </div>

                            <!-- Fecha -->
                            <div class="col-md-6">
                                <label for="fecha" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Fecha <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control @error('fecha') is-invalid @enderror" 
                                       id="fecha" name="fecha" 
                                       value="{{ old('fecha', $agenda['fecha']) }}" 
                                       min="{{ date('Y-m-d') }}" required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Solo se pueden programar fechas futuras</div>
                            </div>

                            <!-- Consultorio -->
                            <div class="col-md-6">
                                <label for="consultorio" class="form-label">
                                    <i class="fas fa-door-open me-1"></i>Consultorio <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('consultorio') is-invalid @enderror" 
                                       id="consultorio" name="consultorio" 
                                       value="{{ old('consultorio', $agenda['consultorio']) }}" 
                                       placeholder="Ej: Consultorio 1" required>
                                @error('consultorio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Etiqueta -->
                            <div class="col-12">
                                <label for="etiqueta" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Etiqueta <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('etiqueta') is-invalid @enderror" 
                                       id="etiqueta" name="etiqueta" 
                                       value="{{ old('etiqueta', $agenda['etiqueta']) }}" 
                                       placeholder="Ej: Consulta General" required>
                                @error('etiqueta')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hora Inicio -->
                            <div class="col-md-4">
                                <label for="hora_inicio" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control @error('hora_inicio') is-invalid @enderror" 
                                       id="hora_inicio" name="hora_inicio" 
                                       value="{{ old('hora_inicio', $agenda['hora_inicio']) }}" 
                                       step="300" required>
                                @error('hora_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hora Fin -->
                            <div class="col-md-4">
                                <label for="hora_fin" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora Fin <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control @error('hora_fin') is-invalid @enderror"
                                       id="hora_fin" name="hora_fin" 
                                       value="{{ old('hora_fin', $agenda['hora_fin']) }}" 
                                       step="300" required>
                                @error('hora_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Intervalo -->
                            <div class="col-md-4">
                                <label for="intervalo" class="form-label">
                                    <i class="fas fa-stopwatch me-1"></i>Intervalo (min) <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('intervalo') is-invalid @enderror" 
                                        id="intervalo" name="intervalo" required>
                                    <option value="">Seleccionar</option>
                                    <option value="15" {{ (old('intervalo', $agenda['intervalo']) == '15') ? 'selected' : '' }}>15 minutos</option>
                                    <option value="20" {{ (old('intervalo', $agenda['intervalo']) == '20') ? 'selected' : '' }}>20 minutos</option>
                                    <option value="30" {{ (old('intervalo', $agenda['intervalo']) == '30') ? 'selected' : '' }}>30 minutos</option>
                                    <option value="45" {{ (old('intervalo', $agenda['intervalo']) == '45') ? 'selected' : '' }}>45 minutos</option>
                                    <option value="60" {{ (old('intervalo', $agenda['intervalo']) == '60') ? 'selected' : '' }}>60 minutos</option>
                                </select>
                                @error('intervalo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Proceso -->
                            <div class="col-md-6">
                                <label for="proceso_id" class="form-label">
                                    <i class="fas fa-cogs me-1"></i>Proceso
                                </label>
                                <select class="form-select @error('proceso_id') is-invalid @enderror" 
                                        id="proceso_id" name="proceso_id">
                                    <option value="">Seleccionar proceso (opcional)</option>
                                    @if(isset($masterData['procesos']) && is_array($masterData['procesos']))
                                        @foreach($masterData['procesos'] as $proceso)
                                            @php
                                                $procesoValue = $proceso['id'] ?? $proceso['uuid'] ?? '';
                                                $currentProcesoId = old('proceso_id', $agenda['proceso_id'] ?? '');
                                            @endphp
                                            
                                            @if(!empty($procesoValue))
                                                <option value="{{ $procesoValue }}" 
                                                        data-id="{{ $proceso['id'] ?? '' }}"
                                                        data-uuid="{{ $proceso['uuid'] ?? '' }}"
                                                        {{ ($currentProcesoId == $procesoValue) ? 'selected' : '' }}>
                                                    {{ $proceso['nombre'] ?? 'Proceso sin nombre' }}
                                                    @if(isset($proceso['n_cups']) && !empty($proceso['n_cups']))
                                                        ({{ $proceso['n_cups'] }})
                                                    @endif
                                                </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                                @error('proceso_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Campo opcional - Deje vacío si no aplica</div>
                            </div>

                            <!-- Brigada -->
                            <div class="col-md-6">
                                <label for="brigada_id" class="form-label">
                                    <i class="fas fa-users me-1"></i>Brigada
                                </label>
                                <select class="form-select @error('brigada_id') is-invalid @enderror" 
                                        id="brigada_id" name="brigada_id">
                                    <option value="">Seleccionar brigada (opcional)</option>
                                    @if(isset($masterData['brigadas']) && is_array($masterData['brigadas']))
                                        @foreach($masterData['brigadas'] as $brigada)
                                            @php
                                                $brigadaValue = $brigada['id'] ?? $brigada['uuid'] ?? '';
                                                $currentBrigadaId = old('brigada_id', $agenda['brigada_id'] ?? '');
                                            @endphp
                                            
                                            @if(!empty($brigadaValue))
                                                <option value="{{ $brigadaValue }}" 
                                                        data-id="{{ $brigada['id'] ?? '' }}"
                                                        data-uuid="{{ $brigada['uuid'] ?? '' }}"
                                                        {{ ($currentBrigadaId == $brigadaValue) ? 'selected' : '' }}>
                                                    {{ $brigada['nombre'] ?? 'Brigada sin nombre' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                                @error('brigada_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Campo opcional - Deje vacío si no aplica</div>
                            </div>
                        </div>

                        <!-- Información calculada -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-calculator me-2"></i>Información Calculada
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Duración Total:</strong>
                                            <span id="duracionTotal">{{ calcularDuracion($agenda) }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Cupos Estimados:</strong>
                                            <span id="cuposEstimados">{{ calcularTotalCupos($agenda) }} cupos</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Último Cupo:</strong>
                                            <span id="ultimoCupo">{{ calcularUltimoCupo($agenda) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advertencias -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-warning d-none" id="alertaCitas" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Atención:</strong> Esta agenda tiene citas programadas. 
                                    Los cambios en horarios pueden afectar las citas existentes.
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('agendas.show', $agenda['uuid']) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-info" onclick="previewChanges()">
                                            <i class="fas fa-eye"></i> Vista Previa
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                                            <i class="fas fa-save"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2"></i>Vista Previa de Cambios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Datos Actuales</h6>
                        <div id="datosActuales" class="border p-3 rounded bg-light">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Nuevos Datos</h6>
                        <div id="datosNuevos" class="border p-3 rounded bg-info bg-opacity-10">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6 class="text-muted">Cambios Detectados</h6>
                    <div id="cambiosDetectados" class="alert alert-info">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarCambios()">
                    <i class="fas fa-save"></i> Confirmar y Guardar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('agendaEditForm');
    const horaInicio = document.getElementById('hora_inicio');
    const horaFin = document.getElementById('hora_fin');
    const intervalo = document.getElementById('intervalo');
    const estado = document.getElementById('estado');
    
    // Datos originales para comparación
    const datosOriginales = {
        modalidad: '{{ $agenda['modalidad'] }}',
        fecha: '{{ $agenda['fecha'] }}',
        consultorio: '{{ $agenda['consultorio'] }}',
        hora_inicio: '{{ $agenda['hora_inicio'] }}',
        hora_fin: '{{ $agenda['hora_fin'] }}',
        intervalo: '{{ $agenda['intervalo'] }}',
        etiqueta: '{{ $agenda['etiqueta'] }}',
        estado: '{{ $agenda['estado'] }}',
        proceso_id: '{{ $agenda['proceso_id'] ?? '' }}',
        brigada_id: '{{ $agenda['brigada_id'] ?? '' }}'
    };

    // ✅ FUNCIÓN PARA MOSTRAR ALERTAS
    function showAlert(type, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'warning' ? 'Advertencia' : 'Información',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(message);
        }
    }
    
    // ✅ VALIDAR FORMATO DE HORA
    function isValidTimeFormat(timeString) {
        if (!timeString) return false;
        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
        return timeRegex.test(timeString);
    }
    
    // Calcular información automáticamente
    function calcularInformacion() {
        const inicio = horaInicio.value;
        const fin = horaFin.value;
        const intervaloMin = parseInt(intervalo.value);
        
        if (!isValidTimeFormat(inicio) || !isValidTimeFormat(fin) || !intervaloMin) {
            resetCalculos();
            return;
        }
        
        try {
            const inicioMinutos = timeToMinutes(inicio);
            const finMinutos = timeToMinutes(fin);
            
            if (finMinutos > inicioMinutos) {
                const duracionMinutos = finMinutos - inicioMinutos;
                const duracionHoras = Math.floor(duracionMinutos / 60);
                const duracionMin = duracionMinutos % 60;
                
                const cupos = Math.floor(duracionMinutos / intervaloMin);
                const ultimoCupoMinutos = inicioMinutos + (cupos - 1) * intervaloMin;
                
                document.getElementById('duracionTotal').textContent = 
                    `${duracionHoras}h ${duracionMin}min`;
                document.getElementById('cuposEstimados').textContent = 
                    `${cupos} cupos`;
                document.getElementById('ultimoCupo').textContent = 
                    minutesToTime(ultimoCupoMinutos);
            } else {
                resetCalculos();
            }
        } catch (error) {
            console.error('Error calculando información:', error);
            resetCalculos();
        }
    }
    
    function timeToMinutes(time) {
        if (!time || !isValidTimeFormat(time)) {
            throw new Error('Formato de hora inválido: ' + time);
        }
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    function minutesToTime(minutes) {
        if (isNaN(minutes) || minutes < 0) {
            return '--:--';
        }
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }
    
    function resetCalculos() {
        document.getElementById('duracionTotal').textContent = '-- horas';
        document.getElementById('cuposEstimados').textContent = '-- cupos';
        document.getElementById('ultimoCupo').textContent = '--:--';
    }
    
    // Event listeners para recálculo automático
    horaInicio.addEventListener('change', function() {
        if (this.value && !isValidTimeFormat(this.value)) {
            showAlert('warning', 'Formato de hora inválido. Use HH:MM (ej: 08:00)');
            this.value = '';
            resetCalculos();
            return;
        }
        calcularInformacion();
    });
    
    horaFin.addEventListener('change', function() {
        if (this.value && !isValidTimeFormat(this.value)) {
            showAlert('warning', 'Formato de hora inválido. Use HH:MM (ej: 17:00)');
            this.value = '';
            resetCalculos();
            return;
        }
        
        if (horaInicio.value && this.value) {
            try {
                if (timeToMinutes(this.value) <= timeToMinutes(horaInicio.value)) {
                    showAlert('warning', 'La hora de fin debe ser posterior a la hora de inicio');
                    this.value = '';
                    resetCalculos();
                    return;
                }
            } catch (error) {
                console.error('Error validando horarios:', error);
                this.value = '';
                resetCalculos();
                return;
            }
        }
        
        calcularInformacion();
    });
    
    intervalo.addEventListener('change', calcularInformacion);
    
    // Mostrar alerta si cambia el estado a ANULADA
    estado.addEventListener('change', function() {
        if (this.value === 'ANULADA') {
            Swal.fire({
                title: '¿Anular Agenda?',
                text: 'Al anular esta agenda, todas las citas programadas podrían verse afectadas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    this.value = datosOriginales.estado;
                }
            });
        }
    });

    // ✅ SUBMIT DEL FORMULARIO
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardar');
        const originalText = btnGuardar.innerHTML;
        
        try {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(form);
            
            // Validar datos antes de enviar
            const horaInicioVal = formData.get('hora_inicio');
            const horaFinVal = formData.get('hora_fin');
            
            if (!isValidTimeFormat(horaInicioVal)) {
                throw new Error('Formato de hora de inicio inválido');
            }
            
            if (!isValidTimeFormat(horaFinVal)) {
                throw new Error('Formato de hora de fin inválido');
            }
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Agenda actualizada exitosamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '{{ route("agendas.show", $agenda["uuid"]) }}';
                    });
                } else {
                    alert(data.message || 'Agenda actualizada exitosamente');
                    window.location.href = '{{ route("agendas.show", $agenda["uuid"]) }}';
                }
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            }
            
        } catch (error) {
            console.error('Error actualizando agenda:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'Error actualizando agenda: ' + error.message,
                    icon: 'error'
                });
            } else {
                alert('Error actualizando agenda: ' + error.message);
            }
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = originalText;
        }
    });
    
    function showValidationErrors(errors) {
        // Limpiar errores previos
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
        
        // Mostrar nuevos errores
        for (const [field, messages] of Object.entries(errors)) {
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.parentNode.appendChild(feedback);
            }
        }
        
        // Scroll al primer error
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Errores de Validación',
                text: 'Por favor corrige los errores marcados en el formulario',
                icon: 'error'
            });
        }
    }
    
    // Calcular información inicial
    calcularInformacion();
    
    // Verificar si hay citas programadas (simulado)
    // En una implementación real, esto vendría del backend
    const tieneCitas = {{ !empty($agenda['citas_count']) && $agenda['citas_count'] > 0 ? 'true' : 'false' }};
    if (tieneCitas) {
        document.getElementById('alertaCitas').classList.remove('d-none');
    }
});

// ✅ FUNCIÓN PARA VISTA PREVIA
function previewChanges() {
    const form = document.getElementById('agendaEditForm');
    const formData = new FormData(form);
    
    // Datos actuales
    const datosActuales = {
        modalidad: '{{ $agenda['modalidad'] }}',
        fecha: '{{ $agenda['fecha'] }}',
        consultorio: '{{ $agenda['consultorio'] }}',
        hora_inicio: '{{ $agenda['hora_inicio'] }}',
        hora_fin: '{{ $agenda['hora_fin'] }}',
        intervalo: '{{ $agenda['intervalo'] }}',
        etiqueta: '{{ $agenda['etiqueta'] }}',
        estado: '{{ $agenda['estado'] }}',
        proceso_id: '{{ $agenda['proceso_id'] ?? '' }}',
        brigada_id: '{{ $agenda['brigada_id'] ?? '' }}'
    };
    
    // Nuevos datos
    const datosNuevos = {};
    for (let [key, value] of formData.entries()) {
        datosNuevos[key] = value;
    }
    
    // Mostrar en modal
    mostrarComparacion(datosActuales, datosNuevos);
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function mostrarComparacion(actuales, nuevos) {
    const contenedorActuales = document.getElementById('datosActuales');
    const contenedorNuevos = document.getElementById('datosNuevos');
    const contenedorCambios = document.getElementById('cambiosDetectados');
    
    let htmlActuales = '';
    let htmlNuevos = '';
    let cambios = [];
    
    const campos = {
        modalidad: 'Modalidad',
        fecha: 'Fecha',
        consultorio: 'Consultorio',
        hora_inicio: 'Hora Inicio',
        hora_fin: 'Hora Fin',
        intervalo: 'Intervalo',
        etiqueta: 'Etiqueta',
        estado: 'Estado'
    };
    
    for (const [key, label] of Object.entries(campos)) {
        const valorActual = actuales[key] || 'No especificado';
        const valorNuevo = nuevos[key] || 'No especificado';
        
        htmlActuales += `<p><strong>${label}:</strong> ${valorActual}</p>`;
        
        if (valorActual !== valorNuevo) {
            htmlNuevos += `<p><strong>${label}:</strong> <span class="text-primary">${valorNuevo}</span></p>`;
            cambios.push(`${label}: ${valorActual} → ${valorNuevo}`);
        } else {
            htmlNuevos += `<p><strong>${label}:</strong> ${valorNuevo}</p>`;
        }
    }
    
    contenedorActuales.innerHTML = htmlActuales;
    contenedorNuevos.innerHTML = htmlNuevos;
    
    if (cambios.length > 0) {
        contenedorCambios.innerHTML = '<ul><li>' + cambios.join('</li><li>') + '</li></ul>';
    } else {
        contenedorCambios.innerHTML = '<p class="text-muted">No se detectaron cambios</p>';
    }
}

function confirmarCambios() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
    modal.hide();
    
    // Enviar formulario
    document.getElementById('agendaEditForm').dispatchEvent(new Event('submit'));
}
</script>
@endpush

@push('styles')
<style>
.info-group {
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
}

.alert-info {
    background-color: #cff4fc;
    border-color: #b6effb;
    color: #055160;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.modal-body .border {
    border-radius: 0.375rem;
}

.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}
</style>
@endpush
@endsection

@php
function formatearFecha($fecha) {
    if (!$fecha) return 'No especificada';
    try {
        return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    } catch (\Exception $e) {
        return $fecha;
    }
}

function calcularDuracion($agenda) {
    if (empty($agenda['hora_inicio']) || empty($agenda['hora_fin'])) {
        return '-- horas';
    }
    
    try {
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $horas = floor($duracionMinutos / 60);
        $minutos = $duracionMinutos % 60;
        
        return "{$horas}h {$minutos}min";
    } catch (\Exception $e) {
        return '-- horas';
    }
}

function calcularTotalCupos($agenda) {
    if (empty($agenda['hora_inicio']) || empty($agenda['hora_fin']) || empty($agenda['intervalo'])) {
        return 0;
    }
    
    try {
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        return floor($duracionMinutos / $intervalo);
    } catch (\Exception $e) {
        return 0;
    }
}

function calcularUltimoCupo($agenda) {
    if (empty($agenda['hora_inicio']) || empty($agenda['hora_fin']) || empty($agenda['intervalo'])) {
        return '--:--';
    }
    
    try {
        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        $cupos = floor($duracionMinutos / $intervalo);
        
        if ($cupos > 0) {
            $ultimoCupo = $inicio->copy()->addMinutes(($cupos - 1) * $intervalo);
            return $ultimoCupo->format('H:i');
        }
        
        return '--:--';
    } catch (\Exception $e) {
        return '--:--';
    }
}
@endphp

