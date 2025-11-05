<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-check me-2"></i>
            Plan de Seguimiento Nutricional
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Diagnóstico Nutricional --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Diagnóstico Nutricional</label>
                <textarea class="form-control" name="diagnostico_nutri" rows="3" 
                    placeholder="Describa el diagnóstico nutricional actual del paciente..."></textarea>
            </div>

            {{-- Análisis Nutricional --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Análisis Nutricional</label>
                <textarea class="form-control" name="analisis_nutricional" rows="4" 
                    placeholder="Análisis de la evolución, adherencia al plan, cambios observados..."></textarea>
            </div>

            {{-- Plan a Seguir --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Plan a Seguir</label>
                <textarea class="form-control" name="plan_seguir_nutri" rows="5" 
                    placeholder="Describa las recomendaciones, ajustes al plan, objetivos para el próximo control..."></textarea>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Nota:</strong> Registre los cambios en el plan nutricional, ajustes en las porciones, 
            nuevas recomendaciones y fecha del próximo control.
        </div>
    </div>
</div>
