{{-- resources/views/historia-clinica/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Historial de Historias Clínicas')

@section('content')
<div class="container-fluid py-4">

    {{-- ✅ FILTROS DE BÚSQUEDA --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="mb-0">
                        <i class="fas fa-file-medical"></i> Historial de Historias Clínicas
                    </h4>
                    <div> Consulta y gestiona </div>
                    
                </div>
                <div class="card-body">
                    <form id="formFiltros">
                        <div class="row g-3">
                            {{-- Documento --}}
                            <div class="col-md-3">
                                <label class="form-label">Documento Paciente</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="filtroDocumento"
                                       name="documento"
                                       placeholder="Ej: 1234567890">
                            </div>

                            {{-- Botones --}}
                            <div class="col-md-9 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ TABLA DE RESULTADOS --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    {{-- Loading --}}
                    <div id="loadingHistorias" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando historias clínicas...</p>
                    </div>

                    {{-- Tabla --}}
                    <div id="tablaHistoriasContainer">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaHistorias">
                                <thead class="table-light">
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Tipo</th>
                                        <th>Especialidad</th>
                                        <th>Profesional</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyHistorias">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-search fa-3x mb-3 d-block"></i>
                                            Utiliza los filtros para buscar historias clínicas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        <div id="paginacionContainer" class="d-flex justify-content-between align-items-center mt-3" style="display: none !important;">
                            <div id="paginacionInfo" class="text-muted"></div>
                            <nav>
                                <ul class="pagination mb-0" id="paginacionLinks"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentFilters = {};

    // ✅ BUSCAR HISTORIAS
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        
        currentFilters = {
            documento: $('#filtroDocumento').val(),
            fecha_desde: $('#filtroFechaDesde').val(),
            fecha_hasta: $('#filtroFechaHasta').val(),
            especialidad: $('#filtroEspecialidad').val(),
            tipo_consulta: $('#filtroTipoConsulta').val()
        };
        
        currentPage = 1;
        cargarHistorias();
    });

    // ✅ LIMPIAR FILTROS
    $('#btnLimpiarFiltros').on('click', function() {
        $('#formFiltros')[0].reset();
        currentFilters = {};
        $('#tbodyHistorias').html(`
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3 d-block"></i>
                    Utiliza los filtros para buscar historias clínicas
                </td>
            </tr>
        `);
        $('#paginacionContainer').hide();
    });

    // ✅ FUNCIÓN PARA CARGAR HISTORIAS
    function cargarHistorias() {
        $('#loadingHistorias').show();
        $('#tablaHistoriasContainer').hide();

        $.ajax({
            url: '{{ route("historia-clinica.index") }}',
            method: 'GET',
            data: { ...currentFilters, page: currentPage },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    renderHistorias(response.data.data);
                    renderPaginacion(response.data);
                } else {
                    mostrarError('Error cargando historias');
                }
            },
            error: function() {
                mostrarError('Error de conexión');
            },
            complete: function() {
                $('#loadingHistorias').hide();
                $('#tablaHistoriasContainer').show();
            }
        });
    }


    // ✅ FUNCIÓN PARA RENDERIZAR HISTORIAS
    function renderHistorias(historias) {
        const tbody = $('#tbodyHistorias');
        tbody.empty();

        if (!historias || historias.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No se encontraron historias clínicas con los filtros aplicados
                    </td>
                </tr>
            `);
            return;
        }

        historias.forEach((historia) => {
            if (!historia.cita || !historia.cita.paciente) {
                return;
            }

            const paciente = historia.cita.paciente;
            const agenda = historia.cita.agenda || {};
            const profesional = agenda.usuario_medico || { nombre_completo: 'N/A' };
            
            // ✅ FORMATEAR FECHA SIN USAR new Date()
            let fecha = 'N/A';
            if (historia.cita.fecha) {
                const fechaCompleta = String(historia.cita.fecha);
                const fechaSolo = fechaCompleta.split(' ')[0];
                const partes = fechaSolo.split('-');
                
                if (partes.length === 3) {
                    const [year, month, day] = partes;
                    fecha = `${day}/${month}/${year}`;
                }
            }
            
            // ✅ MOSTRAR RANGO DE HORAS
            let horario = '';
            if (historia.cita.hora_inicio && historia.cita.hora_final) {
                horario = `<br><small class="text-muted"><i class="far fa-clock"></i> ${historia.cita.hora_inicio} - ${historia.cita.hora_final}</small>`;
            } else if (historia.cita.hora_inicio) {
                horario = `<br><small class="text-muted"><i class="far fa-clock"></i> ${historia.cita.hora_inicio}</small>`;
            }
            
            const tipoBadge = historia.tipo_consulta === 'PRIMERA VEZ' ? 'primary' : 'info';
            
            // ✅ FORMATEAR FECHA DE REGISTRO
            let fechaRegistro = 'N/A';
            if (historia.created_at) {
                const fechaCompleta = String(historia.created_at);
                const partes = fechaCompleta.split(' ');
                
                if (partes.length >= 2) {
                    const fechaParte = partes[0];
                    const horaParte = partes[1];
                    const [year, month, day] = fechaParte.split('-');
                    const [hora, minuto] = horaParte.split(':');
                    fechaRegistro = `${day}/${month}/${year} ${hora}:${minuto}`;
                }
            }
            
            tbody.append(`
                <tr>
                    <td>
                        <div>
                            <span class="badge bg-secondary">${paciente.tipo_documento || 'CC'}</span>
                            ${paciente.documento || 'N/A'}
                        </div>
                        <strong>${paciente.nombre_completo || 'N/A'}</strong>
                    </td>
                    <td>
                        <span class="badge bg-${tipoBadge}">${historia.tipo_consulta || 'N/A'}</span>
                    </td>
                    <td>${historia.especialidad || 'N/A'}</td>
                    <td>${profesional.nombre_completo}</td>
                    <td>
                        <div><strong>${fecha}</strong></div>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ url('historia-clinica') }}/${historia.uuid}" 
                            class="btn btn-outline-primary" 
                            title="Ver Historia">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-outline-secondary" 
                                    onclick="imprimirHistoria('${historia.uuid}')"
                                    title="Imprimir">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        });
    }



   // ✅ RENDERIZAR PAGINACIÓN
        function renderPaginacion(paginationData) {
            // ✅ CORRECCIÓN: Acceder correctamente a los datos de paginación
            if (!paginationData || paginationData.last_page <= 1) {
                $('#paginacionContainer').hide();
                return;
            }

            $('#paginacionInfo').text(`Mostrando ${paginationData.from} a ${paginationData.to} de ${paginationData.total} registros`);
            
            const paginacionLinks = $('#paginacionLinks');
            paginacionLinks.empty();

            // Anterior
            paginacionLinks.append(`
                <li class="page-item ${paginationData.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${paginationData.current_page - 1}">Anterior</a>
                </li>
            `);

            // Páginas
            for (let i = 1; i <= paginationData.last_page; i++) {
                paginacionLinks.append(`
                    <li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            // Siguiente
            paginacionLinks.append(`
                <li class="page-item ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${paginationData.current_page + 1}">Siguiente</a>
                </li>
            `);

            $('#paginacionContainer').show();

            // Event listeners para paginación
            $('.page-link').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page !== currentPage) {
                    currentPage = page;
                    cargarHistorias();
                }
            });
        }

    // ✅ FUNCIÓN PARA IMPRIMIR
    window.imprimirHistoria = function(uuid) {
        window.open(`{{ url('historia-clinica') }}/${uuid}?print=1`, '_blank');
    };

    // ✅ FUNCIÓN PARA MOSTRAR ERRORES
    function mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonColor: '#3085d6'
        });
    }
});
</script>
@endpush
@endsection
