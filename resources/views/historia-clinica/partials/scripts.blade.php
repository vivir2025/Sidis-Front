<script>
$(document).ready(function() {
    // ✅ VARIABLES GLOBALES
    let medicamentoCounter = 0;
    let diagnosticoAdicionalCounter = 0;
    let remisionCounter = 0;
    let cupsCounter = 0;
    let diagnosticoSeleccionado = null;
    
    // ============================================
    // ✅✅✅ FUNCIÓN PARA DISPARAR EVENTO DE HISTORIA GUARDADA ✅✅✅
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
    
    // ✅ CÁLCULO AUTOMÁTICO DE IMC
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
    
    // ✅ HABILITAR/DESHABILITAR CAMPOS DE ANTECEDENTES FAMILIARES
    $('.antecedente-familiar').on('change', function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        const textareaId = 'parentesco_' + name;
        
        if (value === 'SI') {
            $('#' + textareaId).prop('disabled', false).focus();
        } else {
            $('#' + textareaId).prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPOS DE ANTECEDENTES PERSONALES
    $('.antecedente-personal').on('change', function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        const textareaId = 'obs_' + name;
        
        if (value === 'SI') {
            $('#' + textareaId).prop('disabled', false).focus();
        } else {
            $('#' + textareaId).prop('disabled', true).val('');
        }
    });
    
    // ✅ HABILITAR/DESHABILITAR CAMPO DE DROGA
    $('input[name="drogodependiente"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#drogodependiente_cual').prop('disabled', false).focus();
        } else {
            $('#drogodependiente_cual').prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPO DE LESIÓN ÓRGANO BLANCO
    $('input[name="lesion_organo_blanco"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#descripcion_lesion_organo_blanco').prop('disabled', false).focus();
        } else {
            $('#descripcion_lesion_organo_blanco').prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPOS DE HTA Y DM PERSONAL
    $('input[name="hipertension_arterial_personal"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#obs_hipertension_arterial_personal').prop('disabled', false).focus();
        } else {
            $('#obs_hipertension_arterial_personal').prop('disabled', true).val('');
        }
    });

    $('input[name="diabetes_mellitus_personal"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#obs_diabetes_mellitus_personal').prop('disabled', false).focus();
        } else {
            $('#obs_diabetes_mellitus_personal').prop('disabled', true).val('');
        }
    });
    
    // ✅ CÁLCULO AUTOMÁTICO DE ADHERENCIA TEST MORISKY
    $(document).on('change', '.test-morisky-input', function() {
        calcularAdherenciaMorisky();
    });
    
    // ✅ BÚSQUEDA DE DIAGNÓSTICOS PRINCIPAL
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
    
    // ✅ CERRAR DROPDOWNS AL HACER CLICK FUERA
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // ✅ AGREGAR DIAGNÓSTICO ADICIONAL
    $('#agregar_diagnostico_adicional').on('click', function() {
        agregarDiagnosticoAdicional();
    });
    
    function agregarDiagnosticoAdicional() {
        const template = $('#diagnostico_adicional_template').html();
        const $diagnostico = $(template);
        
        // Actualizar índices de los arrays
        $diagnostico.find('input[name*="diagnosticos_adicionales"], select[name*="diagnosticos_adicionales"]').each(function() {
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
                } else {
                    console.error('Error buscando diagnósticos adicionales:', response.error);
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
    
    // ✅ AGREGAR MEDICAMENTO
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
    
    function seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(medicamento.nombre);
        $hiddenId.val(medicamento.uuid || medicamento.id);
        $info.html(`<strong>${medicamento.nombre}</strong><br><small>${medicamento.principio_activo || ''}</small>`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }

    // ✅ ELIMINAR MEDICAMENTO
    $(document).on('click', '.eliminar-medicamento', function() {
        $(this).closest('.medicamento-item').remove();
    });
    
    // ✅ AGREGAR REMISIÓN
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
    
    // ✅ AGREGAR CUPS
    $('#agregar_cups').on('click', function() {
        agregarCups();
    });
    
    function agregarCups() {
        const template = $('#cups_template').html();
        const $cups = $(template);
        
        // Actualizar índices de los arrays
        $cups.find('input[name*="cups"], textarea[name*="cups"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${cupsCounter}]`));
        });
        
        $('#cups_container').append($cups);
        cupsCounter++;
        
        // Configurar búsqueda para este CUPS
        configurarBusquedaCups($cups);
    }
    
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
    
    function buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-cups") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosCups(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando CUPS:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando CUPS:', xhr.responseText);
            }
        });
    }
    
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
    
    function seleccionarCups(cups, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(`${cups.codigo} - ${cups.nombre}`);
        $hiddenId.val(cups.uuid || cups.id);
        $info.text(`${cups.codigo} - ${cups.nombre}`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }
    
    // ✅ ELIMINAR CUPS
    $(document).on('click', '.eliminar-cups', function() {
        $(this).closest('.cups-item').remove();
    });
    
    // ============================================
    // ✅✅✅ ENVÍO DEL FORMULARIO CON EVENTO DE HISTORIA GUARDADA - VERSIÓN CORREGIDA ✅✅✅
    // ============================================
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        console.log('📤 Iniciando envío del formulario...');
        
        // ✅ OBTENER CITA UUID ANTES DE TODO
        const citaUuid = $('input[name="cita_uuid"]').val();
        
        console.log('🔍 Cita UUID detectado:', citaUuid);
        
        // ✅ HABILITAR CAMPO ADHERENTE ANTES DEL ENVÍO
        $('input[name="adherente"]').prop('readonly', false);
        
        // Validar diagnóstico principal
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            
            // ✅ VOLVER A DESHABILITAR SI HAY ERROR
            $('input[name="adherente"]').prop('readonly', true);
            console.log('❌ Validación fallida - falta diagnóstico principal');
            return;
        }
        
        console.log('✅ Validación exitosa, preparando envío...');
        
        // Mostrar loading
        $('#loading_overlay').show();
        
        // Preparar datos
        const formData = new FormData(this);
        
        // ✅ LOGGING PARA VERIFICAR QUE SE ENVÍA
        console.log('Adherente value:', $('input[name="adherente"]:checked').val());
        
        // ✅ VARIABLE PARA CONTROLAR SI YA SE PROCESÓ LA RESPUESTA
        let respuestaProcesada = false;
        
        // ✅ TIMEOUT MEJORADO CON CONTROL DE ESTADO
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
        }, 15000); // 15 segundos de timeout
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // ✅ TIMEOUT DE 30 SEGUNDOS
            success: function(response) {
                // ✅ VERIFICAR SI YA SE PROCESÓ
                if (respuestaProcesada) {
                    console.log('⚠️ Respuesta ignorada - ya se procesó por timeout');
                    return;
                }
                
                respuestaProcesada = true;
                clearTimeout(timeoutId);
                
                console.log('✅ Respuesta recibida:', response);
                
                // ✅ OCULTAR LOADING INMEDIATAMENTE
                $('#loading_overlay').hide();
                
                if (response.success) {
                    // ✅✅✅ DISPARAR EVENTO DE HISTORIA GUARDADA ✅✅✅
                    dispararEventoHistoriaGuardada(
                        citaUuid,
                        response.historia_uuid || null,
                        response.offline || false
                    );
                    
                    // ✅ MOSTRAR MENSAJE Y REDIRIGIR SIN ESPERAR CONFIRMACIÓN
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Historia clínica guardada exitosamente. Cita marcada como atendida.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(() => {
                        // ✅ REDIRIGIR DESPUÉS DEL MENSAJE
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
                // ✅ VERIFICAR SI YA SE PROCESÓ
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
                
                // ✅ OCULTAR LOADING INMEDIATAMENTE
                $('#loading_overlay').hide();
                
                let errorMessage = 'Error interno del servidor';
                let shouldRedirect = false;
                
                if (status === 'timeout') {
                    errorMessage = 'La solicitud tardó demasiado. La historia clínica puede haberse guardado correctamente.';
                    shouldRedirect = true;
                    // ✅ DISPARAR EVENTO INCLUSO EN TIMEOUT
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
                
                // ✅ ASEGURAR QUE EL LOADING SE OCULTE
                setTimeout(function() {
                    $('#loading_overlay').hide();
                }, 100);
                
                // ✅ VOLVER A DESHABILITAR DESPUÉS DEL ENVÍO
                $('input[name="adherente"]').prop('readonly', true);
            }
        });
    });

}); // ✅ CERRAR $(document).ready

// ============================================
// ✅✅✅ FUNCIÓN DE CÁLCULO DE ADHERENCIA MORISKY - FUERA DEL DOCUMENT.READY ✅✅✅
// ============================================
function calcularAdherenciaMorisky() {
    console.log('📊 Calculando adherencia Morisky...');
    
    // ✅ OBTENER RESPUESTAS
    const olvida = $('input[name="test_morisky_olvida_tomar_medicamentos"]:checked').val();
    const horaIndicada = $('input[name="test_morisky_toma_medicamentos_hora_indicada"]:checked').val();
    const cuandoEstaBien = $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"]:checked').val();
    const sienteMal = $('input[name="test_morisky_siente_mal_deja_tomarlos"]:checked').val();
    const psicologia = $('input[name="test_morisky_valoracio_psicologia"]:checked').val();
    
    console.log('Respuestas Test Morisky:', { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia });
    
    // ✅ VERIFICAR QUE TODAS LAS PREGUNTAS ESTÉN RESPONDIDAS
    if (!olvida || !horaIndicada || !cuandoEstaBien || !sienteMal || !psicologia) {
        // Si no están todas respondidas, resetear
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        $('#explicacion_adherencia').hide();
        console.log('⚠️ No todas las preguntas están respondidas');
        return;
    }
    
    // ✅ CALCULAR PUNTUACIÓN MORISKY
    let puntuacion = 0;
    
    // Pregunta 1: ¿Olvida alguna vez tomar los medicamentos? (SI = 1 punto)
    if (olvida === 'SI') puntuacion += 1;
    
    // Pregunta 2: ¿Toma los medicamentos a la hora indicada? (NO = 1 punto)
    if (horaIndicada === 'NO') puntuacion += 1;
    
    // Pregunta 3: ¿Cuando se encuentra bien, deja de tomar los medicamentos? (SI = 1 punto)
    if (cuandoEstaBien === 'SI') puntuacion += 1;
    
    // Pregunta 4: ¿Si alguna vez se siente mal, deja de tomarlos? (SI = 1 punto)
    if (sienteMal === 'SI') puntuacion += 1;
    
    // ✅ DETERMINAR ADHERENCIA
    // Puntuación 0 = Adherente
    // Puntuación 1-4 = No adherente
    let esAdherente = puntuacion === 0;
    let explicacion = '';
    
    if (esAdherente) {
        $('#adherente_si').prop('checked', true);
        $('#adherente_no').prop('checked', false);
        explicacion = `<strong class="text-success">✅ ADHERENTE:</strong> Puntuación: ${puntuacion}/4. El paciente muestra buena adherencia al tratamiento farmacológico.`;
    } else {
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        explicacion = `<strong class="text-danger">❌ NO ADHERENTE:</strong> Puntuación: ${puntuacion}/4. El paciente presenta problemas de adherencia al tratamiento farmacológico.`;
    }
    
    // ✅ MOSTRAR EXPLICACIÓN
    $('#texto_explicacion').html(explicacion);
    $('#explicacion_adherencia').show();
    
    // ✅ AGREGAR RECOMENDACIÓN PARA PSICOLOGÍA SI ES NECESARIO
    if (!esAdherente || psicologia === 'SI') {
        $('#texto_explicacion').append('<br><strong class="text-warning">⚠️ Recomendación:</strong> Considerar valoración por psicología para mejorar adherencia.');
    }
    
    console.log('✅ Test Morisky calculado:', {
        puntuacion: puntuacion,
        adherente: esAdherente,
        respuestas: { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia }
    });
}
</script>

