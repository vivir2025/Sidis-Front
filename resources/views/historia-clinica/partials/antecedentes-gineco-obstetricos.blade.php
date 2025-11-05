<div class="card shadow-sm mb-4">
    <div class="card-header bg-pink text-white">
        <h5 class="mb-0">
            <i class="fas fa-venus me-2"></i>
            Antecedentes Gineco-Obstétricos
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Método Conceptivo --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Método Anticonceptivo</label>
                <select class="form-select" name="metodo_conceptivo" id="metodoConceptivo">
                    <option value="">Seleccione...</option>
                    <option value="NINGUNO">Ninguno</option>
                    <option value="ORAL">Anticonceptivos Orales</option>
                    <option value="INYECTABLE">Inyectable</option>
                    <option value="DIU">DIU</option>
                    <option value="IMPLANTE">Implante Subdérmico</option>
                    <option value="PRESERVATIVO">Preservativo</option>
                    <option value="NATURAL">Método Natural</option>
                    <option value="QUIRURGICO">Quirúrgico (Ligadura/Vasectomía)</option>
                    <option value="OTRO">Otro</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">¿Cuál?</label>
                <input type="text" class="form-control" name="metodo_conceptivo_cual" 
                    id="metodoConceptivoCual" placeholder="Especifique...">
            </div>

            {{-- Embarazo Actual --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">¿Embarazo Actual?</label>
                <select class="form-select" name="embarazo_actual" id="embarazoActual">
                    <option value="">Seleccione...</option>
                    <option value="SI">Sí</option>
                    <option value="NO">No</option>
                </select>
            </div>
            <div class="col-md-6 mb-3" id="semanasGestacionDiv" style="display: none;">
                <label class="form-label fw-bold">Semanas de Gestación</label>
                <input type="number" class="form-control" name="semanas_gestacion" 
                    min="0" max="42" placeholder="Ej: 12">
            </div>

            {{-- Climaterio --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Climaterio/Menopausia</label>
                <input type="text" class="form-control" name="climatero" 
                    placeholder="Ej: Premenopausia, Menopausia, Postmenopausia">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar campo "¿Cuál?" si selecciona OTRO
    document.getElementById('metodoConceptivo')?.addEventListener('change', function() {
        const cualDiv = document.getElementById('metodoConceptivoCual');
        cualDiv.style.display = this.value === 'OTRO' ? 'block' : 'none';
    });

    // Mostrar semanas de gestación si está embarazada
    document.getElementById('embarazoActual')?.addEventListener('change', function() {
        const semanasDiv = document.getElementById('semanasGestacionDiv');
        semanasDiv.style.display = this.value === 'SI' ? 'block' : 'none';
    });
});
</script>
