{{-- resources/views/pacientes/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Paciente - SIDS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Editar Paciente
                    </h1>
                    <p class="text-muted mb-0">
                        Modificar información de {{ $paciente['primer_nombre'] }} {{ $paciente['primer_apellido'] }}
                        <span class="badge bg-secondary ms-2">{{ $paciente['documento'] }}</span>
                    </p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @endif
                    
                    <a href="{{ route('pacientes.show', $paciente['uuid']) }}" class="btn btn-info">
                        <i class="fas fa-eye me-1"></i>Ver Paciente
                    </a>
                    
                    <a href="{{ route('pacientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Offline -->
    @if($isOffline)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Modo Offline</strong>
                    <p class="mb-0">Los cambios se guardarán localmente y se sincronizarán cuando vuelva la conexión.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Formulario -->
    <form id="pacienteEditForm" method="POST" action="{{ route('pacientes.update', $paciente['uuid']) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Información Personal -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Información Personal
                        </h5>
                        <small class="text-muted">Campos obligatorios marcados con *</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Registro -->
                            <div class="col-md-6">
                                <label for="registro" class="form-label">Registro</label>
                                <input type="text" class="form-control @error('registro') is-invalid @enderror" 
                                       id="registro" name="registro" 
                                       value="{{ old('registro', $paciente['registro'] ?? '') }}">
                                @error('registro')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tipo de Documento -->
                            <div class="col-md-6">
                                <label for="tipo_documento_id" class="form-label">Tipo de Documento</label>
                                <select class="form-select @error('tipo_documento_id') is-invalid @enderror" 
                                        id="tipo_documento_id" name="tipo_documento_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['tipos_documento']))
                                        @foreach($masterData['tipos_documento'] as $tipo)
                                            <option value="{{ $tipo['uuid'] }}" 
                                                {{ old('tipo_documento_id', $paciente['tipo_documento_id'] ?? '') == $tipo['uuid'] ? 'selected' : '' }}>
                                                {{ $tipo['abreviacion'] }} - {{ $tipo['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('tipo_documento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Nombres -->
                            <div class="col-md-6">
                                <label for="primer_nombre" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_nombre') is-invalid @enderror" 
                                       id="primer_nombre" name="primer_nombre" 
                                       value="{{ old('primer_nombre', $paciente['primer_nombre']) }}" required>
                                @error('primer_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control @error('segundo_nombre') is-invalid @enderror" 
                                       id="segundo_nombre" name="segundo_nombre" 
                                       value="{{ old('segundo_nombre', $paciente['segundo_nombre'] ?? '') }}">
                                @error('segundo_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Apellidos -->
                            <div class="col-md-6">
                                <label for="primer_apellido" class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('primer_apellido') is-invalid @enderror" 
                                       id="primer_apellido" name="primer_apellido" 
                                       value="{{ old('primer_apellido', $paciente['primer_apellido']) }}" required>
                                @error('primer_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                                <input type="text" class="form-control @error('segundo_apellido') is-invalid @enderror" 
                                       id="segundo_apellido" name="segundo_apellido" 
                                       value="{{ old('segundo_apellido', $paciente['segundo_apellido'] ?? '') }}">
                                @error('segundo_apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Documento -->
                            <div class="col-md-6">
                                <label for="documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('documento') is-invalid @enderror" 
                                           id="documento" name="documento" 
                                           value="{{ old('documento', $paciente['documento']) }}" required>
                                    <button type="button" class="btn btn-outline-info" onclick="validateDocument()" title="Validar documento">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @error('documento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Verifique que el documento sea único</small>
                            </div>
                            
                            <!-- Fecha Nacimiento -->
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror" 
                                       id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="{{ old('fecha_nacimiento', isset($paciente['fecha_nacimiento']) ? \Carbon\Carbon::parse($paciente['fecha_nacimiento'])->format('Y-m-d') : '') }}" required>
                                @error('fecha_nacimiento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted" id="edadCalculada"></small>
                            </div>
                            
                            <!-- Sexo -->
                            <div class="col-md-6">
                                <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                                <select class="form-select @error('sexo') is-invalid @enderror" id="sexo" name="sexo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="M" {{ old('sexo', $paciente['sexo'] ?? '') == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('sexo', $paciente['sexo'] ?? '') == 'F' ? 'selected' : '' }}>Femenino</option>
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
                                    <option value="SOLTERO" {{ old('estado_civil', $paciente['estado_civil'] ?? '') == 'SOLTERO' ? 'selected' : '' }}>Soltero(a)</option>
                                    <option value="CASADO" {{ old('estado_civil', $paciente['estado_civil'] ?? '') == 'CASADO' ? 'selected' : '' }}>Casado(a)</option>
                                    <option value="UNION_LIBRE" {{ old('estado_civil', $paciente['estado_civil'] ?? '') == 'UNION_LIBRE' ? 'selected' : '' }}>Unión Libre</option>
                                    <option value="DIVORCIADO" {{ old('estado_civil', $paciente['estado_civil'] ?? '') == 'DIVORCIADO' ? 'selected' : '' }}>Divorciado(a)</option>
                                    <option value="VIUDO" {{ old('estado_civil', $paciente['estado_civil'] ?? '') == 'VIUDO' ? 'selected' : '' }}>Viudo(a)</option>
                                </select>
                                @error('estado_civil')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado">
                                    <option value="ACTIVO" {{ old('estado', $paciente['estado'] ?? 'ACTIVO') == 'ACTIVO' ? 'selected' : '' }}>Activo</option>
                                    <option value="INACTIVO" {{ old('estado', $paciente['estado'] ?? 'ACTIVO') == 'INACTIVO' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                            <div class="col-md-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror" 
                                       id="direccion" name="direccion" 
                                       value="{{ old('direccion', $paciente['direccion'] ?? '') }}">
                                @error('direccion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                       id="telefono" name="telefono" 
                                       value="{{ old('telefono', $paciente['telefono'] ?? '') }}">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control @error('correo') is-invalid @enderror" 
                                       id="correo" name="correo" 
                                       value="{{ old('correo', $paciente['correo'] ?? '') }}">
                                @error('correo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Afiliación -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-id-card me-2"></i>Información de Afiliación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Empresa -->
                            <div class="col-md-6">
                                <label for="empresa_id" class="form-label">Empresa/EPS</label>
                                <select class="form-select @error('empresa_id') is-invalid @enderror" 
                                        id="empresa_id" name="empresa_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['empresas']))
                                        @foreach($masterData['empresas'] as $empresa)
                                            <option value="{{ $empresa['uuid'] }}" 
                                                {{ old('empresa_id', $paciente['empresa_id'] ?? '') == $empresa['uuid'] ? 'selected' : '' }}>
                                                {{ $empresa['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
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
                                    @if(isset($masterData['regimenes']))
                                        @foreach($masterData['regimenes'] as $regimen)
                                            <option value="{{ $regimen['uuid'] }}" 
                                                {{ old('regimen_id', $paciente['regimen_id'] ?? '') == $regimen['uuid'] ? 'selected' : '' }}>
                                                {{ $regimen['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('regimen_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tipo de Afiliación -->
                            <div class="col-md-6">
                                <label for="tipo_afiliacion_id" class="form-label">Tipo de Afiliación</label>
                                <select class="form-select @error('tipo_afiliacion_id') is-invalid @enderror" 
                                        id="tipo_afiliacion_id" name="tipo_afiliacion_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['tipos_afiliacion']))
                                        @foreach($masterData['tipos_afiliacion'] as $tipo)
                                            <option value="{{ $tipo['uuid'] }}" 
                                                {{ old('tipo_afiliacion_id', $paciente['tipo_afiliacion_id'] ?? '') == $tipo['uuid'] ? 'selected' : '' }}>
                                                {{ $tipo['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
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
                                    @if(isset($masterData['tipos_parentesco']))
                                        @foreach($masterData['tipos_parentesco'] as $parentesco)
                                            <option value="{{ $parentesco['uuid'] }}" 
                                                {{ old('parentesco_id', $paciente['parentesco_id'] ?? '') == $parentesco['uuid'] ? 'selected' : '' }}>
                                                {{ $parentesco['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('parentesco_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Geográfica -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>Información Geográfica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Departamento de Nacimiento -->
                            <div class="col-md-6">
                                <label for="depto_nacimiento_id" class="form-label">Departamento de Nacimiento</label>
                                <select class="form-select @error('depto_nacimiento_id') is-invalid @enderror" 
                                        id="depto_nacimiento_id" name="depto_nacimiento_id" onchange="loadMunicipiosNacimiento()">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['departamentos']))
                                        @foreach($masterData['departamentos'] as $depto)
                                            <option value="{{ $depto['uuid'] }}" 
                                                {{ old('depto_nacimiento_id', $paciente['depto_nacimiento_id'] ?? '') == $depto['uuid'] ? 'selected' : '' }}>
                                                {{ $depto['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('depto_nacimiento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Municipio de Nacimiento -->
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

                            <!-- Departamento de Residencia -->
                            <div class="col-md-6">
                                <label for="depto_residencia_id" class="form-label">Departamento de Residencia</label>
                                <select class="form-select @error('depto_residencia_id') is-invalid @enderror" 
                                        id="depto_residencia_id" name="depto_residencia_id" onchange="loadMunicipiosResidencia()">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['departamentos']))
                                        @foreach($masterData['departamentos'] as $depto)
                                            <option value="{{ $depto['uuid'] }}" 
                                                {{ old('depto_residencia_id', $paciente['depto_residencia_id'] ?? '') == $depto['uuid'] ? 'selected' : '' }}>
                                                {{ $depto['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('depto_residencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Municipio de Residencia -->
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

                            <!-- Zona de Residencia -->
                            <div class="col-md-6">
                                <label for="zona_residencia_id" class="form-label">Zona de Residencia</label>
                                <select class="form-select @error('zona_residencia_id') is-invalid @enderror" 
                                        id="zona_residencia_id" name="zona_residencia_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['zonas_residenciales']))
                                        @foreach($masterData['zonas_residenciales'] as $zona)
                                            <option value="{{ $zona['uuid'] }}" 
                                                {{ old('zona_residencia_id', $paciente['zona_residencia_id'] ?? '') == $zona['uuid'] ? 'selected' : '' }}>
                                                {{ $zona['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('zona_residencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info me-2"></i>Información Adicional
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Raza -->
                            <div class="col-md-6">
                                <label for="raza_id" class="form-label">Raza/Etnia</label>
                                <select class="form-select @error('raza_id') is-invalid @enderror" 
                                        id="raza_id" name="raza_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['razas']))
                                        @foreach($masterData['razas'] as $raza)
                                            <option value="{{ $raza['uuid'] }}" 
                                                {{ old('raza_id', $paciente['raza_id'] ?? '') == $raza['uuid'] ? 'selected' : '' }}>
                                                {{ $raza['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('raza_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Escolaridad -->
                            <div class="col-md-6">
                                <label for="escolaridad_id" class="form-label">Escolaridad</label>
                                <select class="form-select @error('escolaridad_id') is-invalid @enderror" 
                                        id="escolaridad_id" name="escolaridad_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['escolaridades']))
                                        @foreach($masterData['escolaridades'] as $escolaridad)
                                            <option value="{{ $escolaridad['uuid'] }}" 
                                                {{ old('escolaridad_id', $paciente['escolaridad_id'] ?? '') == $escolaridad['uuid'] ? 'selected' : '' }}>
                                                {{ $escolaridad['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
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
                                    @if(isset($masterData['ocupaciones']))
                                        @foreach($masterData['ocupaciones'] as $ocupacion)
                                            <option value="{{ $ocupacion['uuid'] }}" 
                                                {{ old('ocupacion_id', $paciente['ocupacion_id'] ?? '') == $ocupacion['uuid'] ? 'selected' : '' }}>
                                                {{ $ocupacion['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('ocupacion_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Novedad -->
                            <div class="col-md-6">
                                <label for="novedad_id" class="form-label">Novedad</label>
                                <select class="form-select @error('novedad_id') is-invalid @enderror" 
                                        id="novedad_id" name="novedad_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['novedades']))
                                        @foreach($masterData['novedades'] as $novedad)
                                            <option value="{{ $novedad['uuid'] }}" 
                                                {{ old('novedad_id', $paciente['novedad_id'] ?? '') == $novedad['uuid'] ? 'selected' : '' }}>
                                                {{ $novedad['tipo_novedad'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('novedad_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Auxiliar -->
                            <div class="col-md-6">
                                <label for="auxiliar_id" class="form-label">Auxiliar</label>
                                <select class="form-select @error('auxiliar_id') is-invalid @enderror" 
                                        id="auxiliar_id" name="auxiliar_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['auxiliares']))
                                        @foreach($masterData['auxiliares'] as $auxiliar)
                                            <option value="{{ $auxiliar['uuid'] }}" 
                                                {{ old('auxiliar_id', $paciente['auxiliar_id'] ?? '') == $auxiliar['uuid'] ? 'selected' : '' }}>
                                                                                               {{ $auxiliar['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('auxiliar_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Brigada -->
                            <div class="col-md-6">
                                <label for="brigada_id" class="form-label">Brigada</label>
                                <select class="form-select @error('brigada_id') is-invalid @enderror" 
                                        id="brigada_id" name="brigada_id">
                                    <option value="">Seleccione...</option>
                                    @if(isset($masterData['brigadas']))
                                        @foreach($masterData['brigadas'] as $brigada)
                                            <option value="{{ $brigada['uuid'] }}" 
                                                {{ old('brigada_id', $paciente['brigada_id'] ?? '') == $brigada['uuid'] ? 'selected' : '' }}>
                                                {{ $brigada['nombre'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('brigada_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Acudiente -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-friends me-2"></i>Información del Acudiente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre_acudiente" class="form-label">Nombre del Acudiente</label>
                                <input type="text" class="form-control @error('nombre_acudiente') is-invalid @enderror" 
                                       id="nombre_acudiente" name="nombre_acudiente" 
                                       value="{{ old('nombre_acudiente', $paciente['nombre_acudiente'] ?? '') }}">
                                @error('nombre_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="parentesco_acudiente" class="form-label">Parentesco del Acudiente</label>
                                <input type="text" class="form-control @error('parentesco_acudiente') is-invalid @enderror" 
                                       id="parentesco_acudiente" name="parentesco_acudiente" 
                                       value="{{ old('parentesco_acudiente', $paciente['parentesco_acudiente'] ?? '') }}">
                                @error('parentesco_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="telefono_acudiente" class="form-label">Teléfono del Acudiente</label>
                                <input type="text" class="form-control @error('telefono_acudiente') is-invalid @enderror" 
                                       id="telefono_acudiente" name="telefono_acudiente" 
                                       value="{{ old('telefono_acudiente', $paciente['telefono_acudiente'] ?? '') }}">
                                @error('telefono_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="direccion_acudiente" class="form-label">Dirección del Acudiente</label>
                                <input type="text" class="form-control @error('direccion_acudiente') is-invalid @enderror" 
                                       id="direccion_acudiente" name="direccion_acudiente" 
                                       value="{{ old('direccion_acudiente', $paciente['direccion_acudiente'] ?? '') }}">
                                @error('direccion_acudiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Acompañante -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Información del Acompañante
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="acompanante_nombre" class="form-label">Nombre del Acompañante</label>
                                <input type="text" class="form-control @error('acompanante_nombre') is-invalid @enderror" 
                                       id="acompanante_nombre" name="acompanante_nombre" 
                                       value="{{ old('acompanante_nombre', $paciente['acompanante_nombre'] ?? '') }}">
                                @error('acompanante_nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="acompanante_telefono" class="form-label">Teléfono del Acompañante</label>
                                <input type="text" class="form-control @error('acompanante_telefono') is-invalid @enderror" 
                                       id="acompanante_telefono" name="acompanante_telefono" 
                                       value="{{ old('acompanante_telefono', $paciente['acompanante_telefono'] ?? '') }}">
                                @error('acompanante_telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Observaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observaciones Generales</label>
                            <textarea class="form-control @error('observacion') is-invalid @enderror" 
                                      id="observacion" name="observacion" rows="3">{{ old('observacion', $paciente['observacion'] ?? '') }}</textarea>
                            @error('observacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Lateral -->
            <div class="col-lg-4">
                <!-- Información del Sistema -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Información del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">UUID</label>
                            <input type="text" class="form-control font-monospace" value="{{ $paciente['uuid'] }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sede</label>
                            <input type="text" class="form-control" value="{{ $usuario['sede']['nombre'] ?? 'No asignada' }}" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" 
                                   value="{{ isset($paciente['fecha_registro']) ? \Carbon\Carbon::parse($paciente['fecha_registro'])->format('d/m/Y') : 'No disponible' }}" readonly>
                        </div>
                        
                        @if(isset($paciente['fecha_actualizacion']) && $paciente['fecha_actualizacion'])
                        <div class="mb-3">
                            <label class="form-label">Última Actualización</label>
                            <input type="text" class="form-control" 
                                   value="{{ \Carbon\Carbon::parse($paciente['fecha_actualizacion'])->format('d/m/Y H:i') }}" readonly>
                        </div>
                        @endif
                        
                        <div class="mb-3">
                            <label class="form-label">Estado Actual</label>
                            <div>
                                <span class="badge bg-{{ ($paciente['estado'] ?? 'ACTIVO') === 'ACTIVO' ? 'success' : 'danger' }} fs-6">
                                    {{ $paciente['estado'] ?? 'ACTIVO' }}
                                </span>
                            </div>
                        </div>

                        @if(isset($paciente['sync_status']) && $paciente['sync_status'] === 'pending')
                        <div class="mb-3">
                            <label class="form-label">Estado de Sincronización</label>
                            <div>
                                <span class="badge bg-warning fs-6">
                                    <i class="fas fa-sync-alt me-1"></i>Pendiente
                                </span>
                            </div>
                        </div>
                        @endif
                        
                        @if($isOffline)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>Los cambios se sincronizarán automáticamente cuando vuelva la conexión.</small>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Historial de Cambios -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Historial de Cambios
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="historialCambios">
                            <div class="text-center py-3">
                                <i class="fas fa-clock text-muted"></i>
                                <p class="text-muted mb-0 mt-2">Sin cambios recientes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                            
                            <button type="button" class="btn btn-warning" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Deshacer Cambios
                            </button>
                            
                            <hr>
                            
                            <a href="{{ route('pacientes.show', $paciente['uuid']) }}" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Ver Paciente
                            </a>
                            
                            <a href="{{ route('pacientes.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
.sync-pending-badge {
    animation: pulse 2s infinite;
    border-left: 4px solid #ffc107 !important;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

.btn-warning.sync-btn {
    animation: glow 1.5s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 5px rgba(255, 193, 7, 0.5); }
    to { box-shadow: 0 0 15px rgba(255, 193, 7, 0.8); }
}

.offline-indicator {
    position: relative;
}

.offline-indicator::before {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background-color: #ffc107;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

.historial-entry {
    transition: all 0.3s ease;
}

.historial-entry:hover {
    background-color: rgba(0,123,255,0.05);
    border-radius: 5px;
    padding: 8px;
    margin: -8px;
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
    border: none;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #ffc107;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border-left: 4px solid #17a2b8;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left: 4px solid #28a745;
}
</style>
@endpush

@push('scripts')
<script>
// Variables globales
const masterData = @json($masterData ?? []);
const originalData = @json($paciente);
let hasChanges = false;

// ✅ FUNCIÓN PARA MOSTRAR ALERTAS
function showAlert(type, message, title = '') {
    // Mapear tipos a clases de Bootstrap
    const typeMap = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'info'
    };
    
    const alertClass = typeMap[type] || 'info';
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-circle' : 
                 type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    const alertHtml = `
        <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${icon} me-2"></i>
            ${title ? '<strong>' + title + '</strong><br>' : ''}
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insertar al inicio del contenedor
    const container = document.querySelector('.container-fluid');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = alertHtml;
    container.insertBefore(tempDiv.firstElementChild, container.firstChild);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadInitialMunicipios();
    setupChangeDetectors();
    calculateAge();
    checkConnectionStatus();
});

// Cargar municipios iniciales
function loadInitialMunicipios() {
    const deptoNacimientoId = document.getElementById('depto_nacimiento_id').value;
    const deptoResidenciaId = document.getElementById('depto_residencia_id').value;
    
    if (deptoNacimientoId) {
        loadMunicipiosNacimiento();
    }
    
    if (deptoResidenciaId) {
        loadMunicipiosResidencia();
    }
}

// Cargar municipios de nacimiento
function loadMunicipiosNacimiento() {
    const deptoId = document.getElementById('depto_nacimiento_id').value;
    const municipioSelect = document.getElementById('municipio_nacimiento_id');
    const selectedMunicipio = originalData.municipio_nacimiento_id || '';
    
    municipioSelect.innerHTML = '<option value="">Seleccione...</option>';
    
    if (deptoId && masterData.departamentos) {
        const departamento = masterData.departamentos.find(d => d.uuid === deptoId);
        if (departamento && departamento.municipios) {
            departamento.municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio.uuid;
                option.textContent = municipio.nombre;
                if (municipio.uuid === selectedMunicipio) {
                    option.selected = true;
                }
                municipioSelect.appendChild(option);
            });
        }
    }
}

// Cargar municipios de residencia
function loadMunicipiosResidencia() {
    const deptoId = document.getElementById('depto_residencia_id').value;
    const municipioSelect = document.getElementById('municipio_residencia_id');
    const selectedMunicipio = originalData.municipio_residencia_id || '';
    
    municipioSelect.innerHTML = '<option value="">Seleccione...</option>';
    
    if (deptoId && masterData.departamentos) {
        const departamento = masterData.departamentos.find(d => d.uuid === deptoId);
        if (departamento && departamento.municipios) {
            departamento.municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio.uuid;
                option.textContent = municipio.nombre;
                if (municipio.uuid === selectedMunicipio) {
                    option.selected = true;
                }
                municipioSelect.appendChild(option);
            });
        }
    }
}

// Configurar detectores de cambios
function setupChangeDetectors() {
    const form = document.getElementById('pacienteEditForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            detectChanges();
        });
        
        input.addEventListener('input', function() {
            detectChanges();
        });
    });
}

// ✅ MANEJAR ENVÍO DEL FORMULARIO - VERSIÓN MEJORADA PARA OFFLINE
document.getElementById('pacienteEditForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    const isOffline = {{ $isOffline ? 'true' : 'false' }};
    
    // ✅ SI ESTÁ OFFLINE, DEJAR QUE EL SUBMIT TRADICIONAL MANEJE TODO
    if (isOffline) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando Offline...';
        // NO prevenir default - dejar que Laravel redirija
        return true;
    }
    
    // ✅ SI ESTÁ ONLINE, USAR AJAX
    e.preventDefault();
    
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
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (data.offline) {
                // ✅ MODO OFFLINE - Cambios guardados localmente, REDIRIGIR AL INDEX
                showAlert('success', 'Paciente actualizado y guardado localmente. Los cambios se sincronizarán cuando vuelva la conexión.', 'Guardado Offline');
                
                // Redirigir al index después de mostrar el mensaje
                setTimeout(() => {
                    window.location.href = '/pacientes';
                }, 1500);
                
            } else {
                // ✅ MODO ONLINE - Cambios guardados en servidor
                showAlert('success', data.message || 'Paciente actualizado exitosamente');
                
                // Redirigir a la vista del paciente después de 2 segundos
                setTimeout(() => {
                    window.location.href = `/pacientes/{{ $paciente['uuid'] }}`;
                }, 2000);
            }
            
            // Marcar que no hay cambios pendientes
            hasChanges = false;
            
        } else {
            showAlert('error', data.error || 'Error al actualizar paciente');
            
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        if (isOffline) {
            // En modo offline, el error puede ser esperado si no hay AJAX response
            // Esto significa que el submit tradicional tomó control
            showAlert('info', 'Guardando cambios offline...', 'Modo Offline');
            
            // Esperar un poco y redirigir al index
            setTimeout(() => {
                window.location.href = '/pacientes';
            }, 1500);
        } else {
            showAlert('error', 'Error de conexión al actualizar paciente');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .finally(() => {
        // Restaurar botón si no está en modo offline
        if (!isOffline || !submitBtn.classList.contains('btn-warning')) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});

// ✅ NUEVA FUNCIÓN: Actualizar botón para modo offline
function updateOfflineButton(submitBtn) {
    submitBtn.disabled = false;
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-warning');
    submitBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Sincronizar Cambios';
    
    // Agregar evento para sincronización manual
    submitBtn.onclick = function() {
        syncPendingChanges();
    };
}

// ✅ NUEVA FUNCIÓN: Mostrar badge de sincronización pendiente
function showSyncPendingBadge() {
    // Buscar si ya existe el badge
    let existingBadge = document.querySelector('.sync-pending-badge');
    
    if (!existingBadge) {
        // ✅ OBTENER CONTEO DE CAMBIOS PENDIENTES
        getPendingChangesCount().then(count => {
            const badge = document.createElement('div');
            badge.className = 'alert alert-warning sync-pending-badge mt-3';
            badge.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div class="flex-grow-1">
                        <strong>Cambios Pendientes de Sincronización</strong>
                        <p class="mb-0">
                            Tienes <span class="badge bg-warning text-dark">${count.total}</span> cambios pendientes:
                            ${count.pacientes > 0 ? `${count.pacientes} pacientes` : ''}
                            ${count.otros > 0 ? `${count.otros} otros cambios` : ''}
                        </p>
                        <small class="text-muted">Se sincronizarán automáticamente cuando vuelva la conexión.</small>
                    </div>
                    <button type="button" class="btn btn-warning ms-2" onclick="syncPendingChanges()">
                        <i class="fas fa-sync-alt me-1"></i>Sincronizar Todo (${count.total})
                    </button>
                </div>
            `;
            
            // Insertar después del header
            const container = document.querySelector('.container-fluid');
            const header = container.querySelector('.row.mb-4');
            header.insertAdjacentElement('afterend', badge);
        });
    }
}

// ✅ NUEVA FUNCIÓN: Obtener conteo de cambios pendientes
function getPendingChangesCount() {
    return fetch('{{ route("pacientes.pending.count") }}', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        return {
            total: data.total || 0,
            pacientes: data.pacientes || 0,
            otros: data.otros || 0
        };
    })
    .catch(error => {
        console.error('Error obteniendo conteo:', error);
        return { total: 0, pacientes: 0, otros: 0 };
    });
}

// ✅ FUNCIÓN MEJORADA: Sincronizar TODOS los cambios pendientes (creates + updates)
function syncPendingChanges() {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sincronizando...';
    
    // ✅ LLAMAR AL ENDPOINT GENERAL DE SINCRONIZACIÓN
    fetch('{{ route("pacientes.sync.all") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ✅ MOSTRAR RESULTADOS DETALLADOS
            let message = 'Sincronización completada:\n';
            
            if (data.created_count > 0) {
                message += `• ${data.created_count} pacientes nuevos sincronizados\n`;
            }
            
            if (data.updated_count > 0) {
                message += `• ${data.updated_count} pacientes editados sincronizados\n`;
            }
            
            if (data.failed_count > 0) {
                message += `• ${data.failed_count} errores encontrados`;
                showAlert('warning', message, 'Sincronización Parcial');
            } else {
                showAlert('success', message, 'Sincronización Exitosa');
            }
            
            // ✅ SI HAY SINCRONIZACIONES EXITOSAS
            if ((data.created_count + data.updated_count) > 0) {
                // Remover badge de pendiente
                const badge = document.querySelector('.sync-pending-badge');
                if (badge) badge.remove();
                
                // Restaurar botón normal
                submitBtn.classList.remove('btn-warning');
                submitBtn.classList.add('btn-primary');
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Cambios';
                submitBtn.onclick = null;
                
                // Actualizar historial
                addToHistorial('Todos los cambios sincronizados con el servidor', 'success');
                
                // ✅ REDIRIGIR SOLO SI ESTAMOS EN EDIT Y NO HAY ERRORES
                if (window.location.pathname.includes('/edit') && data.failed_count === 0) {
                    setTimeout(() => {
                        window.location.href = `/pacientes/{{ $paciente['uuid'] }}`;
                    }, 2000);
                }
            }
            
        } else {
            showAlert('error', data.error || 'Error en sincronización');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión durante la sincronización');
    })
    .finally(() => {
        submitBtn.disabled = false;
        if (submitBtn.innerHTML.includes('Sincronizando')) {
            submitBtn.innerHTML = originalText;
        }
    });
}

// ✅ NUEVA FUNCIÓN: Verificar estado de conexión periódicamente
function checkConnectionStatus() {
    const isOffline = {{ $isOffline ? 'true' : 'false' }};
    
    if (isOffline) {
        // Verificar cada 30 segundos si volvió la conexión
        setInterval(() => {
            fetch('/api/health-check', { 
                method: 'GET',
                cache: 'no-cache'
            })
            .then(response => {
                if (response.ok) {
                    // ✅ Conexión restaurada
                    showAlert('success', 'Conexión restaurada. Puede sincronizar sus cambios.', 'Conexión Restablecida');
                    
                    // Actualizar badge si existe
                    const badge = document.querySelector('.sync-pending-badge');
                    if (badge) {
                        const button = badge.querySelector('button');
                        if (button) {
                            button.classList.remove('btn-outline-warning');
                            button.classList.add('btn-success');
                            button.innerHTML = '<i class="fas fa-wifi me-1"></i>Sincronizar Ahora';
                        }
                    }
                }
            })
            .catch(() => {
                // Aún sin conexión, no hacer nada
            });
        }, 30000);
    }
}

// Limpiar errores de validación
function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(element => {
        element.remove();
    });
}

// Mostrar errores de validación
function showValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = errors[field][0];
            input.parentNode.appendChild(feedback);
        }
    });
}

// Detectar cambios en el formulario
function detectChanges() {
    const form = document.getElementById('pacienteEditForm');
    const formData = new FormData(form);
    const currentData = {};
    
    for (let [key, value] of formData.entries()) {
        currentData[key] = value;
    }
    
    // Comparar con datos originales
    const changed = Object.keys(currentData).some(key => {
        const originalValue = originalData[key] || '';
        const currentValue = currentData[key] || '';
        return currentValue !== originalValue;
    });
    
    hasChanges = changed;
    
    // Actualizar UI si hay cambios
    const submitBtn = document.getElementById('submitBtn');
    if (hasChanges) {
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-warning');
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Guardar Cambios';
    } else {
        submitBtn.classList.remove('btn-warning');
        submitBtn.classList.add('btn-primary');
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Cambios';
    }
}

// Validar documento
function validateDocument() {
    const documento = document.getElementById('documento').value.trim();
    
    if (!documento) {
        showAlert('warning', 'Debe ingresar un documento para validar');
        return;
    }
    
    if (documento === originalData.documento) {
        showAlert('info', 'Este es el documento actual del paciente');
        return;
    }
    
    fetch(`{{ route('pacientes.search.document') }}?documento=${documento}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Documento ya existe
            showAlert('error', 'Este documento ya está registrado en otro paciente', 'Documento Duplicado');
            document.getElementById('documento').classList.add('is-invalid');
        } else {
            // Documento disponible
            showAlert('success', 'Documento disponible para usar', 'Documento Válido');
            document.getElementById('documento').classList.remove('is-invalid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('warning', 'No se pudo validar el documento. Verifique manualmente.');
    });
}

// Calcular edad
function calculateAge() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
    const edadElement = document.getElementById('edadCalculada');
    
    if (fechaNacimiento) {
        const birth = new Date(fechaNacimiento);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        edadElement.textContent = `Edad: ${age} años`;
        
        if (age > 120) {
            edadElement.classList.add('text-warning');
            edadElement.textContent += ' (Verifique la fecha)';
        } else {
            edadElement.classList.remove('text-warning');
        }
    } else {
        edadElement.textContent = '';
    }
}

// Event listener para calcular edad
document.getElementById('fecha_nacimiento').addEventListener('change', calculateAge);

// Resetear formulario
function resetForm() {
    if (hasChanges) {
        Swal.fire({
            title: '¿Deshacer Cambios?',
            text: 'Se perderán todas las modificaciones realizadas',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, deshacer',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Restaurar valores originales
                restoreOriginalValues();
                
                hasChanges = false;
                detectChanges();
                calculateAge();
                
                showAlert('info', 'Cambios deshecho correctamente');
            }
        });
    } else {
        showAlert('info', 'No hay cambios para deshacer');
    }
}

// Restaurar valores originales
function restoreOriginalValues() {
    Object.keys(originalData).forEach(key => {
        const input = document.getElementById(key);
        if (input) {
            input.value = originalData[key] || '';
            input.classList.remove('is-invalid');
        }
    });
    
    // Limpiar errores de validación
    clearValidationErrors();
    
    // Recargar municipios
    loadInitialMunicipios();
}

// Agregar al historial de cambios
function addToHistorial(mensaje, tipo = 'info') {
    const historial = document.getElementById('historialCambios');
    const now = new Date().toLocaleString();
    
    const iconos = {
        'success': 'fas fa-check-circle text-success',
        'warning': 'fas fa-exclamation-triangle text-warning',
        'error': 'fas fa-times-circle text-danger',
        'info': 'fas fa-info-circle text-info'
    };
    
    const newEntry = document.createElement('div');
    newEntry.className = 'border-start border-3 border-primary ps-3 mb-3';
    newEntry.innerHTML = `
        <div class="d-flex align-items-start">
            <i class="${iconos[tipo]} me-2 mt-1"></i>
            <div class="flex-grow-1">
                <p class="mb-1">${mensaje}</p>
                <small class="text-muted">${now}</small>
            </div>
        </div>
    `;
    
    // Si es el primer cambio, limpiar el mensaje de "sin cambios"
    if (historial.querySelector('.text-muted')) {
        historial.innerHTML = '';
    }
    
    historial.insertBefore(newEntry, historial.firstChild);
    
    // Mantener solo los últimos 5 cambios
    const entries = historial.querySelectorAll('.border-start');
    if (entries.length > 5) {
        entries[entries.length - 1].remove();
    }
}

// Advertir antes de salir si hay cambios no guardados
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Formatear teléfonos
document.getElementById('telefono').addEventListener('input', formatPhone);
document.getElementById('telefono_acudiente').addEventListener('input', formatPhone);
document.getElementById('acompanante_telefono').addEventListener('input', formatPhone);

function formatPhone() {
    let value = this.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    this.value = value;
}

// Validar correo en tiempo real
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

// Función auxiliar para validar email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>
@endpush

@endsection
