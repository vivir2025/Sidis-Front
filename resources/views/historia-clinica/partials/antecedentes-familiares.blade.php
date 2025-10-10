<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Antecedentes Familiares
        </h5>
    </div>
    <div class="card-body">
        @php
        $antecedentesFamiliares = [
            ['key' => 'hipertension_arterial', 'label' => 'Hipertensión Arterial'],
            ['key' => 'diabetes_mellitus', 'label' => 'Diabetes Mellitus'],
            ['key' => 'artritis', 'label' => 'Artritis'],
            ['key' => 'enfermedad_cardiovascular', 'label' => 'Enfermedad Cardiovascular'],
            ['key' => 'antecedentes_metabolico', 'label' => 'Antecedentes Metabólicos'],
            ['key' => 'cancer', 'label' => 'Cáncer'],
            ['key' => 'lucemia', 'label' => 'Leucemia'],
            ['key' => 'vih', 'label' => 'VIH'],
            ['key' => 'otro', 'label' => 'Otros']
        ];
        @endphp

        @foreach($antecedentesFamiliares as $antecedente)
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ $antecedente['label'] }}</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-familiar" type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_si" 
                               value="SI">
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-familiar" type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_no" 
                               value="NO" checked>
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_no">No</label>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <label for="parentesco_{{ $antecedente['key'] }}" class="form-label">Parentesco y Descripción</label>
                <textarea class="form-control parentesco-textarea" 
                          id="parentesco_{{ $antecedente['key'] }}" 
                          name="parentesco_{{ $antecedente['key'] }}" 
                          rows="2" disabled></textarea>
            </div>
        </div>
        @endforeach
    </div>
</div>
