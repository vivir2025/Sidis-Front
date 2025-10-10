<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fas fa-user me-2"></i>
            Antecedentes Personales
        </h5>
    </div>
    <div class="card-body">
        @php
        $antecedentesPersonales = [
            ['key' => 'enfermedad_cardiovascular_personal', 'label' => 'Enfermedad Cardiovascular'],
            ['key' => 'arterial_periferica_personal', 'label' => 'Arterial Periférica'],
            ['key' => 'carotidea_personal', 'label' => 'Carotídea'],
            ['key' => 'aneurisma_aorta_peronal', 'label' => 'Aneurisma de Aorta'],
            ['key' => 'coronario_personal', 'label' => 'Síndrome Coronario Agudo/Angina'],
            ['key' => 'artritis_personal', 'label' => 'Artritis'],
            ['key' => 'iam_personal', 'label' => 'Infarto Agudo del Miocardio'],
            ['key' => 'revascul_coronaria_personal', 'label' => 'Revascularización Coronaria'],
            ['key' => 'insuficiencia_cardiaca_personal', 'label' => 'Insuficiencia Cardíaca'],
            ['key' => 'amputacion_pie_diabetico_personal', 'label' => 'Amputación por Pie Diabético'],
            ['key' => 'enfermedad_pulmonar_personal', 'label' => 'Enfermedad Pulmonar'],
            ['key' => 'victima_maltrato_personal', 'label' => 'Víctima de Maltrato'],
            ['key' => 'antecedentes_quirurgicos_personal', 'label' => 'Antecedentes Quirúrgicos'],
            ['key' => 'acontosis_personal', 'label' => 'Acantosis'],
            ['key' => 'otro_personal', 'label' => 'Otros']
        ];
        @endphp

        @foreach($antecedentesPersonales as $antecedente)
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ $antecedente['label'] }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-personal" type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_si" 
                               value="SI">
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-personal" type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_no" 
                               value="NO" checked>
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_no">No</label>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <label for="obs_{{ $antecedente['key'] }}" class="form-label">Observaciones</label>
                <textarea class="form-control obs-personal-textarea" 
                          id="obs_{{ $antecedente['key'] }}" 
                          name="obs_{{ $antecedente['key'] }}" 
                          rows="2" disabled></textarea>
            </div>
        </div>
        @endforeach

        {{-- ✅ CAMPO ESPECIAL: INSULINA REQUIRIENTE --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Insulina Requiriente</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="insulina_requiriente" id="insulina_si" value="SI">
                        <label class="form-check-label" for="insulina_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="insulina_requiriente" id="insulina_no" value="NO" checked>
                        <label class="form-check-label" for="insulina_no">No</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
