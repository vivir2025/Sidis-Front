{{-- resources/views/agendas/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nueva Agenda - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-plus text-primary me-2"></i>
                        Nueva Agenda
                    </h1>
                    <p class="text-muted mb-0">Crear una nueva agenda médica</p>
                </div>
                
                <a href="{{ route('agendas.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información de la Agenda
                    </h5>
                </div>
                <div class="card-body">
                    <form id="agendaForm" method="POST" action="{{ route('agendas.store') }}">
                        @csrf
                        
                        <div class="row g-3">
                            <!-- Modalidad -->
                            <div class="col-md-6">
                                <label for="modalidad" class="form-label">
                                    <i class="fas fa-laptop-medical me-1"></i>Modalidad <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('modalidad') is-invalid @enderror" 
                                        id="modalidad" name="modalidad" required>
                                    <option value="">Seleccionar modalidad</option>
                                    <option value="Ambulatoria" {{ old('modalidad') == 'Ambulatoria' ? 'selected' : '' }}>
                                        Ambulatoria
                                    </option>
                                    <option value="Telemedicina" {{ old('modalidad') == 'Telemedicina' ? 'selected' : '' }}>
                                        Telemedicina
                                    </option>
                                </select>
                                @error('modalidad')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Fecha -->
                            <div class="col-md-6">
                                <label for="fecha" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Fecha <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control @error('fecha') is-invalid @enderror" 
                                       id="fecha" name="fecha" 
                                       value="{{ old('fecha') }}" 
                                       min="{{ date('Y-m-d') }}" required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Consultorio -->
                            <div class="col-md-6">
                                <label for="consultorio" class="form-label">
                                    <i class="fas fa-door-open me-1"></i>Consultorio <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('consultorio') is-invalid @enderror" 
                                       id="consultorio" name="consultorio" 
                                       value="{{ old('consultorio') }}" 
                                       placeholder="Ej: Consultorio 1" required>
                                @error('consultorio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Etiqueta -->
                            <div class="col-md-6">
                                <label for="etiqueta" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Etiqueta <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('etiqueta') is-invalid @enderror" 
                                       id="etiqueta" name="etiqueta" 
                                       value="{{ old('etiqueta') }}" 
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
                                       value="{{ old('hora_inicio') }}" required>
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
                                       value="{{ old('hora_fin') }}" required>
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
        <option value="15" {{ old('intervalo') == '15' ? 'selected' : '' }}>15 minutos</option>
        <option value="20" {{ old('intervalo') == '20' ? 'selected' : '' }}>20 minutos</option>
        <option value="30" {{ old('intervalo') == '30' ? 'selected' : '' }}>30 minutos</option>
        <option value="45" {{ old('intervalo') == '45' ? 'selected' : '' }}>45 minutos</option>
        <option value="60" {{ old('intervalo') == '60' ? 'selected' : '' }}>60 minutos</option>
    </select>
    @error('intervalo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
                            </div>

{{-- ✅ PROCESO - VERSIÓN FINAL CORREGIDA --}}
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
                    // ✅ PRIORIZAR ID NUMÉRICO SIEMPRE
                    $procesoValue = $proceso['id'] ?? $proceso['uuid'] ?? '';
                @endphp
                
                @if(!empty($procesoValue))
                    <option value="{{ $procesoValue }}" 
                            data-id="{{ $proceso['id'] ?? '' }}"
                            data-uuid="{{ $proceso['uuid'] ?? '' }}"
                            {{ old('proceso_id') == $procesoValue ? 'selected' : '' }}>
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

{{-- ✅ BRIGADA - VERSIÓN FINAL CORREGIDA --}}
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
                    // ✅ PRIORIZAR ID NUMÉRICO SIEMPRE
                    $brigadaValue = $brigada['id'] ?? $brigada['uuid'] ?? '';
                @endphp
                
                @if(!empty($brigadaValue))
                    <option value="{{ $brigadaValue }}" 
                            data-id="{{ $brigada['id'] ?? '' }}"
                            data-uuid="{{ $brigada['uuid'] ?? '' }}"
                            {{ old('brigada_id') == $brigadaValue ? 'selected' : '' }}>
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
                                            <span id="duracionTotal">-- horas</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Cupos Estimados:</strong>
                                            <span id="cuposEstimados">-- cupos</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Último Cupo:</strong>
                                            <span id="ultimoCupo">--:--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('agendas.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                                        <i class="fas fa-save"></i> Guardar Agenda
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('agendaForm');
    const horaInicio = document.getElementById('hora_inicio');
    const horaFin = document.getElementById('hora_fin');
    const intervalo = document.getElementById('intervalo');
    
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
    
    // Calcular información automáticamente
    function calcularInformacion() {
        const inicio = horaInicio.value;
        const fin = horaFin.value;
        const intervaloMin = parseInt(intervalo.value);
        
        if (inicio && fin && intervaloMin) {
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
        } else {
            resetCalculos();
        }
    }
    
    function timeToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    function minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }
    
    function resetCalculos() {
        document.getElementById('duracionTotal').textContent = '-- horas';
        document.getElementById('cuposEstimados').textContent = '-- cupos';
        document.getElementById('ultimoCupo').textContent = '--:--';
    }
    
    // Event listeners para cálculos
    horaInicio.addEventListener('change', calcularInformacion);
    horaFin.addEventListener('change', calcularInformacion);
    intervalo.addEventListener('change', calcularInformacion);
    
    // Validación de horarios
    horaFin.addEventListener('change', function() {
        if (horaInicio.value && horaFin.value) {
            if (timeToMinutes(horaFin.value) <= timeToMinutes(horaInicio.value)) {
                showAlert('warning', 'La hora de fin debe ser posterior a la hora de inicio');
                horaFin.value = '';
                resetCalculos();
            }
        }
    });
    
    // ✅ VALIDACIÓN MEJORADA DE SELECCIONES (OPCIONAL Y MENOS RESTRICTIVA)
    const procesoSelect = document.getElementById('proceso_id');
    const brigadaSelect = document.getElementById('brigada_id');
    
    function isValidSelection(value) {
        // Permitir valores vacíos (campos opcionales)
        if (!value || value.trim() === '') return true;
        
        // Debe ser numérico o UUID válido o texto alfanumérico razonable
        return /^\d+$/.test(value) || 
               /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value) ||
               /^[a-zA-Z0-9_-]{1,50}$/.test(value);
    }
    
    if (procesoSelect) {
        procesoSelect.addEventListener('change', function() {
            const value = this.value;
            if (!isValidSelection(value)) {
                console.warn('Proceso inválido seleccionado:', value);
                this.value = '';
                showAlert('warning', 'Selección de proceso inválida');
            }
        });
    }
    
    if (brigadaSelect) {
        brigadaSelect.addEventListener('change', function() {
            const value = this.value;
            if (!isValidSelection(value)) {
                console.warn('Brigada inválida seleccionada:', value);
                this.value = '';
                showAlert('warning', 'Selección de brigada inválida');
            }
        });
    }
    
    // Submit del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardar');
        const originalText = btnGuardar.innerHTML;
        
        try {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(form);
            
            // ✅ LOG PARA DEBUG
            console.log('📤 Enviando datos del formulario:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
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
                        text: data.message || 'Agenda creada exitosamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = '{{ route("agendas.index") }}';
                        }
                    });
                } else {
                    alert(data.message || 'Agenda creada exitosamente');
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.href = '{{ route("agendas.index") }}';
                    }
                }
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            }
            
        } catch (error) {
            console.error('Error guardando agenda:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'Error guardando agenda: ' + error.message,
                    icon: 'error'
                });
            } else {
                alert('Error guardando agenda: ' + error.message);
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
        
        // Mostrar alerta general
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Errores de Validación',
                text: 'Por favor corrige los errores marcados en el formulario',
                icon: 'error'
            });
        }
    }
    
    // Calcular información inicial si hay valores
    calcularInformacion();
});
</script>
@endpush
@endsection
