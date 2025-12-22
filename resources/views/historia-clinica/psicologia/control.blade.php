{{-- resources/views/historia-clinica/psicologia/control.blade.php --}}
@extends('layouts.app')

@section('title', 'Control Psicología')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')
    
    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="CONTROL">
        
        {{-- ✅ SECCIÓN: DATOS BÁSICOS --}}
        @include('historia-clinica.partials.datos-basicos')

        {{-- ✅ SECCIÓN: ACUDIENTE --}}
        @include('historia-clinica.partials.acudiente')

        {{-- ✅ SECCIÓN: HISTORIA CLÍNICA - CONTROL PSICOLOGÍA --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Historia Clínica - Control Psicología
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- ✅ MOTIVO DE CONSULTA --}}
                    <div class="col-md-12 mb-3">
                        <label for="motivo" class="form-label">
                            Motivo de Consulta <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <textarea 
                                class="form-control" 
                                id="motivo" 
                                name="motivo" 
                                rows="3" 
                                required
                                placeholder="Describa el motivo de la consulta..."
                            ></textarea>
                            <span class="microfono" id="microfono_motivo" title="Dictar por voz"></span>
                        </div>
                    </div>

                    {{-- ✅ DESCRIPCIÓN DEL PROBLEMA --}}
                    <div class="col-md-12 mb-3">
                        <label for="psicologia_descripcion_problema" class="form-label">
                            Descripción del Problema
                        </label>
                        <div class="position-relative">
                            <textarea 
                                class="form-control" 
                                id="psicologia_descripcion_problema" 
                                name="psicologia_descripcion_problema" 
                                rows="4"
                                placeholder="Describa la situación actual del paciente..."
                            ></textarea>
                            <span class="microfono" id="microfono_descripcion" title="Dictar por voz"></span>
                        </div>
                    </div>

                    {{-- ✅ PLAN DE INTERVENCIÓN Y RECOMENDACIONES --}}
                    <div class="col-md-12 mb-3">
                        <label for="psicologia_plan_intervencion_recomendacion" class="form-label">
                            Plan de Intervención y Recomendaciones
                        </label>
                        <div class="position-relative">
                            <textarea 
                                class="form-control" 
                                id="psicologia_plan_intervencion_recomendacion" 
                                name="psicologia_plan_intervencion_recomendacion" 
                                rows="4"
                                placeholder="Describa el plan de intervención y las recomendaciones..."
                            ></textarea>
                            <span class="microfono" id="microfono_plan" title="Dictar por voz"></span>
                        </div>
                    </div>

                    {{-- ✅ AVANCE DEL PACIENTE --}}
                    <div class="col-md-12 mb-3">
                        <label for="avance_paciente" class="form-label">
                            Avance del Paciente
                        </label>
                        <div class="position-relative">
                            <textarea 
                                class="form-control" 
                                id="avance_paciente" 
                                name="avance_paciente" 
                                rows="4"
                                placeholder="Describa el avance y evolución del paciente..."
                            ></textarea>
                            <span class="microfono" id="microfono_avance" title="Dictar por voz"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
        @include('historia-clinica.partials.diagnostico-principal')
        
        {{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES --}}
        @include('historia-clinica.partials.diagnosticos-adicionales')
        
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
let diagnosticoSeleccionado = null;

// ============================================
// ✅ FUNCIONES PRINCIPALES
// ============================================

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
 * ✅ DISPARAR EVENTO DE HISTORIA GUARDADA
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

        // ✅ CARGAR CAMPOS COMPLEMENTARIOS
        if (historiaPrevia.complementaria) {
            console.log('📋 Cargando campos complementarios');
            
            if (historiaPrevia.complementaria.psicologia_descripcion_problema) {
                $('#psicologia_descripcion_problema').val(historiaPrevia.complementaria.psicologia_descripcion_problema);
            }
            
            if (historiaPrevia.complementaria.psicologia_plan_intervencion_recomendacion) {
                $('#psicologia_plan_intervencion_recomendacion').val(historiaPrevia.complementaria.psicologia_plan_intervencion_recomendacion);
            }
            
            if (historiaPrevia.complementaria.avance_paciente) {
                $('#avance_paciente').val(historiaPrevia.complementaria.avance_paciente);
            }
        }

        console.log('✅ Datos previos cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}

/**
 * ✅ CONFIGURAR RECONOCIMIENTO DE VOZ
 */
function configurarReconocimientoVoz(botonId, campoId) {
    const boton = document.getElementById(botonId);
    const campo = document.getElementById(campoId);
    
    if (!boton || !campo) return;
    
    let recognition = null;
    
    boton.addEventListener('click', function() {
        if (!recognition) {
            if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
                recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
                recognition.lang = 'es-ES';
                recognition.continuous = false;
                recognition.interimResults = false;
                
                recognition.onstart = function() {
                    boton.classList.add('active');
                };
                
                recognition.onend = function() {
                    boton.classList.remove('active');
                    recognition = null;
                };
                
                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    campo.value += (campo.value ? ' ' : '') + transcript;
                };
                
                recognition.onerror = function(event) {
                    console.error('Error en reconocimiento de voz:', event.error);
                    boton.classList.remove('active');
                    recognition = null;
                };
                
                recognition.start();
            } else {
                alert('El reconocimiento de voz no es compatible con este navegador.');
            }
        } else {
            recognition.stop();
        }
    });
}

// ============================================
// ✅ DOCUMENT.READY
// ============================================
$(document).ready(function() {
    console.log('🔍 Iniciando script de control.blade.php - Psicología');

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
    // ✅ RECONOCIMIENTO DE VOZ
    // ============================================
    configurarReconocimientoVoz('microfono_motivo', 'motivo');
    configurarReconocimientoVoz('microfono_descripcion', 'psicologia_descripcion_problema');
    configurarReconocimientoVoz('microfono_plan', 'psicologia_plan_intervencion_recomendacion');
    configurarReconocimientoVoz('microfono_avance', 'avance_paciente');

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
            if (respuestaProcesada) return;
            
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
                if (respuestaProcesada) return;
                
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
                        text: response.message || 'Control guardado exitosamente.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = response.redirect_url || '{{ route("cronograma.index") }}';
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
                if (respuestaProcesada) return;
                
                respuestaProcesada = true;
                clearTimeout(timeoutId);
                
                console.error('❌ Error en AJAX:', {
                    status: xhr.status,
                    statusText: status,
                    error: error
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
