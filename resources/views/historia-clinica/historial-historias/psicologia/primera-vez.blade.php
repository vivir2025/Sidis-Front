{{-- resources/views/historia-clinica/historial-historias/psicologia/primera-vez.blade.php --}}
@extends('layouts.app')

@section('title', 'Historia Clínica Psicología - Primera Vez')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                {{-- HEADER --}}
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-brain"></i>
                            Historia Clínica - Psicología (Primera Vez)
                        </h4>
                        <div>
                            <button onclick="window.print()" class="btn btn-light btn-sm">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="{{ route('historia-clinica.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ✅ INFORMACIÓN DEL PACIENTE --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-user"></i> Información del Paciente
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Nombre Completo:</strong> {{ $historia['cita']['paciente']['nombre_completo'] ?? 'N/A' }}</p>
                            <p><strong>Documento:</strong> {{ $historia['cita']['paciente']['tipo_documento'] ?? 'CC' }} {{ $historia['cita']['paciente']['documento'] ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Fecha de Nacimiento:</strong> {{ $historia['cita']['paciente']['fecha_nacimiento'] ?? 'N/A' }}</p>
                            <p><strong>Sexo:</strong> {{ $historia['cita']['paciente']['sexo'] ?? 'N/A' }}</p>
                        </div>
                    </div>

                    {{-- ✅ INFORMACIÓN DE LA CITA --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-calendar-check"></i> Información de la Cita
                            </h5>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fecha:</strong> {{ $historia['cita']['fecha'] ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Hora:</strong> {{ $historia['cita']['hora'] ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Tipo de Consulta:</strong> 
                                <span class="badge bg-info">{{ $historia['tipo_consulta'] ?? 'PRIMERA VEZ' }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Profesional:</strong> {{ $historia['cita']['agenda']['usuario_medico']['nombre_completo'] ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Especialidad:</strong> {{ $historia['especialidad'] ?? 'PSICOLOGÍA' }}</p>
                        </div>
                    </div>

                    {{-- ✅ MOTIVO DE CONSULTA --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-comment-medical"></i> Motivo de Consulta
                            </h5>
                        </div>
                        <div class="col-12">
                            <p>{{ $historia['motivo_consulta'] ?? 'No registrado' }}</p>
                        </div>
                    </div>

                    {{-- ✅ ACUDIENTE (SI EXISTE) --}}
                    @if(!empty($historia['acompanante']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-user-friends"></i> Información del Acudiente
                            </h5>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Nombre:</strong> {{ $historia['acompanante'] }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Parentesco:</strong> {{ $historia['acu_parentesco'] ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Teléfono:</strong> {{ $historia['acu_telefono'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ CAMPOS ESPECÍFICOS DE PSICOLOGÍA - PRIMERA VEZ --}}
                    @if(!empty($historia['complementaria']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-brain"></i> Evaluación Psicológica (Primera Vez)
                            </h5>
                        </div>

                        {{-- ESTRUCTURA FAMILIAR --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-users"></i> Estructura Familiar</h6>
                            <p>{{ $historia['complementaria']['estructura_familiar'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- RED DE APOYO --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-hands-helping"></i> Red de Apoyo</h6>
                            <p>{{ $historia['complementaria']['psicologia_red_apoyo'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- COMPORTAMIENTO EN CONSULTA --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-user-check"></i> Comportamiento en Consulta</h6>
                            <p>{{ $historia['complementaria']['psicologia_comportamiento_consulta'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- TRATAMIENTO ACTUAL Y ADHERENCIA --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-pills"></i> Tratamiento Actual y Adherencia</h6>
                            <p>{{ $historia['complementaria']['psicologia_tratamiento_actual_adherencia'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- DESCRIPCIÓN DEL PROBLEMA --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-exclamation-circle"></i> Descripción del Problema</h6>
                            <p>{{ $historia['complementaria']['psicologia_descripcion_problema'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- ANÁLISIS Y CONCLUSIONES --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-clipboard-check"></i> Análisis y Conclusiones</h6>
                            <p>{{ $historia['complementaria']['analisis_conclusiones'] ?? 'No registrado' }}</p>
                        </div>

                        {{-- PLAN DE INTERVENCIÓN --}}
                        <div class="col-12 mb-3">
                            <h6 class="text-secondary"><i class="fas fa-tasks"></i> Plan de Intervención y Recomendaciones</h6>
                            <p>{{ $historia['complementaria']['psicologia_plan_intervencion_recomendacion'] ?? 'No registrado' }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ DIAGNÓSTICOS --}}
                    @if(!empty($historia['diagnosticos']) && count($historia['diagnosticos']) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-stethoscope"></i> Diagnósticos
                            </h5>
                        </div>
                        <div class="col-12">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Código</th>
                                        <th>Diagnóstico</th>
                                        <th>Tipo Diagnóstico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historia['diagnosticos'] as $diagnostico)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $diagnostico['tipo'] === 'PRINCIPAL' ? 'bg-danger' : 'bg-secondary' }}">
                                                {{ $diagnostico['tipo'] }}
                                            </span>
                                        </td>
                                        <td>{{ $diagnostico['diagnostico']['codigo'] ?? 'N/A' }}</td>
                                        <td>{{ $diagnostico['diagnostico']['nombre'] ?? 'N/A' }}</td>
                                        <td>{{ $diagnostico['tipo_diagnostico'] ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ MEDICAMENTOS (SOLO PRIMERA VEZ) --}}
                    @if(!empty($historia['medicamentos']) && count($historia['medicamentos']) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-pills"></i> Medicamentos Formulados
                            </h5>
                        </div>
                        <div class="col-12">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Principio Activo</th>
                                        <th>Cantidad</th>
                                        <th>Dosis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historia['medicamentos'] as $medicamento)
                                    <tr>
                                        <td>{{ $medicamento['medicamento']['nombre'] ?? 'N/A' }}</td>
                                        <td>{{ $medicamento['medicamento']['principio_activo'] ?? 'N/A' }}</td>
                                        <td>{{ $medicamento['cantidad'] ?? 'N/A' }}</td>
                                        <td>{{ $medicamento['dosis'] ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ REMISIONES (SOLO PRIMERA VEZ) --}}
                    @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-share-square"></i> Remisiones
                            </h5>
                        </div>
                        <div class="col-12">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Remisión</th>
                                        <th>Tipo</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historia['remisiones'] as $remision)
                                    <tr>
                                        <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                                        <td>{{ $remision['remision']['tipo'] ?? 'N/A' }}</td>
                                        <td>{{ $remision['observacion'] ?? 'Sin observaciones' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ OBSERVACIONES GENERALES --}}
                    @if(!empty($historia['observaciones_generales']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-clipboard"></i> Observaciones Generales
                            </h5>
                        </div>
                        <div class="col-12">
                            <p>{{ $historia['observaciones_generales'] }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- ✅ METADATOS --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-light">
                                <small>
                                    <strong>Fecha de Creación:</strong> {{ $historia['created_at'] ?? 'N/A' }} | 
                                    <strong>Última Actualización:</strong> {{ $historia['updated_at'] ?? 'N/A' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ✅ ESTILOS PARA IMPRESIÓN --}}
<style>
    @media print {
        .btn, .card-header .d-flex > div {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            font-size: 12px;
        }
    }
</style>
@endsection
