    {{-- ✅ SECCIÓN: MEDICAMENTOS - DISEÑO COMPACTO --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-pills me-2"></i>
                FÓRMULA MÉDICA
            </h5>
            <div>

                <button type="button" class="btn btn-light btn-sm" id="agregar_medicamento">
                    <i class="fas fa-plus me-1"></i>Agregar Medicamento
                </button>

            </div>
        </div>
        <div class="card-body p-0">
            
            <!-- Tabla de medicamentos -->
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabla_medicamentos">
                    <thead class="table-light">
                        <tr>
                            <th width="40%">MEDICAMENTO</th>
                            <th width="15%" class="text-center">CANTIDAD</th>
                            <th width="40%">DOSIS</th>
                            <th width="5%" class="text-center">
                                <i class="fas fa-cog"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="medicamentos_container">
                        <!-- Los medicamentos se agregarán aquí dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ✅ TEMPLATE MEDICAMENTO - FILA DE TABLA --}}
    <template id="medicamento_template">
        <tr class="medicamento-item">
            <td>
                <div class="medicamento-search-wrapper position-relative">
                    <input type="text" 
                        class="form-control form-control-sm buscar-medicamento" 
                        placeholder="Escriba el nombre del medicamento..."
                        autocomplete="off">
                    
                    <div class="dropdown-menu medicamentos-resultados w-100"></div>
                    
                    <input type="hidden" class="medicamento-id" name="medicamentos[][idMedicamento]">
                    
                    <div class="medicamento-seleccionado mt-1" style="display: none;">
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            <span class="medicamento-info"></span>
                        </small>
                    </div>
                </div>
            </td>
            <td>
                <input type="number" 
                    class="form-control form-control-sm text-center" 
                    name="medicamentos[][cantidad]" 
                    placeholder="0"
                    min="0"
                    required>
            </td>
            <td>
                <input type="text" 
                    class="form-control form-control-sm" 
                    name="medicamentos[][dosis]" 
                    placeholder="TOMAR UNA TABLETA..."
                    required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm eliminar-medicamento" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    {{-- 🎨 ESTILOS PARA DISEÑO COMPACTO --}}
    <style>
    /* ✅ Tabla compacta */
    #tabla_medicamentos {
        font-size: 0.9rem;
    }

    #tabla_medicamentos thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        padding: 0.75rem;
        vertical-align: middle;
    }

    #tabla_medicamentos tbody td {
        padding: 0.5rem;
        vertical-align: middle;
    }

    #tabla_medicamentos tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* ✅ Inputs compactos */
    .form-control-sm {
        font-size: 0.875rem;
        padding: 0.375rem 0.5rem;
    }

    /* ✅ Dropdown de búsqueda */
    .medicamento-search-wrapper {
        position: relative;
    }

    .medicamentos-resultados.dropdown-menu {
        max-height: 250px !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        display: block !important;
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 1050 !important;
        margin-top: 2px !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #dee2e6;
    }

    .medicamentos-resultados.dropdown-menu:empty {
        display: none !important;
    }

    .medicamentos-resultados .dropdown-item {
        white-space: normal !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        padding: 0.5rem 0.75rem !important;
        line-height: 1.4 !important;
        color: #212529 !important;
        font-size: 0.875rem;
    }

    .medicamentos-resultados .dropdown-item:hover {
        background-color: #198754 !important; /* Verde success */
        cursor: pointer !important;
        color: #ffffff !important;
    }

    .medicamentos-resultados .dropdown-item small {
        display: block;
        color: #6c757d;
        margin-top: 0.25rem;
        font-size: 0.8rem;
    }

    .medicamentos-resultados .dropdown-item:hover small {
        color: #e9ecef !important;
    }

    .medicamentos-resultados .dropdown-item strong {
        color: #212529 !important;
        font-weight: 600;
    }

    .medicamentos-resultados .dropdown-item:hover strong {
        color: #ffffff !important;
    }

    /* ✅ Botones compactos */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* ✅ Barra de búsqueda superior */
    #buscar_medicamento_general {
        border: 2px solid #198754;
    }

    #buscar_medicamento_general:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    /* ✅ Mensaje de selección */
    .medicamento-seleccionado {
        font-size: 0.8rem;
    }

    /* ✅ Tabla responsive */
    .table-responsive {
        max-height: 500px;
        overflow-y: auto;
    }

    /* ✅ Scroll suave */
    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #198754;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #157347;
    }

    /* ✅ Estados especiales */
    .medicamento-suspendido {
        background-color: #fff3cd !important;
        color: #856404;
    }

    .medicamento-suspendido td {
        font-style: italic;
    }
    </style>
  <script>
$(document).ready(function() {
    let contadorMedicamentos = 0;
    
    // ✅ Agregar medicamento AL INICIO (ARRIBA)
    $('#agregar_medicamento').on('click', function() {
        const template = $('#medicamento_template').html();
        $('#medicamentos_container').prepend(template); // ✅ Agrega ARRIBA
        contadorMedicamentos++;
        
        // 🎯 OPCIONAL: Hacer scroll al nuevo medicamento
        $('#medicamentos_container tr:first').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        
        // 🎯 OPCIONAL: Hacer focus en el campo de búsqueda
        $('#medicamentos_container tr:first .buscar-medicamento').focus();
    });
    
    // ✅ Eliminar medicamento
    $(document).on('click', '.eliminar-medicamento', function() {
        if (confirm('¿Está seguro de eliminar este medicamento?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // ✅ Búsqueda de medicamentos (AJAX)
    $(document).on('input', '.buscar-medicamento', function() {
        const input = $(this);
        const query = input.val().trim();
        const dropdown = input.siblings('.medicamentos-resultados');
        
        if (query.length < 3) {
            dropdown.empty().hide();
            return;
        }
        
        $.ajax({
            url: '/api/medicamentos/buscar',
            method: 'GET',
            data: { q: query },
            success: function(response) {
                dropdown.empty();
                
                if (response.length === 0) {
                    dropdown.append(`
                        <div class="dropdown-item disabled">
                            <small class="text-muted">No se encontraron resultados</small>
                        </div>
                    `);
                } else {
                    response.forEach(function(med) {
                        dropdown.append(`
                            <a href="#" class="dropdown-item seleccionar-medicamento" 
                               data-id="${med.id}" 
                               data-nombre="${med.nombre}"
                               data-concentracion="${med.concentracion}"
                               data-forma="${med.forma_farmaceutica}">
                                <strong>${med.nombre}</strong>
                                <small>${med.concentracion} - ${med.forma_farmaceutica}</small>
                            </a>
                        `);
                    });
                }
                
                dropdown.show();
            },
            error: function() {
                dropdown.empty().append(`
                    <div class="dropdown-item disabled">
                        <small class="text-danger">Error al buscar medicamentos</small>
                    </div>
                `).show();
            }
        });
    });
    
    // ✅ Seleccionar medicamento del dropdown
    $(document).on('click', '.seleccionar-medicamento', function(e) {
        e.preventDefault();
        
        const item = $(this);
        const row = item.closest('tr');
        const id = item.data('id');
        const nombre = item.data('nombre');
        const concentracion = item.data('concentracion');
        const forma = item.data('forma');
        
        row.find('.medicamento-id').val(id);
        row.find('.buscar-medicamento').val(`${nombre} ${concentracion}`);
        row.find('.medicamento-info').text(`${nombre} ${concentracion} - ${forma}`);
        row.find('.medicamento-seleccionado').show();
        
        item.closest('.medicamentos-resultados').empty().hide();
    });
    
    // ✅ Ocultar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.medicamento-search-wrapper').length) {
            $('.medicamentos-resultados').empty().hide();
        }
    });
    
    // ✅ Búsqueda general (filtrar tabla)
    $('#buscar_medicamento_general').on('input', function() {
        const query = $(this).val().toLowerCase();
        
        $('#medicamentos_container tr').each(function() {
            const texto = $(this).text().toLowerCase();
            $(this).toggle(texto.includes(query));
        });
    });
    
    // ✅ Editar medicamentos
    $('#editar_medicamentos').on('click', function() {
        $('.buscar-medicamento, input[name*="cantidad"], input[name*="dosis"]').prop('disabled', function(i, v) {
            return !v;
        });
        
        $(this).toggleClass('btn-light btn-warning');
        const texto = $(this).find('i').next();
        texto.text(texto.text() === 'Editar' ? 'Guardar' : 'Editar');
    });
    
    // ✅ Borrar todos los medicamentos
    $('#borrar_medicamentos').on('click', function() {
        if (confirm('¿Está seguro de borrar TODOS los medicamentos?')) {
            $('#medicamentos_container').empty();
        }
    });
    
   console.log('ℹ️ Medicamentos se cargan desde historiaPrevia en la función cargarDatosPreviosMedicinaGeneral()');

});
</script>

