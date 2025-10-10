<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Factores de Riesgo
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="numero_frutas_diarias" class="form-label">Número de frutas diarias</label>
                    <input type="number" class="form-control" id="numero_frutas_diarias" 
                           name="numero_frutas_diarias" min="0" max="20">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Elevado consumo de grasa saturada</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="elevado_consumo_grasa_saturada" id="grasa_si" value="SI">
                            <label class="form-check-label" for="grasa_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="elevado_consumo_grasa_saturada" id="grasa_no" value="NO" checked>
                            <label class="form-check-label" for="grasa_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Adiciona sal después de preparar alimentos</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="adiciona_sal_despues_preparar_alimentos" id="sal_si" value="SI">
                            <label class="form-check-label" for="sal_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="adiciona_sal_despues_preparar_alimentos" id="sal_no" value="NO" checked>
                            <label class="form-check-label" for="sal_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Dislipidemia</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="dislipidemia" id="dislipidemia_si" value="SI">
                            <label class="form-check-label" for="dislipidemia_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="dislipidemia" id="dislipidemia_no" value="NO" checked>
                            <label class="form-check-label" for="dislipidemia_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Condición clínica asociada</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="condicion_clinica_asociada" id="condicion_si" value="SI">
                            <label class="form-check-label" for="condicion_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="condicion_clinica_asociada" id="condicion_no" value="NO" checked>
                            <label class="form-check-label" for="condicion_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Lesión de órgano blanco</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="lesion_organo_blanco" id="lesion_si" value="SI">
                            <label class="form-check-label" for="lesion_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="lesion_organo_blanco" id="lesion_no" value="NO" checked>
                            <label class="form-check-label" for="lesion_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="descripcion_lesion_organo_blanco" class="form-label">Descripción lesión órgano blanco</label>
                    <textarea class="form-control" id="descripcion_lesion_organo_blanco" 
                              name="descripcion_lesion_organo_blanco" rows="2" disabled></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
