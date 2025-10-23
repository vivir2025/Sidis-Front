{{-- ✅ SECCIÓN: MEDICAMENTOS --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-pills me-2"></i>
            Medicamentos
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_medicamento">
            <i class="fas fa-plus me-1"></i>Agregar Medicamento
        </button>
    </div>
    <div class="card-body">
        <div id="medicamentos_container">
            <!-- Los medicamentos se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE MEDICAMENTO --}}
<template id="medicamento_template">
    <div class="medicamento-item border rounded p-3 mb-5" style="background-color: #f8f9fa; position: relative;">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3 medicamento-search-wrapper" style="position: relative;">
                    <label class="form-label">Buscar Medicamento <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control buscar-medicamento" 
                           placeholder="Escriba el nombre del medicamento..."
                           autocomplete="off">
                    
                    <div class="dropdown-menu medicamentos-resultados w-100"></div>
                    
                    <input type="hidden" class="medicamento-id" name="medicamentos[][idMedicamento]">
                    
                    <div class="alert alert-info mt-2 medicamento-seleccionado" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="medicamento-info"></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           name="medicamentos[][cantidad]" 
                           placeholder="Ej: 30 tabletas"
                           required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Dosis <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           name="medicamentos[][dosis]" 
                           placeholder="Ej: 1 cada 8 horas"
                           required>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block w-100 eliminar-medicamento">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- 🎨 ESTILOS PARA MEDICAMENTOS DROPDOWN --}}
<style>
.medicamento-search-wrapper {
    position: relative;
}

.medicamentos-resultados.dropdown-menu {
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

.medicamentos-resultados.dropdown-menu:empty {
    display: none !important;
}

.medicamentos-resultados .dropdown-item {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
    color: #000000 !important; /* ✅ LETRA NEGRA */
}

.medicamentos-resultados .dropdown-item:hover {
    background-color: #d1e7dd !important;
    cursor: pointer !important;
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

.medicamentos-resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.medicamentos-resultados .dropdown-item:hover small {
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

.medicamentos-resultados .dropdown-item strong {
    color: #000000 !important; /* ✅ LETRA NEGRA */
    font-weight: 600;
}

.medicamentos-resultados .dropdown-item:hover strong {
    color: #ffffff !important; /* ✅ LETRA BLANCA AL HOVER */
}

/* Asegurar espacio suficiente para el dropdown */
.medicamento-item {
    margin-bottom: 2rem !important;
}

/* Estilo para el alerta de selección */
.medicamento-seleccionado {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

/* Botón eliminar */
.eliminar-medicamento {
    min-width: 40px;
}
</style>
