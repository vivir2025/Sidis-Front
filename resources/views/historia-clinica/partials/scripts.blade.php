<script>
// ============================================
// ✅ FUNCIONES GLOBALES (FUERA DE DOCUMENT.READY)
// ============================================
 // ✅ VARIABLES GLOBALES
    let medicamentoCounter = 0;
    let diagnosticoAdicionalCounter = 0;
    let remisionCounter = 0;
    let cupsCounter = 0;
    let diagnosticoSeleccionado = null;
/**
 * ✅ DISPARAR EVENTO DE HISTORIA GUARDADA
 */
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

/**
 * ✅ CALCULAR ADHERENCIA MORISKY
 */
function calcularAdherenciaMorisky() {
    console.log('📊 Calculando adherencia Morisky...');
    
    const olvida = $('input[name="test_morisky_olvida_tomar_medicamentos"]:checked').val();
    const horaIndicada = $('input[name="test_morisky_toma_medicamentos_hora_indicada"]:checked').val();
    const cuandoEstaBien = $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"]:checked').val();
    const sienteMal = $('input[name="test_morisky_siente_mal_deja_tomarlos"]:checked').val();
    const psicologia = $('input[name="test_morisky_valoracio_psicologia"]:checked').val();
    
    console.log('Respuestas Test Morisky:', { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia });
    
    if (!olvida || !horaIndicada || !cuandoEstaBien || !sienteMal || !psicologia) {
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        $('#explicacion_adherencia').hide();
        console.log('⚠️ No todas las preguntas están respondidas');
        return;
    }
    
    let puntuacion = 0;
    if (olvida === 'SI') puntuacion += 1;
    if (horaIndicada === 'NO') puntuacion += 1;
    if (cuandoEstaBien === 'SI') puntuacion += 1;
    if (sienteMal === 'SI') puntuacion += 1;
    
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
    
    $('#texto_explicacion').html(explicacion);
    $('#explicacion_adherencia').show();
    
    if (!esAdherente || psicologia === 'SI') {
        $('#texto_explicacion').append('<br><strong class="text-warning">⚠️ Recomendación:</strong> Considerar valoración por psicología para mejorar adherencia.');
    }
    
    console.log('✅ Test Morisky calculado:', {
        puntuacion: puntuacion,
        adherente: esAdherente,
        respuestas: { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia }
    });
}

/**
 * ✅ AGREGAR MEDICAMENTO CON DATOS
 */
function agregarMedicamentoConDatos(medicamento) {
    console.log('💊 Agregando medicamento con datos:', medicamento);
    
    // Usar la función global que ya existe en document.ready
    $('#agregar_medicamento').trigger('click');
    
    // Esperar un momento para que se agregue el elemento
    setTimeout(function() {
        const $ultimoMedicamento = $('#medicamentos_container .medicamento-item:last');
        
        $ultimoMedicamento.find('.buscar-medicamento').val(medicamento.medicamento.nombre);
        $ultimoMedicamento.find('.medicamento-id').val(medicamento.medicamento_id);
        $ultimoMedicamento.find('.medicamento-info').html(`<strong>${medicamento.medicamento.nombre}</strong><br><small>${medicamento.medicamento.principio_activo || ''}</small>`);
        $ultimoMedicamento.find('.medicamento-seleccionado').show();
        $ultimoMedicamento.find('input[name*="cantidad"]').val(medicamento.cantidad || '');
        $ultimoMedicamento.find('input[name*="dosis"]').val(medicamento.dosis || '');
        $ultimoMedicamento.find('input[name*="frecuencia"]').val(medicamento.frecuencia || '');
        $ultimoMedicamento.find('input[name*="via_administracion"]').val(medicamento.via_administracion || '');
        $ultimoMedicamento.find('input[name*="duracion"]').val(medicamento.duracion || '');
        $ultimoMedicamento.find('textarea[name*="indicaciones"]').val(medicamento.indicaciones || '');
        
        console.log('✅ Medicamento agregado exitosamente');
    }, 100);
}

/**
 * ✅ AGREGAR REMISIÓN CON DATOS
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
 * ✅ AGREGAR DIAGNÓSTICO ADICIONAL CON DATOS
 */
function agregarDiagnosticoAdicionalConDatos(diagnostico) {
    console.log('🩺 Agregando diagnóstico adicional con datos:', diagnostico);
    
    $('#agregar_diagnostico_adicional').trigger('click');
    
    setTimeout(function() {
        const $ultimoDiagnostico = $('#diagnosticos_adicionales_container .diagnostico-adicional-item:last');
        
        $ultimoDiagnostico.find('.buscar-diagnostico-adicional').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $ultimoDiagnostico.find('.diagnostico-adicional-id').val(diagnostico.diagnostico_id);
        $ultimoDiagnostico.find('.diagnostico-adicional-info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $ultimoDiagnostico.find('.diagnostico-adicional-seleccionado').show();
        $ultimoDiagnostico.find('select[name*="tipo_diagnostico"]').val(diagnostico.tipo || 'RELACIONADO');
        
        console.log('✅ Diagnóstico adicional agregado exitosamente');
    }, 100);
}

/**
 * ✅ AGREGAR CUPS CON DATOS
 */
function agregarCupsConDatos(cups) {
    console.log('🏥 Agregando CUPS con datos:', cups);
    
    $('#agregar_cups').trigger('click');
    
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
        
        console.log('✅ Diagnóstico principal cargado exitosamente');
        
    } catch (error) {
        console.error('❌ Error cargando diagnóstico principal:', error);
    }
}

/**
 * ✅✅✅ CARGAR DATOS PREVIOS MEDICINA GENERAL (FUNCIÓN PRINCIPAL) ✅✅✅
 */
function cargarDatosPreviosMedicinaGeneral(historiaPrevia) {
    try {
        console.log('🔄 Iniciando carga de datos previos para Medicina General');
        console.log('📦 Historia previa recibida:', historiaPrevia);

        // ✅ 1. CARGAR MEDICAMENTOS
        if (historiaPrevia.medicamentos && historiaPrevia.medicamentos.length > 0) {
            console.log('💊 Cargando medicamentos previos:', historiaPrevia.medicamentos.length);
            historiaPrevia.medicamentos.forEach(function(medicamento, index) {
                setTimeout(function() {
                    agregarMedicamentoConDatos(medicamento);
                }, index * 300);
            });
        }

        // ✅ 2. CARGAR REMISIONES
          if (historiaPrevia.remisiones && historiaPrevia.remisiones.length > 0) {
            console.log('📋 Cargando remisiones previas:', historiaPrevia.remisiones.length);
            historiaPrevia.remisiones.forEach(function(remision, index) {
                setTimeout(function() {
                    agregarRemisionConDatos(remision);
                }, index * 300);
            });
        }

        // ✅ 3. CARGAR DIAGNÓSTICOS
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

        // ✅ 4. CARGAR CUPS
        if (historiaPrevia.cups && historiaPrevia.cups.length > 0) {
            console.log('🏥 Cargando CUPS previos:', historiaPrevia.cups.length);
            historiaPrevia.cups.forEach(function(cups, index) {
                setTimeout(function() {
                    agregarCupsConDatos(cups);
                }, index * 300);
            });
        }

        // ✅ 5. CARGAR TALLA
        if (historiaPrevia.talla) {
            $('#talla').val(historiaPrevia.talla);
            console.log('📏 Talla cargada:', historiaPrevia.talla);
        }

        // ✅ 6. CARGAR ANTECEDENTES PERSONALES
        if (historiaPrevia.hipertension_arterial_personal) {
            $('input[name="hipertension_arterial_personal"][value="' + historiaPrevia.hipertension_arterial_personal + '"]').prop('checked', true).trigger('change');
            if (historiaPrevia.obs_hipertension_arterial_personal) {
                $('#obs_hipertension_arterial_personal').val(historiaPrevia.obs_hipertension_arterial_personal);
            }
        }

        if (historiaPrevia.diabetes_mellitus_personal) {
            $('input[name="diabetes_mellitus_personal"][value="' + historiaPrevia.diabetes_mellitus_personal + '"]').prop('checked', true).trigger('change');
            if (historiaPrevia.obs_diabetes_mellitus_personal) {
                $('#obs_diabetes_mellitus_personal').val(historiaPrevia.obs_diabetes_mellitus_personal);
            }
        }

        // ✅ 7. CARGAR CLASIFICACIONES
        if (historiaPrevia.clasificacion_estado_metabolico) {
            $('#ClasificacionEstadoMetabolico').val(historiaPrevia.clasificacion_estado_metabolico);
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
        if (historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente) {
            $('#clasificacion_erc_categoria_ambulatoria_persistente').val(historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente);
        }

        // ✅ 8. CARGAR TASAS DE FILTRACIÓN
        if (historiaPrevia.tasa_filtracion_glomerular_ckd_epi) {
            $('#tasa_filtracion_glomerular_ckd_epi').val(historiaPrevia.tasa_filtracion_glomerular_ckd_epi);
        }
        if (historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault) {
            $('#tasa_filtracion_glomerular_gockcroft_gault').val(historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault);
        }

        // ✅ 9. CARGAR TEST DE MORISKY
        if (historiaPrevia.test_morisky_olvida_tomar_medicamentos) {
            $('input[name="test_morisky_olvida_tomar_medicamentos"][value="' + historiaPrevia.test_morisky_olvida_tomar_medicamentos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_toma_medicamentos_hora_indicada) {
            $('input[name="test_morisky_toma_medicamentos_hora_indicada"][value="' + historiaPrevia.test_morisky_toma_medicamentos_hora_indicada + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_cuando_esta_bien_deja_tomar_medicamentos) {
            $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"][value="' + historiaPrevia.test_morisky_cuando_esta_bien_deja_tomar_medicamentos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_siente_mal_deja_tomarlos) {
            $('input[name="test_morisky_siente_mal_deja_tomarlos"][value="' + historiaPrevia.test_morisky_siente_mal_deja_tomarlos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_valoracio_psicologia) {
            $('input[name="test_morisky_valoracio_psicologia"][value="' + historiaPrevia.test_morisky_valoracio_psicologia + '"]').prop('checked', true);
        }
        if (historiaPrevia.adherente) {
            $('input[name="adherente"][value="' + historiaPrevia.adherente + '"]').prop('checked', true);
        }

        // ✅ 10. RECALCULAR ADHERENCIA
        setTimeout(function() {
            calcularAdherenciaMorisky();
        }, 1500);

        // ✅ 11. CARGAR CAMPOS DE EDUCACIÓN
        const camposEducacion = [
            'alimentacion',
            'disminucion_consumo_sal_azucar',
            'fomento_actividad_fisica',
            'importancia_adherencia_tratamiento',
            'consumo_frutas_verduras',
            'manejo_estres',
            'disminucion_consumo_cigarrillo',
            'disminucion_peso'
        ];

        camposEducacion.forEach(function(campo) {
            if (historiaPrevia[campo]) {
                $('input[name="' + campo + '"][value="' + historiaPrevia[campo] + '"]').prop('checked', true);
                console.log('✅ Campo educación cargado:', campo, '=', historiaPrevia[campo]);
            }
        });

        console.log('✅ Datos previos cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}

// ============================================
// ✅ DOCUMENT.READY
// ============================================
$(document).ready(function() {
    console.log('🔍 Iniciando script de primera-vez.blade.php');
    
   

    // ✅✅✅ CARGAR DATOS PREVIOS SI EXISTEN ✅✅✅
    @if(isset($historiaPrevia) && !empty($historiaPrevia))
        console.log('🔄 Detectada historia previa, iniciando carga...');
        const historiaPrevia = @json($historiaPrevia);
        console.log('📦 Historia previa:', historiaPrevia);
        
        setTimeout(function() {
            cargarDatosPreviosMedicinaGeneral(historiaPrevia);
        }, 500);
    @else
        console.log('ℹ️ No hay historia previa para cargar');
    @endif

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
        
        $diagnostico.find('input[name*="diagnosticos_adicionales"], select[name*="diagnosticos_adicionales"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${diagnosticoAdicionalCounter}]`));
        });
        
        $('#diagnosticos_adicionales_container').append($diagnostico);
        diagnosticoAdicionalCounter++;
        
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
        
        $medicamento.find('input[name*="medicamentos"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${medicamentoCounter}]`));
        });
        
        $('#medicamentos_container').append($medicamento);
        medicamentoCounter++;
        
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
        
        $remision.find('input[name*="remisiones"], textarea[name*="remisiones"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${remisionCounter}]`));
        });
        
        $('#remisiones_container').append($remision);
        remisionCounter++;
        
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
        
        $cups.find('input[name*="cups"], textarea[name*="cups"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${cupsCounter}]`));
        });
        
        $('#cups_container').append($cups);
        cupsCounter++;
        
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
    // ✅ ENVÍO DEL FORMULARIO CON EVENTO DE HISTORIA GUARDADA
    // ============================================
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        console.log('📤 Iniciando envío del formulario...');
        
        const citaUuid = $('input[name="cita_uuid"]').val();
        console.log('🔍 Cita UUID detectado:', citaUuid);
        
        $('input[name="adherente"]').prop('readonly', false);
        
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            
            $('input[name="adherente"]').prop('readonly', true);
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
                        text: response.message || 'Historia clínica guardada exitosamente. Cita marcada como atendida.',
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
                
                $('input[name="adherente"]').prop('readonly', true);
            }
        });
    });

}); // ✅ FIN DOCUMENT.READY
</script>