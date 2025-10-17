    {{-- resources/views/usuarios/create.blade.php --}}
    @extends('layouts.app')

    @section('title', 'Crear Usuario')

    @section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fas fa-user-plus text-primary me-2"></i>
                                    Crear Nuevo Usuario
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-building me-1"></i>
                                    Sede: <strong>{{ $sedeActual['nombre'] ?? 'N/A' }}</strong>
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas de Sesión -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <strong>¡Éxito!</strong>
                                <p class="mb-0">{{ session('success') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                            <div>
                                <strong>¡Error!</strong>
                                <p class="mb-0">{{ session('error') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <strong>Errores de validación:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Formulario -->
                <form id="formCrearUsuario" action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <!-- Columna Izquierda: Datos Personales -->
                        <div class="col-lg-8">
                            <!-- Datos Personales -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-id-card me-2"></i>
                                        Datos Personales
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Sede -->
                                        <div class="col-md-6">
                                            <label for="sede_id" class="form-label required">
                                                <i class="fas fa-building text-primary me-1"></i>
                                                Sede
                                            </label>
                                            <select class="form-select @error('sede_id') is-invalid @enderror" 
                                                    id="sede_id" 
                                                    name="sede_id" 
                                                    required>
                                                <option value="">Seleccione una sede</option>
                                                @if(isset($masterData['sedes']) && is_array($masterData['sedes']))
                                                    @foreach($masterData['sedes'] as $sede)
                                                        <option value="{{ $sede['id'] }}" 
                                                                {{ old('sede_id', $sedeActual['id'] ?? '') == $sede['id'] ? 'selected' : '' }}>
                                                            {{ $sede['nombre'] }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('sede_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Por defecto: {{ $sedeActual['nombre'] ?? 'N/A' }}
                                            </small>
                                        </div>

                                        <!-- Documento -->
                                        <div class="col-md-6">
                                            <label for="documento" class="form-label required">
                                                <i class="fas fa-id-badge text-primary me-1"></i>
                                                Documento
                                            </label>
                                            <input type="text" 
                                                class="form-control @error('documento') is-invalid @enderror" 
                                                id="documento" 
                                                name="documento" 
                                                value="{{ old('documento') }}"
                                                placeholder="Ej: 1234567890"
                                                maxlength="15"
                                                required>
                                            @error('documento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Nombre -->
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label required">
                                                <i class="fas fa-user text-primary me-1"></i>
                                                Nombre
                                            </label>
                                            <input type="text" 
                                                class="form-control @error('nombre') is-invalid @enderror" 
                                                id="nombre" 
                                                name="nombre" 
                                                value="{{ old('nombre') }}"
                                                placeholder="Ej: Juan Carlos"
                                                maxlength="50"
                                                required>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Apellido -->
                                        <div class="col-md-6">
                                            <label for="apellido" class="form-label required">
                                                <i class="fas fa-user text-primary me-1"></i>
                                                Apellido
                                            </label>
                                            <input type="text" 
                                                class="form-control @error('apellido') is-invalid @enderror" 
                                                id="apellido" 
                                                name="apellido" 
                                                value="{{ old('apellido') }}"
                                                placeholder="Ej: Pérez García"
                                                maxlength="50"
                                                required>
                                            @error('apellido')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Teléfono -->
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label required">
                                                <i class="fas fa-phone text-primary me-1"></i>
                                                Teléfono
                                            </label>
                                            <input type="text" 
                                                class="form-control @error('telefono') is-invalid @enderror" 
                                                id="telefono" 
                                                name="telefono" 
                                                value="{{ old('telefono') }}"
                                                placeholder="Ej: 3001234567"
                                                maxlength="10"
                                                required>
                                            @error('telefono')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Correo -->
                                        <div class="col-md-6">
                                            <label for="correo" class="form-label required">
                                                <i class="fas fa-envelope text-primary me-1"></i>
                                                Correo Electrónico
                                            </label>
                                            <input type="email" 
                                                class="form-control @error('correo') is-invalid @enderror" 
                                                id="correo" 
                                                name="correo" 
                                                value="{{ old('correo') }}"
                                                placeholder="Ej: usuario@hospital.com"
                                                maxlength="60"
                                                required>
                                            @error('correo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de Acceso -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-key me-2"></i>
                                        Datos de Acceso
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Login -->
                                        <div class="col-md-12">
                                            <label for="login" class="form-label required">
                                                <i class="fas fa-user-circle text-success me-1"></i>
                                                Usuario (Login)
                                            </label>
                                            <input type="text" 
                                                class="form-control @error('login') is-invalid @enderror" 
                                                id="login" 
                                                name="login" 
                                                value="{{ old('login') }}"
                                                placeholder="Ej: jperez"
                                                maxlength="50"
                                                required>
                                            @error('login')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Usuario único para iniciar sesión
                                            </small>
                                        </div>

                                        <!-- Contraseña -->
                                        <div class="col-md-6">
                                            <label for="password" class="form-label required">
                                                <i class="fas fa-lock text-success me-1"></i>
                                                Contraseña
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                    class="form-control @error('password') is-invalid @enderror" 
                                                    id="password" 
                                                    name="password"
                                                    placeholder="Mínimo 6 caracteres"
                                                    minlength="6"
                                                    required>
                                                <button class="btn btn-outline-secondary" 
                                                        type="button" 
                                                        id="togglePassword">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div class="col-md-6">
                                            <label for="password_confirmation" class="form-label required">
                                                <i class="fas fa-lock text-success me-1"></i>
                                                Confirmar Contraseña
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                    class="form-control" 
                                                    id="password_confirmation" 
                                                    name="password_confirmation"
                                                    placeholder="Repetir contraseña"
                                                    minlength="6"
                                                    required>
                                                <button class="btn btn-outline-secondary" 
                                                        type="button" 
                                                        id="togglePasswordConfirm">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Rol y Especialidad -->
                        <div class="col-lg-4">
                            <!-- Rol y Estado -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-tag me-2"></i>
                                        Rol y Estado
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Rol -->
                                    <div class="mb-3">
                                        <label for="rol_id" class="form-label required">
                                            <i class="fas fa-user-tag text-info me-1"></i>
                                            Rol
                                        </label>
                                        <select class="form-select @error('rol_id') is-invalid @enderror"
                                                id="rol_id"
                                                name="rol_id"
                                                required>
                                            <option value="">Seleccione rol</option>
                                            
                                            @if(isset($masterData['roles']) && is_array($masterData['roles']))
                                                @foreach($masterData['roles'] as $rol)
                                                    <option value="{{ $rol['id'] ?? $rol['uuid'] ?? '' }}"
                                                            data-uuid="{{ $rol['uuid'] ?? '' }}"
                                                            data-nombre="{{ $rol['nombre'] ?? '' }}"
                                                            {{ old('rol_id') == ($rol['id'] ?? $rol['uuid'] ?? '') ? 'selected' : '' }}>
                                                        {{ $rol['nombre'] ?? 'Sin nombre' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>No hay roles disponibles</option>
                                            @endif
                                        </select>
                                        
                                        @error('rol_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Estado -->
                                    <div class="mb-3">
                                        <label for="estado_id" class="form-label required">
                                            <i class="fas fa-toggle-on text-info me-1"></i>
                                            Estado
                                        </label>
                                        <select class="form-select @error('estado_id') is-invalid @enderror" 
                                                id="estado_id" 
                                                name="estado_id" 
                                                required>
                                            <option value="">Seleccione un estado</option>
                                            @if(isset($masterData['estados']) && is_array($masterData['estados']))
                                                @foreach($masterData['estados'] as $estado)
                                                    <option value="{{ $estado['id'] }}" 
                                                            {{ old('estado_id', 1) == $estado['id'] ? 'selected' : '' }}>
                                                        {{ $estado['nombre'] }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="1" selected>ACTIVO</option>
                                                <option value="2">INACTIVO</option>
                                            @endif
                                        </select>
                                        @error('estado_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Profesionales (Solo para profesionales de salud) -->
                            <div class="card shadow-sm mb-4" id="datosProfesionales" style="display: none;">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-stethoscope me-2"></i>
                                        Datos Profesionales
                                    </h5>
                                </div>
                                <div class="card-body">
                                <!-- Especialidad -->
                                    <div class="mb-3">
                                        <label for="especialidad_id" class="form-label">
                                            <i class="fas fa-briefcase-medical text-warning me-1"></i>
                                            Especialidad
                                        </label>
                                        <select class="form-select @error('especialidad_id') is-invalid @enderror"
                                                id="especialidad_id"
                                                name="especialidad_id">
                                            <option value="">Seleccione especialidad</option>
                                            
                                            @if(isset($masterData['especialidades']) && is_array($masterData['especialidades']))
                                                @foreach($masterData['especialidades'] as $especialidad)
                                                    {{-- ✅ USAR UUID EN EL VALUE --}}
                                                    <option value="{{ $especialidad['uuid'] ?? '' }}"
                                                            {{ old('especialidad_id') == ($especialidad['uuid'] ?? '') ? 'selected' : '' }}>
                                                        {{ $especialidad['nombre'] ?? 'Sin nombre' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>No hay especialidades disponibles</option>
                                            @endif
                                        </select>
                                        
                                        @error('especialidad_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Obligatorio para profesionales de salud
                                        </small>
                                    </div>

                                    <!-- Registro Profesional -->
                                    <div class="mb-3">
                                        <label for="registro_profesional" class="form-label">
                                            <i class="fas fa-certificate text-warning me-1"></i>
                                            Registro Profesional
                                        </label>
                                        <input type="text" 
                                            class="form-control @error('registro_profesional') is-invalid @enderror" 
                                            id="registro_profesional" 
                                            name="registro_profesional" 
                                            value="{{ old('registro_profesional') }}"
                                            placeholder="Ej: RM-12345"
                                            maxlength="50">
                                        @error('registro_profesional')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Número de registro médico o profesional
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Firma Digital (Solo para profesionales de salud) -->
                            <div class="card shadow-sm mb-4" id="firmaDigital" style="display: none;">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-signature me-2"></i>
                                        Firma Digital
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Puede firmar de dos formas:
                                        </p>
                                        
                                        <!-- Botón para abrir modal QR -->
                                            <button type="button" 
                                                    class="btn btn-primary mb-3 w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalFirmaQR">
                                                <i class="fas fa-qrcode me-2"></i>
                                                Escanear QR para Firmar desde Celular
                                            </button>
                                        
                                        <p class="text-muted mb-2">
                                            <strong>O dibuje la firma aquí:</strong>
                                        </p>
                                        
                                        <!-- Canvas para firma -->
                                        <div class="border rounded bg-white position-relative" style="display: inline-block;">
                                            <canvas id="canvasFirma" 
                                                    width="400" 
                                                    height="200" 
                                                    style="cursor: crosshair; touch-action: none;">
                                            </canvas>
                                        </div>
                                        
                                        <!-- Input hidden para guardar firma -->
                                        <input type="hidden" id="firma" name="firma">
                                        
                                        <!-- Botones de control -->
                                        <div class="mt-3">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    id="btnLimpiarFirma">
                                                <i class="fas fa-eraser me-1"></i>
                                                Limpiar
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    id="btnDeshacerFirma">
                                                <i class="fas fa-undo me-1"></i>
                                                Deshacer
                                            </button>
                                        </div>
                                        
                                        <!-- Preview de firma -->
                                        <div id="firmaPreview" class="mt-3" style="display: none;">
                                            <p class="text-success mb-2">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Firma capturada
                                            </p>
                                            <img id="firmaPreviewImg" 
                                                src="" 
                                                alt="Preview Firma" 
                                                class="img-thumbnail" 
                                                style="max-width: 200px;">
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mb-0">
                                        <small>
                                            <i class="fas fa-lightbulb me-1"></i>
                                            <strong>Nota:</strong> La firma es opcional pero recomendada para profesionales de salud.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="submit" 
                                                class="btn btn-primary btn-lg" 
                                                id="btnGuardar">
                                            <i class="fas fa-save me-2"></i>
                                            Crear Usuario
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                id="btnCancelar">
                                            <i class="fas fa-times me-2"></i>
                                            Cancelar
                                        </button>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Los campos marcados con <span class="text-danger">*</span> son obligatorios
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-question-circle me-2"></i>
                        Confirmar Creación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">¿Está seguro de crear el usuario con los siguientes datos?</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre:</dt>
                        <dd class="col-sm-8" id="confirmNombre">-</dd>
                        
                        <dt class="col-sm-4">Documento:</dt>
                        <dd class="col-sm-8" id="confirmDocumento">-</dd>
                        
                        <dt class="col-sm-4">Login:</dt>
                        <dd class="col-sm-8" id="confirmLogin">-</dd>
                        
                        <dt class="col-sm-4">Rol:</dt>
                        <dd class="col-sm-8" id="confirmRol">-</dd>
                        
                        <dt class="col-sm-4">Sede:</dt>
                        <dd class="col-sm-8" id="confirmSede">-</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCreacion">
                        <i class="fas fa-check me-1"></i>
                        Confirmar y Crear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ INCLUIR MODAL DE FIRMA CON QR --}}
    @include('usuarios.partials.firma-qr')

    @endsection

    @push('styles')
    <style>
        .required::after {
            content: " *";
            color: red;
            font-weight: bold;
        }
        
        #canvasFirma {
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }
        
        #canvasFirma:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }
        
        /* Animaciones para mostrar/ocultar */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate__fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        /* Indicador visual de campo profesional */
        #datosProfesionales .card-header,
        #firmaDigital .card-header {
            position: relative;
            overflow: hidden;
        }
        
        #datosProfesionales .card-header::after,
        #firmaDigital .card-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 2s infinite;
        }
        
        @keyframes shine {
            to {
                left: 100%;
            }
        }
        
        /* Mejorar preview de firma */
        #firmaPreview {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        #firmaPreviewImg {
            border: 2px solid #28a745;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        }
        
        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Alertas mejoradas */
        .alert {
            border-left: 4px solid;
            animation: slideInDown 0.5s ease-out;
        }
        
        .alert-success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        
        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        @keyframes slideInDown {
            from {            transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Auto-hide después de 5 segundos */
        .alert.auto-dismiss {
            animation: slideInDown 0.5s ease-out, slideOut 0.5s ease-in 4.5s forwards;
        }
        
        @keyframes slideOut {
            to {
                transform: translateY(-100%);
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
            }
        }
    </style>
    @endpush
    

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando formulario de creación de usuarios');
        
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        const formCrearUsuario = document.getElementById('formCrearUsuario');
        const rolSelect = document.getElementById('rol_id');
        const datosProfesionales = document.getElementById('datosProfesionales');
        const firmaDigital = document.getElementById('firmaDigital');
        const especialidadSelect = document.getElementById('especialidad_id');
        const registroProfesional = document.getElementById('registro_profesional');
        
        // Canvas de firma
        const canvas = document.getElementById('canvasFirma');
        const ctx = canvas ? canvas.getContext('2d') : null;
        const firmaInput = document.getElementById('firma');
        const btnLimpiarFirma = document.getElementById('btnLimpiarFirma');
        const btnDeshacerFirma = document.getElementById('btnDeshacerFirma');
        const firmaPreview = document.getElementById('firmaPreview');
        const firmaPreviewImg = document.getElementById('firmaPreviewImg');
        
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        let strokes = []; // Para deshacer
        let currentStroke = [];
        
        // Botones
        const btnGuardar = document.getElementById('btnGuardar');
        const btnCancelar = document.getElementById('btnCancelar');
        const btnConfirmarCreacion = document.getElementById('btnConfirmarCreacion');
        
        // Toggles de contraseña
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirmation');
        
        // Modal
        const modalConfirmacion = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
        
        // ============================================
        // ROLES QUE REQUIEREN DATOS PROFESIONALES
        // ============================================
        const rolesProfesionalesSalud = [
            'MEDICO',
            'MÉDICO',
            'PROFESIONAL EN SALUD',
            'ENFERMERA',
            'ENFERMERO',
            'DOCTOR',
            'DOCTORA'
        ];
        
        // ============================================
        // FUNCIÓN: VERIFICAR SI ES PROFESIONAL DE SALUD
        // ============================================
        function esProfesionalSalud(nombreRol) {
            if (!nombreRol) {
                console.log('⚠️ nombreRol está vacío');
                return false;
            }
            
            const rolNormalizado = nombreRol.toUpperCase().trim();
            console.log('🔍 Verificando rol:', rolNormalizado);
            
            const esProfesional = rolesProfesionalesSalud.some(rol => {
                const match = rolNormalizado.includes(rol) || rol.includes(rolNormalizado);
                if (match) {
                    console.log('✅ Match encontrado con:', rol);
                }
                return match;
            });
            
            console.log('🎯 Resultado:', esProfesional ? 'ES profesional de salud' : 'NO es profesional de salud');
            return esProfesional;
        }
        
        // ============================================
        // EVENTO: CAMBIO DE ROL
        // ============================================
        if (rolSelect) {
            rolSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                // Obtener el nombre del rol del texto visible de la opción
                const nombreRol = selectedOption.text.trim();
                
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.log('🔄 Evento change disparado');
                console.log('📋 Valor seleccionado:', this.value);
                console.log('📝 Texto de la opción:', nombreRol);
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                
                if (esProfesionalSalud(nombreRol)) {
                    console.log('✅ MOSTRANDO campos profesionales');
                    
                    // Mostrar secciones con animación
                    if (datosProfesionales) {
                        datosProfesionales.style.display = 'block';
                        datosProfesionales.classList.add('animate__fadeIn');
                        console.log('✓ datosProfesionales mostrado');
                    }
                    
                    if (firmaDigital) {
                        firmaDigital.style.display = 'block';
                        firmaDigital.classList.add('animate__fadeIn');
                        console.log('✓ firmaDigital mostrado');
                    }
                    
                    // Hacer especialidad obligatoria
                    if (especialidadSelect) {
                        especialidadSelect.setAttribute('required', 'required');
                        console.log('✓ especialidad marcada como requerida');
                    }
                    
                    // Inicializar canvas si no está inicializado
                    if (canvas && ctx && !ctx.initialized) {
                        inicializarCanvas();
                        console.log('✓ Canvas inicializado');
                    }
                    
                } else {
                    console.log('❌ OCULTANDO campos profesionales');
                    
                    // Ocultar secciones
                    if (datosProfesionales) {
                        datosProfesionales.style.display = 'none';
                        console.log('✓ datosProfesionales ocultado');
                    }
                    
                    if (firmaDigital) {
                        firmaDigital.style.display = 'none';
                        console.log('✓ firmaDigital ocultado');
                    }
                    
                    // Quitar obligatoriedad y limpiar valores
                    if (especialidadSelect) {
                        especialidadSelect.removeAttribute('required');
                        especialidadSelect.value = '';
                        especialidadSelect.classList.remove('is-valid', 'is-invalid');
                        console.log('✓ especialidad limpiada');
                    }
                    
                    if (registroProfesional) {
                        registroProfesional.value = '';
                        registroProfesional.classList.remove('is-valid', 'is-invalid');
                        console.log('✓ registro profesional limpiado');
                    }
                    
                    // Limpiar firma
                    limpiarFirma();
                    console.log('✓ firma limpiada');
                }
            });
            
            // Ejecutar al cargar si hay un rol preseleccionado
            if (rolSelect.value) {
                console.log('🔄 Ejecutando change inicial para rol preseleccionado');
                rolSelect.dispatchEvent(new Event('change'));
            }
        } else {
            console.error('❌ rolSelect no encontrado');
        }
        
        // ============================================
        // CANVAS DE FIRMA: INICIALIZACIÓN
        // ============================================
        function inicializarCanvas() {
            if (!canvas || !ctx) {
                console.warn('⚠️ Canvas no disponible');
                return;
            }
            
            console.log('🎨 Inicializando canvas de firma');
            
            // Configurar contexto
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            // Fondo blanco
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.initialized = true;
            
            // ============================================
            // EVENTOS DE DIBUJO - MOUSE
            // ============================================
            canvas.addEventListener('mousedown', (e) => {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                lastX = e.clientX - rect.left;
                lastY = e.clientY - rect.top;
                currentStroke = [{x: lastX, y: lastY}];
            });
            
            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                currentStroke.push({x, y});
                
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('mouseup', () => {
                if (isDrawing) {
                    isDrawing = false;
                    strokes.push([...currentStroke]);
                    currentStroke = [];
                    guardarFirma();
                }
            });
            
            canvas.addEventListener('mouseleave', () => {
                if (isDrawing) {
                    isDrawing = false;
                    strokes.push([...currentStroke]);
                    currentStroke = [];
                    guardarFirma();
                }
            });
            
            // ============================================
            // EVENTOS DE DIBUJO - TOUCH (MÓVIL)
            // ============================================
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                const touch = e.touches[0];
                lastX = touch.clientX - rect.left;
                lastY = touch.clientY - rect.top;
                currentStroke = [{x: lastX, y: lastY}];
            });
            
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const touch = e.touches[0];
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                currentStroke.push({x, y});
                
                lastX = x;
                lastY = y;
            });
            
            canvas.addEventListener('touchend', (e) => {
                e.preventDefault();
                if (isDrawing) {
                    isDrawing = false;
                    strokes.push([...currentStroke]);
                    currentStroke = [];
                    guardarFirma();
                }
            });
            
            console.log('✅ Canvas de firma inicializado con eventos');
        }
        
        // ============================================
        // FUNCIÓN: GUARDAR FIRMA EN BASE64
        // ============================================
        function guardarFirma() {
            if (!canvas || !firmaInput) return;
            
            try {
                // Verificar si hay contenido dibujado
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const hasContent = imageData.data.some((channel, index) => {
                    // Verificar si hay píxeles no blancos (RGB != 255 o Alpha != 255)
                    return index % 4 !== 3 && channel !== 255;
                });
                
                if (hasContent) {
                    const firmaBase64 = canvas.toDataURL('image/png');
                    firmaInput.value = firmaBase64;
                    
                    // Mostrar preview
                    if (firmaPreview && firmaPreviewImg) {
                        firmaPreviewImg.src = firmaBase64;
                        firmaPreview.style.display = 'block';
                    }
                    
                    console.log('✅ Firma guardada en base64');
                } else {
                    firmaInput.value = '';
                    if (firmaPreview) {
                        firmaPreview.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('❌ Error al guardar firma:', error);
            }
        }
        
        // ============================================
        // FUNCIÓN: LIMPIAR FIRMA
        // ============================================
        function limpiarFirma() {
            if (!canvas || !ctx) return;
            
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            strokes = [];
            currentStroke = [];
            
            if (firmaInput) {
                firmaInput.value = '';
            }
            
            if (firmaPreview) {
                firmaPreview.style.display = 'none';
            }
            
            console.log('🧹 Firma limpiada');
        }
        
        // ============================================
        // FUNCIÓN: DESHACER ÚLTIMO TRAZO
        // ============================================
        function deshacerFirma() {
            if (!canvas || !ctx || strokes.length === 0) return;
            
            // Quitar último trazo
            strokes.pop();
            
            // Redibujar todo
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 2;
            
            strokes.forEach(stroke => {
                if (stroke.length > 0) {
                    ctx.beginPath();
                    ctx.moveTo(stroke[0].x, stroke[0].y);
                    
                    for (let i = 1; i < stroke.length; i++) {
                        ctx.lineTo(stroke[i].x, stroke[i].y);
                    }
                    
                    ctx.stroke();
                }
            });
            
            guardarFirma();
            console.log('↩️ Trazo deshecho');
        }
        
        // ============================================
        // EVENTOS: BOTONES DE FIRMA
        // ============================================
        if (btnLimpiarFirma) {
            btnLimpiarFirma.addEventListener('click', limpiarFirma);
        }
        
        if (btnDeshacerFirma) {
            btnDeshacerFirma.addEventListener('click', deshacerFirma);
        }
        
        // ============================================
        // FUNCIÓN GLOBAL: RECIBIR FIRMA DESDE MODAL QR
        // ============================================
        window.recibirFirmaDesdeQR = function(firmaBase64) {
            console.log('📱 Firma recibida desde QR/Modal');
            
            if (firmaInput) {
                firmaInput.value = firmaBase64;
            }
            
            // Mostrar preview
            if (firmaPreview && firmaPreviewImg) {
                firmaPreviewImg.src = firmaBase64;
                firmaPreview.style.display = 'block';
            }
            
            // Opcional: Dibujar en canvas también
            if (canvas && ctx) {
                const img = new Image();
                img.onload = function() {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    console.log('✅ Firma dibujada en canvas desde QR');
                };
                img.src = firmaBase64;
            }
            
            console.log('✅ Firma desde QR procesada correctamente');
        };
        
        // ============================================
        // TOGGLE PASSWORD VISIBILITY
        // ============================================
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        if (togglePasswordConfirm && passwordConfirmInput) {
            togglePasswordConfirm.addEventListener('click', function() {
                const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // ============================================
        // VALIDACIÓN DE CONTRASEÑAS
        // ============================================
        if (passwordConfirmInput && passwordInput) {
            passwordConfirmInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('Las contraseñas no coinciden');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (passwordConfirmInput.value && passwordConfirmInput.value !== this.value) {
                    passwordConfirmInput.setCustomValidity('Las contraseñas no coinciden');
                    passwordConfirmInput.classList.add('is-invalid');
                } else if (passwordConfirmInput.value) {
                    passwordConfirmInput.setCustomValidity('');
                    passwordConfirmInput.classList.remove('is-invalid');
                    passwordConfirmInput.classList.add('is-valid');
                }
            });
        }
        
        // ============================================
        // EVENTO: SUBMIT FORMULARIO CON CONFIRMACIÓN
        // ============================================
        if (formCrearUsuario) {
            formCrearUsuario.addEventListener('submit', function(e) {
                e.preventDefault();
                
                console.log('📝 Submit del formulario interceptado');
                
                // Validar formulario
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    
                    console.warn('⚠️ Formulario inválido');
                    
                    // Scroll al primer campo inválido
                    const primerInvalido = this.querySelector(':invalid');
                    if (primerInvalido) {
                        primerInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        primerInvalido.focus();
                        console.log('🎯 Scroll a campo inválido:', primerInvalido.name);
                    }
                    
                    return;
                }
                
                console.log('✅ Formulario válido, mostrando modal de confirmación');
                
                // Llenar datos del modal de confirmación
                const nombre = document.getElementById('nombre').value;
                const apellido = document.getElementById('apellido').value;
                const documento = document.getElementById('documento').value;
                const login = document.getElementById('login').value;
                const rolSelect = document.getElementById('rol_id');
                const sedeSelect = document.getElementById('sede_id');
                
                document.getElementById('confirmNombre').textContent = `${nombre} ${apellido}`;
                document.getElementById('confirmDocumento').textContent = documento;
                document.getElementById('confirmLogin').textContent = login;
                document.getElementById('confirmRol').textContent = rolSelect.options[rolSelect.selectedIndex].text;
                document.getElementById('confirmSede').textContent = sedeSelect.options[sedeSelect.selectedIndex].text;
                
                // Mostrar modal
                modalConfirmacion.show();
            });
        }
        
        // ============================================
        // EVENTO: CONFIRMAR CREACIÓN
        // ============================================
        if (btnConfirmarCreacion) {
            btnConfirmarCreacion.addEventListener('click', function() {
                console.log('✅ Confirmando creación de usuario...');
                
                // Deshabilitar botón
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';
                
                // Cerrar modal
                modalConfirmacion.hide();
                
                // Mostrar loading en botón principal
                if (btnGuardar) {
                    btnGuardar.disabled = true;
                    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
                }
                
                // Submit formulario
                formCrearUsuario.submit();
            });
        }
        
        // ============================================
        // EVENTO: CANCELAR
        // ============================================
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                if (confirm('¿Está seguro de cancelar? Se perderán los datos ingresados.')) {
                    window.location.href = '{{ route("usuarios.index") }}';
                }
            });
        }
        
        // ============================================
        // VALIDACIÓN EN TIEMPO REAL
        // ============================================
        const inputs = formCrearUsuario.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
        
        // ============================================
        // AUTO-CERRAR ALERTAS DESPUÉS DE 5 SEGUNDOS
        // ============================================
        const alertas = document.querySelectorAll('.alert-success, .alert-info');
        
        alertas.forEach(alerta => {
            // Agregar clase para animación
            alerta.classList.add('auto-dismiss');
            
            // Auto-cerrar después de 5 segundos
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alerta);
                bsAlert.close();
            }, 5000);
        });
        
        // Log para debug
        if (alertas.length > 0) {
            console.log(`✅ ${alertas.length} alerta(s) se cerrarán automáticamente en 5 segundos`);
        }
        
        // ============================================
        // AUTO-GENERAR LOGIN DESDE NOMBRE
        // ============================================
        const nombreInput = document.getElementById('nombre');
        const apellidoInput = document.getElementById('apellido');
        const loginInput = document.getElementById('login');
        
        function generarLogin() {
            if (!loginInput.value && nombreInput.value && apellidoInput.value) {
                const nombre = nombreInput.value.split(' ')[0].toLowerCase();
                const apellido = apellidoInput.value.split(' ')[0].toLowerCase();
                const loginSugerido = `${nombre}.${apellido}`;
                
                loginInput.value = loginSugerido;
                loginInput.classList.add('is-valid');
                
                console.log('💡 Login sugerido:', loginSugerido);
            }
        }
        
        if (nombreInput && apellidoInput) {
            nombreInput.addEventListener('blur', generarLogin);
            apellidoInput.addEventListener('blur', generarLogin);
        }
        
        // ============================================
        // INICIALIZACIÓN FINAL
        // ============================================
        console.log('✅ Formulario de creación de usuarios inicializado correctamente');
        console.log('📋 Master Data disponible:', {
            roles: {{ isset($masterData['roles']) ? count($masterData['roles']) : 0 }},
            especialidades: {{ isset($masterData['especialidades']) ? count($masterData['especialidades']) : 0 }},
            sedes: {{ isset($masterData['sedes']) ? count($masterData['sedes']) : 0 }},
            estados: {{ isset($masterData['estados']) ? count($masterData['estados']) : 0 }}
        });
        
        // Debug: Listar todos los roles disponibles
        if (rolSelect) {
            console.log('📋 Roles disponibles en el select:');
            Array.from(rolSelect.options).forEach((option, index) => {
                if (option.value) {
                    console.log(`  ${index}. "${option.text}" (value: ${option.value})`);
                }
            });
        }
    });


    </script>
    @endpush

