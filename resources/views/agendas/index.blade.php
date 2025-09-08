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
                    
                    <!-- Botones de Sincronización -->
                    @if($isOffline)
                        <button type="button" class="btn btn-outline-info btn-sm me-2" onclick="syncAgendas()">
                            <i class="fas fa-sync-alt"></i> Sincronizar
                        </button>
                    @endif

                    <button type="button" class="btn btn-success btn-sm me-2" onclick="syncAllPendingAgendasData()">
                        <i class="fas fa-sync-alt"></i> Forzar Sync
                    </button>
                    
                    <!-- Botón de Sincronización Automática -->
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
                    
                    <!-- Filtros Rápidos -->
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="verAgendasHoy()" title="Ver agendas de hoy">
                            <i class="fas fa-calendar-day"></i> Hoy
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="verAgendasSemana()" title="Ver agendas de esta semana">
                            <i class="fas fa-calendar-week"></i> Esta Semana
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="verTodasLasAgendas()" title="Ver todas las agendas">
                            <i class="fas fa-list"></i> Todas
                        </button>
                    </div>
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
                <!-- Selector de registros por página -->
                <div class="d-flex align-items-center me-3">
                    <label for="perPageSelect" class="form-label me-2 mb-0 small">Mostrar:</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage()">
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="small text-muted ms-2">por página</span>
                </div>
                
                <!-- Indicador de carga -->
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
                            <th>
                                <a href="#" onclick="sortBy('fecha')" class="text-decoration-none text-dark">
                                    Fecha 
                                    <i id="sort-fecha" class="fas fa-sort-down text-primary"></i>
                                </a>
                            </th>
                            <th>Horario</th>
                            <th>
                                <a href="#" onclick="sortBy('consultorio')" class="text-decoration-none text-dark">
                                    Consultorio 
                                    <i id="sort-consultorio" class="fas fa-sort text-muted"></i>
                                </a>
                            </th>
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

            <!-- ✅ PAGINACIÓN MEJORADA -->
            <div id="paginacionContainer" class="d-flex justify-content-between align-items-center mt-3">
                <div id="infoRegistros" class="text-muted">
                    <!-- Se llena dinámicamente -->
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Navegación rápida -->
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnFirstPage" class="btn btn-outline-secondary btn-sm" onclick="goToPage(1)" title="Primera página">
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button id="btnPrevPage" class="btn btn-outline-secondary btn-sm" onclick="goToPrevPage()" title="Página anterior">
                            <i class="fas fa-angle-left"></i>
                        </button>
                        
                        <!-- Input de página actual -->
                        <div class="d-flex align-items-center gap-1">
                            <span class="small">Página</span>
                            <input type="number" id="currentPageInput" class="form-control form-control-sm text-center" 
                                   style="width: 60px;" min="1" onchange="goToInputPage()" onkeypress="handlePageInputKeypress(event)">
                            <span class="small">de <span id="totalPagesSpan">1</span></span>
                        </div>
                        
                        <button id="btnNextPage" class="btn btn-outline-secondary btn-sm" onclick="goToNextPage()" title="Página siguiente">
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button id="btnLastPage" class="btn btn-outline-secondary btn-sm" onclick="goToLastPage()" title="Última página">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                    
                    <!-- Paginación tradicional -->
                    <nav id="paginacionNav">
                        <!-- Se llena dinámicamente -->
                    </nav>
                </div>
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
let totalPages = 1;
let totalItems = 0;
let currentPerPage = 15;
let isLoading = false;
let currentFilters = {};
let currentSort = { field: 'fecha', order: 'desc' }; // ✅ ORDENAMIENTO POR DEFECTO

// ✅ CONTINUACIÓN DEL SCRIPT
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando carga de agendas');
    
    // Verificar si hay filtros en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const hasFechaParam = urlParams.has('fecha_desde') || urlParams.has('fecha_hasta');
    
    if (!hasFechaParam) {
        console.log('📅 Cargando todas las agendas sin restricción de fecha');
        loadAgendas(1, {});
    } else {
        const filters = {};
        if (urlParams.get('fecha_desde')) {
            filters.fecha_desde = urlParams.get('fecha_desde');
            document.getElementById('fecha_desde').value = urlParams.get('fecha_desde');
        }
        if (urlParams.get('fecha_hasta')) {
            filters.fecha_hasta = urlParams.get('fecha_hasta');
            document.getElementById('fecha_hasta').value = urlParams.get('fecha_hasta');
        }
        loadAgendas(1, filters);
    }
    
    // Verificar pendientes
    checkPendingSync();
    setInterval(checkPendingSync, 30000);
});

// ✅ FUNCIÓN PRINCIPAL PARA CARGAR AGENDAS CON PAGINACIÓN
function loadAgendas(page = 1, filters = {}, perPage = null) {
    if (isLoading) return;
    
    isLoading = true;
    currentPage = page;
    currentFilters = filters;
    
    if (perPage) {
        currentPerPage = perPage;
    }
    
    console.log('📥 Cargando agendas', { page, filters, perPage: currentPerPage });
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
    
    // ✅ AGREGAR PARÁMETROS DE PAGINACIÓN Y ORDENAMIENTO
    params.append('page', page);
    params.append('per_page', currentPerPage);
    params.append('sort_by', currentSort.field);
    params.append('sort_order', currentSort.order);
    
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
            // ✅ ACTUALIZAR VARIABLES GLOBALES DE PAGINACIÓN
            currentPage = data.current_page || page;
            totalPages = data.total_pages || 1;
            totalItems = data.total_items || 0;
            currentPerPage = data.per_page || currentPerPage;
            
            displayAgendas(data.data || [], data.meta || data.pagination || {});
            updatePaginationControls(data.meta || data.pagination || {});
            
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

// ✅ MOSTRAR AGENDAS EN TABLA
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
    
    updateRegistrosInfo(meta);
    
    console.log(`✅ ${agendas.length} agendas renderizadas en la tabla`);
}

// ✅ CREAR FILA DE AGENDA
function createAgendaRow(agenda) {
    const row = document.createElement('tr');
    row.setAttribute('data-uuid', agenda.uuid);
    
    const estadoBadge = getEstadoBadge(agenda.estado);
    
    // Formatear fecha
    let fecha = 'Fecha inválida';
    let diaSemana = '';
    
    try {
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
    
    const horario = `${agenda.hora_inicio || '--:--'} - ${agenda.hora_fin || '--:--'}`;
    const cupos = agenda.cupos_disponibles || 0;
    const cuposClass = cupos > 0 ? 'text-success' : 'text-warning';
    const consultorio = agenda.consultorio || 'Sin asignar';
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

// ✅ CONTROLES DE PAGINACIÓN MEJORADOS
function updatePaginationControls(meta) {
    // Actualizar información de registros
    updateRegistrosInfo(meta);
    
    // Actualizar controles de navegación
    updateNavigationButtons();
    
    // Actualizar input de página actual
    document.getElementById('currentPageInput').value = currentPage;
    document.getElementById('totalPagesSpan').textContent = totalPages;
    
    // Actualizar selector de registros por página
    document.getElementById('perPageSelect').value = currentPerPage;
    
    // Actualizar paginación tradicional
    updateTraditionalPagination(meta);
}

// ✅ ACTUALIZAR BOTONES DE NAVEGACIÓN
function updateNavigationButtons() {
    const btnFirst = document.getElementById('btnFirstPage');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');
    const btnLast = document.getElementById('btnLastPage');
    const pageInput = document.getElementById('currentPageInput');
    
    // Habilitar/deshabilitar botones
    btnFirst.disabled = currentPage <= 1;
    btnPrev.disabled = currentPage <= 1;
    btnNext.disabled = currentPage >= totalPages;
    btnLast.disabled = currentPage >= totalPages;
    
    // Configurar input
    pageInput.max = totalPages;
    pageInput.min = 1;
}

// ✅ ACTUALIZAR PAGINACIÓN TRADICIONAL
function updateTraditionalPagination(meta) {
    const nav = document.getElementById('paginacionNav');
    
    if (!meta || totalPages <= 1) {
        nav.innerHTML = '';
        return;
    }

    let paginationHTML = '<ul class="pagination pagination-sm mb-0">';
    
    // Calcular rango de páginas a mostrar
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    // Páginas numeradas
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                </li>
            `;
        }
    }
    
    paginationHTML += '</ul>';
    nav.innerHTML = paginationHTML;
}

// ✅ FUNCIONES DE NAVEGACIÓN
function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        loadAgendas(page, currentFilters, currentPerPage);
    }
}

function goToPrevPage() {
    if (currentPage > 1) {
        goToPage(currentPage - 1);
    }
}

function goToNextPage() {
    if (currentPage < totalPages) {
        goToPage(currentPage + 1);
    }
}

function goToLastPage() {
    goToPage(totalPages);
}

function goToInputPage() {
    const input = document.getElementById('currentPageInput');
    const page = parseInt(input.value);
    
    if (page >= 1 && page <= totalPages) {
        goToPage(page);
    } else {
        input.value = currentPage; // Restaurar valor válido
    }
}

function handlePageInputKeypress(event) {
    if (event.key === 'Enter') {
        goToInputPage();
    }
}

// ✅ CAMBIAR REGISTROS POR PÁGINA
function changePerPage() {
    const select = document.getElementById('perPageSelect');
    const newPerPage = parseInt(select.value);
    
    if (newPerPage !== currentPerPage) {
        currentPerPage = newPerPage;
        loadAgendas(1, currentFilters, currentPerPage); // Volver a página 1
    }
}

// ✅ ORDENAMIENTO
function sortBy(field) {
    if (currentSort.field === field) {
        // Cambiar dirección si es el mismo campo
        currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
    } else {
        // Nuevo campo, empezar con ASC
        currentSort.field = field;
        currentSort.order = 'asc';
    }
    
    updateSortIcons();
    loadAgendas(1, currentFilters, currentPerPage); // Volver a página 1
}

// ✅ ACTUALIZAR ICONOS DE ORDENAMIENTO
function updateSortIcons() {
    // Limpiar todos los iconos
    document.querySelectorAll('[id^="sort-"]').forEach(icon => {
        icon.className = 'fas fa-sort text-muted';
    });
    
    // Actualizar icono del campo activo
    const activeIcon = document.getElementById(`sort-${currentSort.field}`);
    if (activeIcon) {
        if (currentSort.order === 'asc') {
            activeIcon.className = 'fas fa-sort-up text-primary';
        } else {
            activeIcon.className = 'fas fa-sort-down text-primary';
        }
    }
}

function updateRegistrosInfo(meta) {
    const info = document.getElementById('infoRegistros');
    const total = document.getElementById('totalRegistros');
    
    if (meta && meta.total > 0) {
        const desde = meta.from || (((currentPage - 1) * currentPerPage) + 1);
        const hasta = meta.to || Math.min(currentPage * currentPerPage, meta.total);
        
        info.textContent = `Mostrando ${desde} a ${hasta} de ${meta.total} registros`;
        total.textContent = `${meta.total} registros`;
    } else {
        info.textContent = '';
        total.textContent = '0 registros';
    }
}

// ✅ FILTROS RÁPIDOS
function verAgendasHoy() {
    const hoy = new Date();
    const fechaHoy = hoy.getFullYear() + '-' + 
                   String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(hoy.getDate()).padStart(2, '0');
    
    document.getElementById('fecha_desde').value = fechaHoy;
    document.getElementById('fecha_hasta').value = fechaHoy;
    
    console.log('📅 Cargando agendas de hoy:', fechaHoy);
    loadAgendas(1, { fecha_desde: fechaHoy, fecha_hasta: fechaHoy });
}

function verAgendasSemana() {
    const hoy = new Date();
    const inicioSemana = new Date(hoy.setDate(hoy.getDate() - hoy.getDay()));
    const finSemana = new Date(hoy.setDate(hoy.getDate() - hoy.getDay() + 6));
    
    const fechaInicio = inicioSemana.getFullYear() + '-' + 
                       String(inicioSemana.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(inicioSemana.getDate()).padStart(2, '0');
    
    const fechaFin = finSemana.getFullYear() + '-' + 
                    String(finSemana.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(finSemana.getDate()).padStart(2, '0');
    
    document.getElementById('fecha_desde').value = fechaInicio;
    document.getElementById('fecha_hasta').value = fechaFin;
    
    console.log('📅 Cargando agendas de esta semana:', fechaInicio, 'a', fechaFin);
    loadAgendas(1, { fecha_desde: fechaInicio, fecha_hasta: fechaFin });
}

function verTodasLasAgendas() {
    console.log('📅 Cargando TODAS las agendas');
    
    const form = document.getElementById('filtrosForm');
    form.reset();
    
    document.getElementById('fecha_desde').value = '';
    document.getElementById('fecha_hasta').value = '';
    document.getElementById('estado').value = '';
    document.getElementById('modalidad').value = '';
    document.getElementById('consultorio').value = '';
    
    currentFilters = {};
    
    const url = new URL(window.location);
    url.search = '';
    window.history.replaceState({}, '', url);
    
    loadAgendas(1, { 'force_all': 'true' });
}

function limpiarFiltros() {
    console.log('🧹 Limpiando todos los filtros');
    
    const form = document.getElementById('filtrosForm');
    form.reset();
    
    document.getElementById('fecha_desde').value = '';
    document.getElementById('fecha_hasta').value = '';
    document.getElementById('estado').value = '';
    document.getElementById('modalidad').value = '';
    document.getElementById('consultorio').value = '';
    
    currentFilters = {};
    loadAgendas(1, {});
}

function refreshAgendas() {
    loadAgendas(currentPage, currentFilters, currentPerPage);
    checkPendingSync();
}

// ✅ MANEJAR FORMULARIO DE BÚSQUEDA
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
    loadAgendas(1, filters); // Volver a página 1 al filtrar
});

// ✅ FUNCIONES AUXILIARES
function getDayName(dayIndex) {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return days[dayIndex] || '';
}

function getEstadoBadge(estado) {
    const badges = {
        'ACTIVO': '<span class="badge bg-success">Activo</span>',
        'ANULADA': '<span class="badge bg-danger">Anulada</span>',
        'LLENA': '<span class="badge bg-warning">Llena</span>'
    };
    return badges[estado] || '<span class="badge bg-secondary">Desconocido</span>';
}

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

function showEmptyState() {
    document.getElementById('tablaAgendas').style.display = 'none';
    document.getElementById('estadoVacio').style.display = 'block';
    document.getElementById('totalRegistros').textContent = '0 registros';
    document.getElementById('infoRegistros').textContent = '';
    
    // Limpiar paginación
    document.getElementById('paginacionNav').innerHTML = '';
    document.getElementById('currentPageInput').value = 1;
    document.getElementById('totalPagesSpan').textContent = '1';
    updateNavigationButtons();
}

// ✅ RESTO DE FUNCIONES (sincronización, eliminación, etc.)
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
                refreshAgendas();
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
            refreshAgendas();
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
                refreshAgendas();
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

// ✅ INICIALIZAR ORDENAMIENTO AL CARGAR
document.addEventListener('DOMContentLoaded', function() {
    updateSortIcons(); // Mostrar ordenamiento inicial
});
</script>
@endpush
@endsection
