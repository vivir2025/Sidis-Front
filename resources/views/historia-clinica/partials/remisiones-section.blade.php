{{-- ✅ SECCIÓN: REMISIONES - DISEÑO TABLA COMPACTO --}}
<div class="card mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-share me-2"></i>
            REMISIONES
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_remision">
            <i class="fas fa-plus me-1"></i>Agregar Remisión
        </button>
    </div>
    <div class="card-body p-0">
        <!-- Tabla de remisiones -->
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabla_remisiones">
                <thead class="table-light">
                    <tr>
                        <th width="50%">REMISIÓN</th>
                        <th width="45%">OBSERVACIÓN</th>
                        <th width="5%" class="text-center">
                            <i class="fas fa-cog"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="remisiones_container">
                    <!-- Las remisiones se agregarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE REMISIÓN - FILA DE TABLA --}}
<template id="remision_template">
    <tr class="remision-item">
        <td>
            <div class="remision-search-wrapper position-relative">
                <input type="text" 
                    class="form-control form-control-sm buscar-remision" 
                    placeholder="Escriba el nombre de la remisión..."
                    autocomplete="off">
                
                <div class="dropdown-menu remisiones-resultados w-100"></div>
                
                <input type="hidden" class="remision-id" name="remisiones[][idRemision]">
                
                <div class="remision-seleccionada mt-1" style="display: none;">
                    <small class="text-info">
                        <i class="fas fa-check-circle me-1"></i>
                        <span class="remision-info"></span>
                    </small>
                </div>
            </div>
        </td>
        <td>
            <textarea class="form-control form-control-sm" 
                name="remisiones[][remObservacion]" 
                rows="2" 
                placeholder="Observación de la remisión..."></textarea>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm eliminar-remision" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

{{-- 🎨 ESTILOS PARA DISEÑO COMPACTO DE REMISIONES --}}
<style>
/* ✅ Tabla compacta remisiones */
#tabla_remisiones {
    font-size: 0.9rem;
}

#tabla_remisiones thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    padding: 0.75rem;
    vertical-align: middle;
}

#tabla_remisiones tbody td {
    padding: 0.5rem;
    vertical-align: middle;
}

#tabla_remisiones tbody tr:hover {
    background-color: #f8f9fa;
}

/* ✅ Textarea compacto */
#tabla_remisiones textarea.form-control-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
    resize: vertical;
    min-height: 60px;
}

/* ✅ Dropdown de búsqueda remisiones */
.remision-search-wrapper {
    position: relative;
}

.remisiones-resultados.dropdown-menu {
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

.remisiones-resultados.dropdown-menu:empty {
    display: none !important;
}

.remisiones-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.5rem 0.75rem !important;
    line-height: 1.4 !important;
    color: #212529 !important;
    font-size: 0.875rem;
}

.remisiones-resultados .dropdown-item:hover {
    background-color: #0dcaf0 !important; /* Azul info */
    cursor: pointer !important;
    color: #ffffff !important;
}

.remisiones-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.8rem;
}

.remisiones-resultados .dropdown-item:hover small {
    color: #e9ecef !important;
}

.remisiones-resultados .dropdown-item strong {
    color: #212529 !important;
    font-weight: 600;
}

.remisiones-resultados .dropdown-item:hover strong {
    color: #ffffff !important;
}

/* ✅ Mensaje de selección */
.remision-seleccionada {
    font-size: 0.8rem;
}

/* ✅ Tabla responsive */
#tabla_remisiones .table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

/* ✅ Scroll suave */
#tabla_remisiones .table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

#tabla_remisiones .table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#tabla_remisiones .table-responsive::-webkit-scrollbar-thumb {
    background: #0dcaf0;
    border-radius: 4px;
}

#tabla_remisiones .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #0aa2c0;
}
</style>

<script>
$(document).ready(function() {
    let contadorRemisiones = 0;
    
    // ✅ Agregar remisión AL INICIO (ARRIBA)
    $('#agregar_remision').on('click', function() {
        const template = $('#remision_template').html();
        $('#remisiones_container').prepend(template); // ✅ prepend = agregar arriba
        contadorRemisiones++;
        
        // 🎯 Hacer scroll y focus al nuevo campo
        $('#remisiones_container tr:first').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        $('#remisiones_container tr:first .buscar-remision').focus();
    });
    
    // ✅ Eliminar remisión
    $(document).on('click', '.eliminar-remision', function() {
        if (confirm('¿Está seguro de eliminar esta remisión?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // ✅ Búsqueda de remisiones (AJAX)
    $(document).on('input', '.buscar-remision', function() {
        const input = $(this);
        const query = input.val().trim();
        const dropdown = input.siblings('.remisiones-resultados');
        
        if (query.length < 3) {
            dropdown.empty().hide();
            return;
        }
        
        // Llamada AJAX para buscar remisiones
        $.ajax({
            url: '/api/remisiones/buscar', // ✅ Ajusta tu ruta
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
                    response.forEach(function(rem) {
                        dropdown.append(`
                            <a href="#" class="dropdown-item seleccionar-remision" 
                               data-id="${rem.id}" 
                               data-nombre="${rem.nombre}"
                               data-descripcion="${rem.descripcion || ''}">
                                <strong>${rem.nombre}</strong>
                                ${rem.descripcion ? `<small>${rem.descripcion}</small>` : ''}
                            </a>
                        `);
                    });
                }
                
                dropdown.show();
            },
            error: function() {
                dropdown.empty().append(`
                    <div class="dropdown-item disabled">
                        <small class="text-danger">Error al buscar remisiones</small>
                    </div>
                `).show();
            }
        });
    });
    
    // ✅ Seleccionar remisión del dropdown
    $(document).on('click', '.seleccionar-remision', function(e) {
        e.preventDefault();
        
        const item = $(this);
        const row = item.closest('tr');
        const id = item.data('id');
        const nombre = item.data('nombre');
        const descripcion = item.data('descripcion');
        
        // Llenar campos
        row.find('.remision-id').val(id);
        row.find('.buscar-remision').val(nombre);
        row.find('.remision-info').text(nombre + (descripcion ? ' - ' + descripcion : ''));
        row.find('.remision-seleccionada').show();
        
        // Ocultar dropdown
        item.closest('.remisiones-resultados').empty().hide();
    });
    
    // ✅ Ocultar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.remision-search-wrapper').length) {
            $('.remisiones-resultados').empty().hide();
        }
    });
    
    // ✅ Cargar remisiones existentes desde la base de datos
    const remisionesExistentes = @json($historia->remisiones ?? []);
    
    // ✅ Cargar en orden NORMAL con append() para mantener orden de BD
    remisionesExistentes.forEach(function(rem) {
        const template = $('#remision_template').html();
        $('#remisiones_container').append(template); // ✅ append para remisiones existentes
        
        const ultimaFila = $('#remisiones_container tr:last');
        ultimaFila.find('.remision-id').val(rem.id);
        ultimaFila.find('.buscar-remision').val(rem.nombre);
        ultimaFila.find('textarea[name*="remObservacion"]').val(rem.observacion);
        ultimaFila.find('.remision-info').text(rem.nombre);
        ultimaFila.find('.remision-seleccionada').show();
    });

});
</script>
