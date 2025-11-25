{{-- ✅ SECCIÓN: CUPS - DISEÑO TABLA COMPACTO --}}
<div class="card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            PROCEDIMIENTOS CUPS
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_cups">
            <i class="fas fa-plus me-1"></i>Agregar CUPS
        </button>
    </div>
    <div class="card-body p-0">
        <!-- Tabla de CUPS -->
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabla_cups">
                <thead class="table-light">
                    <tr>
                        <th width="50%">PROCEDIMIENTO CUPS</th>
                        <th width="45%">OBSERVACIÓN</th>
                        <th width="5%" class="text-center">
                            <i class="fas fa-cog"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="cups_container">
                    <!-- Los CUPS se agregarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE CUPS - FILA DE TABLA --}}
<template id="cups_template">
    <tr class="cups-item">
        <td>
            <div class="cups-search-wrapper position-relative">
                <input type="text" 
                    class="form-control form-control-sm buscar-cups" 
                    placeholder="Escriba código o nombre del procedimiento..."
                    autocomplete="off">
                
                <div class="dropdown-menu cups-resultados w-100"></div>
                
                <input type="hidden" class="cups-id" name="cups[][idCups]">
                
                <div class="cups-seleccionado mt-1" style="display: none;">
                    <small class="text-secondary">
                        <i class="fas fa-check-circle me-1"></i>
                        <span class="cups-info"></span>
                    </small>
                </div>
            </div>
        </td>
        <td>
            <textarea class="form-control form-control-sm" 
                name="cups[][cupObservacion]" 
                rows="2" 
                placeholder="Observación del procedimiento..."></textarea>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm eliminar-cups" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

{{-- 🎨 ESTILOS PARA DISEÑO COMPACTO DE CUPS --}}
<style>
/* ✅ Tabla compacta CUPS */
#tabla_cups {
    font-size: 0.9rem;
}

#tabla_cups thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
}

#tabla_cups tbody td {
    padding: 0.5rem;
    vertical-align: middle;
}

#tabla_cups tbody tr:hover {
    background-color: #f8f9fa;
}

/* ✅ Textarea compacto */
#tabla_cups textarea.form-control-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
    resize: vertical;
    min-height: 60px;
}

/* ✅ Dropdown de búsqueda CUPS */
.cups-search-wrapper {
    position: relative;
}

.cups-resultados.dropdown-menu {
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

.cups-resultados.dropdown-menu:empty {
    display: none !important;
}

.cups-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.5rem 0.75rem !important;
    line-height: 1.4 !important;
    color: #212529 !important;
    font-size: 0.875rem;
}

.cups-resultados .dropdown-item:hover {
    background-color: #6c757d !important; /* Gris secondary */
    cursor: pointer !important;
    color: #ffffff !important;
}

.cups-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.8rem;
}

.cups-resultados .dropdown-item:hover small {
    color: #e9ecef !important;
}

.cups-resultados .dropdown-item strong {
    color: #212529 !important;
    font-weight: 600;
}

.cups-resultados .dropdown-item:hover strong {
    color: #ffffff !important;
}

.cups-resultados .dropdown-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* ✅ Mensaje de selección */
.cups-seleccionado {
    font-size: 0.8rem;
}

/* ✅ Tabla responsive */
#tabla_cups .table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

/* ✅ Scroll suave */
#tabla_cups .table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

#tabla_cups .table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#tabla_cups .table-responsive::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: 4px;
}

#tabla_cups .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #5a6268;
}
</style>

<script>
$(document).ready(function() {
    let contadorCups = 0;
    
    // ✅ Agregar CUPS AL INICIO (ARRIBA)
    $('#agregar_cups').on('click', function() {
        const template = $('#cups_template').html();
        $('#cups_container').prepend(template); // ✅ prepend = agregar arriba
        contadorCups++;
        
        // 🎯 Hacer scroll y focus al nuevo campo
        $('#cups_container tr:first').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        $('#cups_container tr:first .buscar-cups').focus();
    });
    
    // ✅ Eliminar CUPS
    $(document).on('click', '.eliminar-cups', function() {
        if (confirm('¿Está seguro de eliminar este procedimiento CUPS?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // ✅ Búsqueda de CUPS (AJAX)
    $(document).on('input', '.buscar-cups', function() {
        const input = $(this);
        const query = input.val().trim();
        const dropdown = input.siblings('.cups-resultados');
        
        if (query.length < 3) {
            dropdown.empty().hide();
            return;
        }
        
        // Llamada AJAX para buscar CUPS
        $.ajax({
            url: '/api/cups/buscar', // ✅ Ajusta tu ruta
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
                    response.forEach(function(cup) {
                        dropdown.append(`
                            <a href="#" class="dropdown-item seleccionar-cups" 
                               data-id="${cup.id}" 
                               data-codigo="${cup.codigo}"
                               data-nombre="${cup.nombre}"
                               data-descripcion="${cup.descripcion || ''}">
                                <strong>${cup.codigo}</strong> - ${cup.nombre}
                                ${cup.descripcion ? `<small>${cup.descripcion}</small>` : ''}
                            </a>
                        `);
                    });
                }
                
                dropdown.show();
            },
            error: function() {
                dropdown.empty().append(`
                    <div class="dropdown-item disabled">
                        <small class="text-danger">Error al buscar procedimientos CUPS</small>
                    </div>
                `).show();
            }
        });
    });
    
    // ✅ Seleccionar CUPS del dropdown
    $(document).on('click', '.seleccionar-cups', function(e) {
        e.preventDefault();
        
        const item = $(this);
        const row = item.closest('tr');
        const id = item.data('id');
        const codigo = item.data('codigo');
        const nombre = item.data('nombre');
        const descripcion = item.data('descripcion');
        
        // Llenar campos
        row.find('.cups-id').val(id);
        row.find('.buscar-cups').val(codigo + ' - ' + nombre);
        row.find('.cups-info').text(codigo + ' - ' + nombre + (descripcion ? ' | ' + descripcion : ''));
        row.find('.cups-seleccionado').show();
        
        // Ocultar dropdown
        item.closest('.cups-resultados').empty().hide();
    });
    
    // ✅ Ocultar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.cups-search-wrapper').length) {
            $('.cups-resultados').empty().hide();
        }
    });
    
    // ✅ Cargar CUPS existentes desde la base de datos
    const cupsExistentes = @json($historia->cups ?? []);
    
    // ✅ Cargar en orden NORMAL con append() para mantener orden de BD
    cupsExistentes.forEach(function(cup) {
        const template = $('#cups_template').html();
        $('#cups_container').append(template); // ✅ append para CUPS existentes
        
        const ultimaFila = $('#cups_container tr:last');
        ultimaFila.find('.cups-id').val(cup.id);
        ultimaFila.find('.buscar-cups').val(cup.codigo + ' - ' + cup.nombre);
        ultimaFila.find('textarea[name*="cupObservacion"]').val(cup.observacion);
        ultimaFila.find('.cups-info').text(cup.codigo + ' - ' + cup.nombre);
        ultimaFila.find('.cups-seleccionado').show();
    });

});
</script>
