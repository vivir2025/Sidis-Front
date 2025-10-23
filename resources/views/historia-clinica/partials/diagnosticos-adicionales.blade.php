<div class="card mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            Diagnósticos Adicionales
        </h5>
        <button type="button" class="btn btn-dark btn-sm" id="agregar_diagnostico_adicional">
            <i class="fas fa-plus me-1"></i>Agregar Diagnóstico
        </button>
    </div>
    <div class="card-body">
        <div id="diagnosticos_adicionales_container">
            {{-- Los diagnósticos adicionales se agregarán aquí dinámicamente --}}
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE PARA DIAGNÓSTICOS ADICIONALES --}}
<template id="diagnostico_adicional_template">
    <div class="diagnostico-adicional-item border rounded p-3 mb-5" style="background-color: #f8f9fa; position: relative;">
        <div class="row">
            <div class="col-md-10">
                {{-- Buscador de Diagnóstico --}}
                <div class="mb-3 diagnostico-adicional-search-wrapper" style="position: relative;">
                    <label class="form-label">Buscar Diagnóstico Adicional <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control buscar-diagnostico-adicional" 
                           placeholder="Escriba código o nombre del diagnóstico..." 
                           autocomplete="off">
                    
                    <div class="dropdown-menu diagnosticos-adicionales-resultados w-100"></div>
                    
                    <input type="hidden" 
                           class="diagnostico-adicional-id" 
                           name="diagnosticos_adicionales[][idDiagnostico]">
                    
                    {{-- Alerta de diagnóstico seleccionado --}}
                    <div class="alert alert-info mt-2 diagnostico-adicional-seleccionado" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="diagnostico-adicional-info"></span>
                    </div>
                </div>
                
                {{-- Tipo de Diagnóstico --}}
                <div class="mb-3">
                    <label class="form-label">Tipo de Diagnóstico <span class="text-danger">*</span></label>
                    <select class="form-select" name="diagnosticos_adicionales[][tipo_diagnostico]" required>
                        <option value="">Seleccione...</option>
                        <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                        <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                        <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                    </select>
                </div>
            </div>
            
            {{-- Botón Eliminar --}}
            <div class="col-md-2 d-flex align-items-start justify-content-end">
                <button type="button" class="btn btn-danger btn-sm eliminar-diagnostico-adicional">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

{{-- 🎨 ESTILOS PARA DIAGNÓSTICOS ADICIONALES DROPDOWN --}}
<style>
.diagnostico-adicional-search-wrapper {
    position: relative;
}

.diagnosticos-adicionales-resultados.dropdown-menu {
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
}

.diagnosticos-adicionales-resultados.dropdown-menu:empty {
    display: none !important;
}

.diagnosticos-adicionales-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
}

.diagnosticos-adicionales-resultados .dropdown-item:hover {
    background-color: #fff3cd !important;
    cursor: pointer !important;
}

.diagnosticos-adicionales-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.diagnosticos-adicionales-resultados .dropdown-item strong {
    color: #856404;
    font-weight: 600;
}

/* Asegurar espacio suficiente para el dropdown */
.diagnostico-adicional-item {
    margin-bottom: 2rem !important;
}

/* Estilo para el alerta de selección */
.diagnostico-adicional-seleccionado {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}
</style>
