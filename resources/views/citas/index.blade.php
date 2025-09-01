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
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Gestión de Citas
                    </h1>
                    <p class="text-muted mb-0">Administrar citas médicas y consultas</p>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" onclick="verCitasDelDia()">
                        <i class="fas fa-calendar-day"></i> Citas de Hoy
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary" onclick="refreshCitas()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    
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
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                </div>
                
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="PROGRAMADA">Programada</option>
                        <option value="EN_ATENCION">En Atención</option>
                        <option value="ATENDIDA">Atendida</option>
                        <option value="CANCELADA">Cancelada</option>
                        <option value="NO_ASISTIO">No Asistió</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="paciente_documento" class="form-label">Documento Paciente</label>
                    <input type="text" class="form-control" id="paciente_documento" name="paciente_documento" 
                           placeholder="Buscar por documento...">
                </div>
                
                <div class="col-md-6">
                    <div class="d-flex align-items-end h-100">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Citas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Citas
            </h5>
            <div id="totalRegistros" class="badge bg-primary">0 registros</div>
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
                            <th>Fecha/Hora</th>
                            <th>Paciente</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Agenda</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="citasTableBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div id="paginacionContainer" class="d-flex justify-content-between align-items-center mt-3">
                <div id="infoRegistros" class="text-muted">
                    <!-- Se llena dinámicamente -->
                </div>
                <nav id="paginacionNav">
                    <!-- Se llena dinámicamente -->
                </nav>
            </div>

            <!-- Estado vacío -->
            <div id="estadoVacio" class="text-center py-5" style="display: none;">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay citas registradas</h5>
                <p class="text-muted">Comienza creando tu primera cita médica</p>
                <a href="{{ route('citas.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Primera Cita
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado de Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCambiarEstado">
                    <input type="hidden" id="citaUuidEstado" name="cita_uuid">
                    
                    <div class="mb-3">
                        <label for="nuevoEstado" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="nuevoEstado" name="estado" required>
                            <option value="PROGRAMADA">Programada</option>
                            <option value="EN_ATENCION">En Atención</option>
                            <option value="ATENDIDA">Atendida</option>
                            <option value="CANCELADA">Cancelada</option>
                            <option value="NO_ASISTIO">No Asistió</option>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cambiar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPage = 1;
let isLoading = false;

// ✅ CARGAR CITAS
async function loadCitas(page = 1, showLoading = true) {
    if (isLoading) return;
    
    try {
        isLoading = true;
        
        if (showLoading) {
            document.getElementById('loadingCitas').style.display = 'block';
            document.getElementById('tablaCitas').style.display = 'none';
            document.getElementById('estadoVacio').style.display = 'none';
        }

        const formData = new FormData(document.getElementById('filtrosForm'));
        const params = new URLSearchParams();
        
        // Agregar filtros
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        params.append('page', page);

        const response = await fetch(`/citas?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            displayCitas(data.data, data.meta);
            
            if (data.message) {
                showToast(data.offline ? 'warning' : 'success', data.message);
            }
        } else {
            throw new Error(data.error || 'Error cargando citas');
        }

    } catch (error) {
        console.error('Error cargando citas:', error);
        showAlert('error', 'Error cargando citas: ' + error.message);
        showEmptyState();
    } finally {
        isLoading = false;
        document.getElementById('loadingCitas').style.display = 'none';
    }
}

// ✅ MOSTRAR CITAS EN TABLA
function displayCitas(citas, meta) {
    const tbody = document.getElementById('citasTableBody');
    const tabla = document.getElementById('tablaCitas');
    const estadoVacio = document.getElementById('estadoVacio');
    
    if (!citas || citas.length === 0) {
        showEmptyState();
        return;
    }

    tbody.innerHTML = '';
    
    citas.forEach(cita => {
        const row = createCitaRow(cita);
        tbody.appendChild(row);
    });

    tabla.style.display = 'table';
    estadoVacio.style.display = 'none';
    
    updatePagination(meta);
    updateRegistrosInfo(meta);
}

// ✅ CREAR FILA DE CITA
function createCitaRow(cita) {
    const row = document.createElement('tr');
    
    // Estado badge
    const estadoBadge = getEstadoBadge(cita.estado);
    
    // Formatear fecha y hora
    const fechaInicio = new Date(cita.fecha_inicio);
    const fecha = fechaInicio.toLocaleDateString('es-ES');
    const hora = fechaInicio.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    
    // Información del paciente
    const pacienteNombre = cita.paciente?.nombre_completo || 'Paciente no encontrado';
    const pacienteDoc = cita.paciente?.documento || 'Sin documento';
    
    row.innerHTML = `
        <td>
            <div class="fw-semibold">${fecha}</div>
            <small class="text-muted">${hora}</small>
        </td>
        <td>
            <div class="fw-semibold">${pacienteNombre}</div>
            <small class="text-muted">Doc: ${pacienteDoc}</small>
        </td>
        <td>
            <div>${cita.motivo || 'Sin motivo especificado'}</div>
            ${cita.nota ? `<small class="text-muted">${cita.nota}</small>` : ''}
        </td>
        <td>${estadoBadge}</td>
        <td>
            <div>${cita.agenda?.consultorio || 'Sin asignar'}</div>
            <small class="text-muted">${cita.agenda?.etiqueta || ''}</small>
        </td>
        <td>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="verCita('${cita.uuid}')" title="Ver">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="editarCita('${cita.uuid}')" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-outline-info" onclick="cambiarEstadoCita('${cita.uuid}', '${cita.estado}')" title="Cambiar Estado">
                    <i class="fas fa-exchange-alt"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="eliminarCita('${cita.uuid}', '${fecha} - ${pacienteNombre}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// ✅ UTILIDADES
function getEstadoBadge(estado) {
    const badges = {
        'PROGRAMADA': '<span class="badge bg-primary">Programada</span>',
        'EN_ATENCION': '<span class="badge bg-warning">En Atención</span>',
        'ATENDIDA': '<span class="badge bg-success">Atendida</span>',
        'CANCELADA': '<span class="badge bg-danger">Cancelada</span>',
        'NO_ASISTIO': '<span class="badge bg-secondary">No Asistió</span>'
    };
    return badges[estado] || '<span class="badge bg-secondary">Desconocido</span>';
}

function showEmptyState() {
    document.getElementById('tablaCitas').style.display = 'none';
    document.getElementById('estadoVacio').style.display = 'block';
    document.getElementById('totalRegistros').textContent = '0 registros';
}

function updatePagination(meta) {
    const nav = document.getElementById('paginacionNav');
    
    if (!meta || meta.last_page <= 1) {
        nav.innerHTML = '';
        return;
    }

    let paginationHTML = '<ul class="pagination pagination-sm mb-0">';
    
    // Botón anterior
    if (meta.current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadCitas(${meta.current_page - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Páginas
    const startPage = Math.max(1, meta.current_page - 2);
    const endPage = Math.min(meta.last_page, meta.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadCitas(${i})">${i}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (meta.current_page < meta.last_page) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadCitas(${meta.current_page + 1})">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    paginationHTML += '</ul>';
    nav.innerHTML = paginationHTML;
}

function updateRegistrosInfo(meta) {
    const info = document.getElementById('infoRegistros');
    const total = document.getElementById('totalRegistros');
    
    if (meta && meta.total > 0) {
        const desde = ((meta.current_page - 1) * meta.per_page) + 1;
        const hasta = Math.min(meta.current_page * meta.per_page, meta.total);
        
        info.textContent = `Mostrando ${desde} a ${hasta} de ${meta.total} registros`;
        total.textContent = `${meta.total} registros`;
    } else {
        info.textContent = '';
        total.textContent = '0 registros';
    }
}

// ✅ ACCIONES
function verCita(uuid) {
    window.location.href = `/citas/${uuid}`;
}

function editarCita(uuid) {
    window.location.href = `/citas/${uuid}/edit`;
}

async function eliminarCita(uuid, descripcion) {
    const result = await Swal.fire({
        title: '¿Eliminar Cita?',
        html: `¿Está seguro que desea eliminar la cita:<br><strong>${descripcion}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`/citas/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('¡Eliminado!', data.message, 'success');
                loadCitas(currentPage, false);
            } else {
                throw new Error(data.error);
            }

        } catch (error) {
            console.error('Error eliminando cita:', error);
            Swal.fire('Error', 'Error eliminando cita: ' + error.message, 'error');
        }
    }
}

// ✅ CAMBIAR ESTADO
function cambiarEstadoCita(uuid, estadoActual) {
    document.getElementById('citaUuidEstado').value = uuid;
    document.getElementById('nuevoEstado').value = estadoActual;
    
    const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
    modal.show();
}

// ✅ CITAS DEL DÍA
async function verCitasDelDia() {
    try {
        const hoy = new Date().toISOString().split('T')[0];
        
        const response = await fetch(`/citas/del-dia?fecha=${hoy}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            mostrarCitasDelDia(data.data);
        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error obteniendo citas del día:', error);
        Swal.fire('Error', 'Error obteniendo citas del día: ' + error.message, 'error');
    }
}

function mostrarCitasDelDia(citas) {
    let html = '<div class="row">';
    
    if (citas.length === 0) {
        html += `
            <div class="col-12 text-center">
                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                <h5>No hay citas programadas para hoy</h5>
            </div>
        `;
    } else {
        citas.forEach(cita => {
            const fechaInicio = new Date(cita.fecha_inicio);
            const hora = fechaInicio.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            const estadoBadge = getEstadoBadge(cita.estado);
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">${hora}</h6>
                                    <p class="card-text mb-1">
                                        <strong>${cita.paciente?.nombre_completo || 'Paciente no encontrado'}</strong>
                                    </p>
                                    <small class="text-muted">Doc: ${cita.paciente?.documento || 'Sin documento'}</small>
                                    <br>
                                    <small class="text-muted">${cita.motivo || 'Sin motivo'}</small>
                                </div>
                                <div class="text-end">
                                    ${estadoBadge}
                                    <br>
                                    <small class="text-muted">${cita.agenda?.consultorio || 'Sin asignar'}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    
    Swal.fire({
        title: `Citas de Hoy (${new Date().toLocaleDateString('es-ES')})`,
        html: html,
        width: '800px',
        showConfirmButton: false,
        showCloseButton: true
    });
}

function refreshCitas() {
    loadCitas(currentPage);
}

function limpiarFiltros() {
    document.getElementById('filtrosForm').reset();
    loadCitas(1);
}

// ✅ EVENTOS
document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    loadCitas(1);
});

// Evento para cambiar estado
document.getElementById('formCambiarEstado').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const uuid = document.getElementById('citaUuidEstado').value;
    const nuevoEstado = document.getElementById('nuevoEstado').value;
    
    try {
        const response = await fetch(`/citas/${uuid}/estado`, {
            method: 'PATCH',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ estado: nuevoEstado })
        });

        const data = await response.json();

        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCambiarEstado'));
            modal.hide();
            
            Swal.fire('¡Éxito!', data.message, 'success');
            loadCitas(currentPage, false);
        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error cambiando estado:', error);
        Swal.fire('Error', 'Error cambiando estado: ' + error.message, 'error');
    }
});

// ✅ TOAST HELPER
function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
    const bgClass = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning' : 'bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

// ✅ INICIALIZAR
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha por defecto (hoy)
    document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
    
    // Cargar citas iniciales
    loadCitas(1);
});
</script>
@endpush
@endsection
