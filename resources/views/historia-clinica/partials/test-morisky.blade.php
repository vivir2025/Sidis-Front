<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-check me-2"></i>
            Test de Morisky (Adherencia al Tratamiento)
        </h5>
    </div>
    <div class="card-body">
        @php
        $preguntasMorisky = [
            ['key' => 'test_morisky_olvida_tomar_medicamentos', 'label' => '¿Olvida alguna vez tomar los medicamentos?'],
            ['key' => 'test_morisky_toma_medicamentos_hora_indicada', 'label' => '¿Toma los medicamentos a la hora indicada?'],
            ['key' => 'test_morisky_cuando_esta_bien_deja_tomar_medicamentos', 'label' => '¿Cuando se encuentra bien, deja de tomar los medicamentos?'],
            ['key' => 'test_morisky_siente_mal_deja_tomarlos', 'label' => '¿Si alguna vez se siente mal, deja de tomarlos?'],
            ['key' => 'test_morisky_valoracio_psicologia', 'label' => '¿Requiere valoración por psicología?']
        ];
        @endphp

        @foreach($preguntasMorisky as $pregunta)
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">{{ $pregunta['label'] }}</label>
            </div>
            <div class="col-md-4">
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input test-morisky-input" type="radio" 
                               name="{{ $pregunta['key'] }}" 
                               id="{{ $pregunta['key'] }}_si" 
                               value="SI">
                        <label class="form-check-label" for="{{ $pregunta['key'] }}_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input test-morisky-input" type="radio" 
                               name="{{ $pregunta['key'] }}" 
                               id="{{ $pregunta['key'] }}_no" 
                               value="NO" checked>
                        <label class="form-check-label" for="{{ $pregunta['key'] }}_no">No</label>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        {{-- ✅ SEPARADOR VISUAL --}}
        <hr class="my-4">

        {{-- ✅ RESULTADO ADHERENCIA - CALCULADO AUTOMÁTICAMENTE --}}
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label"><strong>Resultado: ¿Es adherente al tratamiento?</strong></label>
                <small class="form-text text-muted d-block">
                    Se calcula automáticamente basado en las respuestas del test
                </small>
            </div>
            <div class="col-md-4">
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="adherente" id="adherente_si" value="SI" readonly>
                        <label class="form-check-label" for="adherente_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="adherente" id="adherente_no" value="NO" checked readonly>
                        <label class="form-check-label" for="adherente_no">No</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ EXPLICACIÓN DEL RESULTADO --}}
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info" id="explicacion_adherencia" style="display: none;">
                    <small id="texto_explicacion"></small>
                </div>
            </div>
        </div>
    </div>
</div>
