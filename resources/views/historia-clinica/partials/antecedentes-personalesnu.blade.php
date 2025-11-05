<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-notes-medical me-2"></i>
            Antecedentes Personales
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Enfermedad Diagnóstica --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Enfermedad Diagnóstica</label>
                <textarea class="form-control" name="enfermedad_diagnostica" rows="3" 
                    placeholder="Describa la enfermedad diagnóstica..."></textarea>
            </div>

            {{-- Hábito Intestinal --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Hábito Intestinal</label>
                <textarea class="form-control" name="habito_intestinal" rows="2" 
                    placeholder="Describa el hábito intestinal..."></textarea>
            </div>

            {{-- Antecedentes Quirúrgicos --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Antecedentes Quirúrgicos</label>
                <input type="text" class="form-control" name="quirurgicos" 
                    placeholder="Ej: Apendicectomía">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Quirúrgicos</label>
                <input type="text" class="form-control" name="quirurgicos_observaciones" 
                    placeholder="Detalles adicionales...">
            </div>

            {{-- Antecedentes Alérgicos --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Antecedentes Alérgicos</label>
                <input type="text" class="form-control" name="alergicos" 
                    placeholder="Ej: Penicilina">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Alérgicos</label>
                <input type="text" class="form-control" name="alergicos_observaciones" 
                    placeholder="Detalles adicionales...">
            </div>

            {{-- Antecedentes Familiares --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Antecedentes Familiares</label>
                <input type="text" class="form-control" name="familiares" 
                    placeholder="Ej: Diabetes, HTA">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Familiares</label>
                <input type="text" class="form-control" name="familiares_observaciones" 
                    placeholder="Detalles adicionales...">
            </div>

            {{-- PSA (Psicosociales) --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Antecedentes Psicosociales (PSA)</label>
                <input type="text" class="form-control" name="psa" 
                    placeholder="Ej: Estrés, ansiedad">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones PSA</label>
                <input type="text" class="form-control" name="psa_observaciones" 
                    placeholder="Detalles adicionales...">
            </div>

            {{-- Farmacológicos --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Antecedentes Farmacológicos</label>
                <input type="text" class="form-control" name="farmacologicos" 
                    placeholder="Medicamentos actuales">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Farmacológicos</label>
                <input type="text" class="form-control" name="farmacologicos_observaciones" 
                    placeholder="Dosis, frecuencia...">
            </div>

            {{-- Sueño --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Patrón de Sueño</label>
                <input type="text" class="form-control" name="sueno" 
                    placeholder="Ej: 6-8 horas">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Sueño</label>
                <input type="text" class="form-control" name="sueno_observaciones" 
                    placeholder="Calidad del sueño...">
            </div>

            {{-- ✅ TABAQUISMO - CON AMBOS CAMPOS --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Tabaquismo</label>
                <input type="text" class="form-control" name="tabaquismo" 
                    placeholder="Ej: Fumador activo, Ex fumador">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Tabaquismo</label>
                <input type="text" class="form-control" name="tabaquismo_observaciones" 
                    placeholder="Frecuencia, cantidad, años...">
            </div>

            {{-- Ejercicio --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Actividad Física</label>
                <input type="text" class="form-control" name="ejercicio" 
                    placeholder="Ej: Caminata 30 min/día">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones Ejercicio</label>
                <input type="text" class="form-control" name="ejercicio_observaciones" 
                    placeholder="Frecuencia, intensidad...">
            </div>
        </div>
    </div>
</div>
