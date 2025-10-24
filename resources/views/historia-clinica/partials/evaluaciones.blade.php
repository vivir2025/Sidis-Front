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
                <label for="Actitud" class="form-label">Actitud Postural</label>
                <select name="Actitud" id="Actitud" required class="form-select">
                    <option value="NORMAL">Normal</option>
                    <option value="ALTERADA">Alterada</option>
                </select>
            </div>

            {{-- Evaluación de Sensibilidad --}}
            <div class="col-md-6 mb-3">
                <label for="Evaluaciond" class="form-label">Evaluación de Sensibilidad</label>
                <select name="Evaluaciond" id="Evaluaciond" required class="form-select">
                    <option value="SUPERFICIAL">Superficial</option>
                    <option value="PROFUNDA">Profunda</option>
                </select>
            </div>

            {{-- Evaluación de Piel --}}
            <div class="col-md-6 mb-3">
                <label for="Evaluacionp" class="form-label">Evaluación de Piel</label>
                <select name="Evaluacionp" id="Evaluacionp" required class="form-select">
                    <option value="COLOR">Color</option>
                    <option value="ERIMATOSA">Eritematosa</option>
                    <option value="EQUIMOSIS">Equimosis</option>
                </select>
            </div>

            {{-- Estado --}}
            <div class="col-md-6 mb-3">
                <label for="Estado" class="form-label">Estado</label>
                <select name="Estado" id="Estado" required class="form-select">
                    <option value="SECA">Seca</option>
                    <option value="BRILLANTE">Brillante</option>
                </select>
            </div>

            {{-- Evaluación del Dolor --}}
            <div class="col-md-6 mb-3">
                <label for="Evaluacion_dolor" class="form-label">Evaluación del Dolor</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="Evaluacion_dolor" name="Evaluacion_dolor" rows="3"></textarea>
            </div>

            {{-- Evaluación Osteoarticular --}}
            <div class="col-md-6 mb-3">
                <label for="Evaluacionos" class="form-label">Evaluación Osteoarticular</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="Evaluacionos" name="Evaluacionos" rows="3"></textarea>
            </div>

            {{-- Evaluación Neuromuscular --}}
            <div class="col-md-6 mb-3">
                <label for="Evaluacionneu" class="form-label">Evaluación Neuromuscular</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="Evaluacionneu" name="Evaluacionneu" rows="3"></textarea>
            </div>

           
            {{-- Enfermedad Concomitante --}}
            <div class="col-md-12 mb-3">
                <label for="Comitante" class="form-label">Padece de una Enfermedad Concomitante</label>
                <textarea class="form-control" placeholder="Escribir" 
                          id="Comitante" name="Comitante" rows="3"></textarea>
            </div>
        </div>
    </div>
</div>
