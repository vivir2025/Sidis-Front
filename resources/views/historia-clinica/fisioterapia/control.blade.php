{{-- resources/views/historia-clinica/fisioterapia/control.blade.php --}}
@extends('layouts.app')

@section('title', 'Control Fisioterapia')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')
    
    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="CONTROL">
        <input type="hidden" name="especialidad" value="FISIOTERAPIA">
        
        {{-- ✅ SECCIÓN: DATOS BÁSICOS --}}
        @include('historia-clinica.partials.datos-basicos')

        {{-- ✅ SECCIÓN: ACUDIENTE --}}
        @include('historia-clinica.partials.acudiente')

            <div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Historia Clínica
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="motivo" class="form-label">Motivo de Consulta <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="motivo" name="motivo" rows="4" required></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

        {{-- ✅ SECCIÓN: MEDIDAS ANTROPOMÉTRICAS --}}
        @include('historia-clinica.partials.medidas-antropometricas')



        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
        @include('historia-clinica.partials.diagnostico-principal')
        
        {{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES --}}
        @include('historia-clinica.partials.diagnosticos-adicionales')
        
        
      
        @include('historia-clinica.partials.remisiones-section')
      

        {{-- ✅ SECCIÓN: OBSERVACIONES GENERALES CON MICRÓFONO --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-comment-medical me-2"></i>
                    Observaciones Generales
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3 position-relative">
                            <label for="observaciones_generales" class="form-label">Observaciones <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="observaciones_generales" name="observaciones_generales" 
                                      rows="4" required placeholder="Observaciones generales">{{ $historiaPrevia['observaciones_generales'] ?? '' }}</textarea>
                            <span class="microfono" id="microfono"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ ENLACES ADICIONALES --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>
                    Enlaces Adicionales
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a type="button" class="btn btn-outline-primary w-100 mb-2" target="_blank" 
                           href="{{ url('paraclinicos/' . $cita['paciente']['documento']) }}">
                            <i class="fas fa-flask me-2"></i>Paraclínicos
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a type="button" class="btn btn-outline-primary w-100 mb-2" target="_blank" 
                           href="{{ url('visitas-domiciliarias/' . $cita['paciente']['documento']) }}">
                           <i class="fas fa-home me-2"></i>App Visitas Domiciliarias
                        </a>
                    </div>
                </div>
            </div>
        </div>



        {{-- ✅ BOTONES DE ACCIÓN --}}
        <div class="card">
            <div class="card-body text-center">
                <button type="submit" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-save me-2"></i>
                    Guardar Control
                </button>
                <a href="{{ route('cronograma.index') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ✅ LOADING OVERLAY --}}
<div id="loading_overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
     style="background: rgba(0,0,0,0.5); z-index: 9999; display: none !important;">
    <div class="text-center text-white">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">Guardando control...</div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card-header {
    font-weight: 600;
}

.medicamento-item, .diagnostico-adicional-item, .remision-item, .cups-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6 !important;
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-item:hover {
    background-color: #e9ecef;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.table-borderless td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.required-field {
    border-left: 3px solid #dc3545;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ✅ ESTILOS PARA CAMPOS READONLY */
input[readonly] {
    background-color: #e9ecef !important;
    opacity: 1;
    cursor: not-allowed;
    pointer-events: none;
}

input[readonly]:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.test-morisky-input:checked + label {
    font-weight: bold;
    color: #0d6efd;
}

#explicacion_adherencia {
    border-left: 4px solid #0dcaf0;
    background-color: #f8f9fa;
}

.alert-info strong {
    color: #0c5460;
}

hr.my-4 {
    border-top: 2px solid #dee2e6;
    margin: 1.5rem 0;
}

/* ✅ ESTILOS PARA MICRÓFONO */
.form-group {
    position: relative;
}

.microfono {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background-color: transparent;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3zM19 10v1a7 7 0 0 1-14 0v-1a1 1 0 0 1 2 0v1a5 5 0 0 0 10 0v-1a1 1 0 0 1 2 0z"/><path d="M12 18.5a1 1 0 0 1 1 1V22a1 1 0 0 1-2 0v-2.5a1 1 0 0 1 1-1z"/></svg>');
    background-size: cover;
    cursor: pointer;
    z-index: 1;
    display: inline-block;
    transition: transform 0.3s ease-in-out;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
}

.microfono.active {
    animation: pulse 1s infinite;
    box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
    50% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
    }
    100% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
}
</style>
@endpush

@push('styles')
<style>
.card-header {
    font-weight: 600;
}

.medicamento-item, .diagnostico-adicional-item, .remision-item, .cups-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6 !important;
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-item:hover {
    background-color: #e9ecef;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.table-borderless td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.required-field {
    border-left: 3px solid #dc3545;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ✅ ESTILOS PARA CAMPOS READONLY */
input[readonly] {
    background-color: #e9ecef !important;
    opacity: 1;
    cursor: not-allowed;
    pointer-events: none;
}

input[readonly]:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.test-morisky-input:checked + label {
    font-weight: bold;
    color: #0d6efd;
}

#explicacion_adherencia {
    border-left: 4px solid #0dcaf0;
    background-color: #f8f9fa;
}

.alert-info strong {
    color: #0c5460;
}

hr.my-4 {
    border-top: 2px solid #dee2e6;
    margin: 1.5rem 0;
}

/* ✅ ESTILOS PARA MICRÓFONO */
.form-group {
    position: relative;
}

.microfono {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background-color: transparent;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3zM19 10v1a7 7 0 0 1-14 0v-1a1 1 0 0 1 2 0v1a5 5 0 0 0 10 0v-1a1 1 0 0 1 2 0z"/><path d="M12 18.5a1 1 0 0 1 1 1V22a1 1 0 0 1-2 0v-2.5a1 1 0 0 1 1-1z"/></svg>');
    background-size: cover;
    cursor: pointer;
    z-index: 1;
    display: inline-block;
    transition: transform 0.3s ease-in-out;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
}

.microfono.active {
    animation: pulse 1s infinite;
    box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
    50% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
    }
    100% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
}
</style>
@endpush
@push('scripts')
<script>
// ============================================
// ✅ VARIABLES GLOBALES
// ============================================
let diagnosticoAdicionalCounter = 0;
let remisionCounter = 0;
let diagnosticoSeleccionado = null;

// ============================================
// ✅ FUNCIONES PRINCIPALES (FUERA DE DOCUMENT.READY)
// ============================================

/**
 * ✅ AGREGAR REMISIÓN
 */
function agregarRemision() {
    const template = $('#remision_template').html();
    const $remision = $(template);
    
    $remision.find('input[name*="remisiones"], textarea[name*="remisiones"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${remisionCounter}]`));
    });
    
    $('#remisiones_container').append($remision);
    remisionCounter++;
    
    configurarBusquedaRemision($remision);
}

/**
 * ✅ AGREGAR REMISIÓN CON DATOS
 */
function agregarRemisionConDatos(remision) {
    console.log('📋 Agregando remisión con datos:', remision);
    
    agregarRemision();
    
    const $ultimaRemision = $('#remisiones_container .remision-item:last');
    
    $ultimaRemision.find('.buscar-remision').val(remision.remision.nombre);
    $ultimaRemision.find('.remision-id').val(remision.remision_id);
    $ultimaRemision.find('.remision-info').html(`<strong>${remision.remision.nombre}</strong><br><small>${remision.remision.tipo || ''}</small>`);
    $ultimaRemision.find('.remision-seleccionada').show();
    $ultimaRemision.find('textarea[name*="remObservacion"]').val(remision.observacion || '');
    
    console.log('✅ Remisión agregada exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA REMISIÓN
 */
function configurarBusquedaRemision($container) {
    const $input = $container.find('.buscar-remision');
    const $resultados = $container.find('.remisiones-resultados');
    const $hiddenId = $container.find('.remision-id');
    const $info = $container.find('.remision-info');
    const $alert = $container.find('.remision-seleccionada');
    
    let remisionTimeout;
    
    $input.on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(remisionTimeout);
        
        if (termino.length < 2) {
            $resultados.removeClass('show').empty();
            return;
        }
        
        remisionTimeout = setTimeout(() => {
            buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert);
        }, 300);
    });
}

/**
 * ✅ BUSCAR REMISIONES
 */
function buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-remisiones") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosRemisiones(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando remisiones:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS REMISIONES
 */
function mostrarResultadosRemisiones(remisiones, $resultados, $input, $hiddenId, $info, $alert) {
    $resultados.empty();
    
    if (remisiones.length === 0) {
        $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron remisiones</div>');
    } else {
        remisiones.forEach(function(remision) {
            const $item = $('<a href="#" class="dropdown-item"></a>')
                .html(`<strong>${remision.nombre}</strong><br><small class="text-muted">${remision.tipo || ''}</small>`)
                .data('remision', remision);
            
            $item.on('click', function(e) {
                e.preventDefault();
                seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados);
            });
            
            $resultados.append($item);
        });
    }
    
    $resultados.addClass('show');
}

/**
 * ✅ SELECCIONAR REMISIÓN
 */
function seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(remision.nombre);
    $hiddenId.val(remision.uuid || remision.id);
    $info.html(`<strong>${remision.nombre}</strong><br><small>${remision.tipo || ''}</small>`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ AGREGAR DIAGNÓSTICO ADICIONAL
 */
function agregarDiagnosticoAdicional() {
    const template = $('#diagnostico_adicional_template').html();
    const $diagnostico = $(template);
    
    $diagnostico.find('input[name*="diagnosticos_adicionales"], select[name*="diagnosticos_adicionales"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${diagnosticoAdicionalCounter}]`));
    });
    
    $('#diagnosticos_adicionales_container').append($diagnostico);
    diagnosticoAdicionalCounter++;
    
    configurarBusquedaDiagnosticoAdicional($diagnostico);
}

/**
 * ✅ AGREGAR DIAGNÓSTICO ADICIONAL CON DATOS
 */
function agregarDiagnosticoAdicionalConDatos(diagnostico) {
    console.log('🩺 Agregando diagnóstico adicional con datos:', diagnostico);
    
    agregarDiagnosticoAdicional();
    
    const $ultimoDiagnostico = $('#diagnosticos_adicionales_container .diagnostico-adicional-item:last');
    
    $ultimoDiagnostico.find('.buscar-diagnostico-adicional').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
    $ultimoDiagnostico.find('.diagnostico-adicional-id').val(diagnostico.diagnostico_id);
    $ultimoDiagnostico.find('.diagnostico-adicional-info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
    $ultimoDiagnostico.find('.diagnostico-adicional-seleccionado').show();
    $ultimoDiagnostico.find('select[name*="tipo_diagnostico"]').val(diagnostico.tipo_diagnostico || 'IMPRESION_DIAGNOSTICA');
    
    console.log('✅ Diagnóstico adicional agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA DIAGNÓSTICO ADICIONAL
 */
function configurarBusquedaDiagnosticoAdicional($container) {
    const $input = $container.find('.buscar-diagnostico-adicional');
    const $resultados = $container.find('.diagnosticos-adicionales-resultados');
    const $hiddenId = $container.find('.diagnostico-adicional-id');
    const $info = $container.find('.diagnostico-adicional-info');
    const $alert = $container.find('.diagnostico-adicional-seleccionado');
    
    let diagnosticoTimeout;
    
    $input.on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(diagnosticoTimeout);
        
        if (termino.length < 2) {
            $resultados.removeClass('show').empty();
            return;
        }
        
        diagnosticoTimeout = setTimeout(() => {
            buscarDiagnosticosAdicionales(termino, $resultados, $input, $hiddenId, $info, $alert);
        }, 300);
    });
}

/**
 * ✅ BUSCAR DIAGNÓSTICOS ADICIONALES
 */
function buscarDiagnosticosAdicionales(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-diagnosticos") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosDiagnosticosAdicionales(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando diagnósticos adicionales:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS DIAGNÓSTICOS ADICIONALES
 */
function mostrarResultadosDiagnosticosAdicionales(diagnosticos, $resultados, $input, $hiddenId, $info, $alert) {
    $resultados.empty();
    
    if (diagnosticos.length === 0) {
        $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron diagnósticos</div>');
    } else {
        diagnosticos.forEach(function(diagnostico) {
            const $item = $('<a href="#" class="dropdown-item"></a>')
                .html(`<strong>${diagnostico.codigo}</strong> - ${diagnostico.nombre}`)
                .data('diagnostico', diagnostico);
            
            $item.on('click', function(e) {
                e.preventDefault();
                seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados);
            });
            
            $resultados.append($item);
        });
    }
    
    $resultados.addClass('show');
}

/**
 * ✅ SELECCIONAR DIAGNÓSTICO ADICIONAL
 */
function seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
    $hiddenId.val(diagnostico.uuid || diagnostico.id);
    $info.text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ CARGAR DIAGNÓSTICO PRINCIPAL CON DATOS
 */
function cargarDiagnosticoPrincipalConDatos(diagnostico) {
    console.log('🩺 Cargando diagnóstico principal con datos:', diagnostico);
    
    try {
        $('#buscar_diagnostico').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $('#idDiagnostico').val(diagnostico.diagnostico_id);
        $('#diagnostico_info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $('#diagnostico_seleccionado').show();
        
        if (diagnostico.tipo_diagnostico) {
            $('#tipo_diagnostico').val(diagnostico.tipo_diagnostico);
        }
        
        console.log('✅ Diagnóstico principal cargado exitosamente');
        
    } catch (error) {
        console.error('❌ Error cargando diagnóstico principal:', error);
    }
}

/**
 * ✅ LIMPIAR CAMBIOS PENDIENTES DE CITA EN LOCALSTORAGE
 * Previene que cambios antiguos (como EN_ATENCION) sobrescriban ATENDIDA
 */
function limpiarCambiosPendientesCita(citaUuid) {
    try {
        if (typeof localStorage === 'undefined') return false;
        
        const cambiosPendientes = JSON.parse(localStorage.getItem('cambios_estados_pendientes') || '[]');
        if (cambiosPendientes.length === 0) return true;
        
        const cambioExistente = cambiosPendientes.find(c => c.cita_uuid === citaUuid);
        if (cambioExistente) {
            console.log('🧹 Eliminando cambio pendiente:', {
                citaUuid: citaUuid,
                estadoPendiente: cambioExistente.nuevo_estado
            });
            const cambiosFiltrados = cambiosPendientes.filter(c => c.cita_uuid !== citaUuid);
            localStorage.setItem('cambios_estados_pendientes', JSON.stringify(cambiosFiltrados));
        }
        return true;
    } catch (error) {
        console.error('❌ Error limpiando cambios pendientes:', error);
        return false;
    }
}

/**
 * ✅✅✅ DISPARAR EVENTO DE HISTORIA GUARDADA ✅✅✅
 */
function dispararEventoHistoriaGuardada(citaUuid, historiaUuid, offline) {
    console.log('📋 Disparando evento historiaClinicaGuardada', {
        citaUuid: citaUuid,
        historiaUuid: historiaUuid,
        offline: offline
    });
    
    // ✅ LIMPIAR CAMBIOS PENDIENTES ANTES DE DISPARAR EVENTO
    limpiarCambiosPendientesCita(citaUuid);
    
    window.dispatchEvent(new CustomEvent('historiaClinicaGuardada', {
        detail: {
            cita_uuid: citaUuid,
            historia_uuid: historiaUuid,
            offline: offline || false
        }
    }));
    
    console.log('✅ Evento disparado exitosamente');
}

/**
 * ✅ CARGAR DATOS PREVIOS
 */
function cargarDatosPrevios(historiaPrevia) {
    try {
        console.log('🔄 Iniciando carga de datos previos');
        console.log('📦 Historia previa recibida:', historiaPrevia);

        // ✅ CARGAR REMISIONES
        if (historiaPrevia.remisiones && historiaPrevia.remisiones.length > 0) {
            console.log('📋 Cargando remisiones previas:', historiaPrevia.remisiones.length);
            historiaPrevia.remisiones.forEach(function(remision, index) {
                setTimeout(function() {
                    agregarRemisionConDatos(remision);
                }, index * 200);
            });
        }

        // ✅ CARGAR DIAGNÓSTICOS
        if (historiaPrevia.diagnosticos && historiaPrevia.diagnosticos.length > 0) {
            console.log('🩺 Cargando diagnósticos previos:', historiaPrevia.diagnosticos.length);
            
            const diagnosticoPrincipal = historiaPrevia.diagnosticos[0];
            if (diagnosticoPrincipal) {
                setTimeout(function() {
                    cargarDiagnosticoPrincipalConDatos(diagnosticoPrincipal);
                }, 100);
            }
            
            if (historiaPrevia.diagnosticos.length > 1) {
                for (let i = 1; i < historiaPrevia.diagnosticos.length; i++) {
                    setTimeout(function() {
                        agregarDiagnosticoAdicionalConDatos(historiaPrevia.diagnosticos[i]);
                    }, (i + 1) * 200);
                }
            }
        }

        // ✅ CARGAR TALLA
        if (historiaPrevia.talla) {
            $('#talla').val(historiaPrevia.talla);
            console.log('📏 Talla cargada:', historiaPrevia.talla);
        }

        console.log('✅ Datos previos cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}

// ============================================
// ✅ DOCUMENT.READY
// ============================================
$(document).ready(function() {
    console.log('🔍 Iniciando script de control.blade.php');
    console.log('🔍 Datos de la vista:', {
        especialidad: '{{ $especialidad ?? "N/A" }}',
        tipo_consulta: '{{ $tipo_consulta ?? "N/A" }}',
        tiene_historia_previa: {{ isset($historiaPrevia) && !empty($historiaPrevia) ? 'true' : 'false' }}
    });

    // ✅ CARGAR DATOS PREVIOS
    @if(isset($historiaPrevia) && !empty($historiaPrevia))
        console.log('🔄 Cargando datos previos');
        const historiaPrevia = @json($historiaPrevia);
        console.log('📦 Datos:', historiaPrevia);
        
        setTimeout(function() {
            cargarDatosPrevios(historiaPrevia);
        }, 500);
    @else
        console.log('ℹ️ No se cargan datos previos');
    @endif

    // ============================================
    // ✅ CÁLCULO AUTOMÁTICO DE IMC
    // ============================================
    $('#peso, #talla').on('input', function() {
        calcularIMC();
    });
    
    function calcularIMC() {
        const peso = parseFloat($('#peso').val());
        const talla = parseFloat($('#talla').val());
        
        if (peso && talla && talla > 0) {
            const tallaMts = talla / 100;
            const imc = peso / (tallaMts * tallaMts);
            const imcRedondeado = Math.round(imc * 100) / 100;
            
            $('#imc').val(imcRedondeado);
            $('#clasificacion_imc').val(clasificarIMC(imcRedondeado));
        } else {
            $('#imc').val('');
            $('#clasificacion_imc').val('');
        }
    }
    
    function clasificarIMC(imc) {
        if (imc < 18.5) return 'Bajo peso';
        if (imc < 25) return 'Adecuado';
        if (imc < 30) return 'Sobrepeso';
        if (imc < 35) return 'Obesidad grado 1';
        if (imc < 40) return 'Obesidad grado 2';
        return 'Obesidad grado 3';
    }

    // ✅ CALCULAR IMC AL CARGAR SI YA HAY DATOS
    if ($('#peso').val() && $('#talla').val()) {
        calcularIMC();
    }

    // ============================================
    // ✅ BÚSQUEDA DE DIAGNÓSTICOS PRINCIPAL
    // ============================================
    let diagnosticoTimeout;
    $('#buscar_diagnostico').on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(diagnosticoTimeout);
        
        if (termino.length < 2) {
            $('#diagnosticos_resultados').removeClass('show').empty();
            return;
        }
        
        diagnosticoTimeout = setTimeout(() => {
            buscarDiagnosticos(termino);
        }, 300);
    });
    
    function buscarDiagnosticos(termino) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-diagnosticos") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosDiagnosticos(response.data);
                } else {
                    console.error('Error buscando diagnósticos:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando diagnósticos:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosDiagnosticos(diagnosticos) {
        const $resultados = $('#diagnosticos_resultados');
        $resultados.empty();
        
        if (diagnosticos.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron diagnósticos</div>');
        } else {
            diagnosticos.forEach(function(diagnostico) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${diagnostico.codigo}</strong> - ${diagnostico.nombre}`)
                    .data('diagnostico', diagnostico);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarDiagnostico(diagnostico);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarDiagnostico(diagnostico) {
        diagnosticoSeleccionado = diagnostico;
        
        $('#buscar_diagnostico').val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $('#idDiagnostico').val(diagnostico.uuid || diagnostico.id);
        $('#diagnostico_info').text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $('#diagnostico_seleccionado').show();
        $('#diagnosticos_resultados').removeClass('show').empty();
    }

    // ============================================
    // ✅ CERRAR DROPDOWNS AL HACER CLICK FUERA
    // ============================================
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // ============================================
    // ✅ AGREGAR DIAGNÓSTICO ADICIONAL
    // ============================================
    $('#agregar_diagnostico_adicional').on('click', function() {
        agregarDiagnosticoAdicional();
    });

    // ✅ ELIMINAR DIAGNÓSTICO ADICIONAL
    $(document).on('click', '.eliminar-diagnostico-adicional', function() {
        $(this).closest('.diagnostico-adicional-item').remove();
    });

    // ============================================
    // ✅ AGREGAR REMISIÓN
    // ============================================
    $('#agregar_remision').on('click', function() {
        agregarRemision();
    });

    // ✅ ELIMINAR REMISIÓN
    $(document).on('click', '.eliminar-remision', function() {
        $(this).closest('.remision-item').remove();
    });

    // ============================================
    // ✅ RECONOCIMIENTO DE VOZ PARA OBSERVACIONES
    // ============================================
    const botonMicrofono = document.getElementById('microfono');
    const campoTexto = document.getElementById('observaciones_generales');

    let recognition = null;

    if (botonMicrofono && campoTexto) {
        botonMicrofono.addEventListener('click', function() {
            if (!recognition) {
                if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
                    recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();

                    recognition.lang = 'es-ES';

                    recognition.onstart = function() {
                        botonMicrofono.classList.add('active');
                    };

                    recognition.onend = function() {
                        botonMicrofono.classList.remove('active');
                    };

                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        campoTexto.value += ' ' + transcript;
                    };

                    recognition.start();
                } else {
                    alert('El reconocimiento de voz no es compatible con este navegador.');
                }
            } else {
                recognition.stop();
                recognition = null;
            }
        });
    }

    // ============================================
    // ✅ ENVÍO DEL FORMULARIO
    // ============================================
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        console.log('📤 Iniciando envío del formulario...');
        
        const citaUuid = $('input[name="cita_uuid"]').val();
        console.log('🔍 Cita UUID detectado:', citaUuid);
        
        if (!validarFormulario()) {
            console.log('❌ Validación fallida');
            return;
        }
        
        console.log('✅ Validación exitosa, preparando envío...');
        
        $('#loading_overlay').show();
        
        const formData = new FormData(this);
        let respuestaProcesada = false;
        
        const timeoutId = setTimeout(function() {
            if (respuestaProcesada) {
                console.log('⏰ Timeout ignorado - respuesta ya procesada');
                return;
            }
            
            console.log('⏰ Timeout alcanzado (15s), procesando...');
            respuestaProcesada = true;
            
            $('#loading_overlay').hide();
            
            dispararEventoHistoriaGuardada(citaUuid, null, false);
            
            Swal.fire({
                icon: 'info',
                title: 'Procesando...',
                text: 'La historia clínica se está guardando. Será redirigido al cronograma.',
                timer: 2000,
                showConfirmButton: false,
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '{{ route("cronograma.index") }}';
            });
        }, 15000);
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000,
            success: function(response) {
                if (respuestaProcesada) {
                    console.log('⚠️ Respuesta ignorada - ya se procesó por timeout');
                    return;
                }
                
                respuestaProcesada = true;
                clearTimeout(timeoutId);
                
                console.log('✅ Respuesta recibida:', response);
                
                $('#loading_overlay').hide();
                
                if (response.success) {
                    dispararEventoHistoriaGuardada(
                        citaUuid,
                        response.historia_uuid || null,
                        response.offline || false
                    );
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Control guardado exitosamente. Cita marcada como atendida.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(() => {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.href = '{{ route("cronograma.index") }}';
                        }
                    });
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Error guardando el control',
                        confirmButtonText: 'Entendido',
                        allowOutsideClick: false
                    });
                }
            },
            error: function(xhr, status, error) {
                if (respuestaProcesada) {
                    console.log('⚠️ Error ignorado - ya se procesó por timeout');
                    return;
                }
                
                respuestaProcesada = true;
                clearTimeout(timeoutId);
                
                console.error('❌ Error en AJAX:', {
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                $('#loading_overlay').hide();
                
                let errorMessage = 'Error interno del servidor';
                let shouldRedirect = false;
                
                if (status === 'timeout') {
                    errorMessage = 'La solicitud tardó demasiado. La historia clínica puede haberse guardado correctamente.';
                    shouldRedirect = true;
                    dispararEventoHistoriaGuardada(citaUuid, null, false);
                    
                } else if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                } else if (xhr.responseJSON?.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.status === 0) {
                    errorMessage = 'No se pudo conectar con el servidor. Verifique su conexión.';
                }
                
                Swal.fire({
                    icon: shouldRedirect ? 'warning' : 'error',
                    title: shouldRedirect ? 'Atención' : 'Error',
                    html: errorMessage.replace(/\n/g, '<br>'),
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false
                }).then(() => {
                    if (shouldRedirect) {
                        window.location.href = '{{ route("cronograma.index") }}';
                    }
                });
            },
            complete: function() {
                console.log('🏁 Petición AJAX completada');
                
                setTimeout(function() {
                    $('#loading_overlay').hide();
                }, 100);
            }
        });
    });

    // ============================================
    // ✅ FUNCIÓN DE VALIDACIÓN
    // ============================================
    function validarFormulario() {
        // Validar motivo
        if (!$('#motivo').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar el motivo de consulta'
            });
            $('#motivo').focus();
            return false;
        }
        
        // Validar peso
        if (!$('#peso').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar el peso del paciente'
            });
            $('#peso').focus();
            return false;
        }
        
        // Validar talla
        if (!$('#talla').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la talla del paciente'
            });
            $('#talla').focus();
            return false;
        }
        
        // Validar perímetro abdominal
        if (!$('#perimetro_abdominal').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar el perímetro abdominal'
            });
            $('#perimetro_abdominal').focus();
            return false;
        }
        
        // Validar observaciones generales
        if (!$('#observaciones_generales').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar las observaciones generales'
            });
            $('#observaciones_generales').focus();
            return false;
        }
        
        // Validar diagnóstico principal
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            $('#buscar_diagnostico').focus();
            return false;
        }
        
        // Validar finalidad
        if (!$('#finalidad').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la finalidad de la consulta'
            });
            $('#finalidad').focus();
            return false;
        }
        
        return true;
    }

}); // ✅ FIN DOCUMENT.READY
</script>
@endpush
