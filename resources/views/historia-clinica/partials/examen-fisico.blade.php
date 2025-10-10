<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>
            Examen Físico
        </h5>
    </div>
    <div class="card-body">
        @php
        $examenFisico = [
            ['key' => 'ef_cabeza', 'label' => 'Cabeza'],
            ['key' => 'ef_agudeza_visual', 'label' => 'Agudeza Visual'],
            ['key' => 'ef_cuello', 'label' => 'Cuello'],
            ['key' => 'ef_torax', 'label' => 'Tórax'],
            ['key' => 'ef_mamas', 'label' => 'Mamas'],
            ['key' => 'ef_abdomen', 'label' => 'Abdomen'],
            ['key' => 'ef_genito_urinario', 'label' => 'Genito Urinario'],
            ['key' => 'ef_extremidades', 'label' => 'Extremidades'],
            ['key' => 'ef_piel_anexos_pulsos', 'label' => 'Piel, Anexos y Pulsos'],
            ['key' => 'ef_sistema_nervioso', 'label' => 'Sistema Nervioso'],
            ['key' => 'ef_orientacion', 'label' => 'Orientación']
        ];
        @endphp

        @foreach($examenFisico as $examen)
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="{{ $examen['key'] }}" class="form-label">{{ $examen['label'] }}</label>
                    <select class="form-select" id="{{ $examen['key'] }}" name="{{ $examen['key'] }}">
                        <option value="">Seleccione...</option>
                        <option value="NORMAL">Normal</option>
                        <option value="ANORMAL">Anormal</option>
                        <option value="NO_EVALUADO">No Evaluado</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="{{ $examen['key'] }}_obs" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="{{ $examen['key'] }}_obs" 
                              name="ef_obs_{{ str_replace('ef_', '', $examen['key']) }}" rows="2"></textarea>
                </div>
            </div>
        </div>
        @endforeach

        {{-- ✅ HALLAZGOS POSITIVOS --}}
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label for="ef_hallazco_positivo_examen_fisico" class="form-label">Hallazgos Positivos del Examen Físico</label>
                    <textarea class="form-control" id="ef_hallazco_positivo_examen_fisico" 
                              name="ef_hallazco_positivo_examen_fisico" rows="4"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
