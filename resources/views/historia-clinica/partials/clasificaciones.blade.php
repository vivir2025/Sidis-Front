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
                    <label for="ClasificacionEstadoMetabolico" class="form-label">Clasificación Estado Metabólico <span class="text-danger">*</span></label>
                    <select class="form-select" id="ClasificacionEstadoMetabolico" name="ClasificacionEstadoMetabolico" required>
                        <option value="">Seleccione...</option>
                        <option value="DM_CON_COMPLICACIONES" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'DM_CON_COMPLICACIONES' ? 'selected' : '' }}>DM CON COMPLICACIONES</option>
                        <option value="DM_SIN_COMPLICACIONES" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'DM_SIN_COMPLICACIONES' ? 'selected' : '' }}>DM SIN COMPLICACIONES</option>
                        <option value="ERC_ESTADIO_IIIB" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'ERC_ESTADIO_IIIB' ? 'selected' : '' }}>ERC-ESTADIO IIIB</option>
                        <option value="ERC_ESTADIO_IV" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'ERC_ESTADIO_IV' ? 'selected' : '' }}>ERC-ESTADIO IV</option>
                        <option value="ERC_ESTADIO_V" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'ERC_ESTADIO_V' ? 'selected' : '' }}>ERC-ESTADIO V</option>
                        <option value="HTA_RIESGO_ALTO" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'HTA_RIESGO_ALTO' ? 'selected' : '' }}>HTA RIESGO ALTO</option>
                        <option value="HTA_RIESGO_BAJO" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'HTA_RIESGO_BAJO' ? 'selected' : '' }}>HTA RIESGO BAJO</option>
                        <option value="HTA_RIESGO_MODERADO" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'HTA_RIESGO_MODERADO' ? 'selected' : '' }}>HTA RIESGO MODERADO</option>
                        <option value="HTA_RIESGO_MUY_ALTO" {{ ($historiaPrevia['ClasificacionEstadoMetabolico'] ?? '') === 'HTA_RIESGO_MUY_ALTO' ? 'selected' : '' }}>HTA RIESGO MUY ALTO</option>
                    </select>
                </div>

            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_hta" class="form-label">Clasificación HTA <span class="text-danger">*</span></label>
                    <select class="form-select" id="clasificacion_hta" name="clasificacion_hta" required>
                        <option value="">Seleccione...</option>
                        <option value="NORMAL" {{ ($historiaPrevia['clasificacion_hta'] ?? '') === 'NORMAL' ? 'selected' : '' }}>Normal</option>
                        <option value="ELEVADA" {{ ($historiaPrevia['clasificacion_hta'] ?? '') === 'ELEVADA' ? 'selected' : '' }}>Elevada</option>
                        <option value="ESTADIO_1" {{ ($historiaPrevia['clasificacion_hta'] ?? '') === 'ESTADIO_1' ? 'selected' : '' }}>Estadio 1</option>
                        <option value="ESTADIO_2" {{ ($historiaPrevia['clasificacion_hta'] ?? '') === 'ESTADIO_2' ? 'selected' : '' }}>Estadio 2</option>
                        <option value="CRISIS_HIPERTENSIVA" {{ ($historiaPrevia['clasificacion_hta'] ?? '') === 'CRISIS_HIPERTENSIVA' ? 'selected' : '' }}>Crisis Hipertensiva</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_dm" class="form-label">Clasificación DM <span class="text-danger">*</span></label>
                    <select class="form-select" id="clasificacion_dm" name="clasificacion_dm" required>
                        <option value="">Seleccione...</option>
                        <option value="TIPO_1" {{ ($historiaPrevia['clasificacion_dm'] ?? '') === 'TIPO_1' ? 'selected' : '' }}>Tipo 1</option>
                        <option value="TIPO_2" {{ ($historiaPrevia['clasificacion_dm'] ?? '') === 'TIPO_2' ? 'selected' : '' }}>Tipo 2</option>
                        <option value="GESTACIONAL" {{ ($historiaPrevia['clasificacion_dm'] ?? '') === 'GESTACIONAL' ? 'selected' : '' }}>Gestacional</option>
                        <option value="OTROS_TIPOS" {{ ($historiaPrevia['clasificacion_dm'] ?? '') === 'OTROS_TIPOS' ? 'selected' : '' }}>Otros Tipos</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_rcv" class="form-label">Clasificación RCV <span class="text-danger">*</span></label>
                    <select class="form-select" id="clasificacion_rcv" name="clasificacion_rcv" required>
                        <option value="">Seleccione...</option>
                        <option value="BAJO" {{ ($historiaPrevia['clasificacion_rcv'] ?? '') === 'BAJO' ? 'selected' : '' }}>Bajo</option>
                        <option value="MODERADO" {{ ($historiaPrevia['clasificacion_rcv'] ?? '') === 'MODERADO' ? 'selected' : '' }}>Moderado</option>
                        <option value="ALTO" {{ ($historiaPrevia['clasificacion_rcv'] ?? '') === 'ALTO' ? 'selected' : '' }}>Alto</option>
                        <option value="MUY_ALTO" {{ ($historiaPrevia['clasificacion_rcv'] ?? '') === 'MUY_ALTO' ? 'selected' : '' }}>Muy Alto</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_erc_estado" class="form-label">Clasificación ERC Estadio <span class="text-danger">*</span></label>
                    <select class="form-select" id="clasificacion_erc_estado" name="clasificacion_erc_estado" required>
                        <option value="">Seleccione...</option>
                        <option value="ESTADIO_1" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_1' ? 'selected' : '' }}>Estadio 1</option>
                        <option value="ESTADIO_2" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_2' ? 'selected' : '' }}>Estadio 2</option>
                        <option value="ESTADIO_3A" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_3A' ? 'selected' : '' }}>Estadio 3A</option>
                        <option value="ESTADIO_3B" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_3B' ? 'selected' : '' }}>Estadio 3B</option>
                        <option value="ESTADIO_4" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_4' ? 'selected' : '' }}>Estadio 4</option>
                        <option value="ESTADIO_5" {{ ($historiaPrevia['clasificacion_erc_estado'] ?? '') === 'ESTADIO_5' ? 'selected' : '' }}>Estadio 5</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="clasificacion_erc_categoria_ambulatoria_persistente" class="form-label">Categoría Ambulatoria Persistente <span class="text-danger">*</span></label>
                    <select class="form-select" id="clasificacion_erc_categoria_ambulatoria_persistente" name="clasificacion_erc_categoria_ambulatoria_persistente" required>
                        <option value="">Seleccione...</option>
                        <option value="A1" {{ ($historiaPrevia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '') === 'A1' ? 'selected' : '' }}>A1: Normal a levemente aumentada</option>
                        <option value="A2" {{ ($historiaPrevia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '') === 'A2' ? 'selected' : '' }}>A2: Moderadamente aumentada</option>
                        <option value="A3" {{ ($historiaPrevia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '') === 'A3' ? 'selected' : '' }}>A3: Severamente aumentada</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="tasa_filtracion_glomerular_ckd_epi" class="form-label">Tasa Filtración Glomerular CKD-EPI</label>
                    <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_ckd_epi" 
                           name="tasa_filtracion_glomerular_ckd_epi" min="0" max="200" 
                           value="{{ $historiaPrevia['tasa_filtracion_glomerular_ckd_epi'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="tasa_filtracion_glomerular_gockcroft_gault" class="form-label">Tasa Filtración Glomerular Cockcroft-Gault</label>
                    <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_gockcroft_gault" 
                           name="tasa_filtracion_glomerular_gockcroft_gault" min="0" max="200" 
                           value="{{ $historiaPrevia['tasa_filtracion_glomerular_gockcroft_gault'] ?? '' }}">
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
                            <input class="form-check-input" type="radio" name="hipertension_arterial_personal" 
                                   id="hta_personal_si" value="SI" 
                                   {{ ($historiaPrevia['hipertension_arterial_personal'] ?? 'NO') === 'SI' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hta_personal_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="hipertension_arterial_personal" 
                                   id="hta_personal_no" value="NO" 
                                   {{ ($historiaPrevia['hipertension_arterial_personal'] ?? 'NO') === 'NO' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hta_personal_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="obs_hipertension_arterial_personal" class="form-label">Observaciones HTA Personal</label>
                    <textarea class="form-control" id="obs_hipertension_arterial_personal" 
                              name="obs_hipertension_arterial_personal" rows="2" 
                              {{ ($historiaPrevia['hipertension_arterial_personal'] ?? 'NO') === 'NO' ? 'disabled' : '' }}
                              placeholder="{{ ($historiaPrevia['hipertension_arterial_personal'] ?? 'NO') === 'SI' ? 'Describa los antecedentes de hipertensión arterial...' : '' }}">{{ $historiaPrevia['obs_hipertension_arterial_personal'] ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Diabetes Mellitus Personal</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" 
                                   id="dm_personal_si" value="SI" 
                                   {{ ($historiaPrevia['diabetes_mellitus_personal'] ?? 'NO') === 'SI' ? 'checked' : '' }}>
                            <label class="form-check-label" for="dm_personal_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" 
                                   id="dm_personal_no" value="NO" 
                                   {{ ($historiaPrevia['diabetes_mellitus_personal'] ?? 'NO') === 'NO' ? 'checked' : '' }}>
                            <label class="form-check-label" for="dm_personal_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="obs_diabetes_mellitus_personal" class="form-label">Observaciones DM Personal</label>
                    <textarea class="form-control" id="obs_diabetes_mellitus_personal" 
                              name="obs_diabetes_mellitus_personal" rows="2" 
                              {{ ($historiaPrevia['diabetes_mellitus_personal'] ?? 'NO') === 'NO' ? 'disabled' : '' }}
                              placeholder="{{ ($historiaPrevia['diabetes_mellitus_personal'] ?? 'NO') === 'SI' ? 'Describa los antecedentes de diabetes mellitus...' : '' }}">{{ $historiaPrevia['obs_diabetes_mellitus_personal'] ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
