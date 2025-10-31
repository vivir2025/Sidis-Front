{{-- ✅ SCRIPT COMPLETO PARA PSICOLOGÍA PRIMERA VEZ --}}
<script>
$(document).ready(function() {
    // ✅ VARIABLES GLOBALES
    let diagnosticoSeleccionado = null;
    let diagnosticoAdicionalCounter = 0;
    let medicamentoCounter = 0;
    let remisionCounter = 0;
    
    // ============================================
    // ✅ FUNCIÓN PARA DISPARAR EVENTO DE HISTORIA GUARDADA
    // ============================================
    function dispararEventoHistoriaGuardada(citaUuid, historiaUuid, offline) {
        console.log('📋 Disparando evento historiaClinicaGuardada', {
            citaUuid: citaUuid,
            historiaUuid: historiaUuid,
            offline: offline
        });
        
        window.dispatchEvent(new CustomEvent('historiaClinicaGuardada', {
            detail: {
                cita_uuid: citaUuid,
                historia_uuid: historiaUuid,
                offline: offline || false
            }
        }));
        
        console.log('✅ Evento disparado exitosamente');
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
    // ✅ DIAGNÓSTICOS ADICIONALES
    // ============================================
    $('#agregar_diagnostico_adicional').on('click', function() {
        agregarDiagnosticoAdicional();
    });
    
    function agregarDiagnosticoAdicional() {
        const template = $('#diagnostico_adicional_template').html();
        const $diagnostico = $(template);
        
        // Actualizar índices de los arrays
        $diagnostico.find('input[name*="diagnosticos"], select[name*="diagnosticos"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${diagnosticoAdicionalCounter}]`));
        });
        
        $('#diagnosticos_adicionales_container').append($diagnostico);
        diagnosticoAdicionalCounter++;
        
        // Configurar búsqueda para este diagnóstico
        configurarBusquedaDiagnosticoAdicional($diagnostico);
    }
    
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
    
    function seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $hiddenId.val(diagnostico.uuid || diagnostico.id);
        $info.text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }

    // ✅ ELIMINAR DIAGNÓSTICO ADICIONAL
    $(document).on('click', '.eliminar-diagnostico-adicional', function() {
        $(this).closest('.diagnostico-adicional-item').remove();
    });
    
    // ============================================
    // ✅ MEDICAMENTOS
    // ============================================
    $('#agregar_medicamento').on('click', function() {
        agregarMedicamento();
    });
    
    function agregarMedicamento() {
        const template = $('#medicamento_template').html();
        const $medicamento = $(template);
        
        // Actualizar índices de los arrays
        $medicamento.find('input[name*="medicamentos"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${medicamentoCounter}]`));
        });
        
        $('#medicamentos_container').append($medicamento);
        medicamentoCounter++;
        
        // Configurar búsqueda para este medicamento
        configurarBusquedaMedicamento($medicamento);
    }
    
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
    
    function buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-medicamentos") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosMedicamentos(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando medicamentos:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando medicamentos:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosMedicamentos(medicamentos, $resultados, $input, $hiddenId, $info, $alert) {
        $resultados.empty();
        
        if (medicamentos.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron medicamentos</div>');
        } else {
            medicamentos.forEach(function(medicamento) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${medicamento.nombre}</strong><br><small class="text-muted">${medicamento.descripcion || ''}</small>`)
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
    
    function seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(medicamento.nombre);
        $hiddenId.val(medicamento.uuid || medicamento.id);
        $info.html(`<strong>${medicamento.nombre}</strong><br><small>${medicamento.descripcion || ''}</small>`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }
    
    // ✅ ELIMINAR MEDICAMENTO
    $(document).on('click', '.eliminar-medicamento', function() {
        $(this).closest('.medicamento-item').remove();
    });
    
    // ============================================
    // ✅ REMISIONES
    // ============================================
    $('#agregar_remision').on('click', function() {
        agregarRemision();
    });
    
    function agregarRemision() {
        const template = $('#remision_template').html();
        const $remision = $(template);
        
        // Actualizar índices de los arrays
        $remision.find('input[name*="remisiones"], textarea[name*="remisiones"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${remisionCounter}]`));
        });
        
        $('#remisiones_container').append($remision);
        remisionCounter++;
        
        // Configurar búsqueda para esta remisión
        configurarBusquedaRemision($remision);
    }
    
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
    
    function buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-remisiones") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosRemisiones(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando remisiones:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando remisiones:', xhr.responseText);
            }
        });
    }
    
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
    
    function seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(remision.nombre);
        $hiddenId.val(remision.uuid || remision.id);
        $info.html(`<strong>${remision.nombre}</strong><br><small>${remision.tipo || ''}</small>`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }
    
    // ✅ ELIMINAR REMISIÓN
    $(document).on('click', '.eliminar-remision', function() {
        $(this).closest('.remision-item').remove();
    });
    
    // ============================================
    // ✅ ENVÍO DEL FORMULARIO
    // ============================================
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        console.log('📤 Iniciando envío del formulario de PSICOLOGÍA PRIMERA VEZ...');
        
        // ✅ OBTENER CITA UUID
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
        
        // Validar campos requeridos de psicología
        if (!$('#motivo').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El motivo de consulta es obligatorio'
            });
            return;
        }
        
        if (!$('#psicologia_descripcion_problema').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La descripción del problema es obligatoria'
            });
            return;
        }
        
        if (!$('#psicologia_plan_intervencion_recomendacion').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El plan de intervención es obligatorio'
            });
            return;
        }
        
        console.log('✅ Validación exitosa, preparando envío...');
        
        // Mostrar loading
        $('#loading_overlay').show();
        
        // Preparar datos
        const formData = new FormData(this);
        
        // ✅ VARIABLE PARA CONTROLAR SI YA SE PROCESÓ LA RESPUESTA
        let respuestaProcesada = false;
        
        // ✅ TIMEOUT DE 15 SEGUNDOS
        const timeoutId = setTimeout(function() {
            if (respuestaProcesada) {
                console.log('⏰ Timeout ignorado - respuesta ya procesada');
                return;
            }
            
            console.log('⏰ Timeout alcanzado (15s), procesando...');
            respuestaProcesada = true;
            
            $('#loading_overlay').hide();
            
            // ✅ DISPARAR EVENTO INCLUSO EN TIMEOUT
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
                    // ✅ DISPARAR EVENTO DE HISTORIA GUARDADA
                    dispararEventoHistoriaGuardada(
                        citaUuid,
                        response.historia_uuid || null,
                        response.offline || false
                    );
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Historia clínica de psicología guardada exitosamente.',
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
                        text: response.error || 'Error guardando la historia clínica',
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
                
                console.error('❌ Error en AJAX PSICOLOGÍA:', {
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
                console.log('🏁 Petición AJAX PSICOLOGÍA completada');
                setTimeout(function() {
                    $('#loading_overlay').hide();
                }, 100);
            }
        });
    });

}); // ✅ CERRAR $(document).ready
</script>
