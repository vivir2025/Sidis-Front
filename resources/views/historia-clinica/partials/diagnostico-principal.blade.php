{{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL - DISEÑO TABLA COMPACTO --}}
<div class="card mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>
            DIAGNÓSTICO PRINCIPAL
            <span class="text-warning ms-2">*</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <!-- Tabla de Diagnóstico Principal -->
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabla_diagnostico_principal">
                <thead class="table-light">
                    <tr>
                        <th width="65%">DIAGNÓSTICO</th>
                        <th width="35%">TIPO DE DIAGNÓSTICO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="diagnostico-search-wrapper position-relative">
                                <input type="text" 
                                    class="form-control form-control-sm" 
                                    id="buscar_diagnostico" 
                                    placeholder="Escriba código o nombre del diagnóstico..." 
                                    autocomplete="off" 
                                    required>
                                
                                <div id="diagnosticos_resultados" class="dropdown-menu w-100"></div>
                                
                                <input type="hidden" id="idDiagnostico" name="idDiagnostico" required>
                                
                                <div id="diagnostico_seleccionado" class="mt-1" style="display: none;">
                                    <small class="text-secondary">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <span id="diagnostico_info"></span>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" 
                                id="tipo_diagnostico" 
                                name="tipo_diagnostico" 
                                required>
                                <option value="">Seleccione...</option>
                                <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                                <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                                <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 🎨 ESTILOS PARA DISEÑO COMPACTO DE DIAGNÓSTICO PRINCIPAL --}}
<style>
/* ✅ Tabla compacta Diagnóstico Principal */
#tabla_diagnostico_principal {
    font-size: 0.9rem;
}

#tabla_diagnostico_principal thead th {
    background-color: #f8d7da; /* Rojo claro danger */
    border-bottom: 2px solid #dc3545;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    color: #721c24;
}

#tabla_diagnostico_principal tbody td {
    padding: 0.75rem;
    vertical-align: middle;
}

#tabla_diagnostico_principal tbody tr:hover {
    background-color: #ffe6e8; /* Rojo muy claro */
}

/* ✅ Select compacto */
#tabla_diagnostico_principal select.form-select-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
}

/* ✅ Input compacto */
#tabla_diagnostico_principal input.form-control-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
}

/* ✅ Dropdown de búsqueda Diagnóstico Principal */
.diagnostico-search-wrapper {
    position: relative;
}

#diagnosticos_resultados.dropdown-menu {
    max-height: 300px !important;
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
    border: 1px solid #dc3545;
}

#diagnosticos_resultados.dropdown-menu:empty {
    display: none !important;
}

#diagnosticos_resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
    color: #212529 !important;
}

#diagnosticos_resultados .dropdown-item:hover {
    background-color: #dc3545 !important; /* Rojo danger */
    cursor: pointer !important;
    color: #ffffff !important;
}

#diagnosticos_resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

#diagnosticos_resultados .dropdown-item:hover small {
    color: #f8f9fa !important;
}

#diagnosticos_resultados .dropdown-item strong {
    color: #721c24 !important;
    font-weight: 600;
}

#diagnosticos_resultados .dropdown-item:hover strong {
    color: #ffffff !important;
}

#diagnosticos_resultados .dropdown-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* ✅ Mensaje de selección */
#diagnostico_seleccionado {
    font-size: 0.8rem;
}

/* ✅ Scroll suave */
#diagnosticos_resultados::-webkit-scrollbar {
    width: 8px;
}

#diagnosticos_resultados::-webkit-scrollbar-track {
    background: #ffe6e8;
}

#diagnosticos_resultados::-webkit-scrollbar-thumb {
    background: #dc3545;
    border-radius: 4px;
}

#diagnosticos_resultados::-webkit-scrollbar-thumb:hover {
    background: #c82333;
}
</style>

<script>
$(document).ready(function() {
    
    // ✅ Búsqueda de Diagnóstico Principal (AJAX)
    $('#buscar_diagnostico').on('input', function() {
        const query = $(this).val().trim();
        const dropdown = $('#diagnosticos_resultados');
        
        if (query.length < 3) {
            dropdown.empty().hide();
            return;
        }
        
        // Llamada AJAX para buscar diagnósticos CIE-10
        $.ajax({
            url: '/api/diagnosticos/buscar', // ✅ Ajusta tu ruta
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
                    response.forEach(function(diagnostico) {
                        dropdown.append(`
                            <a href="#" class="dropdown-item seleccionar-diagnostico" 
                               data-id="${diagnostico.id}" 
                               data-codigo="${diagnostico.codigo}"
                               data-nombre="${diagnostico.nombre}"
                               data-descripcion="${diagnostico.descripcion || ''}">
                                <strong>${diagnostico.codigo}</strong> - ${diagnostico.nombre}
                                ${diagnostico.descripcion ? `<small>${diagnostico.descripcion}</small>` : ''}
                            </a>
                        `);
                    });
                }
                
                dropdown.show();
            },
            error: function() {
                dropdown.empty().append(`
                    <div class="dropdown-item disabled">
                        <small class="text-danger">Error al buscar diagnósticos</small>
                    </div>
                `).show();
            }
        });
    });
    
    // ✅ Seleccionar Diagnóstico del dropdown
    $(document).on('click', '.seleccionar-diagnostico', function(e) {
        e.preventDefault();
        
        const id = $(this).data('id');
        const codigo = $(this).data('codigo');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion');
        
        // Llenar campos
        $('#idDiagnostico').val(id);
        $('#buscar_diagnostico').val(codigo + ' - ' + nombre);
        $('#diagnostico_info').text(codigo + ' - ' + nombre + (descripcion ? ' | ' + descripcion : ''));
        $('#diagnostico_seleccionado').show();
        
        // Ocultar dropdown
        $('#diagnosticos_resultados').empty().hide();
    });
    
    // ✅ Ocultar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.diagnostico-search-wrapper').length) {
            $('#diagnosticos_resultados').empty().hide();
        }
    });
    
    // ✅ Cargar Diagnóstico Principal existente desde la base de datos
    @if(isset($historia) && $historia->diagnosticoPrincipal)
        const diagnosticoPrincipal = @json($historia->diagnosticoPrincipal);
        
        $('#idDiagnostico').val(diagnosticoPrincipal.id);
        $('#buscar_diagnostico').val(diagnosticoPrincipal.codigo + ' - ' + diagnosticoPrincipal.nombre);
        $('#tipo_diagnostico').val(diagnosticoPrincipal.tipo_diagnostico);
        $('#diagnostico_info').text(diagnosticoPrincipal.codigo + ' - ' + diagnosticoPrincipal.nombre);
        $('#diagnostico_seleccionado').show();
    @endif

});
</script>
