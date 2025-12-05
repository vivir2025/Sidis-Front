{{-- resources/views/historia-clinica/medicina-general/control.blade.php --}}
@extends('layouts.app')

@section('title', 'Control Gestión del Riesgo Cardio Renal')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')
    
    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="CONTROL">
        
        {{-- ✅ SECCIÓN: DATOS BÁSICOS --}}
        @include('historia-clinica.partials.datos-basicos')

        {{-- ✅ SECCIÓN: ACUDIENTE --}}
        @include('historia-clinica.partials.acudiente')

        {{-- ✅ SECCIÓN: MEDIDAS ANTROPOMÉTRICAS --}}
        @include('historia-clinica.partials.medidas-antropometricas')

        {{-- ✅ SECCIÓN: TEST MORISKY --}}
        @include('historia-clinica.partials.test-morisky')
        
        {{-- ✅ SECCIÓN: REVISIÓN POR SISTEMAS --}}
        @include('historia-clinica.partials.revision-sistemas')
        
        {{-- ✅ SECCIÓN: SIGNOS VITALES --}}
        @include('historia-clinica.partials.signos-vitales')
        
        {{-- ✅ SECCIÓN: EXAMEN FÍSICO --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-stethoscope me-2"></i>
                    Examen Físico por Sistema
                </h5>
            </div>
            <div class="card-body">
                @php
                $examenFisico = [
                    ['key' => 'ef_cabeza', 'label' => 'Cabeza', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_agudeza_visual', 'label' => 'Ojos (Agudeza Visual)', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'oidos', 'label' => 'Oídos', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'nariz_senos_paranasales', 'label' => 'Nariz y Senos Paranasales', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'cavidad_oral', 'label' => 'Cavidad Oral', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_cuello', 'label' => 'Cuello', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'cardio_respiratorio', 'label' => 'Cardio Respiratorio', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_mamas', 'label' => 'Mamas', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'gastrointestinal', 'label' => 'Gastrointestinal', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_genito_urinario', 'label' => 'Genitourinario', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'musculo_esqueletico', 'label' => 'Músculo Esquelético', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_piel_anexos_pulsos', 'label' => 'Piel y Anexos Pulsos', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'inspeccion_sensibilidad_pies', 'label' => 'Inspección y Sensibilidad en Pies', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_sistema_nervioso', 'label' => 'Sistema Nervioso', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'capacidad_congnitiva_orientacion', 'label' => 'Capacidad Cognitiva, Orientación', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_reflejo_aquiliar', 'label' => 'Reflejo Aquiliano', 'default' => 'NORMAL NO REFIERE'],
                    ['key' => 'ef_reflejo_patelar', 'label' => 'Reflejo Patelar', 'default' => 'NORMAL NO REFIERE']
                ];
                @endphp

                @foreach($examenFisico as $index => $examen)
                    @if($index % 2 == 0)
                        <div class="row">
                    @endif
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="{{ $examen['key'] }}" class="form-label">{{ $examen['label'] }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="{{ $examen['key'] }}" 
                                   name="{{ $examen['key'] }}" required 
                                   value="{{ $historiaPrevia[$examen['key']] ?? $examen['default'] }}">
                        </div>
                    </div>
                    
                    @if($index % 2 == 1 || $index == count($examenFisico) - 1)
                        </div>
                    @endif
                @endforeach

                {{-- ✅ CAMPOS ADICIONALES --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="dislipidemia" class="form-label">Antecedente Dislipidemia Familiar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dislipidemia" name="dislipidemia" required 
                                   value="{{ $historiaPrevia['dislipidemia'] ?? 'NO' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Lesión de Órgano Blanco</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="lesion_organo_blanco" 
                                           id="lesion_si" value="SI" 
                                           {{ ($historiaPrevia['lesion_organo_blanco'] ?? '') === 'SI' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="lesion_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="lesion_organo_blanco" 
                                           id="lesion_no" value="NO" 
                                           {{ ($historiaPrevia['lesion_organo_blanco'] ?? 'NO') === 'NO' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="lesion_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" id="descripcion_lesion_container" style="display: {{ ($historiaPrevia['lesion_organo_blanco'] ?? 'NO') === 'SI' ? 'block' : 'none' }};">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="descripcion_lesion_organo_blanco" class="form-label">Descripción Lesión de Órgano Blanco</label>
                            <textarea class="form-control" id="descripcion_lesion_organo_blanco" 
                                      name="descripcion_lesion_organo_blanco" rows="2" 
                                      placeholder="Descripción de la lesión">{{ $historiaPrevia['descripcion_lesion_organo_blanco'] ?? '' }}</textarea>
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
                            <input type="date" class="form-control" id="fex_es" name="fex_es" 
                                   value="{{ $historiaPrevia['fex_es'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="hcElectrocardiograma" class="form-label">Resultado Electrocardiograma <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="hcElectrocardiograma" name="hcElectrocardiograma" 
                                      rows="3" required>{{ $historiaPrevia['electrocardiograma'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fex_es1" class="form-label">Fecha Ecocardiograma</label>
                            <input type="date" class="form-control" id="fex_es1" name="fex_es1" 
                                   value="{{ $historiaPrevia['fex_es1'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="hcEcocardiograma" class="form-label">Resultado Ecocardiograma <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="hcEcocardiograma" name="hcEcocardiograma" 
                                      rows="3" required>{{ $historiaPrevia['ecocardiograma'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="fex_es2" class="form-label">Fecha Ecografía Renal</label>
                            <input type="date" class="form-control" id="fex_es2" name="fex_es2" 
                                   value="{{ $historiaPrevia['fex_es2'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="hcEcografiaRenal" class="form-label">Resultado Ecografía Renal <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="hcEcografiaRenal" name="hcEcografiaRenal" 
                                      rows="3" required>{{ $historiaPrevia['ecografia_renal'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIÓN: CLASIFICACIONES --}}
        @include('historia-clinica.partials.clasificaciones')

        {{-- ✅ SECCIÓN: EDUCACIÓN --}}
       @include('historia-clinica.partials.educacion')

        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
        @include('historia-clinica.partials.diagnostico-principal')
        
        {{-- ✅ SECCIÓN: DIAGNÓSTICOS ADICIONALES --}}
        @include('historia-clinica.partials.diagnosticos-adicionales')
        
        {{-- ✅ SECCIONES DINÁMICAS (MEDICAMENTOS, REMISIONES, CUPS) --}}
        @include('historia-clinica.partials.medicamentos-section')
        @include('historia-clinica.partials.remisiones-section')
        @include('historia-clinica.partials.cups-section')

        {{-- ✅ SECCIÓN: OBSERVACIONES GENERALES CON MICRÓFONO --}}
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
                        <div class="mb-3 position-relative">
                            <label for="observaciones_generales" class="form-label">Observaciones <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="observaciones_generales" name="observaciones_generales" 
                                      rows="4" required placeholder="Observaciones generales">{{ $historiaPrevia['observaciones_generales'] ?? '' }}</textarea>
                            <span class="microfono" id="microfono"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ ENLACES ADICIONALES --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>
                    Enlaces Adicionales
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a type="button" class="btn btn-outline-primary w-100 mb-2" target="_blank" 
                           href="{{ url('paraclinicos/' . $cita['paciente']['documento']) }}">
                            <i class="fas fa-flask me-2"></i>Paraclínicos
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a type="button" class="btn btn-outline-primary w-100 mb-2" target="_blank" 
                           href="{{ url('visitas-domiciliarias/' . $cita['paciente']['documento']) }}">
                           <i class="fas fa-home me-2"></i>App Visitas Domiciliarias
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ FIRMA DIGITAL --}}
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-signature me-2"></i>
                    Firmas
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6">
                        @if(isset($usuario['firma']))
                            <img alt="Firma Digital" width="302px" height="70px" 
                                 src="data:image/jpeg;base64,{{ $usuario['firma'] }}" class="img-fluid mb-2"/>
                        @endif
                        <div>
                            <strong>FIRMA DIGITAL</strong><br>
                            <strong>PROFESIONAL:</strong><br>
                            <em>{{ $usuario['nombre_completo'] }}<br>
                            RM: {{ $usuario['registro_profesional'] ?? 'N/A' }}</em>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="height: 70px; border: 1px solid #ccc; margin-bottom: 10px; display: flex; align-items: center; justify-content: center;">
                            <span class="text-muted">Espacio para firma del paciente</span>
                        </div>
                        <div>
                            <strong>FIRMA PACIENTE:</strong><br>
                            <em>{{ $cita['paciente']['tipo_documento'] ?? '' }}-{{ $cita['paciente']['documento'] ?? '' }}<br>
                            {{ $cita['paciente']['nombre_completo'] ?? '' }}</em>
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
                    Guardar Control
                </button>
                <a href="{{ route('cronograma.index') }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ✅ LOADING OVERLAY --}}
<div id="loading_overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
     style="background: rgba(0,0,0,0.5); z-index: 9999; display: none !important;">
    <div class="text-center text-white">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">Guardando control...</div>
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

/* ✅ ESTILOS PARA MICRÓFONO */
.form-group {
    position: relative;
}

.microfono {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background-color: transparent;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3zM19 10v1a7 7 0 0 1-14 0v-1a1 1 0 0 1 2 0v1a5 5 0 0 0 10 0v-1a1 1 0 0 1 2 0z"/><path d="M12 18.5a1 1 0 0 1 1 1V22a1 1 0 0 1-2 0v-2.5a1 1 0 0 1 1-1z"/></svg>');
    background-size: cover;
    cursor: pointer;
    z-index: 1;
    display: inline-block;
    transition: transform 0.3s ease-in-out;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
}

.microfono.active {
    animation: pulse 1s infinite;
    box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
    50% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
    }
    100% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
}
</style>
@endpush

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

/* ✅ ESTILOS PARA MICRÓFONO */
.form-group {
    position: relative;
}

.microfono {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background-color: transparent;
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3zM19 10v1a7 7 0 0 1-14 0v-1a1 1 0 0 1 2 0v1a5 5 0 0 0 10 0v-1a1 1 0 0 1 2 0z"/><path d="M12 18.5a1 1 0 0 1 1 1V22a1 1 0 0 1-2 0v-2.5a1 1 0 0 1 1-1z"/></svg>');
    background-size: cover;
    cursor: pointer;
    z-index: 1;
    display: inline-block;
    transition: transform 0.3s ease-in-out;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
}

.microfono.active {
    animation: pulse 1s infinite;
    box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
    50% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(0, 0, 255, 0);
    }
    100% {
        transform: scale(0.8);
        box-shadow: 0 0 0 0 rgba(0, 0, 255, 0.7);
    }
}
</style>
@endpush
@push('scripts')
<script>
// ============================================
// ✅ VARIABLES GLOBALES
// ============================================
let medicamentoCounter = 0;
let diagnosticoAdicionalCounter = 0;
let remisionCounter = 0;
let cupsCounter = 0;
let diagnosticoSeleccionado = null;

// ============================================
// ✅ FUNCIONES PRINCIPALES (FUERA DE DOCUMENT.READY)
// ============================================

/**
 * ✅ CALCULAR ADHERENCIA MORISKY
 */
function calcularAdherenciaMorisky() {
    console.log('Calculando adherencia Morisky...');
    
    const olvida = $('input[name="test_morisky_olvida_tomar_medicamentos"]:checked').val();
    const horaIndicada = $('input[name="test_morisky_toma_medicamentos_hora_indicada"]:checked').val();
    const cuandoEstaBien = $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"]:checked').val();
    const sienteMal = $('input[name="test_morisky_siente_mal_deja_tomarlos"]:checked').val();
    const psicologia = $('input[name="test_morisky_valoracio_psicologia"]:checked').val();
    
    console.log('Respuestas:', { olvida, horaIndicada, cuandoEstaBien, sienteMal, psicologia });
    
    if (!olvida || !horaIndicada || !cuandoEstaBien || !sienteMal || !psicologia) {
        $('#adherente_si').prop('checked', false);
        $('#adherente_no').prop('checked', true);
        $('#explicacion_adherencia').hide();
        console.log('No todas las preguntas están respondidas');
        return;
    }
    
    let puntuacion = 0;
    if (olvida === 'SI') puntuacion += 1;
    if (horaIndicada === 'NO') puntuacion += 1;
    if (cuandoEstaBien === 'SI') puntuacion += 1;
    if (sienteMal === 'SI') puntuacion += 1;
    
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
    
    $('#texto_explicacion').html(explicacion);
    $('#explicacion_adherencia').show();
    
    if (!esAdherente || psicologia === 'SI') {
        $('#texto_explicacion').append('<br><strong>Recomendación:</strong> Considerar valoración por psicología para mejorar adherencia.');
    }
    
    console.log('Test Morisky calculado:', { puntuacion, adherente: esAdherente });
}

/**
 * ✅ AGREGAR MEDICAMENTO
 */
function agregarMedicamento() {
    const template = $('#medicamento_template').html();
    const $medicamento = $(template);
    
    $medicamento.find('input[name*="medicamentos"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${medicamentoCounter}]`));
    });
    
    $('#medicamentos_container').append($medicamento);
    medicamentoCounter++;
    
    configurarBusquedaMedicamento($medicamento);
}

/**
 * ✅ AGREGAR MEDICAMENTO CON DATOS
 */
function agregarMedicamentoConDatos(medicamento) {
    console.log('💊 Agregando medicamento con datos:', medicamento);
    
    agregarMedicamento();
    
    const $ultimoMedicamento = $('#medicamentos_container .medicamento-item:last');
    
    $ultimoMedicamento.find('.buscar-medicamento').val(medicamento.medicamento.nombre);
    $ultimoMedicamento.find('.medicamento-id').val(medicamento.medicamento_id);
    $ultimoMedicamento.find('.medicamento-info').html(`<strong>${medicamento.medicamento.nombre}</strong><br><small>${medicamento.medicamento.principio_activo || ''}</small>`);
    $ultimoMedicamento.find('.medicamento-seleccionado').show();
    $ultimoMedicamento.find('input[name*="cantidad"]').val(medicamento.cantidad || '');
    $ultimoMedicamento.find('input[name*="dosis"]').val(medicamento.dosis || '');
    
    console.log('✅ Medicamento agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA MEDICAMENTO
 */
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

/**
 * ✅ BUSCAR MEDICAMENTOS
 */
function buscarMedicamentos(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-medicamentos") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosMedicamentos(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando medicamentos:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS MEDICAMENTOS
 */
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

/**
 * ✅ SELECCIONAR MEDICAMENTO
 */
function seleccionarMedicamento(medicamento, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(medicamento.nombre);
    $hiddenId.val(medicamento.uuid || medicamento.id);
    $info.html(`<strong>${medicamento.nombre}</strong><br><small>${medicamento.principio_activo || ''}</small>`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ AGREGAR REMISIÓN
 */
function agregarRemision() {
    const template = $('#remision_template').html();
    const $remision = $(template);
    
    $remision.find('input[name*="remisiones"], textarea[name*="remisiones"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${remisionCounter}]`));
    });
    
    $('#remisiones_container').append($remision);
    remisionCounter++;
    
    configurarBusquedaRemision($remision);
}

/**
 * ✅ AGREGAR REMISIÓN CON DATOS
 */
function agregarRemisionConDatos(remision) {
    console.log('📋 Agregando remisión con datos:', remision);
    
    agregarRemision();
    
    const $ultimaRemision = $('#remisiones_container .remision-item:last');
    
    $ultimaRemision.find('.buscar-remision').val(remision.remision.nombre);
    $ultimaRemision.find('.remision-id').val(remision.remision_id);
    $ultimaRemision.find('.remision-info').html(`<strong>${remision.remision.nombre}</strong><br><small>${remision.remision.tipo || ''}</small>`);
    $ultimaRemision.find('.remision-seleccionada').show();
    $ultimaRemision.find('textarea[name*="remObservacion"]').val(remision.observacion || '');
    
    console.log('✅ Remisión agregada exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA REMISIÓN
 */
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

/**
 * ✅ BUSCAR REMISIONES
 */
function buscarRemisiones(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-remisiones") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosRemisiones(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando remisiones:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS REMISIONES
 */
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

/**
 * ✅ SELECCIONAR REMISIÓN
 */
function seleccionarRemision(remision, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(remision.nombre);
    $hiddenId.val(remision.uuid || remision.id);
    $info.html(`<strong>${remision.nombre}</strong><br><small>${remision.tipo || ''}</small>`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ AGREGAR DIAGNÓSTICO ADICIONAL
 */
function agregarDiagnosticoAdicional() {
    const template = $('#diagnostico_adicional_template').html();
    const $diagnostico = $(template);
    
    $diagnostico.find('input[name*="diagnosticos_adicionales"], select[name*="diagnosticos_adicionales"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${diagnosticoAdicionalCounter}]`));
    });
    
    $('#diagnosticos_adicionales_container').append($diagnostico);
    diagnosticoAdicionalCounter++;
    
    configurarBusquedaDiagnosticoAdicional($diagnostico);
}

/**
 * ✅ AGREGAR DIAGNÓSTICO ADICIONAL CON DATOS
 */
function agregarDiagnosticoAdicionalConDatos(diagnostico) {
    console.log('🩺 Agregando diagnóstico adicional con datos:', diagnostico);
    
    agregarDiagnosticoAdicional();
    
    const $ultimoDiagnostico = $('#diagnosticos_adicionales_container .diagnostico-adicional-item:last');
    
    $ultimoDiagnostico.find('.buscar-diagnostico-adicional').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
    $ultimoDiagnostico.find('.diagnostico-adicional-id').val(diagnostico.diagnostico_id);
    $ultimoDiagnostico.find('.diagnostico-adicional-info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
    $ultimoDiagnostico.find('.diagnostico-adicional-seleccionado').show();
    $ultimoDiagnostico.find('select[name*="tipo_diagnostico"]').val(diagnostico.tipo_diagnostico || 'IMPRESION_DIAGNOSTICA');
    
    console.log('✅ Diagnóstico adicional agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA DIAGNÓSTICO ADICIONAL
 */
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

/**
 * ✅ BUSCAR DIAGNÓSTICOS ADICIONALES
 */
function buscarDiagnosticosAdicionales(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-diagnosticos") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosDiagnosticosAdicionales(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando diagnósticos adicionales:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS DIAGNÓSTICOS ADICIONALES
 */
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

/**
 * ✅ SELECCIONAR DIAGNÓSTICO ADICIONAL
 */
function seleccionarDiagnosticoAdicional(diagnostico, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(`${diagnostico.codigo} - ${diagnostico.nombre}`);
    $hiddenId.val(diagnostico.uuid || diagnostico.id);
    $info.text(`${diagnostico.codigo} - ${diagnostico.nombre}`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ AGREGAR CUPS
 */
function agregarCups() {
    const template = $('#cups_template').html();
    const $cups = $(template);
    
    $cups.find('input[name*="cups"], textarea[name*="cups"]').each(function() {
        const name = $(this).attr('name');
        $(this).attr('name', name.replace('[]', `[${cupsCounter}]`));
    });
    
    $('#cups_container').append($cups);
    cupsCounter++;
    
    configurarBusquedaCups($cups);
}

/**
 * ✅ AGREGAR CUPS CON DATOS
 */
function agregarCupsConDatos(cups) {
    console.log('🏥 Agregando CUPS con datos:', cups);
    
    agregarCups();
    
    const $ultimoCups = $('#cups_container .cups-item:last');
    
    $ultimoCups.find('.buscar-cups').val(`${cups.cups.codigo} - ${cups.cups.nombre}`);
    $ultimoCups.find('.cups-id').val(cups.cups_id);
    $ultimoCups.find('.cups-info').text(`${cups.cups.codigo} - ${cups.cups.nombre}`);
    $ultimoCups.find('.cups-seleccionado').show();
    $ultimoCups.find('textarea[name*="cupObservacion"]').val(cups.observacion || '');
    
    console.log('✅ CUPS agregado exitosamente');
}

/**
 * ✅ CONFIGURAR BÚSQUEDA CUPS
 */
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

/**
 * ✅ BUSCAR CUPS
 */
function buscarCups(termino, $resultados, $input, $hiddenId, $info, $alert) {
    $.ajax({
        url: '{{ route("historia-clinica.buscar-cups") }}',
        method: 'GET',
        data: { q: termino },
        success: function(response) {
            if (response.success) {
                mostrarResultadosCups(response.data, $resultados, $input, $hiddenId, $info, $alert);
            }
        },
        error: function(xhr) {
            console.error('Error AJAX buscando CUPS:', xhr.responseText);
        }
    });
}

/**
 * ✅ MOSTRAR RESULTADOS CUPS
 */
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

/**
 * ✅ SELECCIONAR CUPS
 */
function seleccionarCups(cups, $input, $hiddenId, $info, $alert, $resultados) {
    $input.val(`${cups.codigo} - ${cups.nombre}`);
    $hiddenId.val(cups.uuid || cups.id);
    $info.text(`${cups.codigo} - ${cups.nombre}`);
    $alert.show();
    $resultados.removeClass('show').empty();
}

/**
 * ✅ CARGAR DIAGNÓSTICO PRINCIPAL CON DATOS
 */
function cargarDiagnosticoPrincipalConDatos(diagnostico) {
    console.log('🩺 Cargando diagnóstico principal con datos:', diagnostico);
    
    try {
        $('#buscar_diagnostico').val(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $('#idDiagnostico').val(diagnostico.diagnostico_id);
        $('#diagnostico_info').text(`${diagnostico.diagnostico.codigo} - ${diagnostico.diagnostico.nombre}`);
        $('#diagnostico_seleccionado').show();
        
        if (diagnostico.tipo_diagnostico) {
            $('#tipo_diagnostico').val(diagnostico.tipo_diagnostico);
        }
        
        console.log('✅ Diagnóstico principal cargado exitosamente');
        
    } catch (error) {
        console.error('❌ Error cargando diagnóstico principal:', error);
    }
}

/**
 * ✅✅✅ NUEVA FUNCIÓN: DISPARAR EVENTO DE HISTORIA GUARDADA ✅✅✅
 */
function dispararEventoHistoriaGuardada(citaUuid, historiaUuid, offline) {
    console.log('📋 Disparando evento historiaClinicaGuardada', {
        citaUuid: citaUuid,
        historiaUuid: historiaUuid,
        offline: offline
    });
    
    // ✅ DISPARAR EVENTO PERSONALIZADO PARA EL CRONOGRAMA
    window.dispatchEvent(new CustomEvent('historiaClinicaGuardada', {
        detail: {
            cita_uuid: citaUuid,
            historia_uuid: historiaUuid,
            offline: offline || false
        }
    }));
    
    console.log('✅ Evento disparado exitosamente');
}

/**
 * ✅ CARGAR DATOS PREVIOS MEDICINA GENERAL
 */
function cargarDatosPreviosMedicinaGeneral(historiaPrevia) {
    try {
        console.log('🔄 Iniciando carga de datos previos para Medicina General');
        console.log('📦 Historia previa recibida:', historiaPrevia);

        // ✅ CARGAR MEDICAMENTOS
        if (historiaPrevia.medicamentos && historiaPrevia.medicamentos.length > 0) {
            console.log('💊 Cargando medicamentos previos:', historiaPrevia.medicamentos.length);
            historiaPrevia.medicamentos.forEach(function(medicamento, index) {
                setTimeout(function() {
                    agregarMedicamentoConDatos(medicamento);
                }, index * 200);
            });
        }

        // ✅ CARGAR REMISIONES
        if (historiaPrevia.remisiones && historiaPrevia.remisiones.length > 0) {
            console.log('📋 Cargando remisiones previas:', historiaPrevia.remisiones.length);
            historiaPrevia.remisiones.forEach(function(remision, index) {
                setTimeout(function() {
                    agregarRemisionConDatos(remision);
                }, index * 200);
            });
        }

        // ✅ CARGAR DIAGNÓSTICOS
        if (historiaPrevia.diagnosticos && historiaPrevia.diagnosticos.length > 0) {
            console.log('🩺 Cargando diagnósticos previos:', historiaPrevia.diagnosticos.length);
            
            const diagnosticoPrincipal = historiaPrevia.diagnosticos[0];
            if (diagnosticoPrincipal) {
                setTimeout(function() {
                    cargarDiagnosticoPrincipalConDatos(diagnosticoPrincipal);
                }, 100);
            }
            
            if (historiaPrevia.diagnosticos.length > 1) {
                for (let i = 1; i < historiaPrevia.diagnosticos.length; i++) {
                    setTimeout(function() {
                        agregarDiagnosticoAdicionalConDatos(historiaPrevia.diagnosticos[i]);
                    }, (i + 1) * 200);
                }
            }
        }

        // ✅ CARGAR CUPS
        if (historiaPrevia.cups && historiaPrevia.cups.length > 0) {
            console.log('🏥 Cargando CUPS previos:', historiaPrevia.cups.length);
            historiaPrevia.cups.forEach(function(cups, index) {
                setTimeout(function() {
                    agregarCupsConDatos(cups);
                }, index * 200);
            });
        }

        // ✅ CARGAR TALLA
        if (historiaPrevia.talla) {
            $('#talla').val(historiaPrevia.talla);
            console.log('📏 Talla cargada:', historiaPrevia.talla);
        }

        // ✅ CARGAR ANTECEDENTES PERSONALES - HTA
        if (historiaPrevia.hipertension_arterial_personal) {
            $('input[name="hipertension_arterial_personal"][value="' + historiaPrevia.hipertension_arterial_personal + '"]').prop('checked', true).trigger('change');
            if (historiaPrevia.obs_hipertension_arterial_personal) {
                $('#obs_hipertension_arterial_personal').val(historiaPrevia.obs_hipertension_arterial_personal);
            }
        }

        // ✅ CARGAR ANTECEDENTES PERSONALES - DM
        if (historiaPrevia.diabetes_mellitus_personal) {
            $('input[name="diabetes_mellitus_personal"][value="' + historiaPrevia.diabetes_mellitus_personal + '"]').prop('checked', true).trigger('change');
            if (historiaPrevia.obs_diabetes_mellitus_personal) {
                $('#obs_diabetes_mellitus_personal').val(historiaPrevia.obs_diabetes_mellitus_personal);
            }
        }

        // ✅ CARGAR CLASIFICACIONES
        if (historiaPrevia.clasificacion_estado_metabolico) {
            $('#ClasificacionEstadoMetabolico').val(historiaPrevia.clasificacion_estado_metabolico);
        }
        if (historiaPrevia.clasificacion_hta) {
            $('#clasificacion_hta').val(historiaPrevia.clasificacion_hta);
        }
        if (historiaPrevia.clasificacion_dm) {
            $('#clasificacion_dm').val(historiaPrevia.clasificacion_dm);
        }
        if (historiaPrevia.clasificacion_rcv) {
            $('#clasificacion_rcv').val(historiaPrevia.clasificacion_rcv);
        }
        if (historiaPrevia.clasificacion_erc_estado) {
            $('#clasificacion_erc_estado').val(historiaPrevia.clasificacion_erc_estado);
        }
        if (historiaPrevia.clasificacion_erc_estadodos) {   
            $('#clasificacion_erc_estadodos').val(historiaPrevia.clasificacion_erc_estadodos);
        }


        if (historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente) {
            $('#clasificacion_erc_categoria_ambulatoria_persistente').val(historiaPrevia.clasificacion_erc_categoria_ambulatoria_persistente);
        }

        // ✅ CARGAR TASAS DE FILTRACIÓN
        if (historiaPrevia.tasa_filtracion_glomerular_ckd_epi) {
            $('#tasa_filtracion_glomerular_ckd_epi').val(historiaPrevia.tasa_filtracion_glomerular_ckd_epi);
        }
        if (historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault) {
            $('#tasa_filtracion_glomerular_gockcroft_gault').val(historiaPrevia.tasa_filtracion_glomerular_gockcroft_gault);
        }

        // ✅ CARGAR TEST DE MORISKY
        if (historiaPrevia.test_morisky_olvida_tomar_medicamentos) {
            $('input[name="test_morisky_olvida_tomar_medicamentos"][value="' + historiaPrevia.test_morisky_olvida_tomar_medicamentos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_toma_medicamentos_hora_indicada) {
            $('input[name="test_morisky_toma_medicamentos_hora_indicada"][value="' + historiaPrevia.test_morisky_toma_medicamentos_hora_indicada + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_cuando_esta_bien_deja_tomar_medicamentos) {
            $('input[name="test_morisky_cuando_esta_bien_deja_tomar_medicamentos"][value="' + historiaPrevia.test_morisky_cuando_esta_bien_deja_tomar_medicamentos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_siente_mal_deja_tomarlos) {
            $('input[name="test_morisky_siente_mal_deja_tomarlos"][value="' + historiaPrevia.test_morisky_siente_mal_deja_tomarlos + '"]').prop('checked', true);
        }
        if (historiaPrevia.test_morisky_valoracio_psicologia) {
            $('input[name="test_morisky_valoracio_psicologia"][value="' + historiaPrevia.test_morisky_valoracio_psicologia + '"]').prop('checked', true);
        }
        if (historiaPrevia.adherente) {
            $('input[name="adherente"][value="' + historiaPrevia.adherente + '"]').prop('checked', true);
        }

        // ✅ RECALCULAR ADHERENCIA
        setTimeout(function() {
            calcularAdherenciaMorisky();
        }, 1000);

        // ✅ CARGAR CAMPOS DE EDUCACIÓN
        const camposEducacion = [
            'alimentacion',
            'disminucion_consumo_sal_azucar',
            'fomento_actividad_fisica',
            'importancia_adherencia_tratamiento',
            'consumo_frutas_verduras',
            'manejo_estres',
            'disminucion_consumo_cigarrillo',
            'disminucion_peso'
        ];

        camposEducacion.forEach(function(campo) {
            if (historiaPrevia[campo]) {
                $('input[name="' + campo + '"][value="' + historiaPrevia[campo] + '"]').prop('checked', true);
                console.log('✅ Campo educación cargado:', campo, '=', historiaPrevia[campo]);
            }
        });

        console.log('✅ Datos previos cargados exitosamente');

    } catch (error) {
        console.error('❌ Error cargando datos previos:', error);
    }
}

// ============================================
// ✅ DOCUMENT.READY
// ============================================
$(document).ready(function() {
    console.log('🔍 Iniciando script de control.blade.php');
    console.log('🔍 Datos de la vista:', {
        especialidad: '{{ $especialidad ?? "N/A" }}',
        tipo_consulta: '{{ $tipo_consulta ?? "N/A" }}',
        tiene_historia_previa: {{ isset($historiaPrevia) && !empty($historiaPrevia) ? 'true' : 'false' }}
    });

    // ✅ CARGAR DATOS PREVIOS SOLO PARA MEDICINA GENERAL
    @if(isset($historiaPrevia) && !empty($historiaPrevia) && ($especialidad ?? '') === 'MEDICINA GENERAL')
        console.log('🔄 Cargando datos previos para Medicina General');
        const historiaPrevia = @json($historiaPrevia);
        console.log('📦 Datos:', historiaPrevia);
        
        setTimeout(function() {
            cargarDatosPreviosMedicinaGeneral(historiaPrevia);
        }, 500);
    @else
        console.log('ℹ️ No se cargan datos previos', {
            tiene_historia: {{ isset($historiaPrevia) && !empty($historiaPrevia) ? 'true' : 'false' }},
            es_medicina_general: {{ ($especialidad ?? '') === 'MEDICINA GENERAL' ? 'true' : 'false' }}
        });
    @endif

    // ============================================
    // ✅ CÁLCULO AUTOMÁTICO DE IMC
    // ============================================
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
        if (imc < 25) return 'Adecuado';
        if (imc < 30) return 'Sobrepeso';
        if (imc < 35) return 'Obesidad grado 1';
        if (imc < 40) return 'Obesidad grado 2';
        return 'Obesidad grado 3';
    }

    // ✅ CALCULAR IMC AL CARGAR SI YA HAY DATOS
    if ($('#peso').val() && $('#talla').val()) {
        calcularIMC();
    }

    // ============================================
    // ✅ MOSTRAR/OCULTAR DESCRIPCIÓN LESIÓN ÓRGANO BLANCO
    // ============================================
    $('input[name="lesion_organo_blanco"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#descripcion_lesion_container').show();
        } else {
            $('#descripcion_lesion_container').hide();
            $('#descripcion_lesion_organo_blanco').val('');
        }
    });

    // ============================================
    // ✅ CÁLCULO AUTOMÁTICO DE ADHERENCIA TEST MORISKY
    // ============================================
    $(document).on('change', '.test-morisky-input', function() {
        calcularAdherenciaMorisky();
    });

    // ✅ CALCULAR ADHERENCIA AL CARGAR SI YA HAY DATOS
    if ($('.test-morisky-input:checked').length > 0) {
        calcularAdherenciaMorisky();
    }

    // ============================================
    // ✅ BÚSQUEDA DE DIAGNÓSTICOS PRINCIPAL
    // ============================================
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

    // ============================================
    // ✅ CERRAR DROPDOWNS AL HACER CLICK FUERA
    // ============================================
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // ============================================
    // ✅ AGREGAR DIAGNÓSTICO ADICIONAL
    // ============================================
    $('#agregar_diagnostico_adicional').on('click', function() {
        agregarDiagnosticoAdicional();
    });

    // ✅ ELIMINAR DIAGNÓSTICO ADICIONAL
    $(document).on('click', '.eliminar-diagnostico-adicional', function() {
        $(this).closest('.diagnostico-adicional-item').remove();
    });

    // ============================================
    // ✅ AGREGAR MEDICAMENTO
    // ============================================
    $('#agregar_medicamento').on('click', function() {
        agregarMedicamento();
    });

    // ✅ ELIMINAR MEDICAMENTO
    $(document).on('click', '.eliminar-medicamento', function() {
        $(this).closest('.medicamento-item').remove();
    });

    // ============================================
    // ✅ AGREGAR REMISIÓN
    // ============================================
    $('#agregar_remision').on('click', function() {
        agregarRemision();
    });

    // ✅ ELIMINAR REMISIÓN
    $(document).on('click', '.eliminar-remision', function() {
        $(this).closest('.remision-item').remove();
    });

    // ============================================
    // ✅ AGREGAR CUPS
    // ============================================
    $('#agregar_cups').on('click', function() {
        agregarCups();
    });

    // ✅ ELIMINAR CUPS
    $(document).on('click', '.eliminar-cups', function() {
        $(this).closest('.cups-item').remove();
    });

    // ============================================
    // ✅ RECONOCIMIENTO DE VOZ PARA OBSERVACIONES
    // ============================================
    const botonMicrofono = document.getElementById('microfono');
    const campoTexto = document.getElementById('observaciones_generales');

    let recognition = null;

    if (botonMicrofono && campoTexto) {
        botonMicrofono.addEventListener('click', function() {
            if (!recognition) {
                if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
                    recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();

                    recognition.lang = 'es-ES';

                    recognition.onstart = function() {
                        botonMicrofono.classList.add('active');
                    };

                    recognition.onend = function() {
                        botonMicrofono.classList.remove('active');
                    };

                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        campoTexto.value += ' ' + transcript;
                    };

                    recognition.start();
                } else {
                    alert('El reconocimiento de voz no es compatible con este navegador.');
                }
            } else {
                recognition.stop();
                recognition = null;
            }
        });
    }

    // ============================================
    // ✅ MANEJAR ANTECEDENTES PERSONALES - HTA
    // ============================================
    $('input[name="hipertension_arterial_personal"]').on('change', function() {
        const $obsField = $('#obs_hipertension_arterial_personal');
        if ($(this).val() === 'SI') {
            $obsField.prop('disabled', false).attr('placeholder', 'Describa los antecedentes de hipertensión arterial...');
        } else {
            $obsField.prop('disabled', true).val('').attr('placeholder', '');
        }
    });

    // ============================================
    // ✅ MANEJAR ANTECEDENTES PERSONALES - DIABETES
    // ============================================
    $('input[name="diabetes_mellitus_personal"]').on('change', function() {
        const $obsField = $('#obs_diabetes_mellitus_personal');
        if ($(this).val() === 'SI') {
            $obsField.prop('disabled', false).attr('placeholder', 'Describa los antecedentes de diabetes mellitus...');
        } else {
            $obsField.prop('disabled', true).val('').attr('placeholder', '');
        }
    });

    // ✅ INICIALIZAR ESTADO AL CARGAR LA PÁGINA
    if ($('input[name="hipertension_arterial_personal"]:checked').val() === 'SI') {
        $('#obs_hipertension_arterial_personal').prop('disabled', false);
    } else {
        $('#obs_hipertension_arterial_personal').prop('disabled', true);
    }

    if ($('input[name="diabetes_mellitus_personal"]:checked').val() === 'SI') {
        $('#obs_diabetes_mellitus_personal').prop('disabled', false);
    } else {
        $('#obs_diabetes_mellitus_personal').prop('disabled', true);
    }

    

   $('#historiaClinicaForm').on('submit', function(e) {
    e.preventDefault();
    
    console.log('📤 Iniciando envío del formulario...');
    
    // ✅ OBTENER CITA UUID ANTES DE TODO
    const citaUuid = $('input[name="cita_uuid"]').val();
    
    console.log('🔍 Cita UUID detectado:', citaUuid);
    
    // ✅ HABILITAR CAMPO ADHERENTE ANTES DEL ENVÍO
    $('input[name="adherente"]').prop('readonly', false);
    
    // Validaciones específicas para control
    if (!validarFormularioControl()) {
        $('input[name="adherente"]').prop('readonly', true);
        console.log('❌ Validación fallida');
        return;
    }
    
    console.log('✅ Validación exitosa, preparando envío...');
    
    // Mostrar loading
    $('#loading_overlay').show();
    
    // Preparar datos
    const formData = new FormData(this);
    
    // ✅ VARIABLE PARA CONTROLAR SI YA SE PROCESÓ LA RESPUESTA
    let respuestaProcesada = false;
    
    // ✅ TIMEOUT MEJORADO CON CONTROL DE ESTADO
    const timeoutId = setTimeout(function() {
        if (respuestaProcesada) {
            console.log('⏰ Timeout ignorado - respuesta ya procesada');
            return;
        }
        
        console.log('⏰ Timeout alcanzado (15s), procesando...');
        respuestaProcesada = true;
        
        $('#loading_overlay').hide();
        
        // ✅ DISPARAR EVENTO INCLUSO EN TIMEOUT
        dispararEventoHistoriaGuardada(citaUuid, null, false);
        
        Swal.fire({
            icon: 'info',
            title: 'Procesando...',
            text: 'La historia clínica se está guardando. Será redirigido al cronograma.',
            timer: 2000,
            showConfirmButton: false,
            allowOutsideClick: false
        }).then(() => {
            window.location.href = '{{ route("cronograma.index") }}';
        });
    }, 15000); // 15 segundos de timeout
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000, // ✅ TIMEOUT DE 30 SEGUNDOS
        success: function(response) {
            // ✅ VERIFICAR SI YA SE PROCESÓ
            if (respuestaProcesada) {
                console.log('⚠️ Respuesta ignorada - ya se procesó por timeout');
                return;
            }
            
            respuestaProcesada = true;
            clearTimeout(timeoutId);
            
            console.log('✅ Respuesta recibida:', response);
            
            // ✅ OCULTAR LOADING INMEDIATAMENTE
            $('#loading_overlay').hide();
            
            if (response.success) {
                // ✅✅✅ DISPARAR EVENTO DE HISTORIA GUARDADA ✅✅✅
                dispararEventoHistoriaGuardada(
                    citaUuid,
                    response.historia_uuid || null,
                    response.offline || false
                );
                
                // ✅ MOSTRAR MENSAJE Y REDIRIGIR SIN ESPERAR CONFIRMACIÓN
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message || 'Control guardado exitosamente. Cita marcada como atendida.',
                    timer: 2000,
                    showConfirmButton: false,
                    allowOutsideClick: false
                }).then(() => {
                    // ✅ REDIRIGIR DESPUÉS DEL MENSAJE
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        window.location.href = '{{ route("cronograma.index") }}';
                    }
                });
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Error guardando el control',
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false
                });
            }
        },
        error: function(xhr, status, error) {
            // ✅ VERIFICAR SI YA SE PROCESÓ
            if (respuestaProcesada) {
                console.log('⚠️ Error ignorado - ya se procesó por timeout');
                return;
            }
            
            respuestaProcesada = true;
            clearTimeout(timeoutId);
            
            console.error('❌ Error en AJAX:', {
                status: xhr.status,
                statusText: status,
                error: error,
                responseText: xhr.responseText
            });
            
            // ✅ OCULTAR LOADING INMEDIATAMENTE
            $('#loading_overlay').hide();
            
            let errorMessage = 'Error interno del servidor';
            let shouldRedirect = false;
            
            if (status === 'timeout') {
                errorMessage = 'La solicitud tardó demasiado. La historia clínica puede haberse guardado correctamente.';
                shouldRedirect = true;
                // ✅ DISPARAR EVENTO INCLUSO EN TIMEOUT
                dispararEventoHistoriaGuardada(citaUuid, null, false);
                
            } else if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    errorMessage = Object.values(errors).flat().join('\n');
                }
            } else if (xhr.responseJSON?.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.status === 0) {
                errorMessage = 'No se pudo conectar con el servidor. Verifique su conexión.';
            }
            
            Swal.fire({
                icon: shouldRedirect ? 'warning' : 'error',
                title: shouldRedirect ? 'Atención' : 'Error',
                html: errorMessage.replace(/\n/g, '<br>'),
                confirmButtonText: 'Entendido',
                allowOutsideClick: false
            }).then(() => {
                if (shouldRedirect) {
                    window.location.href = '{{ route("cronograma.index") }}';
                }
            });
        },
        complete: function() {
            console.log('🏁 Petición AJAX completada');
            
            // ✅ ASEGURAR QUE EL LOADING SE OCULTE
            setTimeout(function() {
                $('#loading_overlay').hide();
            }, 100);
            
            // ✅ VOLVER A DESHABILITAR DESPUÉS DEL ENVÍO
            $('input[name="adherente"]').prop('readonly', true);
        }
    });
});
    // ============================================
    // ✅ FUNCIÓN DE VALIDACIÓN ESPECÍFICA PARA CONTROL
    // ============================================
    function validarFormularioControl() {
        // Validar peso
        if (!$('#peso').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar el peso del paciente'
            });
            $('#peso').focus();
            return false;
        }
        
        // Validar talla
        if (!$('#talla').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la talla del paciente'
            });
            $('#talla').focus();
            return false;
        }
        
        // Validar perímetro abdominal
        if (!$('#perimetro_abdominal').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar el perímetro abdominal'
            });
            $('#perimetro_abdominal').focus();
            return false;
        }
        
        // Validar presión sistólica
        if (!$('#ef_pa_sistolica_sentado_pie').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la presión arterial sistólica'
            });
            $('#ef_pa_sistolica_sentado_pie').focus();
            return false;
        }
        
        // Validar presión diastólica
        if (!$('#ef_pa_distolica_sentado_pie').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la presión arterial diastólica'
            });
            $('#ef_pa_distolica_sentado_pie').focus();
            return false;
        }
        
        // Validar frecuencia cardíaca
        if (!$('#ef_frecuencia_fisica').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la frecuencia cardíaca'
            });
            $('#ef_frecuencia_fisica').focus();
            return false;
        }
        
        // Validar frecuencia respiratoria
        if (!$('#ef_frecuencia_respiratoria').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la frecuencia respiratoria'
            });
            $('#ef_frecuencia_respiratoria').focus();
            return false;
        }
        
        // Validar observaciones generales
        if (!$('#observaciones_generales').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar las observaciones generales'
            });
            $('#observaciones_generales').focus();
            return false;
        }
        
        // Validar diagnóstico principal
        if (!$('#idDiagnostico').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe seleccionar un diagnóstico principal'
            });
            $('#buscar_diagnostico').focus();
            return false;
        }
        
        // Validar campos de examen físico requeridos
        const camposExamenFisico = [
            'ef_cabeza', 'ef_agudeza_visual', 'oidos', 'nariz_senos_paranasales', 
            'cavidad_oral', 'ef_cuello', 'cardio_respiratorio', 'ef_mamas', 
            'gastrointestinal', 'ef_genito_urinario', 'musculo_esqueletico', 
            'ef_piel_anexos_pulsos', 'inspeccion_sensibilidad_pies', 
            'ef_sistema_nervioso', 'capacidad_congnitiva_orientacion', 
            'ef_reflejo_aquiliar', 'ef_reflejo_patelar', 'dislipidemia'
        ];
        
        for (let campo of camposExamenFisico) {
            if (!$('#' + campo).val().trim()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: `Debe completar el campo ${$('#' + campo).prev('label').text().replace(' *', '')}`
                });
                $('#' + campo).focus();
                return false;
            }
        }
        
        // Validar campos de revisión por sistemas
        const camposRevisionSistemas = [
            'general', 'cabeza', 'respiratorio', 'cardiovascular', 
            'gastrointestinal', 'osteoatromuscular', 'snc'
        ];
        
        for (let campo of camposRevisionSistemas) {
            if (!$('#' + campo).val().trim()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: `Debe completar el campo ${$('#' + campo).prev('label').text().replace(' *', '')}`
                });
                $('#' + campo).focus();
                return false;
            }
        }
        
        // Validar campos de exámenes
        const camposExamenes = ['hcElectrocardiograma', 'hcEcocardiograma', 'hcEcografiaRenal'];
        
        for (let campo of camposExamenes) {
            if (!$('#' + campo).val().trim()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: `Debe completar el campo ${$('#' + campo).prev('label').text().replace(' *', '')}`
                });
                $('#' + campo).focus();
                return false;
            }
        }
        
        // Validar campos de clasificaciones
        const camposClasificaciones = [
            'ClasificacionEstadoMetabolico', 'clasificacion_hta', 'clasificacion_dm', 
            'clasificacion_erc_estado',  'clasificacion_erc_categoria_ambulatoria_persistente', 
            'clasificacion_rcv', 'clasificacion_erc_estadodos'
        ];
        
        for (let campo of camposClasificaciones) {
            if (!$('#' + campo).val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: `Debe seleccionar ${$('#' + campo).prev('label').text().replace(' *', '')}`
                });
                $('#' + campo).focus();
                return false;
            }
        }
        
        // ✅ VALIDACIÓN CORRECTA PARA RADIO BUTTONS
        const camposEducacion = [
            'alimentacion', 
            'fomento_actividad_fisica', 
            'importancia_adherencia_tratamiento',
            'disminucion_consumo_sal_azucar', 
            'disminucion_consumo_cigarrillo', 
            'disminucion_peso', 
            'consumo_frutas_verduras',
            'manejo_estres'
        ];

        for (let campo of camposEducacion) {
            // ✅ Verificar si algún radio button está seleccionado
            if (!$('input[name="'
             + campo + '"]:checked').length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: `Debe seleccionar una opción para: ${campo.replace(/_/g, ' ').toUpperCase()}`
                });
                $('input[name="' + campo + '"]').first().focus();
                return false;
            }
        }
        
        // Validar test de Morisky
        const preguntasMorisky = [
            'test_morisky_olvida_tomar_medicamentos',
            'test_morisky_toma_medicamentos_hora_indicada',
            'test_morisky_cuando_esta_bien_deja_tomar_medicamentos',
            'test_morisky_siente_mal_deja_tomarlos',
            'test_morisky_valoracio_psicologia'
        ];
        
        for (let pregunta of preguntasMorisky) {
            if (!$('input[name="' + pregunta + '"]:checked').length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campo requerido',
                    text: 'Debe responder todas las preguntas del Test de Morisky'
                });
                $('input[name="' + pregunta + '"]').first().focus();
                return false;
            }
        }
        
        // Validar finalidad
        if (!$('#finalidad').val().trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'Debe ingresar la finalidad de la consulta'
            });
            $('#finalidad').focus();
            return false;
        }
        
        return true;
    }

}); // ✅ FIN DOCUMENT.READY
</script>
@endpush


