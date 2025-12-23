{{-- resources/views/historia-clinica/internista/control.blade.php --}}
@extends('layouts.app')

@section('title', 'Control Internista')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')
    
    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="{{ $tipo_consulta ?? 'CONTROL' }}">
        <input type="hidden" name="especialidad" value="INTERNISTA">
        
        {{-- ✅ SECCIÓN: DATOS BÁSICOS --}}
        @include('historia-clinica.partials.datos-basicos')

        {{-- ✅ SECCIÓN: ACUDIENTE --}}
        @include('historia-clinica.partials.acudiente')

       
        @include('historia-clinica.partials.historia-clinica-basica')
        @include('historia-clinica.partials.antecedentes-personalesint')
        @include('historia-clinica.partials.revision-por-sistema-int')

           <div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-weight me-2"></i>
            Medidas Antropométricas
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" step="0.1" class="form-control" id="peso" name="peso" min="0" max="300">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="talla" class="form-label">Talla (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="talla" name="talla" min="0" max="250">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="imc" class="form-label">IMC</label>
                    <input type="number" step="0.01" class="form-control" id="imc" name="imc" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="clasificacion_imc" class="form-label">Clasificación IMC</label>
                    <input type="text" class="form-control" id="clasificacion_imc" name="clasificacion_imc" readonly>
                </div>
            </div>

             <div class="col-md-3">
                <div class="mb-3">
                    <label for="perimetro_abdominal" class="form-label">Perímetro Abdominal (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="perimetro_abdominal" name="perimetro_abdominal" min="0" max="200">
                </div>
            </div>
           
        </div>
       
    </div>
</div>


        @include('historia-clinica.partials.signos-vitales')
        @include('historia-clinica.partials.analisis_plan_simple')
        @include('historia-clinica.partials.clasificaciones')
        
        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
        @include('historia-clinica.partials.diagnostico-principal')
        
        {{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES --}}
        @include('historia-clinica.partials.diagnosticos-adicionales')

        @include('historia-clinica.partials.medicamentos-section')
        @include('historia-clinica.partials.remisiones-section')
        @include('historia-clinica.partials.cups-section')
        
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

.diagnostico-adicional-item {
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

.required-field {
    border-left: 3px solid #dc3545;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ✅ ESTILOS PARA MICRÓFONO */
.position-relative {
    position: relative;
}

.microfono {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    background-color: transparent;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3zM19 10v1a7 7 0 0 1-14 0v-1a1 1 0 0 1 2 0v1a5 5 0 0 0 10 0v-1a1 1 0 0 1 2 0z"/><path d="M12 18.5a1 1 0 0 1 1 1V22a1 1 0 0 1-2 0v-2.5a1 1 0 0 1 1-1z"/></svg>');
    background-size: cover;
    cursor: pointer;
    z-index: 10;
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
let medicamentoCounter = 0;
let remisionCounter = 0;
let cupsCounter = 0;
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
 * ✅ AGREGAR CUPS
 */
function agregarCups() {
    const template = $('#cups_template').html();
    const $cups = $(template);
    
    $cups.find('input[name*="cups"], textarea[name*="cups"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${cupsCounter}]`));
    });
    
    $('#cups_container').append($cups);
    cupsCounter++;
    
    configurarBusquedaCups($cups);
}

/**
 * ✅ AGREGAR CUPS CON DATOS
 */
function agregarCupsConDatos(cups) {
    console.log('🏥 Agregando CUPS con datos:', cups);
    
    agregarCups();
    
    const $ultimoCups = $('#cups_container .cups-item:last');
    
    $ultimoCups.find('.buscar-cups').val(`${cups.cups.codigo} - ${cups.cups.nombre}`);
    $ultimoCups.find('.cups-id').val(cups.cups_id);
    $ultimoCups.find('.cups-info').text(`${cups.cups.codigo} - ${cups.cups.nombre}`);
    $ultimoCups.find('.cups-seleccionado').show();
    $ultimoCups.find('textarea[name*="cupObservacion"]').val(cups.observacion || '');
    
    console.log('✅ CUPS agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA CUPS
 */
function configurarBusquedaCups($container) {
    const $input = $container.find('.buscar-cups');
    const $resultados = $container.find('.cups-resultados');
    const $hiddenId = $container.find('.cups-id');
    const $info = $container.find('.cups-info');
    const $alert = $container.find('.cups-seleccionado');
    
    let cupsTimeout;
    
    $input.on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(cupsTimeout);
        
        if (termino.length < 2) {
            $resultados.removeClass('show').empty();
            return;
        }
        
        cupsTimeout = setTimeout(() => {
            buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert);
        }, 300);
    });
}

/**
 * ✅ BUSCAR CUPS
 */
function buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-cups") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosCups(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando CUPS:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS CUPS
 */
function mostrarResultadosCups(cups, $resultados, $input, $hiddenId, $info, $alert) {
    $resultados.empty();
    
    if (cups.length === 0) {
        $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron procedimientos</div>');
    } else {
        cups.forEach(function(cup) {
            const $item = $('<a href="#" class="dropdown-item"></a>')
                .html(`<strong>${cup.codigo}</strong> - ${cup.nombre}`)
                .data('cups', cup);
            
            $item.on('click', function(e) {
                e.preventDefault();
                seleccionarCups(cup, $input, $hiddenId, $info, $alert, $resultados);
            });
            
            $resultados.append($item);
        });
    }
    
    $resultados.addClass('show');
}

/**
 * ✅ SELECCIONAR CUPS
 */
function seleccionarCups(cups, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(`${cups.codigo} - ${cups.nombre}`);
    $hiddenId.val(cups.uuid || cups.id);
    $info.text(`${cups.codigo} - ${cups.nombre}`);
    $alert.show();
    $resultados.removeClass('show').empty();
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

         // ✅ CARGAR CUPS
        if (historiaPrevia.cups && historiaPrevia.cups.length > 0) {
            console.log('🏥 Cargando CUPS previos:', historiaPrevia.cups.length);
            historiaPrevia.cups.forEach(function(cups, index) {
                setTimeout(function() {
                    agregarCupsConDatos(cups);
                }, index * 200);
            });
        }

         // ✅ CARGAR MEDICAMENTOS
        if (historiaPrevia.medicamentos && historiaPrevia.medicamentos.length > 0) {
            console.log('💊 Cargando medicamentos previos:', historiaPrevia.medicamentos.length);
            historiaPrevia.medicamentos.forEach(function(medicamento, index) {
                setTimeout(function() {
                    agregarMedicamentoConDatos(medicamento);
                }, index * 200);
            });
        }

        // ✅ CARGAR TALLA
        if (historiaPrevia.talla) {
            $('#talla').val(historiaPrevia.talla);
            console.log('📏 Talla cargada:', historiaPrevia.talla);
        }

          // ✅ CARGAR CLASIFICACIONES
        if (historiaPrevia.clasificacion_estado_metabolico) {
            $('#clasificacion_estado_metabolico').val(historiaPrevia.clasificacion_estado_metabolico);
        }
        if (historiaPrevia.clasificacion_hta) {
            $('#clasificacion_hta').val(historiaPrevia.clasificacion_hta);
        }
        if (historiaPrevia.clasificacion_dm) {
            $('#clasificacion_dm').val(historiaPrevia.clasificacion_dm);
        }
        if (historiaPrevia.clasificacion_rcv) {
            $('#clasificacion_rcv').val(historiaPrevia.clasificacion_rcv);
        }
        if (historiaPrevia.clasificacion_erc_estado) {
            $('#clasificacion_erc_estado').val(historiaPrevia.clasificacion_erc_estado);
        }
        if (historiaPrevia.clasificacion_erc_estadodos) {
            $('#clasificacion_erc_estadodos').val(historiaPrevia.clasificacion_erc_estadodos);
        }


        if (historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente) {
            $('#clasificacion_erc_categoria_ambulatoria_persistente').val(historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente);
        }

        // ✅ CARGAR TASAS DE FILTRACIÓN
        if (historiaPrevia.tasa_filtracion_glomerular_ckd_epi) {
            $('#tasa_filtracion_glomerular_ckd_epi').val(historiaPrevia.tasa_filtracion_glomerular_ckd_epi);
        }
        if (historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault) {
            $('#tasa_filtracion_glomerular_gockcroft_gault').val(historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault);
        }

        console.log('✅ Datos previos cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}
 // ============================================
    // ✅ AGREGAR MEDICAMENTO
    // ============================================
    $('#agregar_medicamento').on('click', function() {
        agregarMedicamento();
    });

    // ✅ ELIMINAR MEDICAMENTO
    $(document).on('click', '.eliminar-medicamento', function() {
        $(this).closest('.medicamento-item').remove();
    });
/**
 * ✅ AGREGAR MEDICAMENTO
 */
function agregarMedicamento() {
    const template = $('#medicamento_template').html();
    const $medicamento = $(template);
    
    $medicamento.find('input[name*="medicamentos"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${medicamentoCounter}]`));
    });
    
    $('#medicamentos_container').append($medicamento);
    medicamentoCounter++;
    
    configurarBusquedaMedicamento($medicamento);
}

/**
 * ✅ AGREGAR MEDICAMENTO CON DATOS
 */
function agregarMedicamentoConDatos(medicamento) {
    console.log('💊 Agregando medicamento con datos:', medicamento);
    
    agregarMedicamento();
    
    const $ultimoMedicamento = $('#medicamentos_container .medicamento-item:last');
    
    $ultimoMedicamento.find('.buscar-medicamento').val(medicamento.medicamento.nombre);
    $ultimoMedicamento.find('.medicamento-id').val(medicamento.medicamento_id);
    $ultimoMedicamento.find('.medicamento-info').html(`<strong>${medicamento.medicamento.nombre}</strong><br><small>${medicamento.medicamento.principio_activo || ''}</small>`);
    $ultimoMedicamento.find('.medicamento-seleccionado').show();
    $ultimoMedicamento.find('input[name*="cantidad"]').val(medicamento.cantidad || '');
    $ultimoMedicamento.find('input[name*="dosis"]').val(medicamento.dosis || '');
    
    console.log('✅ Medicamento agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA MEDICAMENTO
 */
function configurarBusquedaMedicamento($container) {
    const $input = $container.find('.buscar-medicamento');
    const $resultados = $container.find('.medicamentos-resultados');
    const $hiddenId = $container.find('.medicamento-id');
    const $info = $container.find('.medicamento-info');
    const $alert = $container.find('.medicamento-seleccionado');
    
    let medicamentoTimeout;
    
    $input.on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(medicamentoTimeout);
        
        if (termino.length < 2) {
            $resultados.removeClass('show').empty();
            return;
        }
        
        medicamentoTimeout = setTimeout(() => {
            buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert);
        }, 300);
    });
}

/**
 * ✅ BUSCAR MEDICAMENTOS
 */
function buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-medicamentos") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosMedicamentos(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando medicamentos:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS MEDICAMENTOS
 */
function mostrarResultadosMedicamentos(medicamentos, $resultados, $input, $hiddenId, $info, $alert) {
    $resultados.empty();
    
    if (medicamentos.length === 0) {
        $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron medicamentos</div>');
    } else {
        medicamentos.forEach(function(medicamento) {
            const $item = $('<a href="#" class="dropdown-item"></a>')
                .html(`<strong>${medicamento.nombre}</strong><br><small class="text-muted">${medicamento.principio_activo || ''}</small>`)
                .data('medicamento', medicamento);
            
            $item.on('click', function(e) {
                e.preventDefault();
                seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados);
            });
            
            $resultados.append($item);
        });
    }
    
    $resultados.addClass('show');
}

/**
 * ✅ SELECCIONAR MEDICAMENTO
 */
function seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(medicamento.nombre);
    $hiddenId.val(medicamento.uuid || medicamento.id);
    $info.html(`<strong>${medicamento.nombre}</strong><br><small>${medicamento.principio_activo || ''}</small>`);
    $alert.show();
    $resultados.removeClass('show').empty();
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
// ✅ CÁLCULO AUTOMÁTICO DE CLASIFICACIÓN ERC
// ============================================

/**
 * Clasificar ERC según tasa de filtración glomerular
 * Basado en KDIGO 2012
 */
function clasificarERC(tfg) {
    tfg = parseFloat(tfg);
    
    if (isNaN(tfg) || tfg < 0) {
        return '';
    }
    
    if (tfg >= 90) {
        return 'ESTADIO_1'; // G1: Normal o elevado (≥ 90)
    } else if (tfg >= 60) {
        return 'ESTADIO_2'; // G2: Ligeramente disminuido (60-89)
    } else if (tfg >= 45) {
        return 'ESTADIO_3A'; // G3a: Ligera a moderadamente disminuido (45-59)
    } else if (tfg >= 30) {
        return 'ESTADIO_3B'; // G3b: Moderada a gravemente disminuido (30-44)
    } else if (tfg >= 15) {
        return 'ESTADIO_4'; // G4: Gravemente disminuido (15-29)
    } else {
        return 'ESTADIO_5'; // G5: Fallo renal (< 15)
    }
}

/**
 * Calcular clasificación ERC basada en CKD-EPI
 */
$('#tasa_filtracion_glomerular_ckd_epi').on('input', function() {
    const tfg = $(this).val();
    const clasificacion = clasificarERC(tfg);
    
    if (clasificacion) {
        $('#clasificacion_erc_estado').val(clasificacion);
        console.log('✅ Clasificación ERC (CKD-EPI) calculada:', clasificacion, 'TFG:', tfg);
    } else {
        $('#clasificacion_erc_estado').val('');
    }
});

/**
 * Calcular clasificación ERC basada en Cockcroft-Gault
 */
$('#tasa_filtracion_glomerular_gockcroft_gault').on('input', function() {
    const tfg = $(this).val();
    const clasificacion = clasificarERC(tfg);
    
    if (clasificacion) {
        $('#clasificacion_erc_estadodos').val(clasificacion);
        console.log('✅ Clasificación ERC (Cockcroft-Gault) calculada:', clasificacion, 'TFG:', tfg);
    } else {
        $('#clasificacion_erc_estadodos').val('');
    }
});

// ✅ CALCULAR AL CARGAR SI YA HAY DATOS
if ($('#tasa_filtracion_glomerular_ckd_epi').val()) {
    $('#tasa_filtracion_glomerular_ckd_epi').trigger('input');
}

if ($('#tasa_filtracion_glomerular_gockcroft_gault').val()) {
    $('#tasa_filtracion_glomerular_gockcroft_gault').trigger('input');
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
     // ============================================
    // ✅ AGREGAR CUPS
    // ============================================
    $('#agregar_cups').on('click', function() {
        agregarCups();
    });

    // ✅ ELIMINAR CUPS
    $(document).on('click', '.eliminar-cups', function() {
        $(this).closest('.cups-item').remove();
    });
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
