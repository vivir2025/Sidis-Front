<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-utensils me-2"></i>
            Evaluación Dietética
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Tolerancia Vía Oral --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Tolerancia Vía Oral</label>
                <textarea class="form-control" name="tolerancia_via_oral" rows="2" 
                    placeholder="Describa la tolerancia a alimentos..."></textarea>
            </div>

            {{-- Percepción del Apetito --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Percepción del Apetito</label>
                <select class="form-select" name="percepcion_apetito">
                    <option value="">Seleccione...</option>
                    <option value="BUENO">Bueno</option>
                    <option value="REGULAR">Regular</option>
                    <option value="MALO">Malo</option>
                    <option value="AUMENTADO">Aumentado</option>
                    <option value="DISMINUIDO">Disminuido</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Apetito</label>
                <input type="text" class="form-control" name="percepcion_apetito_observacion" 
                    placeholder="Detalles adicionales...">
            </div>

            {{-- Alimentos Preferidos --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Alimentos Preferidos</label>
                <textarea class="form-control" name="alimentos_preferidos" rows="3" 
                    placeholder="Liste los alimentos que más consume..."></textarea>
            </div>

            {{-- Alimentos Rechazados --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Alimentos Rechazados</label>
                <textarea class="form-control" name="alimentos_rechazados" rows="3" 
                    placeholder="Liste los alimentos que no consume..."></textarea>
            </div>

            {{-- Suplementos Nutricionales --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Suplementos Nutricionales</label>
                <textarea class="form-control" name="suplemento_nutricionales" rows="2" 
                    placeholder="Ej: Vitamina D, Omega 3, Proteína en polvo..."></textarea>
            </div>

            {{-- Dieta Especial --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">¿Sigue Dieta Especial?</label>
                <select class="form-select" name="dieta_especial" id="dietaEspecial">
                    <option value="">Seleccione...</option>
                    <option value="SI">Sí</option>
                    <option value="NO">No</option>
                </select>
            </div>
            <div class="col-md-6 mb-3" id="dietaEspecialCualDiv" style="display: none;">
                <label class="form-label fw-bold">¿Cuál Dieta?</label>
                <input type="text" class="form-control" name="dieta_especial_cual" 
                    placeholder="Ej: Vegetariana, Cetogénica, Baja en sodio...">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('dietaEspecial')?.addEventListener('change', function() {
        const cualDiv = document.getElementById('dietaEspecialCualDiv');
        cualDiv.style.display = this.value === 'SI' ? 'block' : 'none';
    });
});
</script>
