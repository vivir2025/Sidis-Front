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
                <div class="mb-3">
                    <label for="buscar_diagnostico" class="form-label">Buscar Diagnóstico <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="buscar_diagnostico" 
                           placeholder="Escriba código o nombre del diagnóstico..." required>
                    <div id="diagnosticos_resultados" class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                </div>
                <input type="hidden" id="idDiagnostico" name="idDiagnostico" required>
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
