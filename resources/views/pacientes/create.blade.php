{{-- resources/views/pacientes/create.blade.php (VERSIÓN MEJORADA CON NAVEGACIÓN) --}}
@extends('layouts.app')

@section('title', 'Crear Paciente - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- ✅ BREADCRUMB MEJORADO -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('pacientes.index') }}" class="text-decoration-none" onclick="handleBackNavigation(event)">
                    <i class="fas fa-users"></i> Pacientes
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-user-plus"></i> Nuevo Paciente
            </li>
        </ol>
    </nav>

    <!-- ✅ ALERTA DE CAMBIOS SIN GUARDAR -->
    <div id="unsavedChangesAlert" class="alert alert-warning alert-dismissible fade d-none" role="alert">
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
            <div class="flex-grow-1">
                <strong>¡Hay cambios sin guardar!</strong>
                <p class="mb-0">Recuerda guardar tus cambios antes de salir del formulario.</p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        Nuevo Paciente
                    </h1>
                    <p class="text-muted mb-0">Registrar información completa de nuevo paciente</p>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @endif
                    
                    <button type="button" class="btn btn-secondary" onclick="handleBackNavigation()">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ FORMULARIO CON PREVENCIÓN DE CACHÉ -->
    <form id="pacienteForm" method="POST" action="{{ route('pacientes.store') }}" autocomplete="off" novalidate>
        @csrf
        
        <div class="row">
            <div class="col-lg-8">
                
                <!-- 1. INFORMACIÓN PERSONAL -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>1. Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Registro -->
                            <div class="col-md-6">
                                <label for="registro" class="form-label">Número de Registro</label>
                                <input type="text" class="form-control @error('registro') is-invalid @enderror" 
                                       id="registro" name="registro" value="{{ old('registro') }}" 
                                       placeholder="Se generará automáticamente" autocomplete="off">
                                @error('registro')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Tipo Documento -->
                            <div class="col-md-6">
                                <label for="tipo_documento_id" class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_documento_id') is-invalid @enderror" 
                                        id="tipo_documento_id" name="tipo_documento_id" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['tipos_documento'] ?? [] as $tipo)
                                        <option value="{{ $tipo['uuid'] }}" {{ old('tipo_documento_id') == $tipo['uuid'] ? 'selected' : '' }}>
                                            {{ $tipo['abreviacion'] }} - {{ $tipo['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tipo_documento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Documento -->
                            <div class="col-md-6">
                                <label for="documento" class="form-label">Número de Documento <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('documento') is-invalid @enderror" 
                                           id="documento" name="documento" value="{{ old('documento') }}" required autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" onclick="searchExistingPaciente()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                @error('documento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Nombres -->
                            <div class="col-md-6">
                                <label for="primer_nombre" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_nombre') is-invalid @enderror" 
                                       id="primer_nombre" name="primer_nombre" value="{{ old('primer_nombre') }}" required autocomplete="off">
                                @error('primer_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control @error('segundo_nombre') is-invalid @enderror" 
                                       id="segundo_nombre" name="segundo_nombre" value="{{ old('segundo_nombre') }}" autocomplete="off">
                                @error('segundo_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Apellidos -->
                            <div class="col-md-6">
                                <label for="primer_apellido" class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_apellido') is-invalid @enderror" 
                                       id="primer_apellido" name="primer_apellido" value="{{ old('primer_apellido') }}" required autocomplete="off">
                                @error('primer_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                                <input type="text" class="form-control @error('segundo_apellido') is-invalid @enderror" 
                                       id="segundo_apellido" name="segundo_apellido" value="{{ old('segundo_apellido') }}" autocomplete="off">
                                @error('segundo_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Fecha Nacimiento y Sexo -->
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror" 
                                       id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required>
                                @error('fecha_nacimiento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                                <select class="form-select @error('sexo') is-invalid @enderror" id="sexo" name="sexo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="M" {{ old('sexo') == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('sexo') == 'F' ? 'selected' : '' }}>Femenino</option>
                                </select>
                                @error('sexo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Estado Civil -->
                            <div class="col-md-6">
                                <label for="estado_civil" class="form-label">Estado Civil</label>
                                <select class="form-select @error('estado_civil') is-invalid @enderror" id="estado_civil" name="estado_civil">
                                    <option value="">Seleccione...</option>
                                    <option value="SOLTERO" {{ old('estado_civil') == 'SOLTERO' ? 'selected' : '' }}>Soltero(a)</option>
                                    <option value="CASADO" {{ old('estado_civil') == 'CASADO' ? 'selected' : '' }}>Casado(a)</option>
                                    <option value="UNION_LIBRE" {{ old('estado_civil') == 'UNION_LIBRE' ? 'selected' : '' }}>Unión Libre</option>
                                    <option value="DIVORCIADO" {{ old('estado_civil') == 'DIVORCIADO' ? 'selected' : '' }}>Divorciado(a)</option>
                                    <option value="VIUDO" {{ old('estado_civil') == 'VIUDO' ? 'selected' : '' }}>Viudo(a)</option>
                                </select>
                                @error('estado_civil')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Estado -->
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado">
                                    <option value="ACTIVO" {{ old('estado', 'ACTIVO') == 'ACTIVO' ? 'selected' : '' }}>Activo</option>
                                    <option value="INACTIVO" {{ old('estado') == 'INACTIVO' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. INFORMACIÓN DEMOGRÁFICA -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-globe-americas me-2"></i>2. Información Demográfica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Departamento Nacimiento -->
                            <div class="col-md-6">
                                <label for="depto_nacimiento_id" class="form-label">
                                    Departamento de Nacimiento
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1" onclick="copyFromResidencia()" title="Copiar desde residencia">
                                        <i class="fas fa-copy text-muted"></i>
                                    </button>
                                </label>
                                <select class="form-select @error('depto_nacimiento_id') is-invalid @enderror" 
                                        id="depto_nacimiento_id" name="depto_nacimiento_id" onchange="loadMunicipios('nacimiento')">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['departamentos'] ?? [] as $depto)
                                        <option value="{{ $depto['uuid'] }}" {{ old('depto_nacimiento_id') == $depto['uuid'] ? 'selected' : '' }}>
                                            {{ $depto['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('depto_nacimiento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Municipio Nacimiento -->
                            <div class="col-md-6">
                                <label for="municipio_nacimiento_id" class="form-label">Municipio de Nacimiento</label>
                                <select class="form-select @error('municipio_nacimiento_id') is-invalid @enderror" 
                                        id="municipio_nacimiento_id" name="municipio_nacimiento_id">
                                    <option value="">Seleccione departamento primero...</option>
                                </select>
                                @error('municipio_nacimiento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Departamento Residencia -->
                            <div class="col-md-6">
                                <label for="depto_residencia_id" class="form-label">
                                    Departamento de Residencia
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1" onclick="copyFromNacimiento()" title="Copiar desde nacimiento">
                                        <i class="fas fa-copy text-muted"></i>
                                    </button>
                                </label>
                                <select class="form-select @error('depto_residencia_id') is-invalid @enderror" 
                                        id="depto_residencia_id" name="depto_residencia_id" onchange="loadMunicipios('residencia')">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['departamentos'] ?? [] as $depto)
                                        <option value="{{ $depto['uuid'] }}" {{ old('depto_residencia_id') == $depto['uuid'] ? 'selected' : '' }}>
                                            {{ $depto['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('depto_residencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Municipio Residencia -->
                            <div class="col-md-6">
                                <label for="municipio_residencia_id" class="form-label">Municipio de Residencia</label>
                                <select class="form-select @error('municipio_residencia_id') is-invalid @enderror" 
                                        id="municipio_residencia_id" name="municipio_residencia_id">
                                    <option value="">Seleccione departamento primero...</option>
                                </select>
                                @error('municipio_residencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Zona Residencial -->
                            <div class="col-md-6">
                                <label for="zona_residencia_id" class="form-label">Zona Residencial</label>
                                <select class="form-select @error('zona_residencia_id') is-invalid @enderror" 
                                        id="zona_residencia_id" name="zona_residencia_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['zonas_residenciales'] ?? [] as $zona)
                                        <option value="{{ $zona['uuid'] }}" {{ old('zona_residencia_id') == $zona['uuid'] ? 'selected' : '' }}>
                                            {{ $zona['nombre'] }} ({{ $zona['abreviacion'] }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('zona_residencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Raza -->
                            <div class="col-md-6">
                                <label for="raza_id" class="form-label">Raza/Etnia</label>
                                <select class="form-select @error('raza_id') is-invalid @enderror" 
                                        id="raza_id" name="raza_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['razas'] ?? [] as $raza)
                                        <option value="{{ $raza['uuid'] }}" {{ old('raza_id') == $raza['uuid'] ? 'selected' : '' }}>
                                            {{ $raza['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('raza_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Escolaridad -->
                            <div class="col-md-6">
                                <label for="escolaridad_id" class="form-label">Nivel de Escolaridad</label>
                                <select class="form-select @error('escolaridad_id') is-invalid @enderror" 
                                        id="escolaridad_id" name="escolaridad_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['escolaridades'] ?? [] as $escolaridad)
                                        <option value="{{ $escolaridad['uuid'] }}" {{ old('escolaridad_id') == $escolaridad['uuid'] ? 'selected' : '' }}>
                                            {{ $escolaridad['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('escolaridad_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Ocupación -->
                            <div class="col-md-6">
                                <label for="ocupacion_id" class="form-label">Ocupación</label>
                                <select class="form-select @error('ocupacion_id') is-invalid @enderror" 
                                        id="ocupacion_id" name="ocupacion_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['ocupaciones'] ?? [] as $ocupacion)
                                        <option value="{{ $ocupacion['uuid'] }}" {{ old('ocupacion_id') == $ocupacion['uuid'] ? 'selected' : '' }}>
                                            {{ $ocupacion['codigo'] }} - {{ $ocupacion['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ocupacion_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. INFORMACIÓN DE AFILIACIÓN -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-id-card me-2"></i>3. Información de Afiliación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Empresa -->
                            <div class="col-md-6">
                                <label for="empresa_id" class="form-label">EPS/Empresa</label>
                                <select class="form-select @error('empresa_id') is-invalid @enderror" 
                                        id="empresa_id" name="empresa_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['empresas'] ?? [] as $empresa)
                                        <option value="{{ $empresa['uuid'] }}" {{ old('empresa_id') == $empresa['uuid'] ? 'selected' : '' }}>
                                            {{ $empresa['nombre'] }} - {{ $empresa['nit'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('empresa_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Régimen -->
                            <div class="col-md-6">
                                <label for="regimen_id" class="form-label">Régimen</label>
                                <select class="form-select @error('regimen_id') is-invalid @enderror" 
                                        id="regimen_id" name="regimen_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['regimenes'] ?? [] as $regimen)
                                        <option value="{{ $regimen['uuid'] }}" {{ old('regimen_id') == $regimen['uuid'] ? 'selected' : '' }}>
                                            {{ $regimen['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('regimen_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Tipo Afiliación -->
                            <div class="col-md-6">
                                <label for="tipo_afiliacion_id" class="form-label">Tipo de Afiliación</label>
                                <select class="form-select @error('tipo_afiliacion_id') is-invalid @enderror" 
                                        id="tipo_afiliacion_id" name="tipo_afiliacion_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['tipos_afiliacion'] ?? [] as $tipo)
                                        <option value="{{ $tipo['uuid'] }}" {{ old('tipo_afiliacion_id') == $tipo['uuid'] ? 'selected' : '' }}>
                                            {{ $tipo['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tipo_afiliacion_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Parentesco -->
                            <div class="col-md-6">
                                <label for="parentesco_id" class="form-label">Parentesco</label>
                                <select class="form-select @error('parentesco_id') is-invalid @enderror" 
                                        id="parentesco_id" name="parentesco_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['tipos_parentesco'] ?? [] as $parentesco)
                                        <option value="{{ $parentesco['uuid'] }}" {{ old('parentesco_id') == $parentesco['uuid'] ? 'selected' : '' }}>
                                            {{ $parentesco['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parentesco_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. INFORMACIÓN DE CONTACTO -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-address-book me-2"></i>4. Información de Contacto
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="direccion" class="form-label">Dirección de Residencia</label>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror" 
                                       id="direccion" name="direccion" value="{{ old('direccion') }}" autocomplete="off">
                                @error('direccion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                       id="telefono" name="telefono" value="{{ old('telefono') }}" maxlength="10" autocomplete="off">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control @error('correo') is-invalid @enderror" 
                                       id="correo" name="correo" value="{{ old('correo') }}" autocomplete="off">
                                @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. INFORMACIÓN DE ACUDIENTE -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-friends me-2"></i>5. Información de Acudiente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre_acudiente" class="form-label">Nombre del Acudiente</label>
                                <input type="text" class="form-control @error('nombre_acudiente') is-invalid @enderror" 
                                       id="nombre_acudiente" name="nombre_acudiente" value="{{ old('nombre_acudiente') }}" autocomplete="off">
                                @error('nombre_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="parentesco_acudiente" class="form-label">Parentesco del Acudiente</label>
                                <input type="text" class="form-control @error('parentesco_acudiente') is-invalid @enderror" 
                                       id="parentesco_acudiente" name="parentesco_acudiente" value="{{ old('parentesco_acudiente') }}" autocomplete="off">
                                @error('parentesco_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono_acudiente" class="form-label">Teléfono del Acudiente</label>
                                <input type="text" class="form-control @error('telefono_acudiente') is-invalid @enderror" 
                                       id="telefono_acudiente" name="telefono_acudiente" value="{{ old('telefono_acudiente') }}" maxlength="10" autocomplete="off">
                                @error('telefono_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="direccion_acudiente" class="form-label">Dirección del Acudiente</label>
                                <input type="text" class="form-control @error('direccion_acudiente') is-invalid @enderror" 
                                       id="direccion_acudiente" name="direccion_acudiente" value="{{ old('direccion_acudiente') }}" autocomplete="off">
                                @error('direccion_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. INFORMACIÓN DE ACOMPAÑANTE -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">
                                                <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>6. Información de Acompañante
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="acompanante_nombre" class="form-label">Nombre del Acompañante</label>
                                <input type="text" class="form-control @error('acompanante_nombre') is-invalid @enderror" 
                                       id="acompanante_nombre" name="acompanante_nombre" value="{{ old('acompanante_nombre') }}" autocomplete="off">
                                @error('acompanante_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="acompanante_telefono" class="form-label">Teléfono del Acompañante</label>
                                <input type="text" class="form-control @error('acompanante_telefono') is-invalid @enderror" 
                                       id="acompanante_telefono" name="acompanante_telefono" value="{{ old('acompanante_telefono') }}" maxlength="10" autocomplete="off">
                                @error('acompanante_telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. INFORMACIÓN ADICIONAL -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background-color: #6f42c1; color: white;">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>7. Información Adicional
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Novedad -->
                            <div class="col-md-4">
                                <label for="novedad_id" class="form-label">Novedad</label>
                                <select class="form-select @error('novedad_id') is-invalid @enderror" 
                                        id="novedad_id" name="novedad_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['novedades'] ?? [] as $novedad)
                                        <option value="{{ $novedad['uuid'] }}" {{ old('novedad_id') == $novedad['uuid'] ? 'selected' : '' }}>
                                            {{ $novedad['tipo_novedad'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('novedad_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Auxiliar -->
                            <div class="col-md-4">
                                <label for="auxiliar_id" class="form-label">Auxiliar</label>
                                <select class="form-select @error('auxiliar_id') is-invalid @enderror" 
                                        id="auxiliar_id" name="auxiliar_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['auxiliares'] ?? [] as $auxiliar)
                                        <option value="{{ $auxiliar['uuid'] }}" {{ old('auxiliar_id') == $auxiliar['uuid'] ? 'selected' : '' }}>
                                            {{ $auxiliar['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('auxiliar_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Brigada -->
                            <div class="col-md-4">
                                <label for="brigada_id" class="form-label">Brigada</label>
                                <select class="form-select @error('brigada_id') is-invalid @enderror" 
                                        id="brigada_id" name="brigada_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($masterData['brigadas'] ?? [] as $brigada)
                                        <option value="{{ $brigada['uuid'] }}" {{ old('brigada_id') == $brigada['uuid'] ? 'selected' : '' }}>
                                            {{ $brigada['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brigada_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. OBSERVACIONES -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background-color: #fd7e14; color: white;">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-sticky-note me-2"></i>8. Observaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observaciones Generales</label>
                            <textarea class="form-control @error('observacion') is-invalid @enderror" 
                                      id="observacion" name="observacion" rows="4" 
                                      placeholder="Ingrese cualquier observación relevante sobre el paciente..." autocomplete="off">{{ old('observacion') }}</textarea>
                            @error('observacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Lateral FIJO -->
            <div class="col-lg-4">
                <div class="sidebar-sticky">
                    <!-- Información del Sistema -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Información del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Sede</label>
                                <div class="fw-bold">{{ $usuario['sede']['nombre'] ?? 'No asignada' }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">Usuario Registra</label>
                                <div class="fw-bold">{{ $usuario['nombre_completo'] ?? 'Usuario' }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">Fecha de Registro</label>
                                <div class="fw-bold">{{ now()->format('d/m/Y H:i') }}</div>
                            </div>
                            
                            @if($isOffline)
                                <div class="alert alert-warning alert-sm">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>Los datos se guardarán localmente y se sincronizarán automáticamente cuando vuelva la conexión.</small>
                                </div>
                            @endif
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <label class="form-label text-muted small">Progreso del Formulario</label>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="formProgress"></div>
                                </div>
                                <small class="text-muted d-block text-center" id="progressText">0% completado</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2 text-primary"></i>Acciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Guardar Paciente
                                </button>
                                
                                <button type="button" class="btn btn-success" onclick="validateForm()">
                                    <i class="fas fa-check-circle me-2"></i>Validar Datos
                                </button>
                                
                                <button type="button" class="btn btn-warning" onclick="manualSaveAsDraft()">
                                    <i class="fas fa-file-alt me-2"></i>Guardar Borrador
                                </button>
                                
                                <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                    <i class="fas fa-eraser me-2"></i>Limpiar Formulario
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary" onclick="handleBackNavigation()">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                            </div>
                            
                            <!-- Información de Campos Requeridos -->
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Los campos marcados con <span class="text-danger">*</span> son obligatorios
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
/* ✅ ESTILOS MEJORADOS PARA LA VISTA CON NAVEGACIÓN */
.sidebar-sticky {
    position: sticky;
    top: 20px;
    z-index: 1020;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    font-weight: 600;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e6ed;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    transform: translateY(-1px);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 8px;
}

/* ✅ ESTILOS PARA BREADCRUMB */
.breadcrumb {
    background-color: transparent;
    padding: 0.5rem 0;
    margin-bottom: 1rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
    font-weight: bold;
}

.breadcrumb-item a {
    color: #0d6efd;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item a:hover {
    color: #0a58ca;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}

/* ✅ ESTILOS PARA ALERTA DE CAMBIOS SIN GUARDAR */
#unsavedChangesAlert {
    position: sticky;
    top: 0;
    z-index: 1030;
    margin-bottom: 1rem;
    border-radius: 0;
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Mejorar espaciado */
.g-3 > * {
    padding: 0.75rem;
}

/* Responsive mejoras */
@media (max-width: 991.98px) {
    .sidebar-sticky {
        position: relative;
        top: auto;
        max-height: none;
        margin-top: 2rem;
    }
}

@media (max-width: 768px) {
    .card-header h5 {
        font-size: 1rem;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
}

/* Animaciones suaves */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

/* Mejorar inputs con iconos */
.input-group .btn {
    border-left: none;
}

.input-group .form-control:focus + .btn {
    border-color: #0d6efd;
}

/* Tooltips personalizados */
.btn-link {
    text-decoration: none !important;
}

.btn-link:hover i {
    transform: scale(1.2);
    transition: transform 0.2s ease;
}

/* ✅ ESTILOS PARA PREVENIR CACHÉ */
form[autocomplete="off"] input,
form[autocomplete="off"] select,
form[autocomplete="off"] textarea {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

/* Indicador visual de cambios sin guardar */
.form-changed {
    border-left: 4px solid #ffc107 !important;
}

.form-changed .card-header {
    background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
}
</style>
@endpush

@push('scripts')
<script>
// ✅ JAVASCRIPT MEJORADO CON NAVEGACIÓN Y PREVENCIÓN DE CACHÉ
let masterData = @json($masterData ?? []);
let autoSaveEnabled = false;
let formChanged = false;
let isSubmitting = false;

// ✅ FUNCIÓN PARA MANEJAR NAVEGACIÓN HACIA ATRÁS
function handleBackNavigation(event) {
    if (event) {
        event.preventDefault();
    }
    
    if (formChanged && !isSubmitting) {
        Swal.fire({
            title: '¿Salir sin guardar?',
            text: 'Hay cambios sin guardar que se perderán',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Salir sin guardar',
            cancelButtonText: 'Continuar editando',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                clearFormCompletely();
                navigateToIndex();
            }
        });
    } else {
        clearFormCompletely();
        navigateToIndex();
    }
}

// ✅ FUNCIÓN PARA NAVEGAR AL INDEX
function navigateToIndex() {
    // Usar replace para evitar que se pueda volver atrás
    window.location.replace('{{ route("pacientes.index") }}');
}

// ✅ FUNCIÓN PARA LIMPIAR COMPLETAMENTE EL FORMULARIO Y STORAGE
function clearFormCompletely() {
    console.log('🧹 Limpiando formulario y storage completamente...');
    
    // Limpiar formulario
    const form = document.getElementById('pacienteForm');
    if (form) {
        form.reset();
    }
    
    // Limpiar validaciones
    resetValidation();
    
    // Limpiar storage
    localStorage.removeItem('paciente_draft');
    localStorage.removeItem('form_data_backup');
    localStorage.removeItem('autoSaveEnabled');
    
    sessionStorage.removeItem('paciente_form_data');
    sessionStorage.removeItem('form_progress');
    
    // Limpiar caché del navegador si es posible
    if ('caches' in window) {
        caches.delete('form-cache');
    }
    
    // Resetear variables
    formChanged = false;
    autoSaveEnabled = false;
    
    // Ocultar alerta de cambios sin guardar
    hideUnsavedAlert();
    
    console.log('✅ Limpieza completa realizada');
}

// ✅ FUNCIÓN PARA MOSTRAR ALERTA DE CAMBIOS SIN GUARDAR
function showUnsavedAlert() {
    const alert = document.getElementById('unsavedChangesAlert');
    if (alert) {
        alert.classList.remove('d-none');
        alert.classList.add('show');
        
        // Agregar clase visual al formulario
        const form = document.getElementById('pacienteForm');
        if (form) {
            form.classList.add('form-changed');
        }
    }
}

// ✅ FUNCIÓN PARA OCULTAR ALERTA DE CAMBIOS SIN GUARDAR
function hideUnsavedAlert() {
    const alert = document.getElementById('unsavedChangesAlert');
    if (alert) {
        alert.classList.add('d-none');
        alert.classList.remove('show');
        
        // Remover clase visual del formulario
        const form = document.getElementById('pacienteForm');
        if (form) {
            form.classList.remove('form-changed');
        }
    }
}

// ✅ MANEJAR ENVÍO DEL FORMULARIO CON PREVENCIÓN DE NAVEGACIÓN HACIA ATRÁS
document.getElementById('pacienteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting) {
        return; // Prevenir envíos múltiples
    }
    
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    
    // Obtener datos del formulario
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message || 'Paciente creado exitosamente');
            
            if (data.offline) {
                showAlert('info', 'Paciente guardado localmente. Se sincronizará cuando vuelva la conexión.', 'Modo Offline');
            }
            
            // ✅ LIMPIAR COMPLETAMENTE ANTES DE REDIRIGIR
            clearFormCompletely();
            
            // ✅ REDIRIGIR CON PARÁMETROS DE ÉXITO Y USAR REPLACE
            setTimeout(() => {
                const successUrl = '{{ route("pacientes.index") }}?created=success&t=' + Date.now();
                window.location.replace(successUrl);
            }, 2000);
        } else {
            showAlert('error', data.error || 'Error al crear paciente');
            
            // Mostrar errores de validación si existen
            if (data.errors) {
                showValidationErrors(data.errors);
            }
            
            isSubmitting = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión al guardar paciente');
        isSubmitting = false;
    })
    .finally(() => {
        // Restaurar botón solo si no fue exitoso
        if (!isSubmitting) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});

// ✅ CARGAR MUNICIPIOS SEGÚN DEPARTAMENTO
function loadMunicipios(tipo) {
    const deptoId = document.getElementById(`depto_${tipo}_id`).value;
    const municipioSelect = document.getElementById(`municipio_${tipo}_id`);
    
    // Limpiar municipios
    municipioSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (!deptoId) {
        municipioSelect.innerHTML = '<option value="">Seleccione departamento primero...</option>';
        return;
    }
    
    // Buscar departamento en masterData
    const departamento = masterData.departamentos?.find(d => d.uuid === deptoId);
    
    if (departamento && departamento.municipios) {
        municipioSelect.innerHTML = '<option value="">Seleccione...</option>';
        departamento.municipios.forEach(municipio => {
            const option = document.createElement('option');
            option.value = municipio.uuid;
            option.textContent = municipio.nombre;
            
            // Restaurar valor si existe en old()
            const oldValue = '{{ old("municipio_' + tipo + '_id") }}';
            if (oldValue && municipio.uuid === oldValue) {
                option.selected = true;
            }
            
            municipioSelect.appendChild(option);
        });
    } else {
        municipioSelect.innerHTML = '<option value="">No hay municipios disponibles</option>';
    }
    
    // Marcar como cambiado
    formChanged = true;
    showUnsavedAlert();
    updateFormProgress();
}

// ✅ BUSCAR PACIENTE EXISTENTE POR DOCUMENTO
function searchExistingPaciente() {
    const documento = document.getElementById('documento').value.trim();
    
    if (!documento) {
        showAlert('warning', 'Debe ingresar un documento para buscar');
        return;
    }
    
    if (documento.length < 3) {
        showAlert('warning', 'El documento debe tener al menos 3 caracteres');
        return;
    }
    
    // Mostrar loading en el botón
    const searchBtn = event.target;
    const originalContent = searchBtn.innerHTML;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    searchBtn.disabled = true;
    
    fetch(`{{ route('pacientes.search.document') }}?documento=${documento}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Paciente encontrado
            Swal.fire({
                title: 'Paciente Encontrado',
                html: `
                    <div class="text-start">
                        <p><strong>Nombre:</strong> ${data.data.primer_nombre} ${data.data.segundo_nombre || ''} ${data.data.primer_apellido} ${data.data.segundo_apellido || ''}</p>
                        <p><strong>Documento:</strong> ${data.data.documento}</p>
                        <p><strong>Fecha Nacimiento:</strong> ${new Date(data.data.fecha_nacimiento).toLocaleDateString()}</p>
                        <p><strong>Estado:</strong> <span class="badge bg-${data.data.estado === 'ACTIVO' ? 'success' : 'danger'}">${data.data.estado}</span></p>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Ver Paciente',
                cancelButtonText: 'Continuar Creando',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    clearFormCompletely();
                    window.location.replace(`/pacientes/${data.data.uuid}`);
                }
            });
        } else {
            // Paciente no encontrado
            showAlert('success', 'Documento disponible para nuevo paciente', 'Documento Libre');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('warning', 'No se pudo verificar el documento. Puede continuar con la creación.');
    })
    .finally(() => {
        // Restaurar botón
        searchBtn.innerHTML = originalContent;
        searchBtn.disabled = false;
    });
}

// ✅ VALIDAR FORMULARIO
function validateForm() {
    const requiredFields = [
        'primer_nombre', 'primer_apellido', 'documento', 
        'fecha_nacimiento', 'sexo', 'tipo_documento_id'
    ];
    
    let isValid = true;
    let missingFields = [];
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (!field || !field.value.trim()) {
            isValid = false;
            const label = field ? field.previousElementSibling.textContent.replace(' *', '') : fieldName;
            missingFields.push(label);
            if (field) {
                field.classList.add('is-invalid');
                // Scroll al primer campo inválido
                if (missingFields.length === 1) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        } else if (field) {
            field.classList.remove('is-invalid');
        }
    });
    
    if (isValid) {
        showAlert('success', 'Todos los campos obligatorios están completos', 'Validación Exitosa');
    } else {
        showAlert('error', `Faltan campos obligatorios: ${missingFields.join(', ')}`, 'Validación Fallida');
    }
    
    updateFormProgress();
    return isValid;
}

// ✅ GUARDAR COMO BORRADOR (MANUAL)
function manualSaveAsDraft() {
    const formData = new FormData(document.getElementById('pacienteForm'));
    const draftData = {};
    let hasData = false;
    
    for (let [key, value] of formData.entries()) {
        if (value.trim()) {
            draftData[key] = value;
            hasData = true;
        }
    }
    
    if (!hasData) {
        showAlert('warning', 'No hay datos para guardar como borrador');
        return;
    }
    
    draftData.saved_at = new Date().toISOString();
    draftData.manual_save = true;
    localStorage.setItem('paciente_draft', JSON.stringify(draftData));
    showAlert('success', 'Borrador guardado localmente', 'Borrador Guardado');
}

// ✅ VERIFICAR SI HAY BORRADOR AL CARGAR LA PÁGINA
function checkForDraft() {
    const draft = localStorage.getItem('paciente_draft');
    if (!draft) return false;
    
    try {
        const draftData = JSON.parse(draft);
        const savedAt = new Date(draftData.saved_at).toLocaleString();
        
        Swal.fire({
            title: 'Borrador Encontrado',
            text: `¿Desea cargar los datos guardados el ${savedAt}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cargar Borrador',
            cancelButtonText: 'Empezar Nuevo',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                loadDraft();
            } else {
                localStorage.removeItem('paciente_draft');
            }
        });
    } catch (error) {
        console.error('Error al parsear borrador:', error);
        localStorage.removeItem('paciente_draft');
    }
}

// ✅ CARGAR BORRADOR
function loadDraft() {
    const draft = localStorage.getItem('paciente_draft');
    if (!draft) return false;
    
    try {
        const draftData = JSON.parse(draft);
               let fieldsLoaded = 0;
        
        Object.keys(draftData).forEach(key => {
            if (key === 'saved_at' || key === 'manual_save' || key === 'auto_saved') return;
            
            const field = document.getElementById(key);
            if (field && draftData[key]) {
                field.value = draftData[key];
                fieldsLoaded++;
                
                // Disparar eventos para municipios
                if (key === 'depto_nacimiento_id' || key === 'depto_residencia_id') {
                    const tipo = key.includes('nacimiento') ? 'nacimiento' : 'residencia';
                    setTimeout(() => loadMunicipios(tipo), 100);
                }
            }
        });
        
        if (fieldsLoaded > 0) {
            updateFormProgress();
            formChanged = true;
            showUnsavedAlert();
            const savedAt = new Date(draftData.saved_at).toLocaleString();
            showAlert('info', `Borrador cargado (${fieldsLoaded} campos) - Guardado: ${savedAt}`, 'Datos Restaurados');
            return true;
        }
    } catch (error) {
        console.error('Error cargando borrador:', error);
        localStorage.removeItem('paciente_draft');
    }
    
    return false;
}

// ✅ LIMPIAR FORMULARIO CON CONFIRMACIÓN
function clearForm() {
    Swal.fire({
        title: '¿Limpiar Formulario?',
        text: 'Se perderán todos los datos ingresados',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            clearFormCompletely();
            
            // Resetear selects de municipios
            document.getElementById('municipio_nacimiento_id').innerHTML = '<option value="">Seleccione departamento primero...</option>';
            document.getElementById('municipio_residencia_id').innerHTML = '<option value="">Seleccione departamento primero...</option>';
            
            updateFormProgress();
            showAlert('success', 'Formulario limpiado correctamente');
        }
    });
}

// ✅ ACTUALIZAR PROGRESO DEL FORMULARIO
function updateFormProgress() {
    const allFields = document.querySelectorAll('input:not([type="hidden"]), select, textarea');
    const filledFields = Array.from(allFields).filter(field => 
        field.value && field.value.trim() !== ''
    );
    
    const progress = Math.round((filledFields.length / allFields.length) * 100);
    const progressBar = document.getElementById('formProgress');
    const progressText = document.getElementById('progressText');
    
    if (progressBar) {
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        
        // Cambiar color según progreso
        progressBar.className = 'progress-bar';
        if (progress < 30) {
            progressBar.classList.add('bg-danger');
        } else if (progress < 70) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-success');
        }
    }
    
    if (progressText) {
        progressText.textContent = `${progress}% completado (${filledFields.length}/${allFields.length} campos)`;
    }
}

// ✅ MOSTRAR ERRORES DE VALIDACIÓN
function showValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            
            // Remover feedback anterior
            const existingFeedback = input.parentNode.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            // Agregar nuevo feedback
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            input.parentNode.appendChild(feedback);
        }
    });
    
    // Scroll al primer error
    const firstError = document.querySelector('.is-invalid');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ✅ FUNCIONES DE COPIA DE DATOS
function copyFromResidencia() {
    if (confirm('¿Copiar datos de residencia a nacimiento?')) {
        const deptoResidencia = document.getElementById('depto_residencia_id').value;
        const municipioResidencia = document.getElementById('municipio_residencia_id').value;
        
        if (deptoResidencia) {
            document.getElementById('depto_nacimiento_id').value = deptoResidencia;
            loadMunicipios('nacimiento');
            
            if (municipioResidencia) {
                setTimeout(() => {
                    document.getElementById('municipio_nacimiento_id').value = municipioResidencia;
                }, 300);
            }
            
            showAlert('success', 'Datos copiados desde residencia');
            formChanged = true;
            showUnsavedAlert();
            updateFormProgress();
        } else {
            showAlert('warning', 'Primero seleccione el departamento de residencia');
        }
    }
}

function copyFromNacimiento() {
    if (confirm('¿Copiar datos de nacimiento a residencia?')) {
        const deptoNacimiento = document.getElementById('depto_nacimiento_id').value;
        const municipioNacimiento = document.getElementById('municipio_nacimiento_id').value;
        
        if (deptoNacimiento) {
            document.getElementById('depto_residencia_id').value = deptoNacimiento;
            loadMunicipios('residencia');
            
            if (municipioNacimiento) {
                setTimeout(() => {
                    document.getElementById('municipio_residencia_id').value = municipioNacimiento;
                }, 300);
            }
            
            showAlert('success', 'Datos copiados desde nacimiento');
            formChanged = true;
            showUnsavedAlert();
            updateFormProgress();
        } else {
            showAlert('warning', 'Primero seleccione el departamento de nacimiento');
        }
    }
}

// ✅ CONFIGURAR VALIDACIONES EN TIEMPO REAL
function setupRealTimeValidation() {
    // Validar fecha de nacimiento
    document.getElementById('fecha_nacimiento').addEventListener('change', function() {
        const fechaNacimiento = new Date(this.value);
        const hoy = new Date();
        const edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
        
        if (edad > 120) {
            showAlert('warning', 'Verifique la fecha de nacimiento, la edad calculada es muy alta');
        } else if (edad < 0) {
            showAlert('warning', 'La fecha de nacimiento no puede ser futura');
        }
    });
    
    // Formatear teléfonos
    ['telefono', 'telefono_acudiente', 'acompanante_telefono'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                this.value = value;
            });
        }
    });
    
    // Validar correo
    document.getElementById('correo').addEventListener('blur', function() {
        const email = this.value;
        if (email && !isValidEmail(email)) {
            this.classList.add('is-invalid');
            let feedback = this.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                this.parentNode.appendChild(feedback);
            }
            feedback.textContent = 'Formato de correo inválido';
        } else {
            this.classList.remove('is-invalid');
            const feedback = this.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();
        }
    });
    
    // Formatear nombres
    ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
     'nombre_acudiente', 'acompanante_nombre'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', function() {
                if (this.value) {
                    this.value = this.value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
                }
            });
        }
    });
}

// ✅ FUNCIÓN AUXILIAR PARA VALIDAR EMAIL
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// ✅ FUNCIÓN PARA MOSTRAR ALERTAS
function showAlert(type, message, title = '') {
    const alertTypes = {
        'success': { icon: 'success', color: '#28a745' },
        'error': { icon: 'error', color: '#dc3545' },
        'warning': { icon: 'warning', color: '#ffc107' },
        'info': { icon: 'info', color: '#17a2b8' }
    };
    
    const config = alertTypes[type] || alertTypes['info'];
    
    Swal.fire({
        title: title || (type.charAt(0).toUpperCase() + type.slice(1)),
        text: message,
        icon: config.icon,
        confirmButtonColor: config.color,
        timer: type === 'success' ? 3000 : undefined,
        timerProgressBar: type === 'success',
        toast: false,
        position: 'center'
    });
}

// ✅ RESETEAR VALIDACIONES
function resetValidation() {
    document.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(element => {
        element.remove();
    });
}

// ✅ DETECTAR SI LA PÁGINA SE CARGA DESDE CACHÉ
function detectPageFromCache() {
    // Verificar si la página se cargó desde caché
    if (performance.navigation.type === 2) {
        console.log('⚠️ Página cargada desde caché, recargando...');
        window.location.reload(true);
        return;
    }
    
    // Verificar si hay datos residuales en el formulario
    const form = document.getElementById('pacienteForm');
    if (form) {
        const formData = new FormData(form);
        let hasResidualData = false;
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() && key !== '_token') {
                hasResidualData = true;
                break;
            }
        }
        
        if (hasResidualData) {
            console.log('⚠️ Datos residuales detectados, limpiando...');
            clearFormCompletely();
        }
    }
}

// ✅ PREVENIR NAVEGACIÓN HACIA ATRÁS CON DATOS
function preventBackNavigation() {
    // Agregar entrada al historial para prevenir navegación directa hacia atrás
    history.pushState(null, null, location.href);
    
    window.addEventListener('popstate', function(event) {
        if (formChanged && !isSubmitting) {
            // Restaurar el estado actual
            history.pushState(null, null, location.href);
            
            // Mostrar confirmación
            handleBackNavigation();
        } else {
            // Permitir navegación si no hay cambios
            clearFormCompletely();
        }
    });
}

// ✅ INICIALIZACIÓN CUANDO SE CARGA EL DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando formulario de paciente con navegación mejorada...');
    
    // ✅ DETECTAR CARGA DESDE CACHÉ
    detectPageFromCache();
    
    // ✅ CONFIGURAR PREVENCIÓN DE NAVEGACIÓN HACIA ATRÁS
    preventBackNavigation();
    
    // ✅ VERIFICAR SI HAY BORRADOR GUARDADO
    setTimeout(() => {
        checkForDraft();
    }, 500);
    
    // ✅ CONFIGURAR HEADERS PARA PREVENIR CACHÉ
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            registrations.forEach(registration => {
                registration.unregister();
            });
        });
    }
    
    // Actualizar progreso inicial
    updateFormProgress();
    
    // Configurar validaciones en tiempo real
    setupRealTimeValidation();
    
    // ✅ ESCUCHAR CAMBIOS EN CAMPOS
    document.querySelectorAll('input, select, textarea').forEach(field => {
        // Excluir campos hidden y CSRF token
        if (field.type === 'hidden' || field.name === '_token') return;
        
        field.addEventListener('change', function() {
            if (!formChanged) {
                formChanged = true;
                showUnsavedAlert();
            }
            updateFormProgress();
        });
        
        field.addEventListener('input', function() {
            if (!formChanged) {
                formChanged = true;
                showUnsavedAlert();
            }
            updateFormProgress();
        });
    });
    
    // ✅ AUTO-GUARDAR CADA 2 MINUTOS (SOLO SI HAY CAMBIOS Y ESTÁ HABILITADO)
    setInterval(() => {
        if (formChanged && autoSaveEnabled && !isSubmitting) {
            autoSaveAsDraft();
        }
    }, 120000); // 2 minutos
    
    // ✅ CONFIGURAR ATAJOS DE TECLADO
    document.addEventListener('keydown', function(e) {
        // Ctrl + S para guardar
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (!isSubmitting) {
                document.getElementById('submitBtn').click();
            }
        }
        
        // Ctrl + D para guardar borrador
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            manualSaveAsDraft();
        }
        
        // Ctrl + V para validar
        if (e.ctrlKey && e.key === 'v') {
            e.preventDefault();
            validateForm();
        }
        
        // Escape para cancelar
        if (e.key === 'Escape') {
            handleBackNavigation();
        }
    });
    
    // ✅ PREVENIR SALIDA ACCIDENTAL CON CAMBIOS SIN GUARDAR
    window.addEventListener('beforeunload', function(e) {
        if (formChanged && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '¿Está seguro de que desea salir? Los cambios no guardados se perderán.';
            return e.returnValue;
        }
    });
    
    // ✅ MANEJAR VISIBILIDAD DE LA PÁGINA
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Página oculta - guardar estado si hay cambios
            if (formChanged && autoSaveEnabled) {
                autoSaveAsDraft();
            }
        } else {
            // Página visible - verificar si hay datos residuales
            detectPageFromCache();
        }
    });
    
    // ✅ CONFIGURAR TOOLTIPS PARA AYUDA
    const fieldsWithHelp = [
        { id: 'registro', help: 'Número único de registro del paciente. Se genera automáticamente si se deja vacío.' },
        { id: 'zona_residencia_id', help: 'Zona donde reside el paciente (Urbana, Rural, etc.).' },
        { id: 'parentesco_id', help: 'Relación del paciente con el titular de la afiliación.' },
        { id: 'novedad_id', help: 'Tipo de novedad o situación especial del paciente.' },
        { id: 'auxiliar_id', help: 'Auxiliar asignado para el seguimiento del paciente.' },
        { id: 'brigada_id', help: 'Brigada de salud asignada al paciente.' }
    ];
    
    fieldsWithHelp.forEach(item => {
        const field = document.getElementById(item.id);
        if (field) {
            const helpIcon = document.createElement('i');
            helpIcon.className = 'fas fa-question-circle text-muted ms-1';
            helpIcon.style.cursor = 'pointer';
            helpIcon.title = item.help;
            helpIcon.onclick = () => showAlert('info', item.help, 'Ayuda');
            
            const label = field.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                label.appendChild(helpIcon);
            }
        }
    });
    
    // ✅ CONFIGURAR BOTÓN PARA HABILITAR/DESHABILITAR AUTO-SAVE
    const autoSaveToggle = document.createElement('div');
    autoSaveToggle.className = 'form-check form-switch mt-2';
    autoSaveToggle.innerHTML = `
        <input class="form-check-input" type="checkbox" id="autoSaveToggle" ${autoSaveEnabled ? 'checked' : ''}>
        <label class="form-check-label small text-muted" for="autoSaveToggle">
            Auto-guardar borrador
        </label>
    `;
    
    const actionsCard = document.querySelector('.card:has(.btn-primary)');
    if (actionsCard) {
        const cardBody = actionsCard.querySelector('.card-body');
        cardBody.appendChild(autoSaveToggle);
        
        document.getElementById('autoSaveToggle').addEventListener('change', function() {
            autoSaveEnabled = this.checked;
            localStorage.setItem('autoSaveEnabled', autoSaveEnabled);
            showAlert('info', `Auto-guardado ${autoSaveEnabled ? 'activado' : 'desactivado'}`);
        });
    }
    
    // ✅ RESTAURAR PREFERENCIA DE AUTO-SAVE
    const savedAutoSave = localStorage.getItem('autoSaveEnabled');
    if (savedAutoSave !== null) {
        autoSaveEnabled = savedAutoSave === 'true';
        const toggle = document.getElementById('autoSaveToggle');
        if (toggle) toggle.checked = autoSaveEnabled;
    }
    
    // ✅ CARGAR MUNICIPIOS SI HAY VALORES OLD
    const oldDeptoNacimiento = '{{ old("depto_nacimiento_id") }}';
    const oldDeptoResidencia = '{{ old("depto_residencia_id") }}';
    
    if (oldDeptoNacimiento) {
        setTimeout(() => loadMunicipios('nacimiento'), 100);
    }
    
    if (oldDeptoResidencia) {
        setTimeout(() => loadMunicipios('residencia'), 100);
    }
    
    console.log('✅ Formulario inicializado correctamente con navegación mejorada');
});

// ✅ AUTO-GUARDAR SILENCIOSO
function autoSaveAsDraft() {
    if (!formChanged || !autoSaveEnabled || isSubmitting) return;
    
    const formData = new FormData(document.getElementById('pacienteForm'));
    const draftData = {};
    let hasData = false;
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() && key !== '_token') {
            draftData[key] = value;
            hasData = true;
        }
    }
    
    if (hasData) {
        draftData.saved_at = new Date().toISOString();
        draftData.auto_saved = true;
        localStorage.setItem('paciente_draft', JSON.stringify(draftData));
        console.log('💾 Auto-guardado realizado silenciosamente');
    }
}

// ✅ LIMPIAR DATOS AL SALIR DE LA PÁGINA
window.addEventListener('unload', function() {
    if (!isSubmitting) {
        // Solo limpiar si no se está enviando el formulario
        clearFormCompletely();
    }
});

// ✅ FUNCIÓN PARA ESTADÍSTICAS DEL FORMULARIO
function showFormStats() {
    const allFields = document.querySelectorAll('input:not([type="hidden"]), select, textarea');
    const filledFields = Array.from(allFields).filter(field => field.value && field.value.trim() !== '');
    const requiredFields = document.querySelectorAll('[required]');
    const filledRequiredFields = Array.from(requiredFields).filter(field => field.value && field.value.trim() !== '');
    
    const stats = {
        total: allFields.length,
        filled: filledFields.length,
        required: requiredFields.length,
        requiredFilled: filledRequiredFields.length,
        percentage: Math.round((filledFields.length / allFields.length) * 100)
    };
    
    Swal.fire({
        title: 'Estadísticas del Formulario',
        html: `
            <div class="text-start">
                <p><strong>Campos totales:</strong> ${stats.total}</p>
                <p><strong>Campos completados:</strong> ${stats.filled} (${stats.percentage}%)</p>
                <p><strong>Campos obligatorios:</strong> ${stats.required}</p>
                <p><strong>Obligatorios completados:</strong> ${stats.requiredFilled}</p>
                <div class="progress mt-3">
                    <div class="progress-bar ${stats.percentage < 30 ? 'bg-danger' : stats.percentage < 70 ? 'bg-warning' : 'bg-success'}" 
                         style="width: ${stats.percentage}%">${stats.percentage}%</div>
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Cerrar'
    });
}

// ✅ AGREGAR BOTÓN DE ESTADÍSTICAS
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const actionsDiv = document.querySelector('.d-grid.gap-2');
        if (actionsDiv) {
            const statsBtn = document.createElement('button');
            statsBtn.type = 'button';
            statsBtn.className = 'btn btn-info btn-sm';
            statsBtn.innerHTML = '<i class="fas fa-chart-bar me-2"></i>Ver Estadísticas';
            statsBtn.onclick = showFormStats;
            
            actionsDiv.appendChild(statsBtn);
        }
    }, 1000);
});

// ✅ FUNCIÓN PARA EXPORTAR/IMPORTAR DATOS (OPCIONAL)
function exportFormData() {
    const formData = new FormData(document.getElementById('pacienteForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() && key !== '_token') {
            data[key] = value;
        }
    }
    
    if (Object.keys(data).length === 0) {
        showAlert('warning', 'No hay datos para exportar');
        return;
    }
    
    data.exported_at = new Date().toISOString();
    data.exported_by = '{{ $usuario["nombre_completo"] ?? "Usuario" }}';
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `paciente_${data.documento || 'sin_documento'}_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showAlert('success', 'Datos exportados correctamente');
}
</script>

<!-- ✅ HEADERS PARA PREVENIR CACHÉ -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

@endpush
@endsection
