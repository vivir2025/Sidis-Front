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

                            {{-- ✅ USUARIO MÉDICO - NUEVO CAMPO --}}
<div class="col-md-6">
    <label for="usuario_medico_id" class="form-label">
        <i class="fas fa-user-md me-1"></i>Usuario Médico
    </label>
    <select class="form-select @error('usuario_medico_id') is-invalid @enderror" 
            id="usuario_medico_id" name="usuario_medico_id">
        <option value="">Seleccionar usuario médico (opcional)</option>
        @if(isset($masterData['usuarios_con_especialidad']) && is_array($masterData['usuarios_con_especialidad']))
            @foreach($masterData['usuarios_con_especialidad'] as $usuario)
                @php
                    $usuarioValue = $usuario['id'] ?? $usuario['uuid'] ?? '';
                @endphp
                
                @if(!empty($usuarioValue))
                    <option value="{{ $usuarioValue }}" 
                            data-id="{{ $usuario['id'] ?? '' }}"
                            data-uuid="{{ $usuario['uuid'] ?? '' }}"
                            data-especialidad-id="{{ $usuario['especialidad']['id'] ?? '' }}"
                            data-especialidad-nombre="{{ $usuario['especialidad']['nombre'] ?? '' }}"
                            {{ old('usuario_medico_id') == $usuarioValue ? 'selected' : '' }}>
                        {{ $usuario['nombre_completo'] ?? 'Usuario sin nombre' }}
                        @if(isset($usuario['documento']) && !empty($usuario['documento']))
                            ({{ $usuario['documento'] }})
                        @endif
                    </option>
                @endif
            @endforeach
        @endif
    </select>
    @error('usuario_medico_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Campo opcional - Seleccione el médico responsable</div>
</div>

{{-- ✅ ESPECIALIDAD (SOLO LECTURA) - NUEVO CAMPO --}}
<div class="col-md-6">
    <label for="especialidad_display" class="form-label">
        <i class="fas fa-stethoscope me-1"></i>Especialidad
    </label>
    <input type="text" 
           class="form-control bg-light" 
           id="especialidad_display" 
           name="especialidad_display" 
           value="" 
           placeholder="Se mostrará según el usuario seleccionado"
           readonly>
    <div class="form-text">Se actualiza automáticamente según el usuario médico seleccionado</div>
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
// ✅ FUNCIÓN GLOBAL PARA ALERTAS
function showAlert(type, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type === 'warning' ? 'Advertencia' : type === 'error' ? 'Error' : 'Información',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert(message);
    }
}

// ✅ FUNCIÓN GLOBAL PARA VALIDAR SELECCIONES
function isValidSelection(value) {
    if (!value || value.trim() === '') return true;
    return /^\d+$/.test(value) || 
           /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value) ||
           /^[a-zA-Z0-9_-]{1,50}$/.test(value);
}

// ✅ MAPEO ESPECIALIDAD → PROCESO (CORREGIDO)
const MAPEO_ESPECIALIDAD_PROCESO = {
    'NUTRICIONISTA': 'NUTRICIONISTA',
    'PSICOLOGIA': 'PSICOLOGIA',
    'NEFROLOGIA': 'NEFROLOGIA',
    'INTERNISTA': 'INTERNISTA',
    'FISIOTERAPIA': 'FISIOTERAPIA',
    'TRABAJO SOCIAL': 'TRABAJO SOCIAL',
    'REFORMULACION': 'REFORMULACION',
    'MEDICINA GENERAL': 'ESPECIAL CONTROL',
    'ESPECIAL CONTROL': 'ESPECIAL CONTROL'
};

// ✅ AUTO-SELECCIONAR PROCESO POR NOMBRE DE ESPECIALIDAD
function autoSeleccionarProcesoPorNombre(especialidadNombre) {
    const procesoSelect = document.getElementById('proceso_id');
    
    if (!procesoSelect || !especialidadNombre) {
        console.warn('⚠️ No se puede auto-seleccionar proceso');
        return;
    }
    
    console.log('🔍 Buscando proceso para especialidad:', especialidadNombre);
    
    // Normalizar nombre de especialidad
    const especialidadNormalizada = especialidadNombre.trim().toUpperCase();
    
    // Buscar nombre del proceso en el mapeo
    const procesoNombre = MAPEO_ESPECIALIDAD_PROCESO[especialidadNormalizada];
    
    if (!procesoNombre) {
        console.warn('⚠️ No hay mapeo para especialidad:', especialidadNombre);
        procesoSelect.value = '';
        showAlert('info', `No hay proceso automático para "${especialidadNombre}". Seleccione manualmente.`);
        return;
    }
    
    console.log('✅ Proceso mapeado encontrado:', procesoNombre);
    
    // Buscar la opción por nombre en el select
    let procesoEncontrado = false;
    
    for (let i = 0; i < procesoSelect.options.length; i++) {
        const option = procesoSelect.options[i];
        
        // Saltar opción vacía
        if (!option.value) continue;
        
        // Obtener solo el nombre del proceso (sin CUPS)
        const optionText = option.text.trim().toUpperCase();
        const nombreProceso = optionText.split('(')[0].trim();
        
        console.log(`🔎 Comparando: "${nombreProceso}" === "${procesoNombre}"`);
        
        if (nombreProceso === procesoNombre) {
            procesoSelect.value = option.value;
            procesoEncontrado = true;
            
            console.log('✅ Proceso seleccionado automáticamente:', {
                proceso_id: option.value,
                proceso_nombre: option.text,
                especialidad_nombre: especialidadNombre
            });
            
            showAlert('success', `Proceso "${option.text}" seleccionado automáticamente`);
            
            // Efecto visual
            procesoSelect.classList.add('border-success');
            setTimeout(() => {
                procesoSelect.classList.remove('border-success');
            }, 2000);
            
            break;
        }
    }
    
    if (!procesoEncontrado) {
        console.error('❌ Proceso no encontrado en el select:', procesoNombre);
        console.log('📋 Opciones disponibles en el select:');
        for (let i = 0; i < procesoSelect.options.length; i++) {
            const opt = procesoSelect.options[i];
            if (opt.value) {
                console.log(`  - [${opt.value}] ${opt.text}`);
            }
        }
        procesoSelect.value = '';
        showAlert('warning', `Proceso "${procesoNombre}" no encontrado para "${especialidadNombre}"`);
    }
}

// ✅ LIMPIAR SELECCIÓN DE USUARIO MÉDICO
function limpiarUsuarioMedico() {
    const usuarioMedicoSelect = document.getElementById('usuario_medico_id');
    const especialidadDisplay = document.getElementById('especialidad_display');
    const procesoSelect = document.getElementById('proceso_id');
    
    if (usuarioMedicoSelect) usuarioMedicoSelect.value = '';
    if (especialidadDisplay) especialidadDisplay.value = '';
    if (procesoSelect) procesoSelect.value = '';
}

// ✅ ÚNICO EVENTO DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando formulario de agenda...');
    
    // ========== ELEMENTOS DEL FORMULARIO ==========
    const form = document.getElementById('agendaForm');
    const horaInicio = document.getElementById('hora_inicio');
    const horaFin = document.getElementById('hora_fin');
    const intervalo = document.getElementById('intervalo');
    const usuarioMedicoSelect = document.getElementById('usuario_medico_id');
    const especialidadDisplay = document.getElementById('especialidad_display');
    const procesoSelect = document.getElementById('proceso_id');
    const brigadaSelect = document.getElementById('brigada_id');
    
    // ========== FUNCIONES DE CÁLCULO ==========
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
    
    // ========== EVENT LISTENERS DE CÁLCULO ==========
    horaInicio.addEventListener('change', calcularInformacion);
    horaFin.addEventListener('change', calcularInformacion);
    intervalo.addEventListener('change', calcularInformacion);
    
    horaFin.addEventListener('change', function() {
        if (horaInicio.value && horaFin.value) {
            if (timeToMinutes(horaFin.value) <= timeToMinutes(horaInicio.value)) {
                showAlert('warning', 'La hora de fin debe ser posterior a la hora de inicio');
                horaFin.value = '';
                resetCalculos();
            }
        }
    });
    
    // ========== USUARIO MÉDICO Y ESPECIALIDAD ==========
    if (usuarioMedicoSelect && especialidadDisplay) {
        usuarioMedicoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const value = this.value;
            
            console.log('🔄 Usuario médico cambiado:', {
                value: value,
                option_text: selectedOption.text,
                all_datasets: selectedOption.dataset // ← VER TODOS LOS DATASETS
            });
            
            // Validar selección
            if (value && !isValidSelection(value)) {
                console.warn('❌ Usuario médico inválido:', value);
                limpiarUsuarioMedico();
                showAlert('warning', 'Selección de usuario médico inválida');
                return;
            }
            
            // Si no hay selección, limpiar todo
            if (!value) {
                especialidadDisplay.value = '';
                if (procesoSelect) procesoSelect.value = '';
                return;
            }
            
            // ✅ CORRECCIÓN CRÍTICA: Usar getAttribute en lugar de dataset
            // porque data-especialidad-nombre se convierte en dataset.especialidadNombre
            const especialidadNombre = selectedOption.getAttribute('data-especialidad-nombre');
            
            console.log('👨‍⚕️ Datos del usuario médico:', {
                usuario_nombre: selectedOption.text,
                especialidad_nombre: especialidadNombre,
                especialidad_id: selectedOption.getAttribute('data-especialidad-id'),
                usuario_uuid: selectedOption.getAttribute('data-uuid')
            });
            
            // Actualizar campo de especialidad
            if (especialidadNombre) {
                especialidadDisplay.value = especialidadNombre;
                console.log('✅ Especialidad actualizada:', especialidadNombre);
                
                // ✅ AUTO-SELECCIONAR PROCESO POR NOMBRE
                autoSeleccionarProcesoPorNombre(especialidadNombre);
            } else {
                console.warn('⚠️ Usuario médico sin nombre de especialidad');
                especialidadDisplay.value = '';
                if (procesoSelect) procesoSelect.value = '';
            }
        });
        
        // ✅ INICIALIZAR SI HAY VALOR PREVIO
        if (usuarioMedicoSelect.value) {
            const selectedOption = usuarioMedicoSelect.options[usuarioMedicoSelect.selectedIndex];
            const especialidadNombre = selectedOption.getAttribute('data-especialidad-nombre');
            
            console.log('🔄 Inicializando con usuario médico preseleccionado:', {
                especialidad_nombre: especialidadNombre
            });
            
            if (especialidadNombre) {
                especialidadDisplay.value = especialidadNombre;
                autoSeleccionarProcesoPorNombre(especialidadNombre);
            }
        }
    }
    
    // ========== VALIDACIÓN DE PROCESO Y BRIGADA ==========
    if (procesoSelect) {
        procesoSelect.addEventListener('change', function() {
            const value = this.value;
            console.log('🔄 Proceso cambiado manualmente:', value);
            
            if (!isValidSelection(value)) {
                console.warn('❌ Proceso inválido seleccionado:', value);
                this.value = '';
                showAlert('warning', 'Selección de proceso inválida');
            }
        });
    }
    
    if (brigadaSelect) {
        brigadaSelect.addEventListener('change', function() {
            const value = this.value;
            if (!isValidSelection(value)) {
                console.warn('❌ Brigada inválida seleccionada:', value);
                this.value = '';
                showAlert('warning', 'Selección de brigada inválida');
            }
        });
    }
    
    // ========== SUBMIT DEL FORMULARIO ==========
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardar');
        const originalText = btnGuardar.innerHTML;
        
        try {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(form);
            
            // ✅ LOG DETALLADO DE LOS DATOS QUE SE ENVÍAN
            console.log('📤 Enviando datos del formulario:');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value} (${typeof value})`);
            }
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            console.log('📥 Respuesta del servidor:', data);
            
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Agenda creada exitosamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect_url || '{{ route("agendas.index") }}';
                    });
                } else {
                    alert(data.message || 'Agenda creada exitosamente');
                    window.location.href = data.redirect_url || '{{ route("agendas.index") }}';
                }
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            }
            
        } catch (error) {
            console.error('❌ Error guardando agenda:', error);
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
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        
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
    
    // ========== CALCULAR INFORMACIÓN INICIAL ==========
    calcularInformacion();
    
    // ========== DEBUG: LISTAR PROCESOS Y USUARIOS DISPONIBLES ==========
    if (procesoSelect) {
        console.log('📋 Procesos disponibles en el select:');
        for (let i = 0; i < procesoSelect.options.length; i++) {
            const opt = procesoSelect.options[i];
            if (opt.value) {
                console.log(`  - [${opt.value}] ${opt.text}`);
            }
        }
    }
    
    if (usuarioMedicoSelect) {
        console.log('👨‍⚕️ Usuarios médicos disponibles:');
        for (let i = 0; i < usuarioMedicoSelect.options.length; i++) {
            const opt = usuarioMedicoSelect.options[i];
            if (opt.value) {
                console.log(`  - [${opt.value}] ${opt.text}`, {
                    especialidad: opt.getAttribute('data-especialidad-nombre'),
                    uuid: opt.getAttribute('data-uuid')
                });
            }
        }
    }
});
</script>
@endpush
@endsection