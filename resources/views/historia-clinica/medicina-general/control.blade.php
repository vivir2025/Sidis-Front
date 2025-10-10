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
                            <label for="ClasificacionEstadoMetabolico" class="form-label">Clasificación Estado Metabólico <span class="text-danger">*</span></label>
                            <select class="form-select" id="ClasificacionEstadoMetabolico" name="ClasificacionEstadoMetabolico" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $estadosMetabolicos = [
                                    'HTA-RIESGOS BAJO', 'HTA-RIESGOS MODERADO', 'HTA-RIESGOS ALTO', 'HTA-RIESGOS MUY ALTO',
                                    'HTA-RIESGOS SIN CLASIFICAR', 'DM-RIESGOS SIN COMPLICACIONES', 'DM-RIESGOS CON COMPLICACIONES',
                                    'ERC-RIESGOS ESTADIO IIIB', 'ERC-RIESGOS ESTADIO IV', 'ERC-RIESGOS ESTADIO V'
                                ];
                                $estadoActual = $historiaPrevia['clasificacion_estado_metabolico'] ?? '';
                                @endphp
                                @foreach($estadosMetabolicos as $estado)
                                    <option value="{{ $estado }}" {{ $estadoActual === $estado ? 'selected' : '' }}>
                                        {{ $estado }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_hta" class="form-label">Clasificación HTA <span class="text-danger">*</span></label>
                            <select class="form-select" id="clasificacion_hta" name="clasificacion_hta" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $clasificacionesHTA = ['PA Normal', 'PA Normal - Alta', 'HTA Grado 1', 'HTA Grado 2', 'No HTA'];
                                $htaActual = $historiaPrevia['clasificacion_hta'] ?? '';
                                @endphp
                                @foreach($clasificacionesHTA as $hta)
                                    <option value="{{ $hta }}" {{ $htaActual === $hta ? 'selected' : '' }}>
                                        {{ $hta }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_dm" class="form-label">Clasificación DM <span class="text-danger">*</span></label>
                            <select class="form-select" id="clasificacion_dm" name="clasificacion_dm" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $clasificacionesDM = ['TIPO 1', 'TIPO 2', 'NO DIABETICO'];
                                $dmActual = $historiaPrevia['clasificacion_dm'] ?? '';
                                @endphp
                                @foreach($clasificacionesDM as $dm)
                                    <option value="{{ $dm }}" {{ $dmActual === $dm ? 'selected' : '' }}>
                                        {{ $dm }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_rcv" class="form-label">Clasificación Riesgo Cardiovascular <span class="text-danger">*</span></label>
                            <select class="form-select" id="clasificacion_rcv" name="clasificacion_rcv" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $riesgosRCV = ['BAJO', 'MODERADO', 'ALTO', 'MUY ALTO', 'SIN CLASIFICAR'];
                                $rcvActual = $historiaPrevia['clasificacion_rcv'] ?? '';
                                @endphp
                                @foreach($riesgosRCV as $riesgo)
                                    <option value="{{ $riesgo }}" {{ $rcvActual === $riesgo ? 'selected' : '' }}>
                                        {{ $riesgo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_erc_estado" class="form-label">Clasificación ERC Estadio <span class="text-danger">*</span></label>
                            <select class="form-select" id="clasificacion_erc_estado" name="clasificacion_erc_estado" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $estadiosERC = ['I', 'II', 'IIIA', 'IIIB', 'IV', 'V', 'SIN CLASIFICACION'];
                                $ercActual = $historiaPrevia['clasificacion_erc_estado'] ?? '';
                                @endphp
                                @foreach($estadiosERC as $estadio)
                                    <option value="{{ $estadio }}" {{ $ercActual === $estadio ? 'selected' : '' }}>
                                        {{ $estadio }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="clasificacion_erc_categoria_ambulatoria_persistente" class="form-label">Categoría Albuminuria Persistente <span class="text-danger">*</span></label>
                            <select class="form-select" id="clasificacion_erc_categoria_ambulatoria_persistente" 
                                    name="clasificacion_erc_categoria_ambulatoria_persistente" required>
                                <option value="">[SELECCIONE]</option>
                                @php
                                $categoriasERC = ['A1', 'A2', 'A3', 'SIN CLASIFICAR'];
                                $categoriaActual = $historiaPrevia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? '';
                                @endphp
                                @foreach($categoriasERC as $categoria)
                                    <option value="{{ $categoria }}" {{ $categoriaActual === $categoria ? 'selected' : '' }}>
                                        {{ $categoria }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tasa_filtracion_glomerular_ckd_epi" class="form-label">Tasa Filtración Glomerular CKD-EPI</label>
                            <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_ckd_epi" 
                                   name="tasa_filtracion_glomerular_ckd_epi" min="0" max="200" 
                                   value="{{ $historiaPrevia['tasa_filtracion_glomerular_ckd_epi'] ?? '0' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tasa_filtracion_glomerular_gockcroft_gault" class="form-label">Tasa Filtración Glomerular Cockcroft-Gault</label>
                            <input type="number" step="0.01" class="form-control" id="tasa_filtracion_glomerular_gockcroft_gault" 
                                   name="tasa_filtracion_glomerular_gockcroft_gault" min="0" max="200" 
                                   value="{{ $historiaPrevia['tasa_filtracion_glomerular_gockcroft_gault'] ?? '0' }}">
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
                                    <input class="form-check-input" type="radio" name="hipertension_arterial_personal" 
                                           id="hta_personal_si" value="SI" 
                                           {{ ($historiaPrevia['hipertension_arterial_personal'] ?? '') === 'SI' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hta_personal_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="hipertension_arterial_personal" 
                                           id="hta_personal_no" value="NO" 
                                           {{ ($historiaPrevia['hipertension_arterial_personal'] ?? 'NO') === 'NO' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hta_personal_no">No</label>
                                </div>
                            </div>
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
                    ['key' => 'fomento_actividad_fisica', 'label' => 'Fomento de Actividad Física'],
                    ['key' => 'importancia_adherencia_tratamiento', 'label' => 'Importancia de Adherencia a Tratamiento'],
                    ['key' => 'disminucion_consumo_sal_azucar', 'label' => 'Disminución de Consumo de Sal/Azúcar'],
                    ['key' => 'disminucion_consumo_cigarrillo', 'label' => 'Disminución de Consumo Cigarrillo'],
                    ['key' => 'disminucion_peso', 'label' => 'Disminución de Peso'],
                    ['key' => 'consumo_frutas_verduras', 'label' => 'Consumo de Frutas y Verduras']
                ];
                @endphp

                @foreach($educacionItems as $index => $item)
                    @if($index % 2 == 0)
                        <div class="row">
                    @endif
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="{{ $item['key'] }}" class="form-label">{{ $item['label'] }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="{{ $item['key'] }}" name="{{ $item['key'] }}" 
                                   required value="{{ $historiaPrevia[$item['key']] ?? 'SI' }}">
                        </div>
                    </div>
                    
                    @if($index % 2 == 1 || $index == count($educacionItems) - 1)
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ✅ SECCIÓN: DIAGNÓSTICO PRINCIPAL --}}
       @include('historia-clinica.partials.diagnostico-principal')
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

{{-- ✅ TEMPLATES PARA ELEMENTOS DINÁMICOS --}}
{{-- TEMPLATE DIAGNÓSTICO ADICIONAL --}}
<template id="diagnostico_adicional_template">
    <div class="diagnostico-adicional-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Diagnóstico</label>
                    <input type="text" class="form-control buscar-diagnostico-adicional" placeholder="Escriba código o nombre del diagnóstico...">
                    <div class="diagnosticos-adicionales-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
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

{{-- TEMPLATE MEDICAMENTO --}}
<template id="medicamento_template">
    <div class="medicamento-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Medicamento</label>
                    <input type="text" class="form-control buscar-medicamento" placeholder="Escriba el nombre del medicamento...">
                    <div class="medicamentos-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
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
                <div class="mb-3
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

{{-- TEMPLATE REMISIÓN --}}
<template id="remision_template">
    <div class="remision-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar Remisión</label>
                    <input type="text" class="form-control buscar-remision" placeholder="Escriba el nombre de la remisión...">
                    <div class="remisiones-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
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

{{-- TEMPLATE CUPS --}}
<template id="cups_template">
    <div class="cups-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar CUPS</label>
                    <input type="text" class="form-control buscar-cups" placeholder="Escriba código o nombre del procedimiento...">
                    <div class="cups-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
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
        if (imc < 25) return 'Adecuado';
        if (imc < 30) return 'Sobrepeso';
        if (imc < 35) return 'Obesidad grado 1';
        if (imc < 40) return 'Obesidad grado 2';
        return 'Obesidad grado 3';
    }
    
    // ✅ MOSTRAR/OCULTAR DESCRIPCIÓN LESIÓN ÓRGANO BLANCO
    $('input[name="lesion_organo_blanco"]').on('change', function() {
        if ($(this).val() === 'SI') {
            $('#descripcion_lesion_container').show();
        } else {
            $('#descripcion_lesion_container').hide();
            $('#descripcion_lesion_organo_blanco').val('');
        }
    });

    // ✅ CÁLCULO AUTOMÁTICO DE ADHERENCIA TEST MORISKY
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

    // ✅ RECONOCIMIENTO DE VOZ PARA OBSERVACIONES
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

    // ✅ CALCULAR IMC AL CARGAR SI YA HAY DATOS
    if ($('#peso').val() && $('#talla').val()) {
        calcularIMC();
    }

    // ✅ CALCULAR ADHERENCIA AL CARGAR SI YA HAY DATOS
    if ($('.test-morisky-input:checked').length > 0) {
        calcularAdherenciaMorisky();
    }

    // ✅ FUNCIÓN PARA CALCULAR PRESIÓN ARTERIAL
    $('#ef_pa_sistolica_sentado_pie, #ef_pa_distolica_sentado_pie').on('change', function() {
        calcularPresionArterial();
    });

    function calcularPresionArterial() {
        const sistolica = parseInt($('#ef_pa_sistolica_sentado_pie').val());
        const diastolica = parseInt($('#ef_pa_distolica_sentado_pie').val());

        if (sistolica && diastolica) {
            let clasificacion = '';
            
            if (sistolica < 130 && diastolica < 85) {
                clasificacion = 'PA Normal';
            } else if (sistolica >= 130 && sistolica <= 139 && diastolica >= 85 && diastolica <= 89) {
                clasificacion = 'PA Normal - Alta';
            } else if (sistolica >= 140 && sistolica <= 159 && diastolica >= 90 && diastolica <= 99) {
                clasificacion = 'HTA Grado 1';
            } else if (sistolica >= 160 && diastolica >= 100) {
                clasificacion = 'HTA Grado 2';
            }
            
            if (clasificacion) {
                $('#clasificacion_hta').val(clasificacion);
            }
        }
    }

    // ✅ ENVÍO DEL FORMULARIO
    $('#historiaClinicaForm').on('submit', function(e) {
        e.preventDefault();
        
        // ✅ HABILITAR CAMPO ADHERENTE ANTES DEL ENVÍO
        $('input[name="adherente"]').prop('readonly', false);
        
        // Validaciones específicas para control
        if (!validarFormularioControl()) {
            $('input[name="adherente"]').prop('readonly', true);
            return;
        }
        
        // Mostrar loading
        $('#loading_overlay').show();
        
        // Preparar datos
        const formData = new FormData(this);
        
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
                        text: response.error || 'Error guardando el control'
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

    // ✅ FUNCIÓN DE VALIDACIÓN ESPECÍFICA PARA CONTROL
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
            'clasificacion_erc_estado', 'clasificacion_erc_categoria_ambulatoria_persistente', 
            'clasificacion_rcv'
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
        
        // Validar campos de educación
        const camposEducacion = [
            'alimentacion', 'fomento_actividad_fisica', 'importancia_adherencia_tratamiento',
            'disminucion_consumo_sal_azucar', 'disminucion_consumo_cigarrillo', 
            'disminucion_peso', 'consumo_frutas_verduras'
        ];
        
        for (let campo of camposEducacion) {
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


