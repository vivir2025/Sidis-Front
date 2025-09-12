// ✅ JAVASCRIPT INTEGRADO CON TUS RUTAS EXISTENTES
class CronogramaManager {
    constructor() {
        this.fechaActual = document.getElementById('fecha-selector')?.value || new Date().toISOString().split('T')[0];
        this.isLoading = false;
        this.initEventListeners();
    }

    /**
     * ✅ INICIALIZAR EVENT LISTENERS
     */
    initEventListeners() {
        // Selector de fecha
        const fechaSelector = document.getElementById('fecha-selector');
        if (fechaSelector) {
            fechaSelector.addEventListener('change', (e) => {
                this.cambiarFecha(e.target.value);
            });
        }

        // Botón actualizar
        const btnActualizar = document.getElementById('btn-actualizar');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', () => {
                this.actualizarCronograma();
            });
        }

        // Botones de estado de citas
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-estado-cita')) {
                const citaUuid = e.target.dataset.citaUuid;
                const nuevoEstado = e.target.dataset.estado;
                this.cambiarEstadoCita(citaUuid, nuevoEstado);
            }

            if (e.target.classList.contains('btn-ver-citas-agenda')) {
                const agendaUuid = e.target.dataset.agendaUuid;
                this.verCitasAgenda(agendaUuid);
            }

            if (e.target.classList.contains('btn-detalle-cita')) {
                const citaUuid = e.target.dataset.citaUuid;
                this.verDetalleCita(citaUuid);
            }
        });

        // Auto-refresh cada 5 minutos
        setInterval(() => {
            this.actualizarCronogramaAuto();
        }, 300000); // 5 minutos
    }

    /**
     * ✅ CAMBIAR FECHA (usando tu ruta existente)
     */
    cambiarFecha(fecha) {
        if (this.isLoading) return;
        
        this.fechaActual = fecha;
        window.location.href = `/cronograma?fecha=${fecha}`;
    }

    /**
     * ✅ ACTUALIZAR CRONOGRAMA (usando tu nueva ruta)
     */
    async actualizarCronograma() {
        if (this.isLoading) return;

        try {
            this.setLoading(true);
            
            const response = await fetch(`/cronograma/data/${this.fechaActual}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            const result = await response.json();

            if (result.success) {
                this.actualizarContenidoCronograma(result.data);
                this.mostrarMensaje('Cronograma actualizado correctamente', 'success');
            } else {
                throw new Error(result.error || 'Error desconocido');
            }

        } catch (error) {
            console.error('Error actualizando cronograma:', error);
            this.mostrarMensaje('Error actualizando cronograma', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * ✅ ACTUALIZACIÓN AUTOMÁTICA SILENCIOSA
     */
    async actualizarCronogramaAuto() {
        if (this.isLoading) return;

        try {
            const response = await fetch(`/cronograma/refresh?fecha=${this.fechaActual}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.actualizarContenidoCronograma(result.data);
                console.log('✅ Cronograma actualizado automáticamente');
            }

        } catch (error) {
            console.warn('⚠️ Error en actualización automática:', error);
        }
    }

    /**
     * ✅ VER DETALLE DE CITA (usando tu ruta existente)
     */
    async verDetalleCita(citaUuid) {
        try {
            this.setLoading(true);

            const response = await fetch(`/cronograma/cita/${citaUuid}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarModalDetalleCita(result.data);
            } else {
                throw new Error(result.error || 'Error obteniendo detalle');
            }

        } catch (error) {
            console.error('Error obteniendo detalle de cita:', error);
            this.mostrarMensaje('Error obteniendo detalle de cita', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * ✅ CAMBIAR ESTADO DE CITA (usando tu ruta existente)
     */
    async cambiarEstadoCita(citaUuid, nuevoEstado) {
        try {
            this.setLoading(true);

            const response = await fetch(`/cronograma/cita/${citaUuid}/estado`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    estado: nuevoEstado
                })
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarMensaje(`Estado cambiado a ${nuevoEstado}`, 'success');
                // Actualizar la vista sin recargar
                this.actualizarEstadoCitaEnVista(citaUuid, nuevoEstado);
                // Actualizar estadísticas
                this.actualizarCronograma();
            } else {
                throw new Error(result.error || 'Error cambiando estado');
            }

        } catch (error) {
            console.error('Error cambiando estado de cita:', error);
            this.mostrarMensaje('Error cambiando estado de cita', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * ✅ VER CITAS DE AGENDA (usando tu nueva ruta)
     */
    async verCitasAgenda(agendaUuid) {
        try {
            this.setLoading(true);

            const response = await fetch(`/cronograma/agenda/${agendaUuid}/citas?fecha=${this.fechaActual}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarModalCitasAgenda(result.data, agendaUuid);
            } else {
                throw new Error(result.error || 'Error obteniendo citas');
            }

        } catch (error) {
            console.error('Error obteniendo citas de agenda:', error);
            this.mostrarMensaje('Error obteniendo citas de agenda', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * ✅ ACTUALIZAR CONTENIDO DEL CRONOGRAMA
     */
    actualizarContenidoCronograma(cronogramaData) {
        try {
            // Actualizar estadísticas globales
            this.actualizarEstadisticasGlobales(cronogramaData.estadisticas);

            // Actualizar agendas
            this.actualizarAgendasEnVista(cronogramaData.agendas);

            // Actualizar resumen de citas
            this.actualizarResumenCitas(cronogramaData.resumen_citas);

            // Actualizar timestamp
            const timestampElement = document.getElementById('ultima-actualizacion');
            if (timestampElement) {
                timestampElement.textContent = new Date().toLocaleTimeString();
            }

        } catch (error) {
            console.error('Error actualizando contenido:', error);
        }
    }

    /**
     * ✅ ACTUALIZAR ESTADÍSTICAS GLOBALES
     */
    actualizarEstadisticasGlobales(estadisticas) {
        const elementos = {
            'total-agendas': estadisticas.total_agendas,
            'total-cupos': estadisticas.total_cupos,
            'total-citas': estadisticas.total_citas,
            'cupos-disponibles': estadisticas.cupos_disponibles,
            'porcentaje-ocupacion': estadisticas.porcentaje_ocupacion_global + '%'
        };

        Object.entries(elementos).forEach(([id, valor]) => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.textContent = valor;
            }
        });

        // Actualizar estadísticas por estado
        Object.entries(estadisticas.por_estado).forEach(([estado, cantidad]) => {
            const elemento = document.getElementById(`citas-${estado.toLowerCase()}`);
            if (elemento) {
                elemento.textContent = cantidad;
            }
        });
    }

    /**
     * ✅ ACTUALIZAR AGENDAS EN VISTA
     */
    actualizarAgendasEnVista(agendas) {
        const contenedor = document.getElementById('cronograma-content');
        if (!contenedor) return;

        // Aquí puedes implementar la lógica para actualizar las tarjetas de agendas
        // sin recargar toda la página
        agendas.forEach(agenda => {
            const agendaCard = document.querySelector(`[data-agenda-uuid="${agenda.uuid}"]`);
            if (agendaCard) {
                this.actualizarTarjetaAgenda(agendaCard, agenda);
            }
        });
    }

    /**
     * ✅ ACTUALIZAR TARJETA DE AGENDA
     */
    actualizarTarjetaAgenda(tarjeta, agenda) {
        try {
            // Actualizar contadores
            const totalCitas = tarjeta.querySelector('.total-citas');
            if (totalCitas) totalCitas.textContent = agenda.total_citas;

            const cuposDisponibles = tarjeta.querySelector('.cupos-disponibles');
            if (cuposDisponibles) cuposDisponibles.textContent = agenda.cupos_disponibles;

            const porcentajeOcupacion = tarjeta.querySelector('.porcentaje-ocupacion');
            if (porcentajeOcupacion) porcentajeOcupacion.textContent = agenda.porcentaje_ocupacion + '%';

            // Actualizar barra de progreso
            const barraProgreso = tarjeta.querySelector('.progress-bar');
            if (barraProgreso) {
                barraProgreso.style.width = agenda.porcentaje_ocupacion + '%';
                barraProgreso.setAttribute('aria-valuenow', agenda.porcentaje_ocupacion);
            }

        } catch (error) {
            console.error('Error actualizando tarjeta de agenda:', error);
        }
    }

    /**
     * ✅ MOSTRAR MODAL DE DETALLE DE CITA
     */
    mostrarModalDetalleCita(cita) {
        const modal = document.getElementById('modal-detalle-cita');
        if (!modal) return;

        // Llenar datos del modal
        const elementos = {
            'modal-cita-paciente': cita.paciente_nombre || 'Sin nombre',
            'modal-cita-documento': cita.paciente_documento || 'Sin documento',
            'modal-cita-telefono': cita.paciente_telefono || 'Sin teléfono',
            'modal-cita-fecha': cita.fecha_formateada || '',
            'modal-cita-hora': `${cita.hora_inicio || ''} - ${cita.hora_final || ''}`,
            'modal-cita-estado': cita.estado_info?.label || cita.estado,
            'modal-cita-observaciones': cita.observaciones || 'Sin observaciones'
        };

        Object.entries(elementos).forEach(([id, valor]) => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.textContent = valor;
            }
        });

        // Mostrar modal (Bootstrap 5)
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    /**
     * ✅ MOSTRAR MODAL DE CITAS DE AGENDA
     */
    mostrarModalCitasAgenda(citas, agendaUuid) {
        const modal = document.getElementById('modal-citas-agenda');
        if (!modal) return;

        const tbody = modal.querySelector('#tabla-citas-agenda tbody');
        if (!tbody) return;

        // Limpiar tabla
        tbody.innerHTML = '';

        // Llenar tabla con citas
        citas.forEach(cita => {
            const fila = this.crearFilaCita(cita);
            tbody.appendChild(fila);
        });

        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    /**
     * ✅ CREAR FILA DE CITA PARA TABLA
     */
    crearFilaCita(cita) {
        const fila = document.createElement('tr');
        
        fila.innerHTML = `
            <td>${cita.hora_inicio || ''}</td>
            <td>${cita.paciente_nombre || 'Sin nombre'}</td>
            <td>${cita.paciente_documento || ''}</td>
            <td>
                <span class="badge bg-${cita.estado_info?.color || 'primary'}">
                    <i class="fas fa-${cita.estado_info?.icon || 'calendar'} me-1"></i>
                    ${cita.estado_info?.label || cita.estado}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary btn-detalle-cita" 
                            data-cita-uuid="${cita.uuid}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item btn-estado-cita" 
                                   data-cita-uuid="${cita.uuid}" 
                                   data-estado="EN_ATENCION">
                                <i class="fas fa-clock text-warning me-2"></i>En Atención
                            </a></li>
                            <li><a class="dropdown-item btn-estado-cita" 
                                   data-cita-uuid="${cita.uuid}" 
                                   data-estado="ATENDIDA">
                                <i class="fas fa-check text-success me-2"></i>Atendida
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item btn-estado-cita" 
                                   data-cita-uuid="${cita.uuid}" 
                                   data-estado="CANCELADA">
                                <i class="fas fa-times text-danger me-2"></i>Cancelar
                            </a></li>
                        </ul>
                    </div>
                </div>
            </td>
        `;

        return fila;
    }

    /**
     * ✅ UTILIDADES
     */
    setLoading(loading) {
        this.isLoading = loading;
        const btnActualizar = document.getElementById('btn-actualizar');
        
        if (btnActualizar) {
            if (loading) {
                btnActualizar.disabled = true;
                btnActualizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            } else {
                btnActualizar.disabled = false;
                btnActualizar.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
            }
        }
    }

    mostrarMensaje(mensaje, tipo = 'info') {
        // Implementar sistema de notificaciones (toast, alert, etc.)
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[tipo] || 'alert-info';

        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alert);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    actualizarEstadoCitaEnVista(citaUuid, nuevoEstado) {
        const citaElement = document.querySelector(`[data-cita-uuid="${citaUuid}"]`);
        if (citaElement) {
            const badge = citaElement.querySelector('.badge');
            if (badge) {
                const estadoInfo = this.getEstadoInfo(nuevoEstado);
                badge.className = `badge bg-${estadoInfo.color}`;
                badge.innerHTML = `<i class="fas fa-${estadoInfo.icon} me-1"></i>${estadoInfo.label}`;
            }
        }
    }

    getEstadoInfo(estado) {
        const estados = {
            'PROGRAMADA': { label: 'Programada', color: 'primary', icon: 'calendar' },
            'EN_ATENCION': { label: 'En Atención', color: 'warning', icon: 'clock' },
            'ATENDIDA': { label: 'Atendida', color: 'success', icon: 'check' },
            'CANCELADA': { label: 'Cancelada', color: 'danger', icon: 'x' },
            'NO_ASISTIO': { label: 'No Asistió', color: 'secondary', icon: 'user-x' }
        };

        return estados[estado] || estados['PROGRAMADA'];
    }
}

// ✅ INICIALIZAR CUANDO EL DOM ESTÉ LISTO
document.addEventListener('DOMContentLoaded', function() {
    window.cronogramaManager = new CronogramaManager();
    console.log('✅ CronogramaManager inicializado');
});
