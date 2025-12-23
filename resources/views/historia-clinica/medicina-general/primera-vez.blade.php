@extends('layouts.app')

@section('title', 'Nueva Historia Clínica Cardiovascular')

@section('content')
<div class="container-fluid">
    {{-- ✅ HEADER CON INFORMACIÓN DEL PACIENTE --}}
    @include('historia-clinica.partials.header-paciente')

    {{-- ✅ FORMULARIO PRINCIPAL --}}
    <form id="historiaClinicaForm" method="POST" action="{{ route('historia-clinica.store') }}">
        @csrf
        <input type="hidden" name="cita_uuid" value="{{ $cita['uuid'] }}">
        <input type="hidden" name="tipo_consulta" value="PRIMERA VEZ">
        <input type="hidden" name="especialidad" value="MEDICINA GENERAL">
        
        {{-- ✅ TODAS LAS SECCIONES COMO PARTIALS --}}
        @include('historia-clinica.partials.datos-basicos')
        @include('historia-clinica.partials.acudiente')
        @include('historia-clinica.partials.historia-clinica-basica')
        @include('historia-clinica.partials.discapacidades')
        @include('historia-clinica.partials.drogodependencia')
        @include('historia-clinica.partials.medidas-antropometricas')
        @include('historia-clinica.partials.antecedentes-familiares')
        @include('historia-clinica.partials.antecedentes-personales')
        @include('historia-clinica.partials.test-morisky')
        @include('historia-clinica.partials.otros-tratamientos')
        @include('historia-clinica.partials.revision-sistemas')
        @include('historia-clinica.partials.signos-vitales')
        @include('historia-clinica.partials.examen-fisico')
        @include('historia-clinica.partials.factores-riesgo')
        @include('historia-clinica.partials.examenes')
        @include('historia-clinica.partials.clasificaciones')
        @include('historia-clinica.partials.educacion')
        @include('historia-clinica.partials.diagnostico-principal')
        @include('historia-clinica.partials.diagnosticos-adicionales')
        @include('historia-clinica.partials.medicamentos-section')
        @include('historia-clinica.partials.remisiones-section')
        @include('historia-clinica.partials.cups-section')
        @include('historia-clinica.partials.observaciones-generales')
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
@include('historia-clinica.partials.scripts')
@endpush
