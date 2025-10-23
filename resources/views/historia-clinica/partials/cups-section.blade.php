{{-- ✅ SECCIÓN: CUPS --}}
<div class="card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Procedimientos CUPS
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_cups">
            <i class="fas fa-plus me-1"></i>Agregar CUPS
        </button>
    </div>
    <div class="card-body">
        <div id="cups_container">
            <!-- Los CUPS se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

{{-- TEMPLATE CUPS --}}
<template id="cups_template">
    <div class="cups-item border rounded p-3 mb-5" style="position: relative;">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 cups-search-wrapper" style="position: relative;">
                    <label class="form-label">Buscar CUPS</label>
                    <input type="text" class="form-control buscar-cups" placeholder="Escriba código o nombre del procedimiento..." autocomplete="off">
                    <div class="cups-resultados dropdown-menu w-100"></div>
                    <input type="hidden" class="cups-id" name="cups[][idCups]">
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" name="cups[][cupObservacion]" rows="2" placeholder="Observación del procedimiento..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-cups">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info cups-seleccionado" style="display: none;">
                    <strong>CUPS Seleccionado:</strong>
                    <span class="cups-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- 🎨 ESTILOS PARA CUPS DROPDOWN --}}
<style>
.cups-search-wrapper {
    position: relative;
}

.cups-resultados.dropdown-menu {
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

.cups-resultados.dropdown-menu:empty {
    display: none !important;
}

.cups-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
}

.cups-resultados .dropdown-item:hover {
    background-color: #f8f9fa !important;
    cursor: pointer !important;
}

.cups-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

/* Asegurar que el contenedor padre tenga espacio suficiente */
.cups-item {
    margin-bottom: 2rem !important;
}
</style>
