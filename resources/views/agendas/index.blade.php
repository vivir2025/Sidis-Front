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
                    
                    <!-- ✅ BOTONES DE SINCRONIZACIÓN -->
                    @if($isOffline)
                        <button type="button" class="btn btn-outline-info btn-sm me-2" onclick="syncAgendas()">
                            <i class="fas fa-sync-alt"></i> Sincronizar
                        </button>
                    @endif

                    <button type="button" class="btn btn-success btn-sm me-2" onclick="syncAllPendingAgendasData()">
                        <i class="fas fa-sync-alt"></i> Forzar Sync
                    </button>
                    
                    <!-- ✅ BOTÓN DE SINCRONIZACIÓN AUTOMÁTICA -->
                    <button type="button" id="btnSincronizar" class="btn btn-outline-warning position-relative me-2" onclick="sincronizarPendientes()" style="display: none;">
                        <i class="fas fa-sync-alt"></i> Sincronizar
                        <span id="badgePendientes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                            0
                        </span>
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="refreshAgendas()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    
                    <a href="{{ route('agendas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Agenda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ALERTA OFFLINE -->
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
            <div class="d-flex align-items-center gap-2">
                <!-- ✅ INDICADOR DE CARGA -->
                <div id="loadingIndicator" class="d-none">
                    <i class="fas fa-spinner fa-spin me-2"></i>Cargando...
                </div>
                <div id="totalRegistros" class="badge bg-primary">0 registros</div>
            </div>
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
let currentFilters = {};

// ✅ CORRECCIÓN COMPLETA: Cargar agendas al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando carga de agendas');
    
    // ✅ CORRECCIÓN: Establecer fecha de hoy por defecto para mostrar agendas del día
    const fechaDesde = document.getElementById('fecha_desde');
    const hoy = new Date();
    const fechaHoy = hoy.getFullYear() + '-' + 
                   String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(hoy.getDate()).padStart(2, '0');
    
    fechaDesde.value = fechaHoy;
    console.log('📅 Fecha por defecto establecida:', fechaHoy);
    
    // Cargar agendas del día actual
    loadAgendas(1);
    
    // Verificar pendientes
    checkPendingSync();
    setInterval(checkPendingSync, 30000);
});

// ✅ CORRECCIÓN: Función principal para cargar agendas
function loadAgendas(page = 1, filters = {}) {
    if (isLoading) return;
    
    isLoading = true;
    currentPage = page;
    currentFilters = filters;
    
    console.log('📥 Cargando agendas', { page, filters });
    showLoading(true);
    
    const formData = new FormData(document.getElementById('filtrosForm'));
    const params = new URLSearchParams();
    
    // Agregar filtros del formulario
    for (let [key, value] of formData.entries()) {
        if (value && value.toString().trim() !== '') {
            params.append(key, value.toString().trim());
        }
    }
    
    // Agregar filtros adicionales
    Object.keys(filters).forEach(key => {
        if (filters[key] && filters[key].toString().trim() !== '') {
            params.set(key, filters[key].toString().trim());
        }
    });
    
    params.append('page', page);
    
    console.log('🔍 Parámetros de búsqueda:', params.toString());
    
    fetch(`{{ route('agendas.index') }}?${params}`, {
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
            displayAgendas(data.data || [], data.meta || {});
            
            if (data.offline) {
                showAlert('info', data.message || 'Datos cargados desde almacenamiento local', 'Modo Offline');
            } else {
                console.log('✅ Datos cargados desde servidor');
            }
        } else {
            console.error('❌ Error en respuesta:', data.error);
            showAlert('error', data.error || 'Error cargando agendas');
            showEmptyState();
        }
    })
    .catch(error => {
        console.error('💥 Error de conexión:', error);
        showAlert('error', 'Error de conexión al cargar agendas');
        showEmptyState();
    })
    .finally(() => {
        isLoading = false;
        showLoading(false);
    });
}

// ✅ CORRECCIÓN: Mostrar agendas en tabla con fechas corregidas
function displayAgendas(agendas, meta) {
    console.log('🎨 Renderizando agendas:', agendas.length);
    
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
    
    console.log(`✅ ${agendas.length} agendas renderizadas en la tabla`);
}

// ✅ CORRECCIÓN: Crear fila de agenda con formateo de fecha correcto
function createAgendaRow(agenda) {
    const row = document.createElement('tr');
    row.setAttribute('data-uuid', agenda.uuid);
    
    // Estado badge
    const estadoBadge = getEstadoBadge(agenda.estado);
    
    // ✅ CORRECCIÓN: Formatear fecha correctamente sin problemas de zona horaria
    let fecha = 'Fecha inválida';
    let diaSemana = '';
    
    try {
        // Crear fecha directamente desde el string sin conversión UTC
        const partesFecha = agenda.fecha.split('-');
        const fechaObj = new Date(parseInt(partesFecha[0]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[2]));
        
        fecha = fechaObj.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        diaSemana = getDayName(fechaObj.getDay());
    } catch (error) {
        console.error('Error formateando fecha:', agenda.fecha, error);
    }
    
    // Formatear horario
    const horario = `${agenda.hora_inicio || '--:--'} - ${agenda.hora_fin || '--:--'}`;
    
    // Cupos disponibles
    const cupos = agenda.cupos_disponibles || 0;
    const cuposClass = cupos > 0 ? 'text-success' : 'text-warning';
    
    // Consultorio
    const consultorio = agenda.consultorio || 'Sin asignar';
    
    // Etiqueta
    const etiqueta = agenda.etiqueta || 'Sin etiqueta';
    
    row.innerHTML = `
        <td>
            <div class="fw-semibold">${fecha}</div>
            <small class="text-muted">${diaSemana}</small>
        </td>
        <td>
            <div>${horario}</div>
            <small class="text-muted">Intervalo: ${agenda.intervalo || 15}min</small>
        </td>
        <td>
            <div class="fw-semibold">${consultorio}</div>
        </td>
        <td>
            <span class="badge ${agenda.modalidad === 'Telemedicina' ? 'bg-info' : 'bg-secondary'}">
                ${agenda.modalidad || 'Ambulatoria'}
            </span>
        </td>
        <td>${etiqueta}</td>
        <td>${estadoBadge}</td>
        <td>
            <span class="${cuposClass} fw-semibold">${cupos}</span>
        </td>
        <td>
            <div class="btn-group btn-group-sm" role="group">
                <a href="/agendas/${agenda.uuid}" class="btn btn-outline-info" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="/agendas/${agenda.uuid}/edit" class="btn btn-outline-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-outline-danger" onclick="eliminarAgenda('${agenda.uuid}', '${fecha} - ${consultorio}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
}

// ✅ CORRECCIÓN: Función getDayName mejorada
function getDayName(dayIndex) {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return days[dayIndex] || '';
}

// ✅ CORRECCIÓN PRINCIPAL: Función limpiarFiltros completamente corregida
function limpiarFiltros() {
    console.log('🧹 Limpiando todos los filtros');
    
    // Limpiar completamente el formulario
    const form = document.getElementById('filtrosForm');
    form.reset();
    
    // ✅ LIMPIAR TODOS LOS CAMPOS INDIVIDUALMENTE
    document.getElementById('fecha_desde').value = '';
    document.getElementById('fecha_hasta').value = '';
    document.getElementById('estado').value = '';
    document.getElementById('modalidad').value = '';
    document.getElementById('consultorio').value = '';
    
    // ✅ LIMPIAR FILTROS ACTUALES
    currentFilters = {};
    
    // ✅ CARGAR TODAS LAS AGENDAS SIN FILTROS
    console.log('✅ Cargando todas las agendas sin filtros');
    loadAgendas(1, {});
}

// ✅ Verificar registros pendientes
async function checkPendingSync() {
    try {
        const response = await fetch('/agendas/test-sync', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success && data.pending_count > 0) {
            document.getElementById('btnSincronizar').style.display = 'inline-block';
            document.getElementById('badgePendientes').style.display = 'inline-block';
            document.getElementById('badgePendientes').textContent = data.pending_count;
        } else {
            document.getElementById('btnSincronizar').style.display = 'none';
            document.getElementById('badgePendientes').style.display = 'none';
        }

    } catch (error) {
        console.error('Error verificando pendientes de agendas:', error);
    }
}

// ✅ Sincronizar registros pendientes
async function sincronizarPendientes() {
    const btnSincronizar = document.getElementById('btnSincronizar');
    const originalHTML = btnSincronizar.innerHTML;
    
    try {
        btnSincronizar.disabled = true;
        btnSincronizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';

        const response = await fetch('/sync-agendas', {
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
                            <li>✅ Agendas sincronizadas: ${data.synced_count || 0}</li>
                            ${data.failed_count > 0 ? `<li>❌ Errores: ${data.failed_count}</li>` : ''}
                        </ul>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Entendido'
            });

            if (data.synced_count > 0) {
                checkPendingSync();
                loadAgendas(currentPage, currentFilters);
            }

        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error sincronizando agendas:', error);
        Swal.fire('Error', 'Error sincronizando: ' + error.message, 'error');
    } finally {
        btnSincronizar.disabled = false;
        btnSincronizar.innerHTML = originalHTML;
    }
}

// ✅ Funciones de sincronización
function syncAgendas() {
    console.log('🔄 Iniciando sincronización de agendas');
    showLoading(true);
    
    fetch('/sync-agendas', {
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
            Swal.fire({
                title: '¡Sincronización Exitosa!',
                html: `
                    <div class="text-start">
                        <p><strong>Resultados:</strong></p>
                        <ul>
                            <li>✅ Agendas sincronizadas: ${data.synced_count || 0}</li>
                            ${data.failed_count > 0 ? `<li>❌ Errores: ${data.failed_count}</li>` : ''}
                        </ul>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Entendido'
            });
            loadAgendas(currentPage, currentFilters);
            checkPendingSync();
        } else {
            Swal.fire('Error', data.error || 'Error en sincronización', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error sincronizando agendas:', error);
        Swal.fire('Error', 'Error de conexión para sincronizar', 'error');
    })
    .finally(() => {
        showLoading(false);
    });
}

function syncAllPendingAgendasData() {
    Swal.fire({
        title: '¿Forzar Sincronización?',
        text: 'Esto sincronizará todas las agendas pendientes',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, sincronizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            syncAgendas();
        }
    });
}

// ✅ Mostrar/ocultar loading
function showLoading(show) {
    const loadingAgendas = document.getElementById('loadingAgendas');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    if (show) {
        if (loadingAgendas) loadingAgendas.style.display = 'block';
        if (loadingIndicator) loadingIndicator.classList.remove('d-none');
        document.getElementById('tablaAgendas').style.display = 'none';
        document.getElementById('estadoVacio').style.display = 'none';
    } else {
        if (loadingAgendas) loadingAgendas.style.display = 'none';
        if (loadingIndicator) loadingIndicator.classList.add('d-none');
    }
}

// ✅ Utilidades
function getEstadoBadge(estado) {
    const badges = {
        'ACTIVO': '<span class="badge bg-success">Activo</span>',
        'ANULADA': '<span class="badge bg-danger">Anulada</span>',
        'LLENA': '<span class="badge bg-warning">Llena</span>'
    };
    return badges[estado] || '<span class="badge bg-secondary">Desconocido</span>';
}

function showEmptyState() {
    document.getElementById('tablaAgendas').style.display = 'none';
    document.getElementById('estadoVacio').style.display = 'block';
    document.getElementById('totalRegistros').textContent = '0 registros';
    document.getElementById('infoRegistros').textContent = '';
}

// ✅ Paginación
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
                <a class="page-link" href="#" onclick="loadAgendas(${meta.current_page - 1}, currentFilters); return false;">
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
                <a class="page-link" href="#" onclick="loadAgendas(1, currentFilters); return false;">1</a>
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
                    <a class="page-link" href="#" onclick="loadAgendas(${i}, currentFilters); return false;">${i}</a>
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
                <a class="page-link" href="#" onclick="loadAgendas(${meta.last_page}, currentFilters); return false;">${meta.last_page}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    if (meta.current_page < meta.last_page) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAgendas(${meta.current_page + 1}, currentFilters); return false;">
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

// ✅ Acciones
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
                loadAgendas(currentPage, currentFilters);
                checkPendingSync();
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
    loadAgendas(currentPage, currentFilters);
    checkPendingSync();
}

// ✅ CORRECCIÓN: Manejar formulario de búsqueda
document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const filters = {};
    
    for (let [key, value] of formData.entries()) {
        if (value && value.toString().trim() !== '') {
            filters[key] = value.toString().trim();
        }
    }
    
    console.log('🔍 Aplicando filtros:', filters);
    loadAgendas(1, filters);
});

// ✅ Mostrar alertas
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

// ✅ Toast helper
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
</script>
@endpush
@endsection

