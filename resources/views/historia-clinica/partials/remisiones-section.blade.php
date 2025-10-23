{{-- ✅ SECCIÓN: REMISIONES --}}
<div class="card mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-share me-2"></i>
            Remisiones
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_remision">
            <i class="fas fa-plus me-1"></i>Agregar Remisión
        </button>
    </div>
    <div class="card-body">
        <div id="remisiones_container">
            <!-- Las remisiones se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE REMISIÓN --}}
<template id="remision_template">
    <div class="remision-item border rounded p-3 mb-5" style="background-color: #f8f9fa; position: relative;">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 remision-search-wrapper" style="position: relative;">
                    <label class="form-label">Buscar Remisión <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control buscar-remision" 
                           placeholder="Escriba el nombre de la remisión..."
                           autocomplete="off">
                    
                    <div class="dropdown-menu remisiones-resultados w-100"></div>
                    
                    <input type="hidden" class="remision-id" name="remisiones[][idRemision]">
                    
                    <div class="alert alert-info mt-2 remision-seleccionada" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="remision-info"></span>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" 
                              name="remisiones[][remObservacion]" 
                              rows="3" 
                              placeholder="Observación de la remisión..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block w-100 eliminar-remision">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- 🎨 ESTILOS PARA REMISIONES DROPDOWN --}}
<style>
.remision-search-wrapper {
    position: relative;
}

.remisiones-resultados.dropdown-menu {
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

.remisiones-resultados.dropdown-menu:empty {
    display: none !important;
}

.remisiones-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
    color: #000000 !important; /* ✅ LETRA NEGRA */
}

.remisiones-resultados .dropdown-item:hover {
    background-color: #cff4fc !important;
    cursor: pointer !important;
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

.remisiones-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.remisiones-resultados .dropdown-item:hover small {
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

.remisiones-resultados .dropdown-item strong {
    color: #000000 !important; /* ✅ LETRA NEGRA */
    font-weight: 600;
}

.remisiones-resultados .dropdown-item:hover strong {
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

/* Asegurar espacio suficiente para el dropdown */
.remision-item {
    margin-bottom: 2rem !important;
}

/* Estilo para el alerta de selección */
.remision-seleccionada {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

/* Botón eliminar */
.eliminar-remision {
    min-width: 40px;
}

/* Textarea observación */
.remision-item textarea {
    resize: vertical;
    min-height: 70px;
}
</style>