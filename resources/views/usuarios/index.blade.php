{{-- resources/views/usuarios/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

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
                                <i class="fas fa-users text-primary me-2"></i>
                                Gestión de Usuarios
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Administre los usuarios del sistema
                            </p>
                        </div>
                        <div>
                            @if($isOnline)
                                <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i>
                                    Nuevo Usuario
                                </a>
                            @else
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-wifi-slash me-1"></i>
                                    Sin conexión
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('usuarios.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Buscar
                            </label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Nombre, documento o login"
                                   value="{{ $filters['search'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Sede
                            </label>
                            <select name="sede_id" class="form-select">
                                <option value="">Todas</option>
                                @if(isset($masterData['sedes']))
                                    @foreach($masterData['sedes'] as $sede)
                                        <option value="{{ $sede['id'] }}" 
                                                {{ ($filters['sede_id'] ?? '') == $sede['id'] ? 'selected' : '' }}>
                                            {{ $sede['nombre'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-user-tag me-1"></i>
                                Rol
                            </label>
                            <select name="rol_id" class="form-select">
                                <option value="">Todos</option>
                                @if(isset($masterData['roles']))
                                    @foreach($masterData['roles'] as $rol)
                                        <option value="{{ $rol['id'] }}" 
                                                {{ ($filters['rol_id'] ?? '') == $rol['id'] ? 'selected' : '' }}>
                                            {{ $rol['nombre'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>
                                Estado
                            </label>
                            <select name="estado_id" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" {{ ($filters['estado_id'] ?? '') == '1' ? 'selected' : '' }}>
                                    Activo
                                </option>
                                <option value="2" {{ ($filters['estado_id'] ?? '') == '2' ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="card shadow-sm">
                <div class="card-body">
                    @if(isset($usuarios) && count($usuarios) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Documento</th>
                                        <th>Nombre Completo</th>
                                        <th>Login</th>
                                        <th>Rol</th>
                                        <th>Sede</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($usuarios as $usuario)
                                        <tr>
                                            <td>
                                                <i class="fas fa-id-badge text-muted me-1"></i>
                                                {{ $usuario['documento'] }}
                                            </td>
                                            <td>
                                                <strong>{{ $usuario['nombre_completo'] }}</strong>
                                                @if($usuario['es_medico'] ?? false)
                                                    <span class="badge bg-info ms-1">
                                                        <i class="fas fa-user-md"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <code>{{ $usuario['login'] }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $usuario['rol']['nombre'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-building text-muted me-1"></i>
                                                {{ $usuario['sede']['nombre'] ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if(($usuario['estado']['nombre'] ?? '') === 'ACTIVO')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Activo
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('usuarios.show', $usuario['uuid']) }}" 
                                                       class="btn btn-outline-info"
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                
                                                    @if($isOnline)
                                                        <a href="{{ route('usuarios.edit', $usuario['uuid']) }}" 
                                                           class="btn btn-outline-warning"
                                                           title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger"
                                                                onclick="confirmarEliminar('{{ $usuario['uuid'] }}', '{{ $usuario['nombre_completo'] }}')"
                                                                title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        @if(isset($pagination) && $pagination)
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted">
                                    Mostrando {{ $pagination['from'] }} a {{ $pagination['to'] }} 
                                    de {{ $pagination['total'] }} usuarios
                                </div>
                                <nav>
                                    <ul class="pagination mb-0">
                                        @for($i = 1; $i <= $pagination['last_page']; $i++)
                                            <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                                <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                                            </li>
                                        @endfor
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No se encontraron usuarios</p>
                            @if($isOnline)
                                <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>
                                    Crear Primer Usuario
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Está seguro de eliminar al usuario?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong id="nombreUsuarioEliminar"></strong>
                </div>
                <p class="text-danger mb-0">
                    <small>Esta acción no se puede deshacer.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Eliminar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmarEliminar(uuid, nombreCompleto) {
    document.getElementById('nombreUsuarioEliminar').textContent = nombreCompleto;
    document.getElementById('formEliminar').action = `/usuarios/${uuid}`;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

// Mostrar alertas de sesión
@if(session('success'))
    mostrarAlerta('{{ session('success') }}', 'success');
@endif

@if(session('error'))
    mostrarAlerta('{{ session('error') }}', 'danger');
@endif

function mostrarAlerta(mensaje, tipo) {
    const alertHtml = `
        <div class="alert alert-${tipo} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
             role="alert" 
             style="z-index: 9999; min-width: 300px;">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>
@endpush
