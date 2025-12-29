{{-- resources/views/pacientes/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Ver Paciente - SIDS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user text-primary me-2"></i>
                        {{ $paciente['primer_nombre'] }} {{ $paciente['segundo_nombre'] ?? '' }} 
                        {{ $paciente['primer_apellido'] }} {{ $paciente['segundo_apellido'] ?? '' }}
                    </h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-id-card me-1"></i>{{ $paciente['documento'] }}
                        @if($isOffline)
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-wifi-slash"></i> Offline
                            </span>
                        @endif
                        @if(isset($paciente['sync_status']) && $paciente['sync_status'] === 'pending')
                            <span class="badge bg-info ms-2">
                                <i class="fas fa-sync-alt"></i> Pendiente Sync
                            </span>
                        @endif
                    </p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('pacientes.edit', $paciente['uuid']) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-plus me-1"></i>Acciones
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="createCita()">
                                    <i class="fas fa-calendar-plus me-2"></i>Nueva Cita
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="createHistoria()">
                                    <i class="fas fa-file-medical me-2"></i>Nueva Historia
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="printPaciente()">
                                    <i class="fas fa-print me-2"></i>Imprimir
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <a href="{{ route('pacientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <!-- Datos Personales -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Primer Nombre</label>
                                <div class="info-value">{{ $paciente['primer_nombre'] }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Segundo Nombre</label>
                                <div class="info-value">{{ $paciente['segundo_nombre'] ?? '-' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Primer Apellido</label>
                                <div class="info-value">{{ $paciente['primer_apellido'] }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Segundo Apellido</label>
                                <div class="info-value">{{ $paciente['segundo_apellido'] ?? '-' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Tipo de Documento</label>
                                <div class="info-value">
                                    @php
                                        $tipoDoc = '';
                                        $abrev = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['tipo_documento']['nombre'])) {
                                            $tipoDoc = $paciente['tipo_documento']['nombre'];
                                            $abrev = $paciente['tipo_documento']['abreviacion'] ?? '';
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['tipo_documento_nombre'])) {
                                            $tipoDoc = $paciente['tipo_documento_nombre'];
                                            $abrev = $paciente['tipo_documento_abreviacion'] ?? '';
                                        }
                                        else {
                                            $tipoDoc = 'No especificado';
                                        }
                                    @endphp
                                    {{ $tipoDoc }}
                                    @if($abrev)
                                        <small class="text-muted">({{ $abrev }})</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Documento</label>
                                <div class="info-value">
                                    <strong>{{ $paciente['documento'] }}</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Registro</label>
                                <div class="info-value">{{ $paciente['registro'] ?? 'No asignado' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Fecha de Nacimiento</label>
                                <div class="info-value">
                                    @if($paciente['fecha_nacimiento'])
                                        {{ \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->format('d/m/Y') }}
                                        <small class="text-muted">
                                            ({{ $paciente['edad'] ?? \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->age }} años)
                                        </small>
                                    @else
                                        No especificada
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Sexo</label>
                                <div class="info-value">
                                    <span class="badge bg-{{ $paciente['sexo'] === 'M' ? 'primary' : 'pink' }}">
                                        {{ $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Estado Civil</label>
                                <div class="info-value">
                                    @if($paciente['estado_civil'])
                                        {{ ucfirst(strtolower(str_replace('_', ' ', $paciente['estado_civil']))) }}
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información de Contacto -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-address-book me-2"></i>Información de Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="info-item">
                                <label class="info-label">Dirección</label>
                                <div class="info-value">{{ $paciente['direccion'] ?? 'No especificada' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Teléfono</label>
                                <div class="info-value">
                                    @if($paciente['telefono'])
                                        <a href="tel:{{ $paciente['telefono'] }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $paciente['telefono'] }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Correo Electrónico</label>
                                <div class="info-value">
                                    @if($paciente['correo'])
                                        <a href="mailto:{{ $paciente['correo'] }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $paciente['correo'] }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ INFORMACIÓN DE AFILIACIÓN CORREGIDA -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-hospital me-2"></i>Información de Afiliación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Empresa/EPS</label>
                                <div class="info-value">
                                    @php
                                        $empresaNombre = '';
                                        $empresaCodigo = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['empresa']['nombre'])) {
                                            $empresaNombre = $paciente['empresa']['nombre'];
                                            $empresaCodigo = $paciente['empresa']['codigo_eapb'] ?? '';
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['empresa_nombre'])) {
                                            $empresaNombre = $paciente['empresa_nombre'];
                                            $empresaCodigo = $paciente['empresa_codigo'] ?? '';
                                        }
                                        else {
                                            $empresaNombre = 'No especificada';
                                        }
                                    @endphp
                                    {{ $empresaNombre }}
                                    @if($empresaCodigo)
                                        <small class="text-muted">({{ $empresaCodigo }})</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Régimen</label>
                                <div class="info-value">
                                    @php
                                        $regimenNombre = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['regimen']['nombre'])) {
                                            $regimenNombre = $paciente['regimen']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['regimen_nombre'])) {
                                            $regimenNombre = $paciente['regimen_nombre'];
                                        }
                                        else {
                                            $regimenNombre = 'No especificado';
                                        }
                                    @endphp
                                    {{ $regimenNombre }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Tipo de Afiliación</label>
                                <div class="info-value">
                                    @php
                                        $tipoAfiliacion = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['tipo_afiliacion']['nombre'])) {
                                            $tipoAfiliacion = $paciente['tipo_afiliacion']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['tipo_afiliacion_nombre'])) {
                                            $tipoAfiliacion = $paciente['tipo_afiliacion_nombre'];
                                        }
                                        else {
                                            $tipoAfiliacion = 'No especificado';
                                        }
                                    @endphp
                                    {{ $tipoAfiliacion }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Parentesco</label>
                                <div class="info-value">
                                    @php
                                        $parentesco = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['parentesco']['nombre'])) {
                                            $parentesco = $paciente['parentesco']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['parentesco_nombre'])) {
                                            $parentesco = $paciente['parentesco_nombre'];
                                        }
                                        else {
                                            $parentesco = 'No especificado';
                                        }
                                    @endphp
                                    {{ $parentesco }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ INFORMACIÓN GEOGRÁFICA CORREGIDA -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Información Geográfica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Departamento de Nacimiento</label>
                                <div class="info-value">
                                    @php
                                        $deptoNac = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['departamento_nacimiento']['nombre'])) {
                                            $deptoNac = $paciente['departamento_nacimiento']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['depto_nacimiento_nombre'])) {
                                            $deptoNac = $paciente['depto_nacimiento_nombre'];
                                        }
                                        else {
                                            $deptoNac = 'No especificado';
                                        }
                                    @endphp
                                    {{ $deptoNac }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Municipio de Nacimiento</label>
                                <div class="info-value">
                                    @php
                                        $munNac = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['municipio_nacimiento']['nombre'])) {
                                            $munNac = $paciente['municipio_nacimiento']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['municipio_nacimiento_nombre'])) {
                                            $munNac = $paciente['municipio_nacimiento_nombre'];
                                        }
                                        else {
                                            $munNac = 'No especificado';
                                        }
                                    @endphp
                                    {{ $munNac }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Departamento de Residencia</label>
                                <div class="info-value">
                                    @php
                                        $deptoRes = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['departamento_residencia']['nombre'])) {
                                            $deptoRes = $paciente['departamento_residencia']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['depto_residencia_nombre'])) {
                                            $deptoRes = $paciente['depto_residencia_nombre'];
                                        }
                                        else {
                                            $deptoRes = 'No especificado';
                                        }
                                    @endphp
                                    {{ $deptoRes }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Municipio de Residencia</label>
                                <div class="info-value">
                                    @php
                                        $munRes = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['municipio_residencia']['nombre'])) {
                                            $munRes = $paciente['municipio_residencia']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['municipio_residencia_nombre'])) {
                                            $munRes = $paciente['municipio_residencia_nombre'];
                                        }
                                        else {
                                            $munRes = 'No especificado';
                                        }
                                    @endphp
                                    {{ $munRes }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Zona de Residencia</label>
                                <div class="info-value">
                                    @php
                                        $zonaRes = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['zona_residencia']['nombre'])) {
                                            $zonaRes = $paciente['zona_residencia']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['zona_residencia_nombre'])) {
                                            $zonaRes = $paciente['zona_residencia_nombre'];
                                        }
                                        else {
                                            $zonaRes = 'No especificada';
                                        }
                                    @endphp
                                    {{ $zonaRes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ INFORMACIÓN SOCIOECONÓMICA CORREGIDA -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Información Socioeconómica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Raza/Etnia</label>
                                <div class="info-value">
                                    @php
                                        $raza = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['raza']['nombre'])) {
                                            $raza = $paciente['raza']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['raza_nombre'])) {
                                            $raza = $paciente['raza_nombre'];
                                        }
                                        else {
                                            $raza = 'No especificada';
                                        }
                                    @endphp
                                    {{ $raza }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Escolaridad</label>
                                <div class="info-value">
                                    @php
                                        $escolaridad = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['escolaridad']['nombre'])) {
                                            $escolaridad = $paciente['escolaridad']['nombre'];
                                        }
                                        // ✅ OFFLINE: campo directo
                                        elseif (isset($paciente['escolaridad_nombre'])) {
                                            $escolaridad = $paciente['escolaridad_nombre'];
                                        }
                                        else {
                                            $escolaridad = 'No especificada';
                                        }
                                    @endphp
                                    {{ $escolaridad }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Ocupación</label>
                                <div class="info-value">
                                    @php
                                        $ocupacion = '';
                                        $ocupacionCodigo = '';
                                        
                                        // Online: estructura anidada
                                        if (isset($paciente['ocupacion']['nombre'])) {
                                            $ocupacion = $paciente['ocupacion']['nombre'];
                                            $ocupacionCodigo = $paciente['ocupacion']['codigo'] ?? '';
                                        }
                                        // ✅ OFFLINE: campos directos
                                        elseif (isset($paciente['ocupacion_nombre'])) {
                                            $ocupacion = $paciente['ocupacion_nombre'];
                                            $ocupacionCodigo = $paciente['ocupacion_codigo'] ?? '';
                                        }
                                        else {
                                            $ocupacion = 'No especificada';
                                        }
                                    @endphp
                                    {{ $ocupacion }}
                                    @if($ocupacionCodigo)
                                        <small class="text-muted">({{ $ocupacionCodigo }})</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Acudiente -->
            @if($paciente['nombre_acudiente'] || ($paciente['acudiente']['nombre'] ?? null))
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-friends me-2"></i>Información del Acudiente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Nombre del Acudiente</label>
                                <div class="info-value">
                                    {{ $paciente['nombre_acudiente'] ?? $paciente['acudiente']['nombre'] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Parentesco</label>
                                <div class="info-value">
                                    {{ $paciente['parentesco_acudiente'] ?? $paciente['acudiente']['parentesco'] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Teléfono del Acudiente</label>
                                <div class="info-value">
                                    @php
                                        $telefonoAcudiente = $paciente['telefono_acudiente'] ?? $paciente['acudiente']['telefono'] ?? null;
                                    @endphp
                                    @if($telefonoAcudiente)
                                        <a href="tel:{{ $telefonoAcudiente }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $telefonoAcudiente }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Dirección del Acudiente</label>
                                <div class="info-value">
                                    {{ $paciente['direccion_acudiente'] ?? $paciente['acudiente']['direccion'] ?? 'No especificada' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Información de Acompañante -->
            @if($paciente['acompanante_nombre'] || ($paciente['acompanante']['nombre'] ?? null))
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i>Información del Acompañante
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Nombre del Acompañante</label>
                                <div class="info-value">
                                    {{ $paciente['acompanante_nombre'] ?? $paciente['acompanante']['nombre'] ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Teléfono del Acompañante</label>
                                <div class="info-value">
                                    @php
                                        $telefonoAcompanante = $paciente['acompanante_telefono'] ?? $paciente['acompanante']['telefono'] ?? null;
                                    @endphp
                                    @if($telefonoAcompanante)
                                        <a href="tel:{{ $telefonoAcompanante }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $telefonoAcompanante }}
                                        </a>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Observaciones -->
            @if($paciente['observacion'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Observaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-value">{{ $paciente['observacion'] }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Estado del Paciente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                                              <i class="fas fa-info-circle me-2"></i>Estado del Paciente
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="badge bg-{{ $paciente['estado'] === 'ACTIVO' ? 'success' : 'danger' }} fs-6 px-3 py-2">
                            <i class="fas fa-{{ $paciente['estado'] === 'ACTIVO' ? 'check-circle' : 'times-circle' }} me-2"></i>
                            {{ $paciente['estado'] }}
                        </span>
                    </div>
                    
                    @if($paciente['estado'] === 'INACTIVO')
                        <button class="btn btn-success btn-sm" onclick="activatePaciente()">
                            <i class="fas fa-check me-1"></i>Activar Paciente
                        </button>
                    @else
                        <button class="btn btn-warning btn-sm" onclick="deactivatePaciente()">
                            <i class="fas fa-pause me-1"></i>Desactivar Paciente
                        </button>
                    @endif
                </div>
            </div>
            
            <!-- Información del Sistema -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <label class="info-label">UUID</label>
                        <div class="info-value">
                            <small class="font-monospace">{{ $paciente['uuid'] }}</small>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $paciente['uuid'] }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    @if(isset($paciente['id']))
                    <div class="info-item mb-3">
                        <label class="info-label">ID Interno</label>
                        <div class="info-value">{{ $paciente['id'] }}</div>
                    </div>
                    @endif
                    
                    <div class="info-item mb-3">
                        <label class="info-label">Fecha de Registro</label>
                        <div class="info-value">
                            @if($paciente['fecha_registro'])
                                {{ \Carbon\Carbon::parse($paciente['fecha_registro'])->format('d/m/Y') }}
                            @else
                                No disponible
                            @endif
                        </div>
                    </div>
                    
                    @if($paciente['fecha_actualizacion'])
                    <div class="info-item mb-3">
                        <label class="info-label">Última Actualización</label>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse($paciente['fecha_actualizacion'])->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($paciente['sync_status']))
                    <div class="info-item">
                        <label class="info-label">Estado de Sincronización</label>
                        <div class="info-value">
                            <span class="badge bg-{{ $paciente['sync_status'] === 'synced' ? 'success' : ($paciente['sync_status'] === 'pending' ? 'warning' : 'danger') }}">
                                @if($paciente['sync_status'] === 'synced')
                                    <i class="fas fa-check me-1"></i>Sincronizado
                                @elseif($paciente['sync_status'] === 'pending')
                                    <i class="fas fa-clock me-1"></i>Pendiente
                                @else
                                    <i class="fas fa-exclamation-triangle me-1"></i>Error
                                @endif
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

         <!-- ✅ INFORMACIÓN ADMINISTRATIVA CORREGIDA PARA OFFLINE -->
@if(isset($paciente['novedad_id']) || isset($paciente['auxiliar_id']) || isset($paciente['brigada_id']) || 
    isset($paciente['novedad_tipo']) || isset($paciente['auxiliar_nombre']) || isset($paciente['brigada_nombre']))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-cogs me-2"></i>Información Administrativa
        </h5>
    </div>
    <div class="card-body">
        {{-- ✅ NOVEDAD - FUNCIONA ONLINE Y OFFLINE --}}
        @if(isset($paciente['novedad_id']) || isset($paciente['novedad_tipo']))
        <div class="info-item mb-3">
            <label class="info-label">Novedad</label>
            <div class="info-value">
                @php
                    $novedad = '';
                    
                    // ✅ ONLINE: estructura anidada
                    if (isset($paciente['novedad']['tipo_novedad'])) {
                        $novedad = $paciente['novedad']['tipo_novedad'];
                    }
                    // ✅ OFFLINE: campo directo
                    elseif (isset($paciente['novedad_tipo'])) {
                        $novedad = $paciente['novedad_tipo'];
                    }
                    else {
                        $novedad = 'No especificada';
                    }
                @endphp
                {{ $novedad }}
            </div>
        </div>
        @endif
        
        {{-- ✅ AUXILIAR - FUNCIONA ONLINE Y OFFLINE --}}
        @if(isset($paciente['auxiliar_id']) || isset($paciente['auxiliar_nombre']))
        <div class="info-item mb-3">
            <label class="info-label">Auxiliar</label>
            <div class="info-value">
                @php
                    $auxiliar = '';
                    
                    // ✅ ONLINE: estructura anidada
                    if (isset($paciente['auxiliar']['nombre'])) {
                        $auxiliar = $paciente['auxiliar']['nombre'];
                    }
                    // ✅ OFFLINE: campo directo
                    elseif (isset($paciente['auxiliar_nombre'])) {
                        $auxiliar = $paciente['auxiliar_nombre'];
                    }
                    else {
                        $auxiliar = 'No especificado';
                    }
                @endphp
                {{ $auxiliar }}
            </div>
        </div>
        @endif
        
        {{-- ✅ BRIGADA - FUNCIONA ONLINE Y OFFLINE --}}
        @if(isset($paciente['brigada_id']) || isset($paciente['brigada_nombre']))
        <div class="info-item">
            <label class="info-label">Brigada</label>
            <div class="info-value">
                @php
                    $brigada = '';
                    
                    // ✅ ONLINE: estructura anidada
                    if (isset($paciente['brigada']['nombre'])) {
                        $brigada = $paciente['brigada']['nombre'];
                    }
                    // ✅ OFFLINE: campo directo
                    elseif (isset($paciente['brigada_nombre'])) {
                        $brigada = $paciente['brigada_nombre'];
                    }
                    else {
                        $brigada = 'No especificada';
                    }
                @endphp
                {{ $brigada }}
            </div>
        </div>
        @endif
    </div>
</div>
@endif

            
            <!-- Acciones Rápidas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="createCita()">
                            <i class="fas fa-calendar-plus me-2"></i>Agendar Cita
                        </button>
                        
                        <button class="btn btn-outline-info" onclick="createHistoria()">
                            <i class="fas fa-file-medical me-2"></i>Nueva Historia
                        </button>
                        
                        <button class="btn btn-outline-success" onclick="generateReport()">
                            <i class="fas fa-chart-line me-2"></i>Generar Reporte
                        </button>
                        
                        <hr>
                        
                        <button class="btn btn-outline-danger" onclick="deletePaciente()">
                            <i class="fas fa-trash me-2"></i>Eliminar Paciente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.info-item {
    margin-bottom: 1rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}

.info-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.bg-pink {
    background-color: #e91e63 !important;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.badge {
    font-size: 0.875em;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
}

/* Estilos para modo offline */
.badge.bg-warning {
    color: #000;
}

.badge.bg-info {
    color: #fff;
}

/* Mejoras visuales */
.info-value a {
    color: #0d6efd;
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header h5 {
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .info-value {
        font-size: 0.9rem;
    }
    
    .info-label {
        font-size: 0.8rem;
    }
}

/* Estilos para impresión */
@media print {
    .btn, .dropdown, .card:last-child {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        break-inside: avoid;
    }
    
    .card-header {
        background-color: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Crear nueva cita
function createCita() {
    // Redirigir al formulario de crear cita con paciente preseleccionado
    window.location.href = `/citas/create?paciente_uuid={{ $paciente['uuid'] }}`;
}

// Crear nueva historia clínica
function createHistoria() {
    showAlert('info', 'Función de historias clínicas en desarrollo', 'Próximamente');
}

// Generar reporte
function generateReport() {
    showAlert('info', 'Función de reportes en desarrollo', 'Próximamente');
}

// Imprimir paciente
function printPaciente() {
    window.print();
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('success', 'UUID copiado al portapapeles');
    }, function(err) {
        console.error('Error copiando al portapapeles: ', err);
        showAlert('error', 'Error copiando al portapapeles');
    });
}

// Activar paciente
function activatePaciente() {
    Swal.fire({
        title: '¿Activar Paciente?',
        text: 'El paciente volverá a estar disponible en el sistema',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            updatePacienteStatus('ACTIVO');
        }
    });
}

// Desactivar paciente
function deactivatePaciente() {
    Swal.fire({
        title: '¿Desactivar Paciente?',
        text: 'El paciente no aparecerá en búsquedas regulares',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ffc107'
    }).then((result) => {
        if (result.isConfirmed) {
            updatePacienteStatus('INACTIVO');
        }
    });
}

// Actualizar estado del paciente
function updatePacienteStatus(estado) {
    const loadingAlert = Swal.fire({
        title: 'Actualizando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/pacientes/{{ $paciente['uuid'] }}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        loadingAlert.close();
        
        if (data.success) {
            showAlert('success', `Paciente ${estado.toLowerCase()} exitosamente`);
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('error', data.error || 'Error actualizando estado');
        }
    })
    .catch(error => {
        loadingAlert.close();
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
    });
}

// Eliminar paciente
function deletePaciente() {
    Swal.fire({
        title: '¿Eliminar Paciente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const loadingAlert = Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/pacientes/{{ $paciente['uuid'] }}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingAlert.close();
                
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = '{{ route("pacientes.index") }}';
                    }, 2000);
                } else {
                    showAlert('error', data.error || 'Error eliminando paciente');
                }
            })
            .catch(error => {
                loadingAlert.close();
                console.error('Error:', error);
                showAlert('error', 'Error de conexión');
            });
        }
    });
}

// Función auxiliar para mostrar alertas
function showAlert(type, message, title = null) {
    const icons = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };

    Swal.fire({
        title: title || (type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : 'Información'),
        text: message,
        icon: icons[type] || 'info',
        confirmButtonColor: colors[type] || '#007bff',
        timer: type === 'success' ? 3000 : undefined,
        timerProgressBar: type === 'success'
    });
}

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay mensajes de sesión para mostrar
    @if(session('success'))
        showAlert('success', '{{ session('success') }}');
    @endif
    
    @if(session('error'))
        showAlert('error', '{{ session('error') }}');
    @endif
    
    @if(session('warning'))
        showAlert('warning', '{{ session('warning') }}');
    @endif
    
    @if(session('info'))
        showAlert('info', '{{ session('info') }}');
    @endif

    // Si está en modo offline, mostrar indicador
    @if($isOffline)
        console.log('🔌 Modo offline activo');
    @endif

    // Si hay estado de sincronización pendiente, mostrar información
    @if(isset($paciente['sync_status']) && $paciente['sync_status'] === 'pending')
        console.log('⏳ Paciente con sincronización pendiente');
    @endif
});

// Función para manejar errores de red
window.addEventListener('online', function() {
    showAlert('success', 'Conexión restaurada', 'Conectado');
});

window.addEventListener('offline', function() {
    showAlert('warning', 'Sin conexión a internet', 'Modo Offline');
});

// Detectar si el paciente tiene cambios pendientes
@if(isset($paciente['sync_status']) && $paciente['sync_status'] === 'pending')
    // Mostrar botón de sincronización si está disponible
    if (navigator.onLine) {
        const syncButton = document.createElement('button');
        syncButton.className = 'btn btn-info btn-sm mt-2';
        syncButton.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sincronizar Ahora';
        syncButton.onclick = function() {
            syncPaciente('{{ $paciente['uuid'] }}');
        };
        
        // Agregar el botón al panel de estado
        const estadoCard = document.querySelector('.card-body .badge').parentElement;
        if (estadoCard) {
            estadoCard.appendChild(syncButton);
        }
    }
@endif

// Función para sincronizar paciente específico
function syncPaciente(uuid) {
    const loadingAlert = Swal.fire({
        title: 'Sincronizando...',
        text: 'Enviando datos al servidor',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/pacientes/sync-pending', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingAlert.close();
        
        if (data.success) {
            showAlert('success', 'Datos sincronizados correctamente');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.error || 'Error en la sincronización');
        }
    })
    .catch(error => {
        loadingAlert.close();
        console.error('Error:', error);
        showAlert('error', 'Error de conexión durante la sincronización');
    });
}
</script>
@endpush
@endsection
