<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>
            Evaluaciones
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Actitud Postural --}}
            <div class="col-md-6 mb-3">
                <label for="actitud" class="form-label">Actitud Postural</label>
                <select name="actitud" id="actitud" required class="form-select">
                    <option value="NORMAL">Normal</option>
                    <option value="ALTERADA">Alterada</option>
                </select>
            </div>

            {{-- Evaluación de Sensibilidad --}}
            <div class="col-md-6 mb-3">
                <label for="evaluacion_d" class="form-label">Evaluación de Sensibilidad</label>
                <select name="evaluacion_d" id="evaluacion_d" required class="form-select">
                    <option value="SUPERFICIAL">Superficial</option>
                    <option value="PROFUNDA">Profunda</option>
                </select>
            </div>

            {{-- Evaluación de Piel --}}
            <div class="col-md-6 mb-3">
                <label for="evaluacion_p" class="form-label">Evaluación de Piel</label>
                <select name="evaluacion_p" id="evaluacion_p" required class="form-select">
                    <option value="COLOR">Color</option>
                    <option value="ERIMATOSA">Eritematosa</option>
                    <option value="EQUIMOSIS">Equimosis</option>
                </select>
            </div>

            {{-- Estado --}}
            <div class="col-md-6 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" required class="form-select">
                    <option value="SECA">Seca</option>
                    <option value="BRILLANTE">Brillante</option>
                </select>
            </div>

            {{-- Evaluación del Dolor --}}
            <div class="col-md-6 mb-3">
                <label for="evaluacion_dolor" class="form-label">Evaluación del Dolor</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="evaluacion_dolor" name="evaluacion_dolor" rows="3"></textarea>
            </div>

            {{-- Evaluación Osteoarticular --}}
            <div class="col-md-6 mb-3">
                <label for="evaluacion_os" class="form-label">Evaluación Osteoarticular</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="evaluacion_os" name="evaluacion_os" rows="3"></textarea>
            </div>

            {{-- Evaluación Neuromuscular --}}
            <div class="col-md-6 mb-3">
                <label for="evaluacion_neu" class="form-label">Evaluación Neuromuscular</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="evaluacion_neu" name="evaluacion_neu" rows="3"></textarea>
            </div>

            {{-- Enfermedad Concomitante --}}
            <div class="col-md-12 mb-3">
                <label for="comitante" class="form-label">Padece de una Enfermedad Concomitante</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="comitante" name="comitante" rows="3"></textarea>
            </div>
        </div>
    </div>
</div>