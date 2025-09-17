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
                    
                    {{-- ✅ NUEVO: Botón de sincronización de citas --}}
                    <button type="button" class="btn btn-info me-2" onclick="sincronizarCitas()" id="btnSincronizarCitas">
                        <i class="fas fa-sync-alt"></i> Sincronizar Citas
                    </button>
                    
                    <a href="{{ route('citas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Cita
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ NUEVO: Panel de estado de sincronización --}}
    <div class="row mb-4" id="panelSincronizacionCitas" style="display: none;">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-3" role="status" id="spinnerSyncCitas">
                        <span class="visually-hidden">Sincronizando...</span>
                    </div>
                    <div>
                        <strong>Sincronizando citas...</strong>
                        <div id="estadoSincronizacionCitas" class="small text-muted">
                            Preparando sincronización...
                        </div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar" role="progressbar" id="progressSyncCitas" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resto del contenido existente... -->
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
                <button type="button" class="btn btn-sm btn-warning" onclick="mostrarCitasPendientes()">
                    <i class="fas fa-clock"></i> Pendientes
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
                            <th>Sync</th> {{-- ✅ COLUMNA DE SYNC --}}
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
let isSyncingCitas = false; // ✅ NUEVO

document.addEventListener('DOMContentLoaded', function() {
    cargarCitas();
    verificarCitasPendientes(); // ✅ NUEVO
});

// ✅ CARGAR CITAS (ACTUALIZADA PARA MOSTRAR ESTADO DE SYNC)
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

// ✅ MOSTRAR CITAS EN TABLA (HORAS COMPACTAS)
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
        // ✅ FORMATEAR FECHA CORRECTAMENTE SIN TIMEZONE ISSUES
        const fecha = formatearFecha(cita.fecha);
        const horaInicio = formatearHora(cita.fecha_inicio);
        const horaFin = formatearHora(cita.fecha_final);
        
        const estadoBadge = getEstadoBadge(cita.estado);
        const modalidadBadge = getModalidadBadge(cita.agenda?.modalidad);
        const syncBadge = getSyncBadge(cita.sync_status || 'synced', cita.offline);
        
        html += `
            <tr>
                <td>${fecha}</td>
                <td>
                    <div class="text-center">
                        <div class="fw-bold">${horaInicio}</div>
                        <small class="text-muted">${horaFin}</small>
                    </div>
                </td> <!-- ✅ HORAS EN FORMATO VERTICAL COMPACTO -->
                <td>${cita.paciente?.nombre_completo || (cita.paciente?.nombres + ' ' + cita.paciente?.apellidos) || 'No disponible'}</td>
                <td>${cita.paciente?.numero_documento || cita.paciente?.documento || 'No disponible'}</td>
                <td>${cita.agenda?.consultorio || 'No disponible'}</td>
                <td>${modalidadBadge}</td>
                <td>${estadoBadge}</td>
                <td>${syncBadge}</td>
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
                       
                       
                        <div class="btn-group">
                           
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


// ✅ NUEVA FUNCIÓN: Formatear fecha sin problemas de timezone
function formatearFecha(fechaString) {
    if (!fechaString) return 'No disponible';
    
    try {
        // Si viene con timestamp, extraer solo la fecha
        if (fechaString.includes('T')) {
            fechaString = fechaString.split('T')[0];
        }
        
        // Parsear manualmente para evitar timezone issues
        const partes = fechaString.split('-');
        if (partes.length === 3) {
            const año = parseInt(partes[0]);
            const mes = parseInt(partes[1]);
            const dia = parseInt(partes[2]);
            
            // Formatear como dd/mm/yyyy
            return `${dia.toString().padStart(2, '0')}/${mes.toString().padStart(2, '0')}/${año}`;
        }
        
        return fechaString;
    } catch (error) {
        console.error('Error formateando fecha:', error, fechaString);
        return fechaString;
    }
}

// ✅ FUNCIÓN CORREGIDA: Formatear hora (solo extraer HH:MM)
function formatearHora(fechaHoraString) {
    if (!fechaHoraString) return '00:00';
    
    try {
        let horaString = fechaHoraString;
        
        // Si viene con fecha completa como "2025-09-12 10:48", extraer solo la hora
        if (fechaHoraString.includes(' ')) {
            const partes = fechaHoraString.split(' ');
            horaString = partes[1]; // Tomar la segunda parte (la hora)
        }
        
        // Si viene con fecha completa como "2025-09-12T10:48:00", extraer solo la hora
        if (fechaHoraString.includes('T')) {
            horaString = fechaHoraString.split('T')[1];
        }
        
        // Si viene con segundos, extraer solo HH:MM
        if (horaString && horaString.includes(':')) {
            const partes = horaString.split(':');
            if (partes.length >= 2) {
                return `${partes[0].padStart(2, '0')}:${partes[1].padStart(2, '0')}`;
            }
        }
        
        return horaString || '00:00';
    } catch (error) {
        console.error('Error formateando hora:', error, fechaHoraString);
        return '00:00';
    }
}


// ✅ NUEVA FUNCIÓN: Badge de estado de sincronización
function getSyncBadge(syncStatus, isOffline) {
    if (isOffline || syncStatus === 'pending') {
        return '<span class="badge bg-warning text-dark" title="Pendiente de sincronización"><i class="fas fa-clock"></i> Pendiente</span>';
    } else if (syncStatus === 'synced') {
        return '<span class="badge bg-success" title="Sincronizado"><i class="fas fa-check"></i> Sync</span>';
    } else if (syncStatus === 'error') {
        return '<span class="badge bg-danger" title="Error de sincronización"><i class="fas fa-exclamation-triangle"></i> Error</span>';
    }
    return '<span class="badge bg-secondary" title="Estado desconocido"><i class="fas fa-question"></i> N/A</span>';
}

// ✅ NUEVA FUNCIÓN: Verificar citas pendientes
async function verificarCitasPendientes() {
    try {
        const response = await fetch('/citas/pendientes-sync', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.pending_count > 0) {
            const btnSincronizar = document.getElementById('btnSincronizarCitas');
            btnSincronizar.innerHTML = `
                <i class="fas fa-sync-alt"></i> 
                Sincronizar Citas 
                <span class="badge bg-warning text-dark ms-1">${data.pending_count}</span>
            `;
            btnSincronizar.classList.add('btn-warning');
            btnSincronizar.classList.remove('btn-info');
        }
        
    } catch (error) {
        console.error('Error verificando citas pendientes:', error);
    }
}

// ✅ NUEVA FUNCIÓN: Sincronizar citas
async function sincronizarCitas() {
    if (isSyncingCitas) {
        Swal.fire({
            title: 'Sincronización en progreso',
            text: 'Ya hay una sincronización de citas en curso, por favor espera.',
            icon: 'info'
        });
        return;
    }

    try {
        // Confirmar sincronización
        const result = await Swal.fire({
            title: '¿Sincronizar citas?',
            text: 'Se sincronizarán todas las citas pendientes con el servidor.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, sincronizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745'
        });

        if (!result.isConfirmed) return;

        isSyncingCitas = true;
        mostrarPanelSincronizacionCitas(true);
        actualizarEstadoSincronizacionCitas('Iniciando sincronización...', 10);

        // Verificar conexión
        actualizarEstadoSincronizacionCitas('Verificando conexión...', 20);
        const healthResponse = await fetch('/api/health');
        
        if (!healthResponse.ok) {
            throw new Error('Sin conexión al servidor');
        }

        // Obtener citas pendientes
        actualizarEstadoSincronizacionCitas('Obteniendo citas pendientes...', 30);
        const pendingResponse = await fetch('/citas/pendientes-sync', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const pendingData = await pendingResponse.json();
        
        if (!pendingData.success) {
            throw new Error(pendingData.error || 'Error obteniendo citas pendientes');
        }

        if (pendingData.pending_count === 0) {
            mostrarPanelSincronizacionCitas(false);
            Swal.fire({
                title: 'Sin citas pendientes',
                text: 'No hay citas pendientes de sincronización.',
                icon: 'info'
            });
            return;
        }

        // Sincronizar
        actualizarEstadoSincronizacionCitas(`Sincronizando ${pendingData.pending_count} citas...`, 50);
        
        const syncResponse = await fetch('/citas/sincronizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const syncData = await syncResponse.json();
        
        actualizarEstadoSincronizacionCitas('Procesando resultados...', 80);

        if (syncData.success) {
            actualizarEstadoSincronizacionCitas('Sincronización completada', 100);
            
            setTimeout(() => {
                mostrarPanelSincronizacionCitas(false);
                
                Swal.fire({
                    title: '¡Sincronización completada!',
                    html: `
                        <div class="text-start">
                            <p><strong>Resultados:</strong></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Sincronizadas: ${syncData.synced_count || 0}</li>
                                <li><i class="fas fa-times text-danger me-2"></i>Errores: ${syncData.failed_count || 0}</li>
                            </ul>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Recargar tabla y verificar pendientes
                    const filtros = obtenerFiltrosActuales();
                    cargarCitas(currentPage, filtros);
                    verificarCitasPendientes();
                });
            }, 1000);

        } else {
            throw new Error(syncData.error || 'Error en la sincronización');
        }

    } catch (error) {
        console.error('Error sincronizando citas:', error);
        mostrarPanelSincronizacionCitas(false);
        
        Swal.fire({
            title: 'Error de sincronización',
            text: error.message,
            icon: 'error'
        });
    } finally {
        isSyncingCitas = false;
    }
}

// ✅ NUEVAS FUNCIONES: Panel de sincronización
function mostrarPanelSincronizacionCitas(show) {
    const panel = document.getElementById('panelSincronizacionCitas');
    const btnSincronizar = document.getElementById('btnSincronizarCitas');
    
    if (show) {
        panel.style.display = 'block';
        btnSincronizar.disabled = true;
        btnSincronizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
    } else {
        panel.style.display = 'none';
        btnSincronizar.disabled = false;
        btnSincronizar.innerHTML = '<i class="fas fa-sync-alt"></i> Sincronizar Citas';
        btnSincronizar.classList.remove('btn-warning');
        btnSincronizar.classList.add('btn-info');
    }
}

function actualizarEstadoSincronizacionCitas(mensaje, progreso) {
    const estadoElement = document.getElementById('estadoSincronizacionCitas');
    const progressElement = document.getElementById('progressSyncCitas');
    
    if (estadoElement) {
        estadoElement.textContent = mensaje;
    }
    
    if (progressElement) {
        progressElement.style.width = progreso + '%';
    }
}

// ✅ NUEVA FUNCIÓN: Mostrar citas pendientes
async function mostrarCitasPendientes() {
    try {
        document.getElementById('filtro_fecha').value = '';
        document.getElementById('filtro_estado').value = '';
        document.getElementById('filtro_documento').value = '';
        
        // Aplicar filtro especial para pendientes
        const filtros = { sync_status: 'pending' };
        cargarCitas(1, filtros);
        
        Swal.fire({
            title: 'Citas Pendientes',
            text: 'Mostrando solo citas pendientes de sincronización',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('Error mostrando citas pendientes:', error);
    }
}

// ✅ RESTO DE FUNCIONES EXISTENTES (sin cambios)
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

// ✅ PAGINACIÓN (sin cambios)
function actualizarPaginacion(meta) {
    if (!meta) return;
    
    const infoPaginacion = document.getElementById('infoPaginacion');
    const paginacionLinks = document.getElementById('paginacionLinks');
    
    const desde = meta.from || 0;
    const hasta = meta.to || 0;
    const total = meta.total || 0;
    
    infoPaginacion.innerHTML = `Mostrando ${desde} a ${hasta} de ${total} resultados`;
    
    totalPages = meta.last_page || 1;
    const currentPageNum = meta.current_page || 1;
    
    let linksHtml = '';
    
    if (currentPageNum > 1) {
        linksHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(${currentPageNum - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
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

// ✅ FILTROS (sin cambios)
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

// ✅ ACCIONES (sin cambios)
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
