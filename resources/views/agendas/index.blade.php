{{-- resources/views/agendas/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Agendas - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Gestión de Agendas
                    </h1>
                    <p class="text-muted mb-0">Administrar agendas médicas y horarios de atención</p>
                </div>
                
                <div class="d-flex gap-2">
                    <!-- ✅ BOTÓN DE SINCRONIZACIÓN -->
                    <button type="button" id="btnSincronizar" class="btn btn-outline-warning position-relative" onclick="sincronizarPendientes()" style="display: none;">
                        <i class="fas fa-sync-alt"></i> Sincronizar
                        <span id="badgePendientes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                            0
                        </span>
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary" onclick="refreshAgendas()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    
                    <a href="{{ route('agendas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Agenda
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
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                </div>
                
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="ANULADA">Anulada</option>
                        <option value="LLENA">Llena</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="modalidad" class="form-label">Modalidad</label>
                    <select class="form-select" id="modalidad" name="modalidad">
                        <option value="">Todas</option>
                        <option value="Ambulatoria">Ambulatoria</option>
                        <option value="Telemedicina">Telemedicina</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="consultorio" class="form-label">Consultorio</label>
                    <input type="text" class="form-control" id="consultorio" name="consultorio" placeholder="Buscar...">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Agendas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Agendas
            </h5>
            <div id="totalRegistros" class="badge bg-primary">0 registros</div>
        </div>
        <div class="card-body">
            <!-- Loading -->
            <div id="loadingAgendas" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando agendas...</p>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover" id="tablaAgendas">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Consultorio</th>
                            <th>Modalidad</th>
                            <th>Etiqueta</th>
                            <th>Estado</th>
                            <th>Cupos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="agendasTableBody">
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
                <h5 class="text-muted">No hay agendas registradas</h5>
                <p class="text-muted">Comienza creando tu primera agenda médica</p>
                <a href="{{ route('agendas.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Primera Agenda
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPage = 1;
let isLoading = false;

// ✅ CARGAR AGENDAS
async function loadAgendas(page = 1, showLoading = true) {
    if (isLoading) return;
    
    try {
        isLoading = true;
        
        if (showLoading) {
            document.getElementById('loadingAgendas').style.display = 'block';
            document.getElementById('tablaAgendas').style.display = 'none';
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

        const response = await fetch(`/agendas?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            displayAgendas(data.data, data.meta);
            
            if (data.message) {
                showToast(data.offline ? 'warning' : 'success', data.message);
            }
        } else {
            throw new Error(data.error || 'Error cargando agendas');
        }

    } catch (error) {
        console.error('Error cargando agendas:', error);
        showAlert('error', 'Error cargando agendas: ' + error.message);
        showEmptyState();
    } finally {
        isLoading = false;
        document.getElementById('loadingAgendas').style.display = 'none';
    }
}

// ✅ MOSTRAR AGENDAS EN TABLA
function displayAgendas(agendas, meta) {
    const tbody = document.getElementById('agendasTableBody');
    const tabla = document.getElementById('tablaAgendas');
    const estadoVacio = document.getElementById('estadoVacio');
    
    if (!agendas || agendas.length === 0) {
        showEmptyState();
        return;
    }

    tbody.innerHTML = '';
    
    agendas.forEach(agenda => {
        const row = createAgendaRow(agenda);
        tbody.appendChild(row);
    });

    tabla.style.display = 'table';
    estadoVacio.style.display = 'none';
    
    updatePagination(meta);
    updateRegistrosInfo(meta);
}

// ✅ CREAR FILA DE AGENDA
function createAgendaRow(agenda) {
    const row = document.createElement('tr');
    
    // Estado badge
    const estadoBadge = getEstadoBadge(agenda.estado);
    
    // Formatear fecha
    const fecha = new Date(agenda.fecha).toLocaleDateString('es-ES');
    
    // Formatear horario
    const horario = `${agenda.hora_inicio} - ${agenda.hora_fin}`;
    
    // Cupos disponibles
    const cupos = agenda.cupos_disponibles || 0;
    const cuposClass = cupos > 0 ? 'text-success' : 'text-warning';
    
    row.innerHTML = `
        <td>
            <div class="fw-semibold">${fecha}</div>
            <small class="text-muted">${getDayName(agenda.fecha)}</small>
        </td>
        <td>
            <div>${horario}</div>
            <small class="text-muted">Intervalo: ${agenda.intervalo}min</small>
        </td>
        <td>
            <div class="fw-semibold">${agenda.consultorio}</div>
        </td>
        <td>
            <span class="badge ${agenda.modalidad === 'Telemedicina' ? 'bg-info' : 'bg-secondary'}">
                ${agenda.modalidad}
            </span>
        </td>
        <td>${agenda.etiqueta}</td>
        <td>${estadoBadge}</td>
        <td>
            <span class="${cuposClass} fw-semibold">${cupos}</span>
        </td>
        <td>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="verAgenda('${agenda.uuid}')" title="Ver">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="editarAgenda('${agenda.uuid}')" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="eliminarAgenda('${agenda.uuid}', '${agenda.fecha} - ${agenda.consultorio}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// ✅ VERIFICAR REGISTROS PENDIENTES
async function checkPendingSync() {
    try {
        const response = await fetch('/agendas/pending-count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success && data.data.total > 0) {
            document.getElementById('btnSincronizar').style.display = 'inline-block';
            document.getElementById('badgePendientes').style.display = 'inline-block';
            document.getElementById('badgePendientes').textContent = data.data.total;
        } else {
            document.getElementById('btnSincronizar').style.display = 'none';
            document.getElementById('badgePendientes').style.display = 'none';
        }

    } catch (error) {
        console.error('Error verificando pendientes:', error);
    }
}

// ✅ SINCRONIZAR REGISTROS PENDIENTES
async function sincronizarPendientes() {
    const btnSincronizar = document.getElementById('btnSincronizar');
    const originalHTML = btnSincronizar.innerHTML;
    
    try {
        // Mostrar loading
        btnSincronizar.disabled = true;
        btnSincronizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';

        const response = await fetch('/agendas/sync-pending', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                title: '¡Sincronización Exitosa!',
                html: `
                    <div class="text-start">
                        <p><strong>Resultados:</strong></p>
                        <ul>
                            <li>✅ Registros sincronizados: ${data.data.totals.success}</li>
                            ${data.data.totals.errors > 0 ? `<li>❌ Errores: ${data.data.totals.errors}</li>` : ''}
                        </ul>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Entendido'
            });

            // Ocultar botón si no hay más pendientes
            if (data.data.totals.success > 0) {
                checkPendingSync();
                loadAgendas(currentPage, false);
            }

        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error sincronizando:', error);
        Swal.fire('Error', 'Error sincronizando: ' + error.message, 'error');
    } finally {
        btnSincronizar.disabled = false;
        btnSincronizar.innerHTML = originalHTML;
    }
}

// ✅ UTILIDADES
function getEstadoBadge(estado) {
    const badges = {
        'ACTIVO': '<span class="badge bg-success">Activo</span>',
        'ANULADA': '<span class="badge bg-danger">Anulada</span>',
        'LLENA': '<span class="badge bg-warning">Llena</span>'
    };
    return badges[estado] || '<span class="badge bg-secondary">Desconocido</span>';
}

function getDayName(fecha) {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return days[new Date(fecha).getDay()];
}

function showEmptyState() {
    document.getElementById('tablaAgendas').style.display = 'none';
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
                <a class="page-link" href="#" onclick="loadAgendas(${meta.current_page - 1})">
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
                <a class="page-link" href="#" onclick="loadAgendas(${i})">${i}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (meta.current_page < meta.last_page) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAgendas(${meta.current_page + 1})">
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
function verAgenda(uuid) {
    window.location.href = `/agendas/${uuid}`;
}

function editarAgenda(uuid) {
    window.location.href = `/agendas/${uuid}/edit`;
}

async function eliminarAgenda(uuid, descripcion) {
    const result = await Swal.fire({
        title: '¿Eliminar Agenda?',
        html: `¿Está seguro que desea eliminar la agenda:<br><strong>${descripcion}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`/agendas/${uuid}`, {
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
                loadAgendas(currentPage, false);
                checkPendingSync(); // Verificar pendientes después de eliminar
            } else {
                throw new Error(data.error);
            }

        } catch (error) {
            console.error('Error eliminando agenda:', error);
            Swal.fire('Error', 'Error eliminando agenda: ' + error.message, 'error');
        }
    }
}

function refreshAgendas() {
    loadAgendas(currentPage);
    checkPendingSync(); // Verificar pendientes también
}

function limpiarFiltros() {
    document.getElementById('filtrosForm').reset();
    loadAgendas(1);
}

// ✅ EVENTOS
document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    loadAgendas(1);
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
    document.getElementById('fecha_desde').value = new Date().toISOString().split('T')[0];
    
    // Cargar agendas iniciales
    loadAgendas(1);
    
    // ✅ VERIFICAR PENDIENTES AL CARGAR
    checkPendingSync();
    
    // ✅ VERIFICAR PENDIENTES CADA 30 SEGUNDOS
    setInterval(checkPendingSync, 30000);
});
</script>
@endpush
@endsection
