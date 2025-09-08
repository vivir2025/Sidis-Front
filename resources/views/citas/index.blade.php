{{-- resources/views/citas/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Citas - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Gestión de Citas
                    </h1>
                    <p class="text-muted mb-0">Administra las citas médicas del sistema</p>
                </div>
                
                <div>
                    @if($isOffline)
                        <span class="badge bg-warning me-2">
                            <i class="fas fa-wifi-slash"></i> Modo Offline
                        </span>
                    @endif
                    <a href="{{ route('citas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Cita
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <form id="filtrosForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filtro_fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="filtro_fecha" name="fecha">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_estado" class="form-label">Estado</label>
                        <select class="form-select" id="filtro_estado" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="PROGRAMADA">Programada</option>
                            <option value="EN_ATENCION">En Atención</option>
                            <option value="ATENDIDA">Atendida</option>
                            <option value="CANCELADA">Cancelada</option>
                            <option value="NO_ASISTIO">No Asistió</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_documento" class="form-label">Documento Paciente</label>
                        <input type="text" class="form-control" id="filtro_documento" name="paciente_documento" 
                               placeholder="Número de documento">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Citas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista de Citas
            </h6>
            <div>
                <button type="button" class="btn btn-sm btn-info" onclick="citasDelDia()">
                    <i class="fas fa-calendar-day"></i> Citas del Día
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="exportarCitas()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Loading -->
            <div id="loadingCitas" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando citas...</p>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover" id="tablaCitas">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>Consultorio</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Motivo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="citasTableBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Sin resultados -->
            <div id="sinResultados" class="text-center py-5" style="display: none;">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron citas</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
            </div>

            <!-- Paginación -->
            <div id="paginacion" class="d-flex justify-content-between align-items-center mt-4">
                <div id="infoPaginacion" class="text-muted">
                    <!-- Info de paginación -->
                </div>
                <nav>
                    <ul class="pagination mb-0" id="paginacionLinks">
                        <!-- Links de paginación -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPage = 1;
let totalPages = 1;
let isLoading = false;

document.addEventListener('DOMContentLoaded', function() {
    cargarCitas();
});

// ✅ CARGAR CITAS
async function cargarCitas(page = 1, filters = {}) {
    if (isLoading) return;
    
    isLoading = true;
    mostrarLoading(true);
    
    try {
        const params = new URLSearchParams({
            page: page,
            ...filters
        });
        
        const response = await fetch(`/citas?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarCitas(data.data);
            actualizarPaginacion(data.meta || data.pagination);
            currentPage = page;
        } else {
            throw new Error(data.error || 'Error cargando citas');
        }
        
    } catch (error) {
        console.error('Error cargando citas:', error);
        mostrarError('Error cargando citas: ' + error.message);
    } finally {
        isLoading = false;
        mostrarLoading(false);
    }
}

// ✅ MOSTRAR CITAS EN TABLA
function mostrarCitas(citas) {
    const tbody = document.getElementById('citasTableBody');
    const sinResultados = document.getElementById('sinResultados');
    
    if (!citas || citas.length === 0) {
        tbody.innerHTML = '';
        sinResultados.style.display = 'block';
        return;
    }
    
    sinResultados.style.display = 'none';
    
    let html = '';
    citas.forEach(cita => {
        const fecha = new Date(cita.fecha).toLocaleDateString('es-ES');
        const horaInicio = new Date(cita.fecha_inicio).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        const horaFin = new Date(cita.fecha_final).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const estadoBadge = getEstadoBadge(cita.estado);
        const modalidadBadge = getModalidadBadge(cita.agenda?.modalidad);
        
        html += `
            <tr>
                <td>${fecha}</td>
                <td>${horaInicio} - ${horaFin}</td>
                <td>${cita.paciente?.nombre_completo || 'No disponible'}</td>
                <td>${cita.paciente?.documento || 'No disponible'}</td>
                <td>${cita.agenda?.consultorio || 'No disponible'}</td>
                <td>${modalidadBadge}</td>
                <td>${estadoBadge}</td>
                <td>
                    <span title="${cita.motivo || 'Sin motivo'}">
                        ${truncateText(cita.motivo || 'Sin motivo', 20)}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="/citas/${cita.uuid}" class="btn btn-outline-primary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/citas/${cita.uuid}/edit" class="btn btn-outline-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                                                <button type="button" class="btn btn-outline-danger" 
                                onclick="eliminarCita('${cita.uuid}')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" 
                                    data-bs-toggle="dropdown" title="Cambiar Estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="cambiarEstado('${cita.uuid}', 'PROGRAMADA')">
                                    <i class="fas fa-calendar text-primary me-2"></i>Programada
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="cambiarEstado('${cita.uuid}', 'EN_ATENCION')">
                                    <i class="fas fa-play text-info me-2"></i>En Atención
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="cambiarEstado('${cita.uuid}', 'ATENDIDA')">
                                    <i class="fas fa-check text-success me-2"></i>Atendida
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="cambiarEstado('${cita.uuid}', 'CANCELADA')">
                                    <i class="fas fa-times text-danger me-2"></i>Cancelada
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="cambiarEstado('${cita.uuid}', 'NO_ASISTIO')">
                                    <i class="fas fa-user-times text-warning me-2"></i>No Asistió
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// ✅ FUNCIONES AUXILIARES
function getEstadoBadge(estado) {
    const badges = {
        'PROGRAMADA': '<span class="badge bg-primary">Programada</span>',
        'EN_ATENCION': '<span class="badge bg-info">En Atención</span>',
        'ATENDIDA': '<span class="badge bg-success">Atendida</span>',
        'CANCELADA': '<span class="badge bg-danger">Cancelada</span>',
        'NO_ASISTIO': '<span class="badge bg-warning">No Asistió</span>'
    };
    
    return badges[estado] || '<span class="badge bg-secondary">Desconocido</span>';
}

function getModalidadBadge(modalidad) {
    if (modalidad === 'Telemedicina') {
        return '<span class="badge bg-info">Telemedicina</span>';
    } else if (modalidad === 'Ambulatoria') {
        return '<span class="badge bg-secondary">Ambulatoria</span>';
    }
    return '<span class="badge bg-light text-dark">No disponible</span>';
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// ✅ PAGINACIÓN
function actualizarPaginacion(meta) {
    if (!meta) return;
    
    const infoPaginacion = document.getElementById('infoPaginacion');
    const paginacionLinks = document.getElementById('paginacionLinks');
    
    // Información de paginación
    const desde = meta.from || 0;
    const hasta = meta.to || 0;
    const total = meta.total || 0;
    
    infoPaginacion.innerHTML = `Mostrando ${desde} a ${hasta} de ${total} resultados`;
    
    // Links de paginación
    totalPages = meta.last_page || 1;
    const currentPageNum = meta.current_page || 1;
    
    let linksHtml = '';
    
    // Botón anterior
    if (currentPageNum > 1) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(${currentPageNum - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Números de página
    const startPage = Math.max(1, currentPageNum - 2);
    const endPage = Math.min(totalPages, currentPageNum + 2);
    
    if (startPage > 1) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(1)">1</a>
            </li>
        `;
        if (startPage > 2) {
            linksHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPageNum ? 'active' : '';
        linksHtml += `
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>
            </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            linksHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(${totalPages})">${totalPages}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (currentPageNum < totalPages) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(${currentPageNum + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    paginacionLinks.innerHTML = linksHtml;
}

function cambiarPagina(page) {
    if (page !== currentPage && page >= 1 && page <= totalPages) {
        const filtros = obtenerFiltrosActuales();
        cargarCitas(page, filtros);
    }
}

// ✅ FILTROS
function aplicarFiltros() {
    const filtros = obtenerFiltrosActuales();
    cargarCitas(1, filtros);
}

function limpiarFiltros() {
    document.getElementById('filtrosForm').reset();
    cargarCitas(1, {});
}

function obtenerFiltrosActuales() {
    const form = document.getElementById('filtrosForm');
    const formData = new FormData(form);
    const filtros = {};
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            filtros[key] = value.trim();
        }
    }
    
    return filtros;
}

// ✅ ACCIONES
async function cambiarEstado(uuid, nuevoEstado) {
    try {
        const result = await Swal.fire({
            title: '¿Confirmar cambio de estado?',
            text: `¿Cambiar estado a "${nuevoEstado.replace('_', ' ')}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const response = await fetch(`/citas/${uuid}/estado`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ estado: nuevoEstado })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: data.message || 'Estado cambiado exitosamente',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Recargar tabla
                const filtros = obtenerFiltrosActuales();
                cargarCitas(currentPage, filtros);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        }
    } catch (error) {
        console.error('Error cambiando estado:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error cambiando estado: ' + error.message,
            icon: 'error'
        });
    }
}

async function eliminarCita(uuid) {
    try {
        const result = await Swal.fire({
            title: '¿Eliminar cita?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const response = await fetch(`/citas/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    title: '¡Eliminada!',
                    text: data.message || 'Cita eliminada exitosamente',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Recargar tabla
                const filtros = obtenerFiltrosActuales();
                cargarCitas(currentPage, filtros);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        }
    } catch (error) {
        console.error('Error eliminando cita:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error eliminando cita: ' + error.message,
            icon: 'error'
        });
    }
}

// ✅ FUNCIONES ADICIONALES
async function citasDelDia() {
    try {
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('filtro_fecha').value = hoy;
        aplicarFiltros();
        
        Swal.fire({
            title: 'Citas del Día',
            text: 'Mostrando citas de hoy',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('Error cargando citas del día:', error);
    }
}

function exportarCitas() {
    Swal.fire({
        title: 'Exportar Citas',
        text: 'Funcionalidad de exportación en desarrollo',
        icon: 'info'
    });
}

// ✅ UTILIDADES
function mostrarLoading(show) {
    const loading = document.getElementById('loadingCitas');
    const tabla = document.getElementById('tablaCitas');
    
    if (show) {
        loading.style.display = 'block';
        tabla.style.opacity = '0.5';
    } else {
        loading.style.display = 'none';
        tabla.style.opacity = '1';
    }
}

function mostrarError(mensaje) {
    Swal.fire({
        title: 'Error',
        text: mensaje,
        icon: 'error'
    });
}
</script>
@endpush
@endsection
