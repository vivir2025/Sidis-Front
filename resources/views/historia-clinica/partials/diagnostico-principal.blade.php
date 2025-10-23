<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>
            Diagnóstico Principal <span class="text-warning">*</span>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 diagnostico-search-wrapper" style="position: relative;">
                    <label for="buscar_diagnostico" class="form-label">Buscar Diagnóstico <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="buscar_diagnostico" 
                           placeholder="Escriba código o nombre del diagnóstico..." 
                           autocomplete="off" required>
                    <div id="diagnosticos_resultados" class="dropdown-menu w-100"></div>
                    <input type="hidden" id="idDiagnostico" name="idDiagnostico" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="tipo_diagnostico" class="form-label">Tipo de Diagnóstico <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipo_diagnostico" name="tipo_diagnostico" required>
                        <option value="">Seleccione...</option>
                        <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                        <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                        <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info" id="diagnostico_seleccionado" style="display: none;">
                    <strong>Diagnóstico Seleccionado:</strong>
                    <span id="diagnostico_info"></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 🎨 ESTILOS PARA DIAGNÓSTICO DROPDOWN --}}
<style>
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
}

#diagnosticos_resultados .dropdown-item:hover {
    background-color: #f8f9fa !important;
    cursor: pointer !important;
}

#diagnosticos_resultados .dropdown-item small {
    display: block;
    color: #6c757d;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

#diagnosticos_resultados .dropdown-item strong {
    color: #dc3545;
    font-weight: 600;
}
</style>
