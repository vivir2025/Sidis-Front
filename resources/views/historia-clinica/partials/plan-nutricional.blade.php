<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Plan Nutricional
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Peso Ideal --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Peso Ideal (kg)</label>
                <input type="number" class="form-control" name="peso_ideal" 
                    step="0.1" min="0" max="300" placeholder="Ej: 65.5">
            </div>

            {{-- Meta en Meses --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Meta en Meses</label>
                <input type="number" class="form-control" name="meta_meses" 
                    min="0" max="24" placeholder="Ej: 3">
            </div>

            {{-- Interpretación --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Interpretación</label>
                <select class="form-select" name="interpretacion">
                    <option value="">Seleccione...</option>
                    <option value="BAJO_PESO">Bajo Peso</option>
                    <option value="PESO_NORMAL">Peso Normal</option>
                    <option value="SOBREPESO">Sobrepeso</option>
                    <option value="OBESIDAD_I">Obesidad Grado I</option>
                    <option value="OBESIDAD_II">Obesidad Grado II</option>
                    <option value="OBESIDAD_III">Obesidad Grado III</option>
                </select>
            </div>

            {{-- Análisis Nutricional --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Análisis Nutricional</label>
                <textarea class="form-control" name="analisis_nutricional" rows="4" 
                    placeholder="Describa el análisis nutricional completo del paciente..."></textarea>
            </div>

            {{-- Plan a Seguir --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Plan a Seguir</label>
                <textarea class="form-control" name="plan_seguir" rows="5" 
                    placeholder="Describa el plan nutricional detallado, recomendaciones, objetivos..."></textarea>
            </div>
        </div>
    </div>
</div>
