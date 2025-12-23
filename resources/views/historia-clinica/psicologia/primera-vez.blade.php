@extends('layouts.app')

@section('title', 'Nueva Historia Clínica de Psicología')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')

    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="PRIMERA VEZ">
        <input type="hidden" name="especialidad" value="PSICOLOGIA">
        <input type="hidden" name="paciente_uuid" value="{{ $cita['paciente_uuid'] ?? $cita['paciente']['uuid'] }}">
        <input type="hidden" name="usuario_id" value="{{ $usuario['id'] }}">
        <input type="hidden" name="sede_id" value="{{ $usuario['sede_id'] }}">
        
        {{-- ✅ SECCIONES COMUNES --}}
        @include('historia-clinica.partials.datos-basicos')
        @include('historia-clinica.partials.acudiente')

        {{-- ✅ SECCIÓN ESPECÍFICA DE PSICOLOGÍA --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-brain me-2"></i>
                    Evaluación Psicológica
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                     <div class="col-md-12 mb-3">
                <label for="motivo" class="form-label">
                    Motivo de Consulta <span class="text-danger">*</span>
                </label>
                <textarea 
                    class="form-control" 
                    id="motivo" 
                    name="motivo" 
                    rows="3" 
                    required 
                    placeholder="Describa el motivo principal de la consulta"></textarea>
                <small class="text-muted">Campo obligatorio</small>
            </div>
            
                    {{-- ✅ Estructura Familiar --}}
                    <div class="col-md-6 mb-3">
                        <label for="estructura_familiar" class="form-label">
                            Estructura Familiar
                        </label>
                        <textarea 
                            class="form-control" 
                            id="estructura_familiar" 
                            name="estructura_familiar" 
                            rows="3" 
                            placeholder="Describe la composición familiar del paciente"></textarea>
                        <small class="text-muted">Ej: Nuclear, monoparental, extensa, etc.</small>
                    </div>

                    {{-- ✅ Red de Apoyo --}}
                    <div class="col-md-6 mb-3">
                        <label for="psicologia_red_apoyo" class="form-label">
                            Red de Apoyo
                        </label>
                        <textarea 
                            class="form-control" 
                            id="psicologia_red_apoyo" 
                            name="psicologia_red_apoyo" 
                            rows="3" 
                            placeholder="Personas o instituciones de apoyo"></textarea>
                        <small class="text-muted">Familia, amigos, instituciones, etc.</small>
                    </div>

                    {{-- ✅ Comportamiento en Consulta --}}
                    <div class="col-md-6 mb-3">
                        <label for="psicologia_comportamiento_consulta" class="form-label">
                            Comportamiento en Consulta
                        </label>
                        <textarea 
                            class="form-control" 
                            id="psicologia_comportamiento_consulta" 
                            name="psicologia_comportamiento_consulta" 
                            rows="3" 
                            placeholder="Observaciones del comportamiento durante la consulta"></textarea>
                        <small class="text-muted">Actitud, colaboración, comunicación, etc.</small>
                    </div>

                    {{-- ✅ Tratamiento Actual y Adherencia --}}
                    <div class="col-md-6 mb-3">
                        <label for="psicologia_tratamiento_actual_adherencia" class="form-label">
                            Tratamiento Actual y Adherencia
                        </label>
                        <textarea 
                            class="form-control" 
                            id="psicologia_tratamiento_actual_adherencia" 
                            name="psicologia_tratamiento_actual_adherencia" 
                            rows="3" 
                            placeholder="Tratamientos actuales y nivel de adherencia"></textarea>
                        <small class="text-muted">Medicamentos, terapias, cumplimiento, etc.</small>
                    </div>

                    {{-- ✅ Descripción del Problema --}}
                    <div class="col-md-12 mb-3">
                        <label for="psicologia_descripcion_problema" class="form-label">
                            Descripción del Problema <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="psicologia_descripcion_problema" 
                            name="psicologia_descripcion_problema" 
                            rows="4" 
                            required 
                            placeholder="Descripción detallada del problema psicológico"></textarea>
                        <small class="text-muted">Campo obligatorio - Describa el motivo principal de consulta</small>
                    </div>

                    {{-- ✅ Análisis y Conclusiones --}}
                    <div class="col-md-12 mb-3">
                        <label for="analisis_conclusiones" class="form-label">
                            Análisis y Conclusiones
                        </label>
                        <textarea 
                            class="form-control" 
                            id="analisis_conclusiones" 
                            name="analisis_conclusiones" 
                            rows="4" 
                            placeholder="Análisis clínico y conclusiones del caso"></textarea>
                        <small class="text-muted">Impresión diagnóstica, factores de riesgo, pronóstico, etc.</small>
                    </div>

                    {{-- ✅ Plan de Intervención y Recomendaciones --}}
                    <div class="col-md-12 mb-3">
                        <label for="psicologia_plan_intervencion_recomendacion" class="form-label">
                            Plan de Intervención y Recomendaciones <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="psicologia_plan_intervencion_recomendacion" 
                            name="psicologia_plan_intervencion_recomendacion" 
                            rows="4" 
                            required 
                            placeholder="Plan terapéutico y recomendaciones para el paciente"></textarea>
                        <small class="text-muted">Campo obligatorio - Objetivos, técnicas, frecuencia de sesiones, etc.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ SECCIONES COMUNES --}}
        @include('historia-clinica.partials.diagnostico-principal')
        @include('historia-clinica.partials.diagnosticos-adicionales')
        @include('historia-clinica.partials.medicamentos-section')
        @include('historia-clinica.partials.remisiones-section')
        @include('historia-clinica.partials.botones-accion')
    </form>
</div>

{{-- ✅ TEMPLATES PARA ELEMENTOS DINÁMICOS --}}
@include('historia-clinica.partials.templates')

{{-- ✅ LOADING OVERLAY --}}
@include('historia-clinica.partials.loading-overlay')
@endsection

@push('styles')
@include('historia-clinica.partials.styles')
@endpush

@push('scripts')
@include('historia-clinica.partials.scriptspsico')
@endpush
