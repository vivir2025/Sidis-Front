{{-- resources/views/partials/antecedentes_personales.blade.php --}}

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-user-md me-2"></i>
            Antecedentes Personales
        </h5>
    </div>
    <div class="card-body">
        @php
        $antecedentesPersonales = [
            [
                'key' => 'ef_sistema_nervioso',
                'obs_key' => 'descripcion_sistema_nervioso',
                'label' => 'Sistema Nervioso',
                'descripcion' => 'Dolores de cabeza, convulsiones, mareos, parálisis, trastornos mentales'
            ],
            [
                'key' => 'sistema_hemolinfatico',
                'obs_key' => 'descripcion_sistema_hemolinfatico',
                'label' => 'Sistema Hemolinfático',
                'descripcion' => 'Anemia, desórdenes sanguíneos o problemas de coagulación'
            ],
            [
                'key' => 'aparato_digestivo',
                'obs_key' => 'descripcion_aparato_digestivo',
                'label' => 'Aparato Digestivo',
                'descripcion' => 'Úlceras, gastritis, cirrosis, divertículos, colitis, hemorroides'
            ],
            [
                'key' => 'organo_sentido',
                'obs_key' => 'descripcion_organos_sentidos',
                'label' => 'Órganos de los Sentidos',
                'descripcion' => 'Cataratas, pterigios, visión corta, otitis, desviación del tabique, sinusitis, amigdalitis'
            ],
            [
                'key' => 'endocrino_metabolico',
                'obs_key' => 'descripcion_endocrino_metabolico',
                'label' => 'Endocrino-Metabólicos',
                'descripcion' => 'Diabetes, enfermedades de la tiroides, alteración de las grasas, o ácidos úrico sanguíneos'
            ],
            [
                'key' => 'inmunologico',
                'obs_key' => 'descripcion_inmunologico',
                'label' => 'Inmunológicos',
                'descripcion' => 'Lupus, artritis reumatoides'
            ],
            [
                'key' => 'cancer_tumores_radioterapia_quimio',
                'obs_key' => 'descripcion_cancer_tumores_radio_quimioterapia',
                'label' => 'Cáncer, Tumores',
                'descripcion' => 'Radioterapia o quimioterapia'
            ],
            [
                'key' => 'glandula_mamaria',
                'obs_key' => 'descripcion_glandulas_mamarias',
                'label' => 'Glándulas Mamarias',
                'descripcion' => 'Dolores, masas, secreciones'
            ],
            [
                'key' => 'hipertension_diabetes_erc',
                'obs_key' => 'descripcion_hipertension_diabetes_erc',
                'label' => 'Hipertensión, Diabetes, ERC',
                'descripcion' => 'Hipertensión arterial, diabetes mellitus, enfermedad renal crónica'
            ],
            [
                'key' => 'reacciones_alergica',
                'obs_key' => 'descripcion_reacion_alergica',
                'label' => 'Reacciones Alérgicas',
                'descripcion' => 'Alergias a medicamentos, alimentos u otros'
            ],
            [
                'key' => 'cardio_vasculares',
                'obs_key' => 'descripcion_cardio_vasculares',
                'label' => 'Cardio Vasculares',
                'descripcion' => 'Hipertensión, infartos, anginas, soplos, arritmias, enfermedades coronarias'
            ],
            [
                'key' => 'respiratorios',
                'obs_key' => 'descripcion_respiratorios',
                'label' => 'Respiratorios',
                'descripcion' => 'Asma, enfisema, infección laríngea o en bronquios'
            ],
            [
                'key' => 'urinarias',
                'obs_key' => 'descripcion_urinarias',
                'label' => 'Urinarias',
                'descripcion' => 'Insuficiencia renal, cálculos, orina con sangre, infecciones frecuentes, próstatas enfermas'
            ],
            [
                'key' => 'osteoarticulares',
                'obs_key' => 'descripcion_osteoarticulares',
                'label' => 'Osteoarticulares',
                'descripcion' => 'Enfermedades de la columna, dolor de rodilla, deformidades'
            ],
            [
                'key' => 'infecciosos',
                'obs_key' => 'descripcion_infecciosos',
                'label' => 'Infecciosos',
                'descripcion' => 'Hepatitis, tuberculosis, SIDA o VIH(+), enfermedades de transmisión sexual'
            ],
            [
                'key' => 'cirugia_trauma',
                'obs_key' => 'descripcion_cirugias_traumas',
                'label' => 'Cirugías y Traumas',
                'descripcion' => 'Accidentes, cirugías previas'
            ],
            [
                'key' => 'tratamiento_medicacion',
                'obs_key' => 'descripcion_tratamiento_medicacion',
                'label' => 'Tratamientos con Medicación',
                'descripcion' => 'Medicamentos actuales o tratamientos en curso'
            ],
            [
                'key' => 'antecedente_quirurgico',
                'obs_key' => 'descripcion_antecedentes_quirurgicos',
                'label' => 'Antecedentes Quirúrgicos',
                'descripcion' => 'Cirugías previas realizadas'
            ],
            [
                'key' => 'antecedentes_familiares',
                'obs_key' => 'descripcion_antecedentes_familiares',
                'label' => 'Antecedentes Familiares',
                'descripcion' => 'Enfermedades hereditarias o familiares'
            ],
            [
                'key' => 'consumo_tabaco',
                'obs_key' => 'descripcion_consumo_tabaco',
                'label' => 'Consumo de Tabaco',
                'descripcion' => 'Frecuencia y cantidad de consumo'
            ],
            [
                'key' => 'antecedentes_alcohol',
                'obs_key' => 'descripcion_antecedentes_alcohol',
                'label' => 'Antecedentes de Alcohol',
                'descripcion' => 'Frecuencia y cantidad de consumo'
            ],
            [
                'key' => 'sedentarismo',
                'obs_key' => 'descripcion_sedentarismo',
                'label' => 'Sedentarismo',
                'descripcion' => 'Nivel de actividad física'
            ],
            [
                'key' => 'ginecologico',
                'obs_key' => 'descripcion_ginecologicos',
                'label' => 'Ginecológicos',
                'descripcion' => 'Tumores o masa en ovarios, útero, menstruación anormal'
            ],
            [
                'key' => 'citologia_vaginal',
                'obs_key' => 'descripcion_citologia_vaginal',
                'label' => 'Citología Vaginal',
                'descripcion' => 'Patológicas o anormales'
            ]
        ];
        @endphp

        {{-- Antecedentes con SI/NO y Descripción --}}
        @foreach($antecedentesPersonales as $antecedente)
        <div class="row mb-3 border-bottom pb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ $antecedente['label'] }}</label>
                <p class="text-muted small mb-2">{{ $antecedente['descripcion'] }}</p>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-personal" 
                               type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_si" 
                               value="SI">
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input antecedente-personal" 
                               type="radio" 
                               name="{{ $antecedente['key'] }}" 
                               id="{{ $antecedente['key'] }}_no" 
                               value="NO" 
                               checked>
                        <label class="form-check-label" for="{{ $antecedente['key'] }}_no">No</label>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <label for="{{ $antecedente['obs_key'] }}" class="form-label">Descripción</label>
                <textarea class="form-control descripcion-textarea" 
                          id="{{ $antecedente['obs_key'] }}" 
                          name="{{ $antecedente['obs_key'] }}" 
                          rows="2" 
                          placeholder="Descripción detallada..."
                          disabled></textarea>
            </div>
        </div>
        @endforeach

        {{-- Sección Ginecológica --}}
        <div class="card mt-4 bg-light">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Antecedentes Gineco-Obstétricos</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="menarquia" class="form-label">Menarquia (edad)</label>
                        <input type="number" 
                               name="menarquia" 
                               id="menarquia" 
                               class="form-control" 
                               value="0" 
                               min="0" 
                               max="25">
                    </div>
                    <div class="col-md-6">
                        <label for="gestaciones" class="form-label">Gestaciones</label>
                        <input type="number" 
                               name="gestaciones" 
                               id="gestaciones" 
                               class="form-control" 
                               value="0" 
                               min="0">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="parto" class="form-label">Partos</label>
                        <input type="number" 
                               name="parto" 
                               id="parto" 
                               class="form-control" 
                               value="0" 
                               min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="aborto" class="form-label">Abortos</label>
                        <input type="number" 
                               name="aborto" 
                               id="aborto" 
                               class="form-control" 
                               value="0" 
                               min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="cesaria" class="form-label">Cesáreas</label>
                        <input type="number" 
                               name="cesaria" 
                               id="cesaria" 
                               class="form-control" 
                               value="0" 
                               min="0">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Métodos Anticonceptivos</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="metodo_conceptivo" 
                                       id="metodo_conceptivo_si" 
                                       value="SI">
                                <label class="form-check-label" for="metodo_conceptivo_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="metodo_conceptivo" 
                                       id="metodo_conceptivo_no" 
                                       value="NO" 
                                       checked>
                                <label class="form-check-label" for="metodo_conceptivo_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="metodo_conceptivo_cual" class="form-label">¿Cuál método?</label>
                        <input type="text" 
                               name="metodo_conceptivo_cual" 
                               id="metodo_conceptivo_cual" 
                               class="form-control" 
                               value="NINGUNO" 
                               placeholder="Especifique el método..."
                               disabled>
                    </div>
                </div>
            </div>
        </div>

        {{-- Observaciones Generales --}}
        <div class="row mt-4">
            <div class="col-12">
                <label for="antecedente_personal" class="form-label fw-bold">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Observaciones Generales de Antecedentes Personales
                </label>
                <textarea class="form-control" 
                          id="antecedente_personal" 
                          name="antecedente_personal" 
                          rows="4" 
                          placeholder="Observaciones adicionales sobre los antecedentes personales del paciente...">NINGUNO</textarea>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript para manejar la habilitación/deshabilitación de campos --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mapeo de campos principales a sus descripciones
    const fieldMappings = {
        'ef_sistema_nervioso': 'descripcion_sistema_nervioso',
        'sistema_hemolinfatico': 'descripcion_sistema_hemolinfatico',
        'aparato_digestivo': 'descripcion_aparato_digestivo',
        'organo_sentido': 'descripcion_organos_sentidos',
        'endocrino_metabolico': 'descripcion_endocrino_metabolico',
        'inmunologico': 'descripcion_inmunologico',
        'cancer_tumores_radioterapia_quimio': 'descripcion_cancer_tumores_radio_quimioterapia',
        'glandula_mamaria': 'descripcion_glandulas_mamarias',
        'hipertension_diabetes_erc': 'descripcion_hipertension_diabetes_erc',
        'reacciones_alergica': 'descripcion_reacion_alergica',
        'cardio_vasculares': 'descripcion_cardio_vasculares',
        'respiratorios': 'descripcion_respiratorios',
        'urinarias': 'descripcion_urinarias',
        'osteoarticulares': 'descripcion_osteoarticulares',
        'infecciosos': 'descripcion_infecciosos',
        'cirugia_trauma': 'descripcion_cirugias_traumas',
        'tratamiento_medicacion': 'descripcion_tratamiento_medicacion',
        'antecedente_quirurgico': 'descripcion_antecedentes_quirurgicos',
        'antecedentes_familiares': 'descripcion_antecedentes_familiares',
        'consumo_tabaco': 'descripcion_consumo_tabaco',
        'antecedentes_alcohol': 'descripcion_antecedentes_alcohol',
        'sedentarismo': 'descripcion_sedentarismo',
        'ginecologico': 'descripcion_ginecologicos',
        'citologia_vaginal': 'descripcion_citologia_vaginal'
    };

    // Manejar antecedentes personales
    document.querySelectorAll('.antecedente-personal').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const name = this.name;
            const descripcionKey = fieldMappings[name];
            const descripcionTextarea = document.getElementById(descripcionKey);
            
            if (descripcionTextarea) {
                if (this.value === 'SI') {
                    descripcionTextarea.disabled = false;
                    descripcionTextarea.required = true;
                    descripcionTextarea.focus();
                } else {
                    descripcionTextarea.disabled = true;
                    descripcionTextarea.required = false;
                    descripcionTextarea.value = '';
                }
            }
        });
    });

    // Manejar método anticonceptivo
    document.querySelectorAll('input[name="metodo_conceptivo"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const cualInput = document.getElementById('metodo_conceptivo_cual');
            
            if (this.value === 'SI') {
                cualInput.disabled = false;
                cualInput.required = true;
                cualInput.value = '';
                cualInput.focus();
            } else {
                cualInput.disabled = true;
                cualInput.required = false;
                cualInput.value = 'NINGUNO';
            }
        });
    });
});
</script>
@endpush

{{-- Estilos adicionales --}}
@push('styles')
<style>
.border-bottom {
    border-bottom: 1px solid #dee2e6 !important;
}

.antecedente-personal:checked + label,
.form-check-input:checked + label {
    font-weight: 600;
    color: #0d6efd;
}

.descripcion-textarea:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.descripcion-textarea:enabled {
    background-color: #fff;
    border-color: #0d6efd;
}

.small {
    font-size: 0.875rem;
}

.text-muted {
    color: #6c757d !important;
}
</style>
@endpush
