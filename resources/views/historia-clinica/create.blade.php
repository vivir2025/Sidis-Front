{{-- resources/views/historia-clinica/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nueva Historia Clínica Cardiovascular')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        Historia Clínica Cardiovascular
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">Información del Paciente</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>{{ $cita['paciente']['nombre_completo'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Documento:</strong></td>
                                    <td>{{ $cita['paciente']['documento'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Nacimiento:</strong></td>
                                    <td>{{ $cita['paciente']['fecha_nacimiento'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sexo:</strong></td>
                                    <td>{{ $cita['paciente']['sexo'] === 'M' ? 'Masculino' : 'Femenino' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary">Información de la Cita</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td>{{ $cita['fecha'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Hora:</strong></td>
                                    <td>{{ $cita['hora'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Consultorio:</strong></td>
                                    <td>{{ $cita['agenda']['consultorio'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Profesional:</strong></td>
                                    <td>{{ $usuario['nombre_completo'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        
        {{-- ✅ SECCIÓN: DATOS BÁSICOS --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard me-2"></i>
                    Datos Básicos de la Consulta
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="finalidad" class="form-label">Finalidad de la Consulta</label>
                            <select class="form-select" id="finalidad" name="finalidad">
                                <option value="">Seleccione...</option>
                                <option value="ATENCION_PARTO">Atención del Parto</option>
                                <option value="ATENCION_RECIEN_NACIDO">Atención del Recién Nacido</option>
                                <option value="ATENCION_PLANIFICACION_FAMILIAR">Atención en Planificación Familiar</option>
                                <option value="DETECCION_ALTERACIONES_CRECIMIENTO">Detección de Alteraciones del Crecimiento y Desarrollo del Menor de 18 años</option>
                                <option value="DETECCION_ALTERACIONES_DESARROLLO_JOVEN">Detección de Alteraciones del Desarrollo del Joven de 18 a 29 años</option>
                                <option value="DETECCION_ALTERACIONES_EMBARAZO">Detección de Alteraciones del Embarazo</option>
                                <option value="DETECCION_ALTERACIONES_ADULTO">Detección de Alteraciones del Adulto</option>
                                <option value="DETECCION_ALTERACIONES_AGUDEZA_VISUAL">Detección de Alteraciones de la Agudeza Visual</option>
                                <option value="DETECCION_ENFERMEDAD_PROFESIONAL">Detección de Enfermedad Profesional</option>
                                <option value="NO_APLICA">No Aplica</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="causa_externa" class="form-label">Causa Externa</label>
                            <select class="form-select" id="causa_externa" name="causa_externa">
                                <option value="">Seleccione...</option>
                                <option value="ACCIDENTE_TRABAJO">Accidente de Trabajo</option>
                                <option value="ACCIDENTE_TRANSITO">Accidente de Tránsito</option>
                                <option value="ACCIDENTE_RABICO">Accidente Rábico</option>
                                <option value="ACCIDENTE_OFIDICO">Accidente Ofídico</option>
                                <option value="OTRO_TIPO_ACCIDENTE">Otro Tipo de Accidente</option>
                                <option value="EVENTO_CATASTROFICO">Evento Catastrófico</option>
                                <option value="LESION_AUTOINFLIGIDA">Lesión Autoinfligida</option>
                                <option value="LESION_INFLIGIDA_TERCERO">Lesión Infligida por Tercero</option>
                                <option value="ENFERMEDAD_GENERAL">Enfermedad General</option>
                                <option value="ENFERMEDAD_PROFESIONAL">Enfermedad Profesional</option>
                                <option value="NO_APLICA">No Aplica</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: ACUDIENTE --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-friends me-2"></i>
                    Información del Acudiente
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="acompanante" class="form-label">Nombre del Acompañante</label>
                            <input type="text" class="form-control" id="acompanante" name="acompanante">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="parentesco" class="form-label">Parentesco</label>
                            <input type="text" class="form-control" id="parentesco" name="parentesco">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="telefono_acudiente" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono_acudiente" name="telefono_acudiente">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: HISTORIA CLÍNICA BÁSICA --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Historia Clínica
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de Consulta <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="enfermedad_actual" class="form-label">Enfermedad Actual <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="enfermedad_actual" name="enfermedad_actual" rows="4" required></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: DISCAPACIDADES --}}
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-wheelchair me-2"></i>
                    Discapacidades
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Física</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_fisica" id="disc_fisica_si" value="SI">
                                    <label class="form-check-label" for="disc_fisica_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_fisica" id="disc_fisica_no" value="NO" checked>
                                    <label class="form-check-label" for="disc_fisica_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Visual</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_visual" id="disc_visual_si" value="SI">
                                    <label class="form-check-label" for="disc_visual_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_visual" id="disc_visual_no" value="NO" checked>
                                    <label class="form-check-label" for="disc_visual_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Mental</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_mental" id="disc_mental_si" value="SI">
                                    <label class="form-check-label" for="disc_mental_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_mental" id="disc_mental_no" value="NO" checked>
                                    <label class="form-check-label" for="disc_mental_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Auditiva</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_auditiva" id="disc_auditiva_si" value="SI">
                                    <label class="form-check-label" for="disc_auditiva_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_auditiva" id="disc_auditiva_no" value="NO" checked>
                                    <label class="form-check-label" for="disc_auditiva_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Intelectual</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_intelectual" id="disc_intelectual_si" value="SI">
                                    <label class="form-check-label" for="disc_intelectual_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="discapacidad_intelectual" id="disc_intelectual_no" value="NO" checked>
                                    <label class="form-check-label" for="disc_intelectual_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: DROGODEPENDENCIA --}}
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-pills me-2"></i>
                    Drogodependencia
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">¿Es drogodependiente?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="drogodependiente" id="drogo_si" value="SI">
                                    <label class="form-check-label" for="drogo_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="drogodependiente" id="drogo_no" value="NO" checked>
                                    <label class="form-check-label" for="drogo_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="drogodependiente_cual" class="form-label">¿Cuál droga?</label>
                            <input type="text" class="form-control" id="drogodependiente_cual" name="drogodependiente_cual" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: MEDIDAS ANTROPOMÉTRICAS --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-weight me-2"></i>
                    Medidas Antropométricas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="peso" class="form-label">Peso (kg)</label>
                            <input type="number" step="0.1" class="form-control" id="peso" name="peso" min="0" max="300">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="talla" class="form-label">Talla (cm)</label>
                            <input type="number" step="0.1" class="form-control" id="talla" name="talla" min="0" max="250">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="imc" class="form-label">IMC</label>
                            <input type="number" step="0.01" class="form-control" id="imc" name="imc" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="clasificacion_imc" class="form-label">Clasificación IMC</label>
                            <input type="text" class="form-control" id="clasificacion_imc" name="clasificacion_imc" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="perimetro_abdominal" class="form-label">Perímetro Abdominal (cm)</label>
                            <input type="number" step="0.1" class="form-control" id="perimetro_abdominal" name="perimetro_abdominal" min="0" max="200">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="obs_perimetro_abdominal" class="form-label">Observaciones Perímetro Abdominal</label>
                            <textarea class="form-control" id="obs_perimetro_abdominal" name="obs_perimetro_abdominal" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: ANTECEDENTES FAMILIARES --}}
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

        {{-- ✅ SECCIÓN: ANTECEDENTES PERSONALES --}}
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

     {{-- ✅ SECCIÓN: TEST MORISKY - CORREGIDO --}}
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
                {{-- ✅ CAMBIO: quitar disabled y usar readonly --}}
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

        {{-- ✅ SECCIÓN: OTROS TRATAMIENTOS --}}
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-leaf me-2"></i>
                    Otros Tratamientos
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">¿Recibe tratamiento alternativo?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_tratamiento_alternativo" id="trat_alt_si" value="SI">
                                    <label class="form-check-label" for="trat_alt_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_tratamiento_alternativo" id="trat_alt_no" value="NO" checked>
                                    <label class="form-check-label" for="trat_alt_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">¿Recibe tratamiento con plantas medicinales?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_tratamiento_plantas_medicinales" id="plantas_si" value="SI">
                                    <label class="form-check-label" for="plantas_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_tratamiento_plantas_medicinales" id="plantas_no" value="NO" checked>
                                    <label class="form-check-label" for="plantas_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">¿Recibe ritual de medicina tradicional?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_ritual_medicina_tradicional" id="ritual_si" value="SI">
                                    <label class="form-check-label" for="ritual_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="recibe_ritual_medicina_tradicional" id="ritual_no" value="NO" checked>
                                    <label class="form-check-label" for="ritual_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: REVISIÓN POR SISTEMAS --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>
                    Revisión por Sistemas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="general" class="form-label">General</label>
                            <textarea class="form-control" id="general" name="general" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cabeza" class="form-label">Cabeza</label>
                            <textarea class="form-control" id="cabeza" name="cabeza" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="respiratorio" class="form-label">Respiratorio</label>
                            <textarea class="form-control" id="respiratorio" name="respiratorio" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cardiovascular" class="form-label">Cardiovascular</label>
                            <textarea class="form-control" id="cardiovascular" name="cardiovascular" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gastrointestinal" class="form-label">Gastrointestinal</label>
                            <textarea class="form-control" id="gastrointestinal" name="gastrointestinal" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="osteoatromuscular" class="form-label">Osteomuscular</label>
                            <textarea class="form-control" id="osteoatromuscular" name="osteoatromuscular" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="snc" class="form-label">Sistema Nervioso Central</label>
                            <textarea class="form-control" id="snc" name="snc" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: SIGNOS VITALES --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-heartbeat me-2"></i>
                    Signos Vitales
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="ef_pa_sistolica_sentado_pie" class="form-label">Presión Sistólica (mmHg)</label>
                            <input type="number" class="form-control" id="ef_pa_sistolica_sentado_pie" 
                                   name="ef_pa_sistolica_sentado_pie" min="50" max="300">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="ef_pa_distolica_sentado_pie" class="form-label">Presión Diastólica (mmHg)</label>
                            <input type="number" class="form-control" id="ef_pa_distolica_sentado_pie" 
                                   name="ef_pa_distolica_sentado_pie" min="30" max="200">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="ef_frecuencia_fisica" class="form-label">Frecuencia Cardíaca (lpm)</label>
                            <input type="number" class="form-control" id="ef_frecuencia_fisica" 
                                   name="ef_frecuencia_fisica" min="30" max="200">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="ef_frecuencia_respiratoria" class="form-label">Frecuencia Respiratoria (rpm)</label>
                            <input type="number" class="form-control" id="ef_frecuencia_respiratoria" 
                                   name="ef_frecuencia_respiratoria" min="8" max="50">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: EXAMEN FÍSICO --}}
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

        {{-- ✅ SECCIÓN: FACTORES DE RIESGO --}}
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Factores de Riesgo
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="numero_frutas_diarias" class="form-label">Número de frutas diarias</label>
                            <input type="number" class="form-control" id="numero_frutas_diarias" 
                                   name="numero_frutas_diarias" min="0" max="20">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Elevado consumo de grasa saturada</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="elevado_consumo_grasa_saturada" id="grasa_si" value="SI">
                                    <label class="form-check-label" for="grasa_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="elevado_consumo_grasa_saturada" id="grasa_no" value="NO" checked>
                                    <label class="form-check-label" for="grasa_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Adiciona sal después de preparar alimentos</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="adiciona_sal_despues_preparar_alimentos" id="sal_si" value="SI">
                                    <label class="form-check-label" for="sal_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="adiciona_sal_despues_preparar_alimentos" id="sal_no" value="NO" checked>
                                    <label class="form-check-label" for="sal_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Dislipidemia</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dislipidemia" id="dislipidemia_si" value="SI">
                                    <label class="form-check-label" for="dislipidemia_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="dislipidemia" id="dislipidemia_no" value="NO" checked>
                                    <label class="form-check-label" for="dislipidemia_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Condición clínica asociada</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="condicion_clinica_asociada" id="condicion_si" value="SI">
                                    <label class="form-check-label" for="condicion_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="condicion_clinica_asociada" id="condicion_no" value="NO" checked>
                                    <label class="form-check-label" for="condicion_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Lesión de órgano blanco</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="lesion_organo_blanco" id="lesion_si" value="SI">
                                    <label class="form-check-label" for="lesion_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="lesion_organo_blanco" id="lesion_no" value="NO" checked>
                                    <label class="form-check-label" for="lesion_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="descripcion_lesion_organo_blanco" class="form-label">Descripción lesión órgano blanco</label>
                            <textarea class="form-control" id="descripcion_lesion_organo_blanco" 
                                      name="descripcion_lesion_organo_blanco" rows="2" disabled></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: EXÁMENES --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-flask me-2"></i>
                    Exámenes
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fex_es" class="form-label">Fecha Electrocardiograma</label>
                            <input type="date" class="form-control" id="fex_es" name="fex_es">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="hcElectrocardiograma" class="form-label">Resultado Electrocardiograma</label>
                            <textarea class="form-control" id="hcElectrocardiograma" name="hcElectrocardiograma" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fex_es1" class="form-label">Fecha Ecocardiograma</label>
                            <input type="date" class="form-control" id="fex_es1" name="fex_es1">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="hcEcocardiograma" class="form-label">Resultado Ecocardiograma</label>
                            <textarea class="form-control" id="hcEcocardiograma" name="hcEcocardiograma" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fex_es2" class="form-label">Fecha Ecografía Renal</label>
                            <input type="date" class="form-control" id="fex_es2" name="fex_es2">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="hcEcografiaRenal" class="form-label">Resultado Ecografía Renal</label>
                            <textarea class="form-control" id="hcEcografiaRenal" name="hcEcografiaRenal" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: CLASIFICACIONES --}}
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tags me-2"></i>
                    Clasificaciones
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ClasificacionEstadoMetabolico" class="form-label">Clasificación Estado Metabólico</label>
                            <select class="form-select" id="ClasificacionEstadoMetabolico" name="ClasificacionEstadoMetabolico">
                                <option value="">Seleccione...</option>
                                <option value="NORMAL">Normal</option>
                                <option value="PREDIABETES">Prediabetes</option>
                                <option value="DIABETES_TIPO_1">Diabetes Tipo 1</option>
                                <option value="DIABETES_TIPO_2">Diabetes Tipo 2</option>
                                <option value="DIABETES_GESTACIONAL">Diabetes Gestacional</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_hta" class="form-label">Clasificación HTA</label>
                            <select class="form-select" id="clasificacion_hta" name="clasificacion_hta">
                                <option value="">Seleccione...</option>
                                <option value="NORMAL">Normal</option>
                                <option value="ELEVADA">Elevada</option>
                                <option value="ESTADIO_1">Estadio 1</option>
                                <option value="ESTADIO_2">Estadio 2</option>
                                <option value="CRISIS_HIPERTENSIVA">Crisis Hipertensiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_dm" class="form-label">Clasificación DM</label>
                            <select class="form-select" id="clasificacion_dm" name="clasificacion_dm">
                                <option value="">Seleccione...</option>
                                <option value="TIPO_1">Tipo 1</option>
                                <option value="TIPO_2">Tipo 2</option>
                                <option value="GESTACIONAL">Gestacional</option>
                                <option value="OTROS_TIPOS">Otros Tipos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_rcv" class="form-label">Clasificación RCV</label>
                            <select class="form-select" id="clasificacion_rcv" name="clasificacion_rcv">
                                <option value="">Seleccione...</option>
                                <option value="BAJO">Bajo</option>
                                <option value="MODERADO">Moderado</option>
                                <option value="ALTO">Alto</option>
                                <option value="MUY_ALTO">Muy Alto</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_erc_estado" class="form-label">Clasificación ERC Estado</label>
                            <select class="form-select" id="clasificacion_erc_estado" name="clasificacion_erc_estado">
                                <option value="">Seleccione...</option>
                                <option value="ESTADIO_1">Estadio 1</option>
                                <option value="ESTADIO_2">Estadio 2</option>
                                <option value="ESTADIO_3A">Estadio 3A</option>
                                <option value="ESTADIO_3B">Estadio 3B</option>
                                <option value="ESTADIO_4">Estadio 4</option>
                                <option value="ESTADIO_5">Estadio 5</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_erc_categoria_ambulatoria_persistente" class="form-label">Categoría Ambulatoria Persistente</label>
                            <select class="form-select" id="clasificacion_erc_categoria_ambulatoria_persistente" name="clasificacion_erc_categoria_ambulatoria_persistente">
                                <option value="">Seleccione...</option>
                                <option value="A1">A1: Normal a levemente aumentada</option>
                                <option value="A2">A2: Moderadamente aumentada</option>
                                <option value="A3">A3: Severamente aumentada</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tasa_filtracion_glomerular_ckd_epi" class="form-label">Tasa Filtración Glomerular CKD-EPI</label>
                            <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_ckd_epi" 
                                   name="tasa_filtracion_glomerular_ckd_epi" min="0" max="200">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tasa_filtracion_glomerular_gockcroft_gault" class="form-label">Tasa Filtración Glomerular Cockcroft-Gault</label>
                            <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_gockcroft_gault" 
                                   name="tasa_filtracion_glomerular_gockcroft_gault" min="0" max="200">
                        </div>
                    </div>
                </div>

                {{-- ✅ ANTECEDENTES PERSONALES ESPECÍFICOS --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Hipertensión Arterial Personal</label>
                            <div>
                                <div class="form-check form-check-inline">
                                                                       <input class="form-check-input" type="radio" name="hipertension_arterial_personal" id="hta_personal_si" value="SI">
                                    <label class="form-check-label" for="hta_personal_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="hipertension_arterial_personal" id="hta_personal_no" value="NO" checked>
                                    <label class="form-check-label" for="hta_personal_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="obs_hipertension_arterial_personal" class="form-label">Observaciones HTA Personal</label>
                            <textarea class="form-control" id="obs_hipertension_arterial_personal" 
                                      name="obs_hipertension_arterial_personal" rows="2" disabled></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Diabetes Mellitus Personal</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" id="dm_personal_si" value="SI">
                                    <label class="form-check-label" for="dm_personal_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="diabetes_mellitus_personal" id="dm_personal_no" value="NO" checked>
                                    <label class="form-check-label" for="dm_personal_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="obs_diabetes_mellitus_personal" class="form-label">Observaciones DM Personal</label>
                            <textarea class="form-control" id="obs_diabetes_mellitus_personal" 
                                      name="obs_diabetes_mellitus_personal" rows="2" disabled></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: EDUCACIÓN --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Educación en Salud
                </h5>
            </div>
            <div class="card-body">
                @php
                $educacionItems = [
                    ['key' => 'alimentacion', 'label' => 'Alimentación'],
                    ['key' => 'disminucion_consumo_sal_azucar', 'label' => 'Disminución consumo de sal y azúcar'],
                    ['key' => 'fomento_actividad_fisica', 'label' => 'Fomento de actividad física'],
                    ['key' => 'importancia_adherencia_tratamiento', 'label' => 'Importancia adherencia al tratamiento'],
                    ['key' => 'consumo_frutas_verduras', 'label' => 'Consumo de frutas y verduras'],
                    ['key' => 'manejo_estres', 'label' => 'Manejo del estrés'],
                    ['key' => 'disminucion_consumo_cigarrillo', 'label' => 'Disminución consumo de cigarrillo'],
                    ['key' => 'disminucion_peso', 'label' => 'Disminución de peso']
                ];
                @endphp

                <div class="row">
                    @foreach($educacionItems as $index => $item)
                        @if($index % 2 == 0 && $index > 0)
                </div>
                <div class="row">
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ $item['label'] }}</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="{{ $item['key'] }}" 
                                               id="{{ $item['key'] }}_si" value="SI">
                                        <label class="form-check-label" for="{{ $item['key'] }}_si">Sí</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="{{ $item['key'] }}" 
                                               id="{{ $item['key'] }}_no" value="NO" checked>
                                        <label class="form-check-label" for="{{ $item['key'] }}_no">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-stethoscope me-2"></i>
                    Diagnóstico Principal <span class="text-warning">*</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="buscar_diagnostico" class="form-label">Buscar Diagnóstico <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buscar_diagnostico" 
                                   placeholder="Escriba código o nombre del diagnóstico..." required>
                            <div id="diagnosticos_resultados" class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                        </div>
                        <input type="hidden" id="idDiagnostico" name="idDiagnostico" required>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo_diagnostico" class="form-label">Tipo de Diagnóstico <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_diagnostico" name="tipo_diagnostico" required>
                                <option value="">Seleccione...</option>
                                <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                                <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                                <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info" id="diagnostico_seleccionado" style="display: none;">
                            <strong>Diagnóstico Seleccionado:</strong>
                            <span id="diagnostico_info"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES --}}
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Diagnósticos Adicionales
                </h5>
                <button type="button" class="btn btn-dark btn-sm" id="agregar_diagnostico_adicional">
                    <i class="fas fa-plus me-1"></i>Agregar Diagnóstico
                </button>
            </div>
            <div class="card-body">
                <div id="diagnosticos_adicionales_container">
                    <!-- Los diagnósticos adicionales se agregarán aquí dinámicamente -->
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: MEDICAMENTOS --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-pills me-2"></i>
                    Medicamentos
                </h5>
                <button type="button" class="btn btn-light btn-sm" id="agregar_medicamento">
                    <i class="fas fa-plus me-1"></i>Agregar Medicamento
                </button>
            </div>
            <div class="card-body">
                <div id="medicamentos_container">
                    <!-- Los medicamentos se agregarán aquí dinámicamente -->
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: REMISIONES --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-share me-2"></i>
                    Remisiones
                </h5>
                <button type="button" class="btn btn-light btn-sm" id="agregar_remision">
                    <i class="fas fa-plus me-1"></i>Agregar Remisión
                </button>
            </div>
            <div class="card-body">
                <div id="remisiones_container">
                    <!-- Las remisiones se agregarán aquí dinámicamente -->
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: CUPS --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Procedimientos CUPS
                </h5>
                <button type="button" class="btn btn-light btn-sm" id="agregar_cups">
                    <i class="fas fa-plus me-1"></i>Agregar CUPS
                </button>
            </div>
            <div class="card-body">
                <div id="cups_container">
                    <!-- Los CUPS se agregarán aquí dinámicamente -->
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: OBSERVACIONES GENERALES --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-comment-medical me-2"></i>
                    Observaciones Generales
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="observaciones_generales" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones_generales" name="observaciones_generales" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ BOTONES DE ACCIÓN --}}
        <div class="card">
            <div class="card-body text-center">
                <button type="submit" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-save me-2"></i>
                    Guardar Historia Clínica
                </button>
                <a href="{{ route('cronograma.index') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ✅ TEMPLATES PARA ELEMENTOS DINÁMICOS --}}
{{-- TEMPLATE DIAGNÓSTICO ADICIONAL - CORREGIDO --}}
<template id="diagnostico_adicional_template">
    <div class="diagnostico-adicional-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Diagnóstico</label>
                    <input type="text" class="form-control buscar-diagnostico-adicional" placeholder="Escriba código o nombre del diagnóstico...">
                    <div class="diagnosticos-adicionales-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    {{-- ✅ CAMBIAR AQUÍ: --}}
                    <input type="hidden" class="diagnostico-adicional-id" name="diagnosticos_adicionales[][idDiagnostico]">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Tipo de Diagnóstico</label>
                    <select class="form-select" name="diagnosticos_adicionales[][tipo_diagnostico]">
                        <option value="">Seleccione...</option>
                        <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                        <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                        <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <input type="text" class="form-control" name="diagnosticos_adicionales[][observacion]" placeholder="Observación opcional">
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-diagnostico-adicional">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info diagnostico-adicional-seleccionado" style="display: none;">
                    <strong>Diagnóstico Seleccionado:</strong>
                    <span class="diagnostico-adicional-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE MEDICAMENTO - CORREGIDO --}}
<template id="medicamento_template">
    <div class="medicamento-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Medicamento</label>
                    <input type="text" class="form-control buscar-medicamento" placeholder="Escriba el nombre del medicamento...">
                    <div class="medicamentos-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    {{-- ✅ CAMBIAR AQUÍ: --}}
                    <input type="hidden" class="medicamento-id" name="medicamentos[][idMedicamento]">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="text" class="form-control" name="medicamentos[][cantidad]" placeholder="Ej: 30 tabletas">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Dosis</label>
                    <input type="text" class="form-control" name="medicamentos[][dosis]" placeholder="Ej: 1 tableta cada 8 horas">
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-medicamento">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info medicamento-seleccionado" style="display: none;">
                    <strong>Medicamento Seleccionado:</strong>
                    <span class="medicamento-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE REMISIÓN - CORREGIDO --}}
<template id="remision_template">
    <div class="remision-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar Remisión</label>
                    <input type="text" class="form-control buscar-remision" placeholder="Escriba el nombre de la remisión...">
                    <div class="remisiones-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    {{-- ✅ CAMBIAR AQUÍ: --}}
                    <input type="hidden" class="remision-id" name="remisiones[][idRemision]">
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" name="remisiones[][remObservacion]" rows="2" placeholder="Observación de la remisión..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-remision">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info remision-seleccionada" style="display: none;">
                    <strong>Remisión Seleccionada:</strong>
                    <span class="remision-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>


{{-- TEMPLATE CUPS - CORREGIDO --}}
<template id="cups_template">
    <div class="cups-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar CUPS</label>
                    <input type="text" class="form-control buscar-cups" placeholder="Escriba código o nombre del procedimiento...">
                    <div class="cups-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    {{-- ✅ CAMBIAR AQUÍ: --}}
                    <input type="hidden" class="cups-id" name="cups[][idCups]">
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" name="cups[][cupObservacion]" rows="2" placeholder="Observación del procedimiento..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-cups">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info cups-seleccionado" style="display: none;">
                    <strong>CUPS Seleccionado:</strong>
                    <span class="cups-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>


{{-- ✅ LOADING OVERLAY --}}
<div id="loading_overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
     style="background: rgba(0,0,0,0.5); z-index: 9999; display: none !important;">
    <div class="text-center text-white">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">Guardando historia clínica...</div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card-header {
    font-weight: 600;
}

.medicamento-item, .diagnostico-adicional-item, .remision-item, .cups-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6 !important;
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-item:hover {
    background-color: #e9ecef;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.table-borderless td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.required-field {
    border-left: 3px solid #dc3545;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* ✅ ESTILOS PARA CAMPOS READONLY */
input[readonly] {
    background-color: #e9ecef !important;
    opacity: 1;
    cursor: not-allowed;
    pointer-events: none;
}

input[readonly]:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.test-morisky-input:checked + label {
    font-weight: bold;
    color: #0d6efd;
}

#explicacion_adherencia {
    border-left: 4px solid #0dcaf0;
    background-color: #f8f9fa;
}

.alert-info strong {
    color: #0c5460;
}

hr.my-4 {
    border-top: 2px solid #dee2e6;
    margin: 1.5rem 0;
}

</style>
@endpush
@push('scripts')
<script>
$(document).ready(function() {
    // ✅ VARIABLES GLOBALES
    let medicamentoCounter = 0;
    let diagnosticoAdicionalCounter = 0;
    let remisionCounter = 0;
    let cupsCounter = 0;
    let diagnosticoSeleccionado = null;
    
    // ✅ CÁLCULO AUTOMÁTICO DE IMC
    $('#peso, #talla').on('input', function() {
        calcularIMC();
    });
    
    function calcularIMC() {
        const peso = parseFloat($('#peso').val());
        const talla = parseFloat($('#talla').val());
        
        if (peso && talla && talla > 0) {
            const tallaMts = talla / 100;
            const imc = peso / (tallaMts * tallaMts);
            const imcRedondeado = Math.round(imc * 100) / 100;
            
            $('#imc').val(imcRedondeado);
            $('#clasificacion_imc').val(clasificarIMC(imcRedondeado));
        } else {
            $('#imc').val('');
            $('#clasificacion_imc').val('');
        }
    }
    
    function clasificarIMC(imc) {
        if (imc < 18.5) return 'Bajo peso';
        if (imc < 25) return 'Normal';
        if (imc < 30) return 'Sobrepeso';
        if (imc < 35) return 'Obesidad grado I';
        if (imc < 40) return 'Obesidad grado II';
        return 'Obesidad grado III';
    }
    
    // ✅ HABILITAR/DESHABILITAR CAMPOS DE ANTECEDENTES FAMILIARES
    $('.antecedente-familiar').on('change', function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        const textareaId = 'parentesco_' + name;
        
        if (value === 'SI') {
            $('#' + textareaId).prop('disabled', false).focus();
        } else {
            $('#' + textareaId).prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPOS DE ANTECEDENTES PERSONALES
    $('.antecedente-personal').on('change', function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        const textareaId = 'obs_' + name;
        
        if (value === 'SI') {
            $('#' + textareaId).prop('disabled', false).focus();
        } else {
            $('#' + textareaId).prop('disabled', true).val('');
        }
    });
    
    // ✅ HABILITAR/DESHABILITAR CAMPO DE DROGA
    $('input[name="drogodependiente"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#drogodependiente_cual').prop('disabled', false).focus();
        } else {
            $('#drogodependiente_cual').prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPO DE LESIÓN ÓRGANO BLANCO
    $('input[name="lesion_organo_blanco"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#descripcion_lesion_organo_blanco').prop('disabled', false).focus();
        } else {
            $('#descripcion_lesion_organo_blanco').prop('disabled', true).val('');
        }
    });

    // ✅ HABILITAR/DESHABILITAR CAMPOS DE HTA Y DM PERSONAL
    $('input[name="hipertension_arterial_personal"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#obs_hipertension_arterial_personal').prop('disabled', false).focus();
        } else {
            $('#obs_hipertension_arterial_personal').prop('disabled', true).val('');
        }
    });

    $('input[name="diabetes_mellitus_personal"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#obs_diabetes_mellitus_personal').prop('disabled', false).focus();
        } else {
            $('#obs_diabetes_mellitus_personal').prop('disabled', true).val('');
        }
    });
    
    // ✅ CÁLCULO AUTOMÁTICO DE ADHERENCIA TEST MORISKY - DENTRO DEL DOCUMENT.READY
    $(document).on('change', '.test-morisky-input', function() {
        calcularAdherenciaMorisky();
    });
    
    // ✅ BÚSQUEDA DE DIAGNÓSTICOS PRINCIPAL
    let diagnosticoTimeout;
    $('#buscar_diagnostico').on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(diagnosticoTimeout);
        
        if (termino.length < 2) {
            $('#diagnosticos_resultados').removeClass('show').empty();
            return;
        }
        
        diagnosticoTimeout = setTimeout(() => {
            buscarDiagnosticos(termino);
        }, 300);
    });
    
    function buscarDiagnosticos(termino) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-diagnosticos") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosDiagnosticos(response.data);
                } else {
                    console.error('Error buscando diagnósticos:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando diagnósticos:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosDiagnosticos(diagnosticos) {
        const $resultados = $('#diagnosticos_resultados');
        $resultados.empty();
        
        if (diagnosticos.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron diagnósticos</div>');
        } else {
            diagnosticos.forEach(function(diagnostico) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${diagnostico.codigo}</strong> - ${diagnostico.nombre}`)
                    .data('diagnostico', diagnostico);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarDiagnostico(diagnostico);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarDiagnostico(diagnostico) {
        diagnosticoSeleccionado = diagnostico;
        
        $('#buscar_diagnostico').val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $('#idDiagnostico').val(diagnostico.uuid || diagnostico.id);
        $('#diagnostico_info').text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $('#diagnostico_seleccionado').show();
        $('#diagnosticos_resultados').removeClass('show').empty();
    }
    
    // ✅ CERRAR DROPDOWNS AL HACER CLICK FUERA
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // ✅ AGREGAR DIAGNÓSTICO ADICIONAL
    $('#agregar_diagnostico_adicional').on('click', function() {
        agregarDiagnosticoAdicional();
    });
    
    function agregarDiagnosticoAdicional() {
        const template = $('#diagnostico_adicional_template').html();
        const $diagnostico = $(template);
        
        // Actualizar índices de los arrays
        $diagnostico.find('input[name*="diagnosticos_adicionales"], select[name*="diagnosticos_adicionales"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${diagnosticoAdicionalCounter}]`));
        });
        
        $('#diagnosticos_adicionales_container').append($diagnostico);
        diagnosticoAdicionalCounter++;
        
        // Configurar búsqueda para este diagnóstico
        configurarBusquedaDiagnosticoAdicional($diagnostico);
    }
    
    function configurarBusquedaDiagnosticoAdicional($container) {
        const $input = $container.find('.buscar-diagnostico-adicional');
        const $resultados = $container.find('.diagnosticos-adicionales-resultados');
        const $hiddenId = $container.find('.diagnostico-adicional-id');
        const $info = $container.find('.diagnostico-adicional-info');
        const $alert = $container.find('.diagnostico-adicional-seleccionado');
        
        let diagnosticoTimeout;
        
        $input.on('input', function() {
            const termino = $(this).val().trim();
            
            clearTimeout(diagnosticoTimeout);
            
            if (termino.length < 2) {
                $resultados.removeClass('show').empty();
                return;
            }
            
            diagnosticoTimeout = setTimeout(() => {
                buscarDiagnosticosAdicionales(termino, $resultados, $input, $hiddenId, $info, $alert);
            }, 300);
        });
    }
    
    function buscarDiagnosticosAdicionales(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-diagnosticos") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosDiagnosticosAdicionales(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando diagnósticos adicionales:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando diagnósticos adicionales:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosDiagnosticosAdicionales(diagnosticos, $resultados, $input, $hiddenId, $info, $alert) {
        $resultados.empty();
        
        if (diagnosticos.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron diagnósticos</div>');
        } else {
            diagnosticos.forEach(function(diagnostico) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${diagnostico.codigo}</strong> - ${diagnostico.nombre}`)
                    .data('diagnostico', diagnostico);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $hiddenId.val(diagnostico.uuid || diagnostico.id);
        $info.text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }

    // ✅ ELIMINAR DIAGNÓSTICO ADICIONAL
    $(document).on('click', '.eliminar-diagnostico-adicional', function() {
        $(this).closest('.diagnostico-adicional-item').remove();
    });
    
    // ✅ AGREGAR MEDICAMENTO
    $('#agregar_medicamento').on('click', function() {
        agregarMedicamento();
    });
    
    function agregarMedicamento() {
        const template = $('#medicamento_template').html();
        const $medicamento = $(template);
        
        // Actualizar índices de los arrays
        $medicamento.find('input[name*="medicamentos"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${medicamentoCounter}]`));
        });
        
        $('#medicamentos_container').append($medicamento);
        medicamentoCounter++;
        
        // Configurar búsqueda para este medicamento
        configurarBusquedaMedicamento($medicamento);
    }
    
    function configurarBusquedaMedicamento($container) {
        const $input = $container.find('.buscar-medicamento');
        const $resultados = $container.find('.medicamentos-resultados');
        const $hiddenId = $container.find('.medicamento-id');
        const $info = $container.find('.medicamento-info');
        const $alert = $container.find('.medicamento-seleccionado');
        
        let medicamentoTimeout;
        
        $input.on('input', function() {
            const termino = $(this).val().trim();
            
            clearTimeout(medicamentoTimeout);
            
            if (termino.length < 2) {
                $resultados.removeClass('show').empty();
                return;
            }
            
            medicamentoTimeout = setTimeout(() => {
                buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert);
            }, 300);
        });
    }
    
    function buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-medicamentos") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosMedicamentos(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando medicamentos:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando medicamentos:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosMedicamentos(medicamentos, $resultados, $input, $hiddenId, $info, $alert) {
        $resultados.empty();
        
        if (medicamentos.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron medicamentos</div>');
        } else {
            medicamentos.forEach(function(medicamento) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${medicamento.nombre}</strong><br><small class="text-muted">${medicamento.principio_activo || ''}</small>`)
                    .data('medicamento', medicamento);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(medicamento.nombre);
        $hiddenId.val(medicamento.uuid || medicamento.id);
        $info.html(`<strong>${medicamento.nombre}</strong><br><small>${medicamento.principio_activo || ''}</small>`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }

    // ✅ ELIMINAR MEDICAMENTO
    $(document).on('click', '.eliminar-medicamento', function() {
        $(this).closest('.medicamento-item').remove();
    });
    
    // ✅ AGREGAR REMISIÓN
    $('#agregar_remision').on('click', function() {
        agregarRemision();
    });
    
    function agregarRemision() {
        const template = $('#remision_template').html();
        const $remision = $(template);
        
        // Actualizar índices de los arrays
        $remision.find('input[name*="remisiones"], textarea[name*="remisiones"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${remisionCounter}]`));
        });
        
        $('#remisiones_container').append($remision);
        remisionCounter++;
        
        // Configurar búsqueda para esta remisión
        configurarBusquedaRemision($remision);
    }
    
    function configurarBusquedaRemision($container) {
        const $input = $container.find('.buscar-remision');
        const $resultados = $container.find('.remisiones-resultados');
        const $hiddenId = $container.find('.remision-id');
        const $info = $container.find('.remision-info');
        const $alert = $container.find('.remision-seleccionada');
        
        let remisionTimeout;
        
        $input.on('input', function() {
            const termino = $(this).val().trim();
            
            clearTimeout(remisionTimeout);
            
            if (termino.length < 2) {
                $resultados.removeClass('show').empty();
                return;
            }
            
            remisionTimeout = setTimeout(() => {
                buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert);
            }, 300);
        });
    }
    
    function buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-remisiones") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosRemisiones(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando remisiones:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando remisiones:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosRemisiones(remisiones, $resultados, $input, $hiddenId, $info, $alert) {
        $resultados.empty();
        
        if (remisiones.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron remisiones</div>');
        } else {
            remisiones.forEach(function(remision) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${remision.nombre}</strong><br><small class="text-muted">${remision.tipo || ''}</small>`)
                    .data('remision', remision);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(remision.nombre);
        $hiddenId.val(remision.uuid || remision.id);
        $info.html(`<strong>${remision.nombre}</strong><br><small>${remision.tipo || ''}</small>`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }
    
    // ✅ ELIMINAR REMISIÓN
    $(document).on('click', '.eliminar-remision', function() {
        $(this).closest('.remision-item').remove();
    });
    
    // ✅ AGREGAR CUPS
    $('#agregar_cups').on('click', function() {
        agregarCups();
    });
    
    function agregarCups() {
        const template = $('#cups_template').html();
        const $cups = $(template);
        
        // Actualizar índices de los arrays
        $cups.find('input[name*="cups"], textarea[name*="cups"]').each(function() {
            const name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', `[${cupsCounter}]`));
        });
        
        $('#cups_container').append($cups);
        cupsCounter++;
        
        // Configurar búsqueda para este CUPS
        configurarBusquedaCups($cups);
    }
    
    function configurarBusquedaCups($container) {
        const $input = $container.find('.buscar-cups');
        const $resultados = $container.find('.cups-resultados');
        const $hiddenId = $container.find('.cups-id');
        const $info = $container.find('.cups-info');
        const $alert = $container.find('.cups-seleccionado');
        
        let cupsTimeout;
        
        $input.on('input', function() {
            const termino = $(this).val().trim();
            
            clearTimeout(cupsTimeout);
            
            if (termino.length < 2) {
                $resultados.removeClass('show').empty();
                return;
            }
            
            cupsTimeout = setTimeout(() => {
                buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert);
            }, 300);
        });
    }
    
    function buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert) {
        $.ajax({
            url: '{{ route("historia-clinica.buscar-cups") }}',
            method: 'GET',
            data: { q: termino },
            success: function(response) {
                if (response.success) {
                    mostrarResultadosCups(response.data, $resultados, $input, $hiddenId, $info, $alert);
                } else {
                    console.error('Error buscando CUPS:', response.error);
                }
            },
            error: function(xhr) {
                console.error('Error AJAX buscando CUPS:', xhr.responseText);
            }
        });
    }
    
    function mostrarResultadosCups(cups, $resultados, $input, $hiddenId, $info, $alert) {
        $resultados.empty();
        
        if (cups.length === 0) {
            $resultados.append('<div class="dropdown-item-text text-muted">No se encontraron procedimientos</div>');
        } else {
            cups.forEach(function(cup) {
                const $item = $('<a href="#" class="dropdown-item"></a>')
                    .html(`<strong>${cup.codigo}</strong> - ${cup.nombre}`)
                    .data('cups', cup);
                
                $item.on('click', function(e) {
                    e.preventDefault();
                    seleccionarCups(cup, $input, $hiddenId, $info, $alert, $resultados);
                });
                
                $resultados.append($item);
            });
        }
        
        $resultados.addClass('show');
    }
    
    function seleccionarCups(cups, $input, $hiddenId, $info, $alert, $resultados) {
        $input.val(`${cups.codigo} - ${cups.nombre}`);
        $hiddenId.val(cups.uuid || cups.id);
        $info.text(`${cups.codigo} - ${cups.nombre}`);
        $alert.show();
        $resultados.removeClass('show').empty();
    }
    
    // ✅ ELIMINAR CUPS
    $(document).on('click', '.eliminar-cups', function() {
        $(this).closest('.cups-item').remove();
    });
    
    // ✅ ENVÍO DEL FORMULARIO - MODIFICADO CON ADHERENTE
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        // ✅ HABILITAR CAMPO ADHERENTE ANTES DEL ENVÍO
        $('input[name="adherente"]').prop('readonly', false);
        
        // Validar diagnóstico principal
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            
            // ✅ VOLVER A DESHABILITAR SI HAY ERROR
            $('input[name="adherente"]').prop('readonly', true);
            return;
        }
        
        // Mostrar loading
        $('#loading_overlay').show();
        
        // Preparar datos
        const formData = new FormData(this);
        
        // ✅ LOGGING PARA VERIFICAR QUE SE ENVÍA
        console.log('Adherente value:', $('input[name="adherente"]:checked').val());
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#loading_overlay').hide();
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        confirmButtonText: 'Continuar'
                    }).then((result) => {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Error guardando la historia clínica'
                    });
                }
            },
            error: function(xhr) {
                $('#loading_overlay').hide();
                
                let errorMessage = 'Error interno del servidor';
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                } else if (xhr.responseJSON?.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            },
            complete: function() {
                // ✅ VOLVER A DESHABILITAR DESPUÉS DEL ENVÍO
                $('input[name="adherente"]').prop('readonly', true);
            }
        });
    });

}); // ✅ CERRAR $(document).ready

// ✅ FUNCIÓN DE CÁLCULO DE ADHERENCIA MORISKY - FUERA DEL DOCUMENT.READY
function calcularAdherenciaMorisky() {
    console.log('Calculando adherencia Morisky...');
    
    // ✅ OBTENER RESPUESTAS
    const olvida = $('input[name="test_morisky_olvida_tomar_medicamentos"]:checked').val();
    const horaIndicada = $('input[name="test_morisky_toma_medicamentos_hora_indicada"]:checked').val();
    const cuandoEstaBien = $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"]:checked').val();
    const sienteMal = $('input[name="test_morisky_siente_mal_deja_tomarlos"]:checked').val();
    const psicologia = $('input[name="test_morisky_valoracio_psicologia"]:checked').val();
    
    console.log('Respuestas:', { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia });
    
    // ✅ VERIFICAR QUE TODAS LAS PREGUNTAS ESTÉN RESPONDIDAS
    if (!olvida || !horaIndicada || !cuandoEstaBien || !sienteMal || !psicologia) {
        // Si no están todas respondidas, resetear
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        $('#explicacion_adherencia').hide();
        console.log('No todas las preguntas están respondidas');
        return;
    }
    
    // ✅ CALCULAR PUNTUACIÓN MORISKY
    let puntuacion = 0;
    
    // Pregunta 1: ¿Olvida alguna vez tomar los medicamentos? (SI = 1 punto)
    if (olvida === 'SI') puntuacion += 1;
    
    // Pregunta 2: ¿Toma los medicamentos a la hora indicada? (NO = 1 punto)
    if (horaIndicada === 'NO') puntuacion += 1;
    
    // Pregunta 3: ¿Cuando se encuentra bien, deja de tomar los medicamentos? (SI = 1 punto)
    if (cuandoEstaBien === 'SI') puntuacion += 1;
    
    // Pregunta 4: ¿Si alguna vez se siente mal, deja de tomarlos? (SI = 1 punto)
    if (sienteMal === 'SI') puntuacion += 1;
    
    // ✅ DETERMINAR ADHERENCIA
    // Puntuación 0 = Adherente
    // Puntuación 1-4 = No adherente
    let esAdherente = puntuacion === 0;
    let explicacion = '';
    
    if (esAdherente) {
               $('#adherente_si').prop('checked', true);
        $('#adherente_no').prop('checked', false);
        explicacion = `<strong>ADHERENTE:</strong> Puntuación: ${puntuacion}/4. El paciente muestra buena adherencia al tratamiento farmacológico.`;
    } else {
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        explicacion = `<strong>NO ADHERENTE:</strong> Puntuación: ${puntuacion}/4. El paciente presenta problemas de adherencia al tratamiento farmacológico.`;
    }
    
    // ✅ MOSTRAR EXPLICACIÓN
    $('#texto_explicacion').html(explicacion);
    $('#explicacion_adherencia').show();
    
    // ✅ AGREGAR RECOMENDACIÓN PARA PSICOLOGÍA SI ES NECESARIO
    if (!esAdherente || psicologia === 'SI') {
        $('#texto_explicacion').append('<br><strong>Recomendación:</strong> Considerar valoración por psicología para mejorar adherencia.');
    }
    
    console.log('Test Morisky calculado:', {
        puntuacion: puntuacion,
        adherente: esAdherente,
        respuestas: { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia }
    });
}

</script>
@endpush
