<script>
// ============================================
// ✅ VARIABLES GLOBALES
// ============================================
let diagnosticoAdicionalCounter = 0;
let remisionCounter = 0;
let cupsCounter = 0;
let diagnosticoSeleccionado = null;

// ============================================
// ✅ FUNCIONES PRINCIPALES (FUERA DE DOCUMENT.READY)
// ============================================

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
 * ✅✅✅ AGREGAR REMISIÓN CON DATOS (DEL CONTROL) ✅✅✅
 */
function agregarRemisionConDatos(remision) {
    console.log('📋 Agregando remisión con datos:', remision);
    
    agregarRemision();
    
    setTimeout(function() {
        const $ultimaRemision = $('#remisiones_container .remision-item:last');
        
        $ultimaRemision.find('.buscar-remision').val(remision.remision.nombre);
        $ultimaRemision.find('.remision-id').val(remision.remision_id);
        $ultimaRemision.find('.remision-info').html(`<strong>${remision.remision.nombre}</strong><br><small>${remision.remision.tipo || ''}</small>`);
        $ultimaRemision.find('.remision-seleccionada').show();
        $ultimaRemision.find('textarea[name*="remObservacion"]').val(remision.observacion || '');
        
        console.log('✅ Remisión agregada exitosamente');
    }, 100);
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
 * ✅✅✅ AGREGAR DIAGNÓSTICO ADICIONAL CON DATOS (DEL CONTROL) ✅✅✅
 */
function agregarDiagnosticoAdicionalConDatos(diagnostico) {
    console.log('🩺 Agregando diagnóstico adicional con datos:', diagnostico);
    
    agregarDiagnosticoAdicional();
    
    setTimeout(function() {
        const $ultimoDiagnostico = $('#diagnosticos_adicionales_container .diagnostico-adicional-item:last');
        
        $ultimoDiagnostico.find('.buscar-diagnostico-adicional').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $ultimoDiagnostico.find('.diagnostico-adicional-id').val(diagnostico.diagnostico_id);
        $ultimoDiagnostico.find('.diagnostico-adicional-info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $ultimoDiagnostico.find('.diagnostico-adicional-seleccionado').show();
        $ultimoDiagnostico.find('select[name*="tipo_diagnostico"]').val(diagnostico.tipo_diagnostico || 'IMPRESION_DIAGNOSTICA');
        
        console.log('✅ Diagnóstico adicional agregado exitosamente');
    }, 100);
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
 * ✅✅✅ CARGAR DIAGNÓSTICO PRINCIPAL CON DATOS (DEL CONTROL) ✅✅✅
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
 * ✅✅✅ CARGAR DATOS PREVIOS (DEL CONTROL) ✅✅✅
 */
function cargarDatosPrevios(historiaPrevia) {
    try {
        console.log('🔄 Iniciando carga de datos previos de FISIOTERAPIA');
        console.log('📦 Historia previa recibida:', historiaPrevia);

        // ✅ CARGAR REMISIONES
        if (historiaPrevia.remisiones && historiaPrevia.remisiones.length > 0) {
            console.log('📋 Cargando remisiones previas:', historiaPrevia.remisiones.length);
            historiaPrevia.remisiones.forEach(function(remision, index) {
                setTimeout(function() {
                    agregarRemisionConDatos(remision);
                }, index * 300);
            });
        }

        // ✅ CARGAR DIAGNÓSTICOS
        if (historiaPrevia.diagnosticos && historiaPrevia.diagnosticos.length > 0) {
            console.log('🩺 Cargando diagnósticos previos:', historiaPrevia.diagnosticos.length);
            
            // Diagnóstico principal
            const diagnosticoPrincipal = historiaPrevia.diagnosticos[0];
            if (diagnosticoPrincipal) {
                setTimeout(function() {
                    cargarDiagnosticoPrincipalConDatos(diagnosticoPrincipal);
                }, 100);
            }
            
            // Diagnósticos adicionales
            if (historiaPrevia.diagnosticos.length > 1) {
                for (let i = 1; i < historiaPrevia.diagnosticos.length; i++) {
                    setTimeout(function() {
                        agregarDiagnosticoAdicionalConDatos(historiaPrevia.diagnosticos[i]);
                    }, (i + 1) * 300);
                }
            }
        }

    
        
        // ✅ CARGAR TALLA
        if (historiaPrevia.talla) {
            $('#talla').val(historiaPrevia.talla);
            console.log('📏 Talla cargada:', historiaPrevia.talla);
        }

        console.log('✅ Datos previos de FISIOTERAPIA cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}

/**
 * ✅ AGREGAR CUPS CON DATOS
 */
function agregarCupsConDatos(cups) {
    console.log('🏥 Agregando CUPS con datos:', cups);
    
    agregarCups();
    
    setTimeout(function() {
        const $ultimoCups = $('#cups_container .cups-item:last');
        
        $ultimoCups.find('.buscar-cups').val(`${cups.cups.codigo} - ${cups.cups.nombre}`);
        $ultimoCups.find('.cups-id').val(cups.cups_id);
        $ultimoCups.find('.cups-info').text(`${cups.cups.codigo} - ${cups.cups.nombre}`);
        $ultimoCups.find('.cups-seleccionado').show();
        $ultimoCups.find('input[name*="cantidad"]').val(cups.cantidad || 1);
        $ultimoCups.find('textarea[name*="observaciones"]').val(cups.observaciones || '');
        
        console.log('✅ CUPS agregado exitosamente');
    }, 100);
}

// ============================================
// ✅ DOCUMENT.READY
// ============================================
$(document).ready(function() {
    console.log('🔍 Iniciando script de primera-vez.blade.php FISIOTERAPIA');
    console.log('🔍 Datos de la vista:', {
        especialidad: '{{ $especialidad ?? "N/A" }}',
        tipo_consulta: '{{ $tipo_consulta ?? "N/A" }}',
        tiene_historia_previa: {{ isset($historiaPrevia) && !empty($historiaPrevia) ? 'true' : 'false' }}
    });

    // ✅✅✅ CARGAR DATOS PREVIOS SI EXISTEN ✅✅✅
    @if(isset($historiaPrevia) && !empty($historiaPrevia))
        console.log('🔄 Detectada historia previa, iniciando carga...');
        const historiaPrevia = @json($historiaPrevia);
        console.log('📦 Historia previa:', historiaPrevia);
        
        setTimeout(function() {
            cargarDatosPrevios(historiaPrevia);
        }, 500);
    @else
        console.log('ℹ️ No hay historia previa para cargar');
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
        if (imc < 25) return 'Normal';
        if (imc < 30) return 'Sobrepeso';
        if (imc < 35) return 'Obesidad grado I';
        if (imc < 40) return 'Obesidad grado II';
        return 'Obesidad grado III';
    }

    // ✅ CALCULAR IMC AL CARGAR SI YA HAY DATOS
    if ($('#peso').val() && $('#talla').val()) {
        calcularIMC();
    }

    // ============================================
    // ✅ BÚSQUEDA DE DIAGNÓSTICO PRINCIPAL
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
    // ✅ AGREGAR CUPS
    // ============================================
    $('#agregar_cups').on('click', function() {
        agregarCups();
    });

    // ✅ ELIMINAR CUPS
    $(document).on('click', '.eliminar-cups', function() {
        $(this).closest('.cups-item').remove();
    });

    // ============================================
    // ✅✅✅ ENVÍO DEL FORMULARIO ✅✅✅
    // ============================================
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        console.log('📤 Iniciando envío del formulario de FISIOTERAPIA...');
        
        const citaUuid = $('input[name="cita_uuid"]').val();
        console.log('🔍 Cita UUID detectado:', citaUuid);
        
        // Validar diagnóstico principal
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            
            console.log('❌ Validación fallida - falta diagnóstico principal');
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
                        text: response.message || 'Historia clínica de fisioterapia guardada exitosamente. Cita marcada como atendida.',
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
                        text: response.error || 'Error guardando la historia clínica de fisioterapia',
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
                
                console.error('❌ Error en AJAX FISIOTERAPIA:', {
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
                console.log('🏁 Petición AJAX FISIOTERAPIA completada');
                
                setTimeout(function() {
                    $('#loading_overlay').hide();
                }, 100);
            }
        });
    });

}); // ✅ FIN DOCUMENT.READY
</script>
