{{-- resources/views/pacientes/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Pacientes - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-users text-primary me-2"></i>
                        Gestión de Pacientes
                    </h1>
                    <p class="text-muted mb-0">Administrar información de pacientes</p>
                </div>
                
                <!-- Header Actions -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Estado de Conexión -->
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @else
                        <span class="badge bg-success me-2">
                            <i class="fas fa-wifi"></i> Conectado
                        </span>
                    @endif
                    
                    {{-- ℹ️ SINCRONIZACIÓN UNIFICADA: Usar el botón del sidebar --}}
                    {{-- Los botones individuales de sincronización han sido reemplazados por un sistema unificado --}}
                    @if($isOffline && ($pending_sync_count ?? 0) > 0)
                        <span class="badge bg-warning text-dark me-2">
                            <i class="fas fa-clock"></i> {{ $pending_sync_count ?? 0 }} pendiente(s) de sincronizar
                        </span>
                    @endif
                    
                    {{-- BOTONES DESACTIVADOS - Usar sincronización unificada del sidebar
                    <button type="button" class="btn btn-outline-info btn-sm me-2" onclick="syncPacientes()" disabled>
                        <i class="fas fa-sync-alt"></i> Sincronizar (usar sidebar)
                    </button>
                    --}}
                    
                    <!-- Botón Nuevo Paciente -->
                    <a href="{{ route('pacientes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Nuevo Paciente
                    </a>

                 


                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Offline -->
    @if($isOffline)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Modo Offline Activo</strong>
                    <p class="mb-0">Trabajando con datos locales. Los cambios se sincronizarán cuando vuelva la conexión.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filtros de Búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-search me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-3">
                    <label for="searchDocumento" class="form-label">Documento</label>
                    <input type="text" class="form-control" id="searchDocumento" name="documento" 
                           placeholder="Número de documento">
                </div>
                <div class="col-md-3">
                    <label for="searchNombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="searchNombre" name="nombre" 
                           placeholder="Nombres o apellidos">
                </div>
                <div class="col-md-2">
                    <label for="searchEstado" class="form-label">Estado</label>
                    <select class="form-select" id="searchEstado" name="estado">
                        <option value="">Todos</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                        <button type="button" class="btn btn-info" onclick="searchByDocument()">
                            <i class="fas fa-id-card me-1"></i>Buscar por Documento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Pacientes -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Pacientes
            </h5>
            <div id="loadingIndicator" class="d-none">
                <i class="fas fa-spinner fa-spin me-2"></i>Cargando...
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="pacientesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Fecha Nacimiento</th>
                            <th>Sexo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="pacientesTableBody">
                        <!-- Se carga dinámicamente -->
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <nav aria-label="Paginación de pacientes">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- Se carga dinámicamente -->
                </ul>
            </nav>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

// ✅ CARGAR PACIENTES AL INICIAR
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando carga de pacientes');
    loadPacientes();
});

// ✅ FUNCIÓN PRINCIPAL PARA CARGAR PACIENTES
function loadPacientes(page = 1, filters = {}) {
    currentPage = page;
    currentFilters = filters;
    
    console.log('📥 Cargando pacientes', { page, filters });
    showLoading(true);
    
    const params = new URLSearchParams({
        page: page,
        ...filters
    });
    
    fetch(`{{ route('pacientes.index') }}?${params}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        console.log('📡 Respuesta recibida:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('✅ Datos procesados:', data);
        
        if (data.success) {
            renderPacientesTable(data.data || []);
            renderPagination(data.meta || {});
            
            if (data.offline) {
                showAlert('info', data.message || 'Datos cargados desde almacenamiento local', 'Modo Offline');
            } else {
                console.log('✅ Datos cargados desde servidor');
            }
        } else {
            console.error('❌ Error en respuesta:', data.error);
            showAlert('error', data.error || 'Error cargando pacientes');
            renderEmptyTable('Error cargando pacientes');
        }
    })
    .catch(error => {
        console.error('💥 Error de conexión:', error);
        showAlert('error', 'Error de conexión al cargar pacientes');
        renderEmptyTable('Error de conexión');
    })
    .finally(() => {
        showLoading(false);
    });
}
function renderPacientesTable(pacientes) {
    console.log('🎨 Renderizando pacientes:', pacientes.length);
    
    const tbody = document.getElementById('pacientesTableBody');
    
    if (!pacientes || pacientes.length === 0) {
        renderEmptyTable('No se encontraron pacientes');
        return;
    }
    
    tbody.innerHTML = pacientes.map(paciente => {
        // ✅ CONSTRUIR NOMBRE COMPLETO
        const nombreCompleto = [
            paciente.primer_nombre,
            paciente.segundo_nombre,
            paciente.primer_apellido,
            paciente.segundo_apellido
        ].filter(Boolean).join(' ') || paciente.nombre_completo || 'Sin nombre';
        
        // ✅ FORMATEAR DATOS
        const documento = paciente.documento || 'Sin documento';
        const tipoDoc = paciente.tipo_documento ? paciente.tipo_documento.abreviacion : '';
        const documentoCompleto = tipoDoc ? `${tipoDoc}: ${documento}` : documento;
        
        const fechaNacimiento = paciente.fecha_nacimiento 
            ? new Date(paciente.fecha_nacimiento).toLocaleDateString('es-CO')
            : 'No registrada';
            
        const sexo = paciente.sexo === 'M' ? 'Masculino' : 
                    paciente.sexo === 'F' ? 'Femenino' : 'No especificado';
                    
        const telefono = paciente.telefono || '-';
        
        const estadoBadge = paciente.estado === 'ACTIVO' 
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';
        
       const sexoBadge = paciente.sexo === 'M' 
    ? '<span class="badge bg-primary">Masculino</span>'
    : paciente.sexo === 'F' 
        ? '<span class="badge" style="background-color: #ff528cff; color: white;">Femenino</span>'
        : '<span class="badge bg-secondary">No especificado</span>';
        
        return `
            <tr data-uuid="${paciente.uuid}">
                <td>
                    <div>
                        <strong>${documento}</strong>
                        ${tipoDoc ? `<br><small class="text-muted">${tipoDoc}</small>` : ''}
                    </div>
                </td>
                <td>
                    <div>
                        <strong>${nombreCompleto}</strong>
                        ${paciente.edad ? `<br><small class="text-muted">${paciente.edad} años</small>` : ''}
                    </div>
                </td>
                <td>${fechaNacimiento}</td>
                <td>${sexoBadge}</td>
                <td>${telefono}</td>
                <td>${estadoBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="/pacientes/${paciente.uuid}" class="btn btn-outline-info" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/pacientes/${paciente.uuid}/edit" class="btn btn-outline-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deletePaciente('${paciente.uuid}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    console.log(`✅ ${pacientes.length} pacientes renderizados en la tabla`);
}

// ✅ RENDERIZAR TABLA VACÍA
function renderEmptyTable(message = 'No se encontraron pacientes') {
    const tbody = document.getElementById('pacientesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p class="mb-0">${message}</p>
                </div>
            </td>
        </tr>
    `;
}

// ✅ RENDERIZAR PAGINACIÓN (ajustado a tu HTML)
function renderPagination(meta) {
    console.log('📄 Renderizando paginación:', meta);
    
    const pagination = document.getElementById('pagination');
    
    if (!meta || meta.last_page <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Botón anterior
    if (meta.current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPacientes(${meta.current_page - 1}, currentFilters); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Páginas numeradas
    const startPage = Math.max(1, meta.current_page - 2);
    const endPage = Math.min(meta.last_page, meta.current_page + 2);
    
    if (startPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPacientes(1, currentFilters); return false;">1</a>
            </li>
        `;
        if (startPage > 2) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === meta.current_page) {
            paginationHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadPacientes(${i}, currentFilters); return false;">${i}</a>
                </li>
            `;
        }
    }
    
    if (endPage < meta.last_page) {
        if (endPage < meta.last_page - 1) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPacientes(${meta.last_page}, currentFilters); return false;">${meta.last_page}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (meta.current_page < meta.last_page) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadPacientes(${meta.current_page + 1}, currentFilters); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    pagination.innerHTML = paginationHTML;
    
    // Mostrar información de paginación
    const info = `Mostrando ${((meta.current_page - 1) * meta.per_page) + 1} a ${Math.min(meta.current_page * meta.per_page, meta.total)} de ${meta.total} registros`;
    console.log('📊 Info paginación:', info);
}

// ✅ MANEJAR FORMULARIO DE BÚSQUEDA
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const filters = {};
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim()) {
            filters[key] = value.trim();
        }
    }
    
    console.log('🔍 Aplicando filtros:', filters);
    loadPacientes(1, filters);
});

// ✅ LIMPIAR BÚSQUEDA
function clearSearch() {
    document.getElementById('searchForm').reset();
    console.log('🧹 Limpiando filtros');
    loadPacientes(1, {});
}

// ✅ BUSCAR POR DOCUMENTO ESPECÍFICO
function searchByDocument() {
    Swal.fire({
        title: 'Buscar Paciente',
        html: `
            <div class="mb-3 text-start">
                <label for="documentoInput" class="form-label">Número de Documento</label>
                <input type="text" class="form-control" id="documentoInput" placeholder="Ingrese el documento">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Buscar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d6efd',
        preConfirm: () => {
            const documento = document.getElementById('documentoInput').value;
            if (!documento || !documento.trim()) {
                Swal.showValidationMessage('Debe ingresar un documento');
                return false;
            }
            return documento.trim();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            searchPacienteByDocument(result.value);
        }
    });
}

// ✅ BUSCAR PACIENTE POR DOCUMENTO
function searchPacienteByDocument(documento) {
    console.log('🔍 Buscando paciente por documento:', documento);
    showLoading(true);
    
    fetch(`/pacientes/search/document?documento=${encodeURIComponent(documento)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            renderPacientesTable([data.data]);
            document.getElementById('pagination').innerHTML = '';
            showAlert('success', 'Paciente encontrado');
        } else {
            renderEmptyTable('Paciente no encontrado');
            showAlert('warning', data.error || 'Paciente no encontrado');
        }
    })
    .catch(error => {
        console.error('❌ Error buscando paciente:', error);
        renderEmptyTable('Error en la búsqueda');
        showAlert('error', 'Error buscando paciente');
    })
    .finally(() => {
        showLoading(false);
    });
}

// ✅ ELIMINAR PACIENTE
function deletePaciente(uuid) {
    Swal.fire({
        title: '¿Eliminar Paciente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/pacientes/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Paciente eliminado correctamente');
                    loadPacientes(currentPage, currentFilters);
                } else {
                    showAlert('error', data.error || 'Error eliminando paciente');
                }
            })
            .catch(error => {
                console.error('❌ Error eliminando paciente:', error);
                showAlert('error', 'Error de conexión');
            });
        }
    });
}

// ✅ SINCRONIZAR PACIENTES
function syncPacientes() {
    console.log('🔄 Iniciando sincronización');
    showLoading(true);
    
    fetch('/sync-pacientes', {
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
            showAlert('success', 'Sincronización completada');
            loadPacientes(currentPage, currentFilters);
        } else {
            showAlert('error', data.error || 'Error en sincronización');
        }
    })
    .catch(error => {
        console.error('❌ Error sincronizando:', error);
        showAlert('error', 'Error de conexión para sincronizar');
    })
    .finally(() => {
        showLoading(false);
    });
}

// ✅ MOSTRAR/OCULTAR LOADING
function showLoading(show) {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        if (show) {
            indicator.classList.remove('d-none');
        } else {
            indicator.classList.add('d-none');
        }
    }
}

// ✅ MOSTRAR ALERTAS
function showAlert(type, message, title = '') {
    const iconMap = {
        'success': 'success',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    Swal.fire({
        icon: iconMap[type] || 'info',
        title: title || (type === 'error' ? 'Error' : 'Información'),
        text: message,
        timer: type === 'success' ? 3000 : undefined,
        showConfirmButton: type !== 'success'
    });
}
</script>
@endpush

@endsection
