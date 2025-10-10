<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-weight me-2"></i>
            Medidas Antropométricas
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" step="0.1" class="form-control" id="peso" name="peso" min="0" max="300">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="talla" class="form-label">Talla (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="talla" name="talla" min="0" max="250">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label for="imc" class="form-label">IMC</label>
                    <input type="number" step="0.01" class="form-control" id="imc" name="imc" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="clasificacion_imc" class="form-label">Clasificación IMC</label>
                    <input type="text" class="form-control" id="clasificacion_imc" name="clasificacion_imc" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="perimetro_abdominal" class="form-label">Perímetro Abdominal (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="perimetro_abdominal" name="perimetro_abdominal" min="0" max="200">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label for="obs_perimetro_abdominal" class="form-label">Observaciones Perímetro Abdominal</label>
                    <textarea class="form-control" id="obs_perimetro_abdominal" name="obs_perimetro_abdominal" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
