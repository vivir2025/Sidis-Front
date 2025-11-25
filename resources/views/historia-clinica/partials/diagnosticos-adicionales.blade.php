{{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES - DISEÑO TABLA COMPACTO --}}
<div class="card mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            DIAGNÓSTICOS ADICIONALES
        </h5>
        <button type="button" class="btn btn-dark btn-sm" id="agregar_diagnostico_adicional">
            <i class="fas fa-plus me-1"></i>Agregar Diagnóstico
        </button>
    </div>
    <div class="card-body p-0">
        <!-- Tabla de Diagnósticos Adicionales -->
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabla_diagnosticos_adicionales">
                <thead class="table-light">
                    <tr>
                        <th width="55%">DIAGNÓSTICO ADICIONAL</th>
                        <th width="40%">TIPO DE DIAGNÓSTICO</th>
                        <th width="5%" class="text-center">
                            <i class="fas fa-cog"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="diagnosticos_adicionales_container">
                    <!-- Los diagnósticos adicionales se agregarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE DIAGNÓSTICO ADICIONAL - FILA DE TABLA --}}
<template id="diagnostico_adicional_template">
    <tr class="diagnostico-adicional-item">
        <td>
            <div class="diagnostico-adicional-search-wrapper position-relative">
                <input type="text" 
                    class="form-control form-control-sm buscar-diagnostico-adicional" 
                    placeholder="Escriba código o nombre del diagnóstico..."
                    autocomplete="off">
                
                <div class="dropdown-menu diagnosticos-adicionales-resultados w-100"></div>
                
                <input type="hidden" 
                    class="diagnostico-adicional-id" 
                    name="diagnosticos_adicionales[][idDiagnostico]">
                
                <div class="diagnostico-adicional-seleccionado mt-1" style="display: none;">
                    <small class="text-secondary">
                        <i class="fas fa-check-circle me-1"></i>
                        <span class="diagnostico-adicional-info"></span>
                    </small>
                </div>
            </div>
        </td>
        <td>
            <select class="form-select form-select-sm" 
                name="diagnosticos_adicionales[][tipo_diagnostico]" 
                required>
                <option value="">Seleccione...</option>
                <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" 
                class="btn btn-danger btn-sm eliminar-diagnostico-adicional" 
                title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

{{-- 🎨 ESTILOS PARA DISEÑO COMPACTO DE DIAGNÓSTICOS ADICIONALES --}}
<style>
/* ✅ Tabla compacta Diagnósticos Adicionales */
#tabla_diagnosticos_adicionales {
    font-size: 0.9rem;
}

#tabla_diagnosticos_adicionales thead th {
    background-color: #fff3cd; /* Amarillo claro warning */
    border-bottom: 2px solid #ffc107;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
    color: #856404;
}

#tabla_diagnosticos_adicionales tbody td {
    padding: 0.5rem;
    vertical-align: middle;
}

#tabla_diagnosticos_adicionales tbody tr:hover {
    background-color: #fff9e6; /* Amarillo muy claro */
}

/* ✅ Select compacto */
#tabla_diagnosticos_adicionales select.form-select-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
}

/* ✅ Dropdown de búsqueda Diagnósticos Adicionales */
.diagnostico-adicional-search-wrapper {
    position: relative;
}

.diagnosticos-adicionales-resultados.dropdown-menu {
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
    border: 1px solid #ffc107;
}

.diagnosticos-adicionales-resultados.dropdown-menu:empty {
    display: none !important;
}

.diagnosticos-adicionales-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.5rem 0.75rem !important;
    line-height: 1.4 !important;
    color: #212529 !important;
    font-size: 0.875rem;
}

.diagnosticos-adicionales-resultados .dropdown-item:hover {
    background-color: #ffc107 !important; /* Amarillo warning */
    cursor: pointer !important;
    color: #000000 !important;
}

.diagnosticos-adicionales-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.8rem;
}

.diagnosticos-adicionales-resultados .dropdown-item:hover small {
    color: #495057 !important;
}

.diagnosticos-adicionales-resultados .dropdown-item strong {
    color: #856404 !important;
    font-weight: 600;
}

.diagnosticos-adicionales-resultados .dropdown-item:hover strong {
    color: #000000 !important;
}

.diagnosticos-adicionales-resultados .dropdown-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* ✅ Mensaje de selección */
.diagnostico-adicional-seleccionado {
    font-size: 0.8rem;
}

/* ✅ Tabla responsive */
#tabla_diagnosticos_adicionales .table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

/* ✅ Scroll suave */
#tabla_diagnosticos_adicionales .table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

#tabla_diagnosticos_adicionales .table-responsive::-webkit-scrollbar-track {
    background: #fff9e6;
}

#tabla_diagnosticos_adicionales .table-responsive::-webkit-scrollbar-thumb {
    background: #ffc107;
    border-radius: 4px;
}

#tabla_diagnosticos_adicionales .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #e0a800;
}
</style>

<script>
$(document).ready(function() {
    let contadorDiagnosticosAdicionales = 0;
    
    // ✅ Agregar Diagnóstico Adicional AL INICIO (ARRIBA)
    $('#agregar_diagnostico_adicional').on('click', function() {
        const template = $('#diagnostico_adicional_template').html();
        $('#diagnosticos_adicionales_container').prepend(template); // ✅ prepend = agregar arriba
        contadorDiagnosticosAdicionales++;
        
        // 🎯 Hacer scroll y focus al nuevo campo
        $('#diagnosticos_adicionales_container tr:first').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        $('#diagnosticos_adicionales_container tr:first .buscar-diagnostico-adicional').focus();
    });
    
    // ✅ Eliminar Diagnóstico Adicional
    $(document).on('click', '.eliminar-diagnostico-adicional', function() {
        if (confirm('¿Está seguro de eliminar este diagnóstico adicional?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // ✅ Búsqueda de Diagnósticos Adicionales (AJAX)
    $(document).on('input', '.buscar-diagnostico-adicional', function() {
        const input = $(this);
        const query = input.val().trim();
        const dropdown = input.siblings('.diagnosticos-adicionales-resultados');
        
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
                            <a href="#" class="dropdown-item seleccionar-diagnostico-adicional" 
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
    
    // ✅ Seleccionar Diagnóstico Adicional del dropdown
    $(document).on('click', '.seleccionar-diagnostico-adicional', function(e) {
        e.preventDefault();
        
        const item = $(this);
        const row = item.closest('tr');
        const id = item.data('id');
        const codigo = item.data('codigo');
        const nombre = item.data('nombre');
        const descripcion = item.data('descripcion');
        
        // Llenar campos
        row.find('.diagnostico-adicional-id').val(id);
        row.find('.buscar-diagnostico-adicional').val(codigo + ' - ' + nombre);
        row.find('.diagnostico-adicional-info').text(codigo + ' - ' + nombre + (descripcion ? ' | ' + descripcion : ''));
        row.find('.diagnostico-adicional-seleccionado').show();
        
        // Ocultar dropdown
        item.closest('.diagnosticos-adicionales-resultados').empty().hide();
    });
    
    // ✅ Ocultar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.diagnostico-adicional-search-wrapper').length) {
            $('.diagnosticos-adicionales-resultados').empty().hide();
        }
    });
    
    // ✅ Cargar Diagnósticos Adicionales existentes desde la base de datos
    const diagnosticosAdicionalesExistentes = @json($historia->diagnosticosAdicionales ?? []);
    
    // ✅ Cargar en orden NORMAL con append() para mantener orden de BD
    diagnosticosAdicionalesExistentes.forEach(function(diagnostico) {
        const template = $('#diagnostico_adicional_template').html();
        $('#diagnosticos_adicionales_container').append(template); // ✅ append para datos existentes
        
        const ultimaFila = $('#diagnosticos_adicionales_container tr:last');
        ultimaFila.find('.diagnostico-adicional-id').val(diagnostico.id);
        ultimaFila.find('.buscar-diagnostico-adicional').val(diagnostico.codigo + ' - ' + diagnostico.nombre);
        ultimaFila.find('select[name*="tipo_diagnostico"]').val(diagnostico.tipo_diagnostico);
        ultimaFila.find('.diagnostico-adicional-info').text(diagnostico.codigo + ' - ' + diagnostico.nombre);
        ultimaFila.find('.diagnostico-adicional-seleccionado').show();
    });

});
</script>
