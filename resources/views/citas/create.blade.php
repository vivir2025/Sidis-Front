{{-- resources/views/citas/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Nueva Cita - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-plus text-primary me-2"></i>
                        Nueva Cita
                    </h1>
                    <p class="text-muted mb-0">Agendar una nueva cita médica</p>
                </div>
                
                <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información de la Cita
                    </h5>
                </div>
                <div class="card-body">
                    <form id="citaForm" method="POST" action="{{ route('citas.store') }}">
                        @csrf
                        
                        <!-- Paso 1: Buscar Paciente -->
                        <div class="step-section mb-4" id="step1">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user-search me-2"></i>Paso 1: Buscar Paciente
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="buscar_documento" class="form-label">
                                        Documento del Paciente <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="buscar_documento" 
                                               placeholder="Ingrese número de documento">
                                        <button type="button" class="btn btn-primary" onclick="buscarPaciente()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div id="pacienteInfo" class="alert alert-info" style="display: none;">
                                        <h6 class="alert-heading">Paciente Encontrado</h6>
                                        <div id="pacienteDetalles"></div>
                                    </div>
                                    
                                    <div id="pacienteNoEncontrado" class="alert alert-warning" style="display: none;">
                                        <h6 class="alert-heading">Paciente No Encontrado</h6>
                                        <p class="mb-0">El paciente debe estar registrado antes de agendar una cita.</p>
                                        <a href="{{ route('pacientes.create') }}" class="btn btn-sm btn-warning mt-2" target="_blank">
                                            <i class="fas fa-plus"></i> Registrar Paciente
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" id="paciente_uuid" name="paciente_uuid">
                        </div>

                        <hr>

                        <!-- Paso 2: Seleccionar Agenda -->
                        <div class="step-section mb-4" id="step2" style="display: none;">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Paso 2: Seleccionar Agenda
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filtro_fecha" class="form-label">Filtrar por Fecha</label>
                                    <input type="date" class="form-control" id="filtro_fecha" 
                                           min="{{ date('Y-m-d') }}" onchange="filtrarAgendas()">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="filtro_modalidad" class="form-label">Modalidad</label>
                                    <select class="form-select" id="filtro_modalidad" onchange="filtrarAgendas()">
                                        <option value="">Todas</option>
                                        <option value="Ambulatoria">Ambulatoria</option>
                                        <option value="Telemedicina">Telemedicina</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" onclick="cargarAgendas()">
                                            <i class="fas fa-sync-alt"></i> Actualizar Agendas
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div id="agendasDisponibles" class="row">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                    
                                    <div id="noAgendas" class="text-center py-4" style="display: none;">
                                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No hay agendas disponibles</p>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" id="agenda_uuid" name="agenda_uuid">
                        </div>

                        <hr>

                        <!-- Paso 3: Detalles de la Cita -->
                        <div class="step-section mb-4" id="step3" style="display: none;">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clipboard-list me-2"></i>Paso 3: Detalles de la Cita
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fecha" class="form-label">
                                        Fecha <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control @error('fecha') is-invalid @enderror" 
                                           id="fecha" name="fecha" 
                                           value="{{ old('fecha') }}" required readonly>
                                    @error('fecha')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="fecha_inicio" class="form-label">
                                        Hora Inicio <span class="text-danger">*</span>
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                           id="fecha_inicio" name="fecha_inicio" 
                                           value="{{ old('fecha_inicio') }}" required>
                                    @error('fecha_inicio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="fecha_final" class="form-label">
                                        Hora Fin <span class="text-danger">*</span>
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control @error('fecha_final') is-invalid @enderror" 
                                           id="fecha_final" name="fecha_final" 
                                           value="{{ old('fecha_final') }}" required readonly>
                                    @error('fecha_final')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="motivo" class="form-label">Motivo de la Consulta</label>
                                    <input type="text" 
                                           class="form-control @error('motivo') is-invalid @enderror" 
                                           id="motivo" name="motivo" 
                                           value="{{ old('motivo') }}" 
                                           placeholder="Ej: Consulta de control">
                                    @error('motivo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="patologia" class="form-label">Patología</label>
                                    <input type="text" 
                                           class="form-control @error('patologia') is-invalid @enderror" 
                                           id="patologia" name="patologia" 
                                           value="{{ old('patologia') }}" 
                                           placeholder="Ej: Hipertensión">
                                    @error('patologia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="nota" class="form-label">
                                        Notas <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('nota') is-invalid @enderror" 
                                              id="nota" name="nota" rows="3" 
                                              placeholder="Observaciones adicionales..." required>{{ old('nota') }}</textarea>
                                    @error('nota')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="btnGuardar" style="display: none;">
                                        <i class="fas fa-save"></i> Guardar Cita
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let pacienteSeleccionado = null;
let agendaSeleccionada = null;
let agendasDisponibles = [];

document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha mínima (hoy)
    document.getElementById('filtro_fecha').value = new Date().toISOString().split('T')[0];
    
    // Cargar agendas iniciales
    cargarAgendas();
    
    // Event listener para fecha_inicio
    document.getElementById('fecha_inicio').addEventListener('change', function() {
        if (agendaSeleccionada && this.value) {
            calcularFechaFin();
        }
    });
});

// ✅ BUSCAR PACIENTE
async function buscarPaciente() {
    const documento = document.getElementById('buscar_documento').value.trim();
    
    if (!documento) {
        Swal.fire('Atención', 'Ingrese un número de documento', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`/citas/buscar-paciente?documento=${documento}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success && data.data) {
            mostrarPacienteEncontrado(data.data);
            mostrarPaso(2);
        } else {
            mostrarPacienteNoEncontrado();
        }

    } catch (error) {
        console.error('Error buscando paciente:', error);
        Swal.fire('Error', 'Error buscando paciente: ' + error.message, 'error');
    }
}

function mostrarPacienteEncontrado(paciente) {
    pacienteSeleccionado = paciente;
    
    document.getElementById('paciente_uuid').value = paciente.uuid;
    
    const detalles = `
        <strong>${paciente.nombre_completo}</strong><br>
        <small>Documento: ${paciente.documento}</small><br>
        <small>Teléfono: ${paciente.telefono || 'No registrado'}</small>
    `;
    
    document.getElementById('pacienteDetalles').innerHTML = detalles;
    document.getElementById('pacienteInfo').style.display = 'block';
    document.getElementById('pacienteNoEncontrado').style.display = 'none';
}

function mostrarPacienteNoEncontrado() {
    pacienteSeleccionado = null;
    document.getElementById('paciente_uuid').value = '';
    document.getElementById('pacienteInfo').style.display = 'none';
    document.getElementById('pacienteNoEncontrado').style.display = 'block';
}

// ✅ CARGAR AGENDAS
async function cargarAgendas() {
    try {
        const filtros = {
            modalidad: document.getElementById('filtro_modalidad').value,
            fecha_desde: document.getElementById('filtro_fecha').value
        };
        
        const params = new URLSearchParams();
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) params.append(key, filtros[key]);
        });

        const response = await fetch(`/agendas/disponibles?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            agendasDisponibles = data.data;
            mostrarAgendas(agendasDisponibles);
        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error cargando agendas:', error);
        mostrarAgendas([]);
    }
}

function filtrarAgendas() {
    const fecha = document.getElementById('filtro_fecha').value;
    const modalidad = document.getElementById('filtro_modalidad').value;
    
    let agendasFiltradas = agendasDisponibles;
    
    if (fecha) {
        agendasFiltradas = agendasFiltradas.filter(agenda => agenda.fecha === fecha);
    }
    
    if (modalidad) {
        agendasFiltradas = agendasFiltradas.filter(agenda => agenda.modalidad === modalidad);
    }
    
    mostrarAgendas(agendasFiltradas);
}

function mostrarAgendas(agendas) {
    const container = document.getElementById('agendasDisponibles');
    const noAgendas = document.getElementById('noAgendas');
    
    if (!agendas || agendas.length === 0) {
        container.innerHTML = '';
        noAgendas.style.display = 'block';
        return;
    }
    
    noAgendas.style.display = 'none';
    
    let html = '';
    agendas.forEach(agenda => {
        const fecha = new Date(agenda.fecha).toLocaleDateString('es-ES');
        const cupos = agenda.cupos_disponibles || 0;
        const cuposClass = cupos > 0 ? 'text-success' : 'text-warning';
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card agenda-card ${agendaSeleccionada?.uuid === agenda.uuid ? 'border-primary' : ''}" 
                     onclick="seleccionarAgenda('${agenda.uuid}')">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">${agenda.consultorio}</h6>
                            <span class="badge ${agenda.modalidad === 'Telemedicina' ? 'bg-info' : 'bg-secondary'}">
                                ${agenda.modalidad}
                            </span>
                        </div>
                        <p class="card-text mb-1">
                            <i class="fas fa-calendar me-1"></i><strong>${fecha}</strong>
                        </p>
                        
                        <p class="card-text mb-1">
                            <i class="fas fa-clock me-1"></i>${agenda.hora_inicio} - ${agenda.hora_fin}
                        </p>
                        
                        <p class="card-text mb-1">
                            <i class="fas fa-tag me-1"></i>${agenda.etiqueta}
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Intervalo: ${agenda.intervalo}min</small>
                            <span class="${cuposClass} fw-semibold">
                                <i class="fas fa-users me-1"></i>${cupos} cupos
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// ✅ SELECCIONAR AGENDA
function seleccionarAgenda(agendaUuid) {
    const agenda = agendasDisponibles.find(a => a.uuid === agendaUuid);
    
    if (!agenda) {
        Swal.fire('Error', 'Agenda no encontrada', 'error');
        return;
    }
    
    if (agenda.cupos_disponibles <= 0) {
        Swal.fire('Atención', 'Esta agenda no tiene cupos disponibles', 'warning');
        return;
    }
    
    agendaSeleccionada = agenda;
    document.getElementById('agenda_uuid').value = agenda.uuid;
    
    // Actualizar estilos visuales
    document.querySelectorAll('.agenda-card').forEach(card => {
        card.classList.remove('border-primary');
    });
    
    event.currentTarget.classList.add('border-primary');
    
    // Llenar datos automáticamente
    document.getElementById('fecha').value = agenda.fecha;
    
    // Generar horarios disponibles
    generarHorariosDisponibles(agenda);
    
    mostrarPaso(3);
}

// ✅ GENERAR HORARIOS DISPONIBLES
function generarHorariosDisponibles(agenda) {
    // Por simplicidad, tomamos el primer horario disponible
    const fechaBase = agenda.fecha;
    const horaInicio = agenda.hora_inicio;
    
    // Crear datetime para fecha_inicio
    const fechaInicio = `${fechaBase}T${horaInicio}`;
    document.getElementById('fecha_inicio').value = fechaInicio;
    
    calcularFechaFin();
}

function calcularFechaFin() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    
    if (fechaInicio && agendaSeleccionada) {
        const inicio = new Date(fechaInicio);
        const intervalo = parseInt(agendaSeleccionada.intervalo);
        
        const fin = new Date(inicio.getTime() + (intervalo * 60000)); // Agregar minutos
        
        const fechaFin = fin.toISOString().slice(0, 16); // Format: YYYY-MM-DDTHH:MM
        document.getElementById('fecha_final').value = fechaFin;
    }
}

// ✅ NAVEGACIÓN ENTRE PASOS
function mostrarPaso(paso) {
    // Ocultar todos los pasos
    document.querySelectorAll('.step-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Mostrar pasos hasta el actual
    for (let i = 1; i <= paso; i++) {
        const stepElement = document.getElementById(`step${i}`);
        if (stepElement) {
            stepElement.style.display = 'block';
        }
    }
    
    // Mostrar botón guardar solo en el paso 3
    const btnGuardar = document.getElementById('btnGuardar');
    btnGuardar.style.display = paso >= 3 ? 'block' : 'none';
}

// ✅ SUBMIT DEL FORMULARIO
document.getElementById('citaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!pacienteSeleccionado) {
        Swal.fire('Atención', 'Debe seleccionar un paciente', 'warning');
        return;
    }
    
    if (!agendaSeleccionada) {
        Swal.fire('Atención', 'Debe seleccionar una agenda', 'warning');
        return;
    }
    
    const btnGuardar = document.getElementById('btnGuardar');
    const originalText = btnGuardar.innerHTML;
    
    try {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        
        const formData = new FormData(this);
        
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: data.message || 'Cita creada exitosamente',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    window.location.href = '{{ route("citas.index") }}';
                }
            });
        } else {
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        }
        
    } catch (error) {
        console.error('Error guardando cita:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error guardando cita: ' + error.message,
            icon: 'error'
        });
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = originalText;
    }
});

function showValidationErrors(errors) {
    // Limpiar errores previos
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });
    
    // Mostrar nuevos errores
    for (const [field, messages] of Object.entries(errors)) {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
            input.parentNode.appendChild(feedback);
        }
    }
    
    // Scroll al primer error
    const firstError = document.querySelector('.is-invalid');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ✅ ESTILOS PARA CARDS SELECCIONABLES
const style = document.createElement('style');
style.textContent = `
    .agenda-card {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .agenda-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .agenda-card.border-primary {
        border-width: 2px !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection

