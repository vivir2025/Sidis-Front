<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fas fa-tags me-2"></i>
            Clasificaciones
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="ClasificacionEstadoMetabolico" class="form-label">Clasificación Estado Metabólico</label>
                    <select class="form-select" id="ClasificacionEstadoMetabolico" name="ClasificacionEstadoMetabolico">
                        <option value="">Seleccione...</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PREDIABETES">Prediabetes</option>
                        <option value="DIABETES_TIPO_1">Diabetes Tipo 1</option>
                        <option value="DIABETES_TIPO_2">Diabetes Tipo 2</option>
                        <option value="DIABETES_GESTACIONAL">Diabetes Gestacional</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_hta" class="form-label">Clasificación HTA</label>
                    <select class="form-select" id="clasificacion_hta" name="clasificacion_hta">
                        <option value="">Seleccione...</option>
                        <option value="NORMAL">Normal</option>
                        <option value="ELEVADA">Elevada</option>
                        <option value="ESTADIO_1">Estadio 1</option>
                        <option value="ESTADIO_2">Estadio 2</option>
                        <option value="CRISIS_HIPERTENSIVA">Crisis Hipertensiva</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_dm" class="form-label">Clasificación DM</label>
                    <select class="form-select" id="clasificacion_dm" name="clasificacion_dm">
                        <option value="">Seleccione...</option>
                        <option value="TIPO_1">Tipo 1</option>
                        <option value="TIPO_2">Tipo 2</option>
                        <option value="GESTACIONAL">Gestacional</option>
                        <option value="OTROS_TIPOS">Otros Tipos</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_rcv" class="form-label">Clasificación RCV</label>
                    <select class="form-select" id="clasificacion_rcv" name="clasificacion_rcv">
                        <option value="">Seleccione...</option>
                        <option value="BAJO">Bajo</option>
                        <option value="MODERADO">Moderado</option>
                        <option value="ALTO">Alto</option>
                        <option value="MUY_ALTO">Muy Alto</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_erc_estado" class="form-label">Clasificación ERC Estado</label>
                    <select class="form-select" id="clasificacion_erc_estado" name="clasificacion_erc_estado">
                        <option value="">Seleccione...</option>
                        <option value="ESTADIO_1">Estadio 1</option>
                        <option value="ESTADIO_2">Estadio 2</option>
                        <option value="ESTADIO_3A">Estadio 3A</option>
                        <option value="ESTADIO_3B">Estadio 3B</option>
                        <option value="ESTADIO_4">Estadio 4</option>
                        <option value="ESTADIO_5">Estadio 5</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_erc_categoria_ambulatoria_persistente" class="form-label">Categoría Ambulatoria Persistente</label>
                    <select class="form-select" id="clasificacion_erc_categoria_ambulatoria_persistente" name="clasificacion_erc_categoria_ambulatoria_persistente">
                        <option value="">Seleccione...</option>
                                               <option value="A1">A1: Normal a levemente aumentada</option>
                        <option value="A2">A2: Moderadamente aumentada</option>
                        <option value="A3">A3: Severamente aumentada</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="tasa_filtracion_glomerular_ckd_epi" class="form-label">Tasa Filtración Glomerular CKD-EPI</label>
                    <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_ckd_epi" 
                           name="tasa_filtracion_glomerular_ckd_epi" min="0" max="200">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="tasa_filtracion_glomerular_gockcroft_gault" class="form-label">Tasa Filtración Glomerular Cockcroft-Gault</label>
                    <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_gockcroft_gault" 
                           name="tasa_filtracion_glomerular_gockcroft_gault" min="0" max="200">
                </div>
            </div>
        </div>

        {{-- ✅ ANTECEDENTES PERSONALES ESPECÍFICOS --}}
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Hipertensión Arterial Personal</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="hipertension_arterial_personal" id="hta_personal_si" value="SI">
                            <label class="form-check-label" for="hta_personal_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="hipertension_arterial_personal" id="hta_personal_no" value="NO" checked>
                            <label class="form-check-label" for="hta_personal_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="obs_hipertension_arterial_personal" class="form-label">Observaciones HTA Personal</label>
                    <textarea class="form-control" id="obs_hipertension_arterial_personal" 
                              name="obs_hipertension_arterial_personal" rows="2" disabled></textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Diabetes Mellitus Personal</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" id="dm_personal_si" value="SI">
                            <label class="form-check-label" for="dm_personal_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" id="dm_personal_no" value="NO" checked>
                            <label class="form-check-label" for="dm_personal_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="obs_diabetes_mellitus_personal" class="form-label">Observaciones DM Personal</label>
                    <textarea class="form-control" id="obs_diabetes_mellitus_personal" 
                              name="obs_diabetes_mellitus_personal" rows="2" disabled></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
