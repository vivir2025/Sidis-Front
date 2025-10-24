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
        
        {{-- ✅ TODAS LAS SECCIONES COMO PARTIALS --}}
        @include('historia-clinica.partials.datos-basicos')
        @include('historia-clinica.partials.acudiente')
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
        </div>
    </div>
</div>
        @include('historia-clinica.partials.medidas-antropometricas')
        @include('historia-clinica.partials.evaluaciones')
        @include('historia-clinica.partials.plan-tratamiento')
        @include('historia-clinica.partials.diagnostico-principal')
        @include('historia-clinica.partials.diagnosticos-adicionales')
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
@include('historia-clinica.partials.scriptsfisio')
@endpush
