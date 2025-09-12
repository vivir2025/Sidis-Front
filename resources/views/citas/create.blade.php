{{-- resources/views/citas/create.blade.php --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

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

    <!-- Indicador de Pasos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-center">
                        <div class="step-indicator">
                            <div class="step active" id="indicator-1">
                                <div class="step-number">1</div>
                                <div class="step-label">Seleccionar Agenda</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" id="indicator-2">
                                <div class="step-number">2</div>
                                <div class="step-label">Elegir Horario</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" id="indicator-3">
                                <div class="step-number">3</div>
                                <div class="step-label">Buscar Paciente</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" id="indicator-4">
                                <div class="step-number">4</div>
                                <div class="step-label">Detalles de Cita</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <form id="citaForm" method="POST" action="{{ route('citas.store') }}">
                        @csrf
                        
                        <!-- Paso 1: Seleccionar Agenda -->
                        <div class="step-section" id="step1">
                            <h6 class="text-primary mb-4">
                                <i class="fas fa-calendar-alt me-2"></i>Paso 1: Seleccionar Agenda
                            </h6>
                            
                            <!-- Filtros de Agenda -->
                            <div class="row g-3 mb-4">
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
                                            <i class="fas fa-sync-alt"></i> Actualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lista de Agendas -->
                            <div class="row" id="agendasDisponibles">
                                <!-- Se llena dinámicamente -->
                            </div>
                            
                            <div id="noAgendas" class="text-center py-5" style="display: none;">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay agendas disponibles</h5>
                                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                            </div>
                            
                            <input type="hidden" id="agenda_uuid" name="agenda_uuid">
                        </div>

                        <!-- Paso 2: Seleccionar Horario -->
                        <div class="step-section" id="step2" style="display: none;">
                            <h6 class="text-primary mb-4">
                                <i class="fas fa-clock me-2"></i>Paso 2: Seleccionar Horario
                            </h6>
                            
                            <!-- Info de Agenda Seleccionada -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div id="agendaSeleccionadaInfo" class="alert alert-info">
                                        <!-- Info de agenda seleccionada -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Horarios Disponibles -->
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label mb-3">Horarios Disponibles</label>
                                    <div id="horariosDisponibles" class="row g-2">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                    
                                    <div id="noHorarios" class="text-center py-5" style="display: none;">
                                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay horarios disponibles</h5>
                                        <p class="text-muted">Esta agenda no tiene cupos libres</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campos ocultos para fecha_inicio y fecha_final -->
                            <input type="hidden" id="fecha_inicio" name="fecha_inicio">
                            <input type="hidden" id="fecha_final" name="fecha_final">
                            <input type="hidden" id="fecha" name="fecha">
                        </div>

                        <!-- Paso 3: Buscar Paciente -->
                        <div class="step-section" id="step3" style="display: none;">
                            <h6 class="text-primary mb-4">
                                <i class="fas fa-user-search me-2"></i>Paso 3: Buscar Paciente
                            </h6>
                            
                            <!-- Resumen del Horario Seleccionado -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div id="horarioSeleccionadoInfo" class="alert alert-success">
                                        <!-- Info del horario seleccionado -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Buscar Paciente -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="buscar_documento" class="form-label">
                                        Número de Cédula <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="buscar_documento" 
                                               placeholder="Ingrese número de cédula">
                                        <button type="button" class="btn btn-primary" onclick="buscarPaciente()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Ingrese solo números, sin puntos ni espacios</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <div id="pacienteInfo" class="alert alert-info" style="display: none;">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-user-check me-2"></i>Paciente Encontrado
                                        </h6>
                                        <div id="pacienteDetalles"></div>
                                    </div>
                                    
                                    <div id="pacienteNoEncontrado" class="alert alert-warning" style="display: none;">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-user-times me-2"></i>Paciente No Encontrado
                                        </h6>
                                        <p class="mb-2">No se encontró un paciente con esa cédula.</p>
                                        <a href="{{ route('pacientes.create') }}" class="btn btn-sm btn-warning" target="_blank">
                                            <i class="fas fa-plus"></i> Registrar Nuevo Paciente
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" id="paciente_uuid" name="paciente_uuid">
                        </div>

                      <!-- Paso 4: Detalles de la Cita -->
<div class="step-section" id="step4" style="display: none;">
    <h6 class="text-primary mb-4">
        <i class="fas fa-clipboard-list me-2"></i>Paso 4: Completar Detalles
    </h6>
    
    <!-- Resumen Final -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>Resumen de la Cita
                    </h6>
                </div>
                <div class="card-body">
                    <div id="resumenCita">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detalles Adicionales -->
    <div class="row g-3">
        <!-- CUPS Contratado - NUEVA SECCIÓN -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-file-medical me-2"></i>CUPS (Código Único de Procedimientos)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="cups_codigo" class="form-label">Código CUPS</label>
                            <div class="cups-autocomplete-container">
                                <input type="text" 
                                       class="form-control cups-input" 
                                       id="cups_codigo" 
                                       placeholder="Ej: 890201">
                                <div id="cups_results" class="cups-results"></div>
                            </div>
                            <small class="form-text text-muted">Ingrese el código para buscar automáticamente</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="cups_nombre" class="form-label">Nombre del Procedimiento</label>
                            <input type="text" 
                                   class="form-control cups-input" 
                                   id="cups_nombre" 
                                   placeholder="Busque por nombre del procedimiento"
                                   readonly>
                            <small class="form-text text-muted">O busque por nombre del procedimiento</small>
                        </div>
                           <div class="col-md-2">
                            <label class="form-label">Acciones</label>
                            <div class="d-flex gap-2">
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm" 
                                        id="btnLimpiarCups"
                                        title="Limpiar selección">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-info btn-sm" 
                                        id="btnSincronizarCups"
                                        title="Sincronizar CUPS desde servidor">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo oculto para enviar UUID -->
                    <input type="hidden" id="cups_contratado_uuid" name="cups_contratado_uuid">
                    
                    <!-- Información del CUPS seleccionado -->
                    <div id="cups_info" class="mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>CUPS Seleccionado:</strong>
                            <span id="cups_info_text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Motivo y Patología -->
        <div class="col-md-6">
            <label for="motivo" class="form-label">Motivo de la Consulta</label>
            <textarea class="form-control" 
                      id="motivo" 
                      name="motivo" 
                      rows="3" 
                      maxlength="200"
                      placeholder="Describa brevemente el motivo de la consulta"></textarea>
            <small class="form-text text-muted">Máximo 200 caracteres</small>
        </div>
        
        <div class="col-md-6">
            <label for="patologia" class="form-label">Patología</label>
            <input type="text" 
                   class="form-control" 
                   id="patologia" 
                   name="patologia" 
                   maxlength="50"
                   placeholder="Patología relacionada">
            <small class="form-text text-muted">Opcional - Máximo 50 caracteres</small>
        </div>
        
        <!-- Notas Adicionales -->
        <div class="col-12">
            <label for="nota" class="form-label">Notas Adicionales <span class="text-danger">*</span></label>
            <textarea class="form-control" 
                      id="nota" 
                      name="nota" 
                      rows="3" 
                      maxlength="200"
                      placeholder="Notas importantes sobre la cita"
                      required></textarea>
            <small class="form-text text-muted">Campo obligatorio - Máximo 200 caracteres</small>
        </div>
        
        <!-- Estado de la Cita -->
        <div class="col-md-6">
            <label for="estado" class="form-label">Estado de la Cita</label>
            <select class="form-select" id="estado" name="estado">
                <option value="PROGRAMADA" selected>Programada</option>
                <option value="CONFIRMADA">Confirmada</option>
                <option value="EN_ESPERA">En Espera</option>
            </select>
        </div>
        
        <!-- Fecha Deseada (Opcional) -->
        <div class="col-md-6">
            <label for="fecha_deseada" class="form-label">Fecha Deseada (Opcional)</label>
            <input type="date" 
                   class="form-control" 
                   id="fecha_deseada" 
                   name="fecha_deseada"
                   min="{{ date('Y-m-d') }}">
            <small class="form-text text-muted">Si difiere de la fecha programada</small>
        </div>
    </div>
</div>

                        <!-- Botones de Navegación -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" id="btnAnterior" 
                                                onclick="anteriorPaso()" style="display: none;">
                                            <i class="fas fa-chevron-left"></i> Anterior
                                        </button>
                                    </div>
                                    <div>
                                        <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="button" class="btn btn-primary" id="btnSiguiente" 
                                                onclick="siguientePaso()" style="display: none;">
                                            Siguiente <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <button type="submit" class="btn btn-success" id="btnGuardar" style="display: none;">
                                            <i class="fas fa-save"></i> Agendar Cita
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Estilos para el indicador de pasos */
.horario-btn:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

.horario-btn:disabled:hover {
    transform: none !important;
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.horario-btn:disabled small {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 0.7em;
}

/* Horarios disponibles */
.horario-btn:not(:disabled) {
    transition: all 0.2s ease;
}

.horario-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.horario-btn.selected {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.horario-btn.selected:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
}
.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    opacity: 0.5;
    transition: all 0.3s ease;
}

.step.active {
    opacity: 1;
}

.step.completed {
    opacity: 1;
    color: #28a745;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background-color: #007bff;
    color: white;
}

.step.completed .step-number {
    background-color: #28a745;
    color: white;
}

.step-label {
    font-size: 12px;
    font-weight: 500;
    max-width: 80px;
}

.step-line {
    width: 60px;
    height: 2px;
    background-color: #e9ecef;
    margin: 0 10px;
    margin-top: -20px;
}

/* Estilos para las cards de agenda */
.agenda-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.agenda-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.agenda-card.selected {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.agenda-card.sin-cupos {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Estilos para botones de horario */
.horario-btn {
    transition: all 0.2s ease;
    min-width: 80px;
}

.horario-btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.horario-btn.selected {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

/* Animaciones */
.step-section {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@push('scripts')
<script>
let pasoActual = 1;
let agendaSeleccionada = null;
let horarioSeleccionado = null;
let pacienteSeleccionado = null;
let agendasDisponibles = [];
let horariosDisponibles = [];
let cupsAutocomplete;

document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha mínima (hoy)
    document.getElementById('filtro_fecha').value = new Date().toISOString().split('T')[0];
    
    // ✅ CARGAR AGENDAS CON DATOS REALES
    cargarAgendasConDatosReales();
    
    // Mostrar primer paso
    mostrarPaso(1);

    // ✅ INICIALIZAR CUPS AUTOCOMPLETE
    initCupsAutocomplete();
    
    // ✅ CONFIGURAR BOTONES DE CUPS
    setupCupsButtons();
});

// ✅ NUEVA FUNCIÓN DE INICIALIZACIÓN CON DATOS REALES
async function cargarAgendasConDatosReales() {
    try {
        console.log('🚀 Inicializando carga de agendas con datos reales...');
        await cargarAgendas();
    } catch (error) {
        console.error('❌ Error en inicialización:', error);
    }
}

// ✅ NAVEGACIÓN ENTRE PASOS
function mostrarPaso(paso) {
    // Ocultar todos los pasos
    document.querySelectorAll('.step-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Mostrar paso actual
    const stepElement = document.getElementById(`step${paso}`);
    if (stepElement) {
        stepElement.style.display = 'block';
    }
    
    // Actualizar indicadores
    actualizarIndicadores(paso);
    
    // Actualizar botones
    actualizarBotones(paso);
    
    pasoActual = paso;
}

function actualizarIndicadores(paso) {
    for (let i = 1; i <= 4; i++) {
        const indicator = document.getElementById(`indicator-${i}`);
        if (indicator) {
            indicator.classList.remove('active', 'completed');
            
            if (i === paso) {
                indicator.classList.add('active');
            } else if (i < paso) {
                indicator.classList.add('completed');
            }
        }
    }
}

function actualizarBotones(paso) {
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnGuardar = document.getElementById('btnGuardar');
    
    // Botón anterior
    btnAnterior.style.display = paso > 1 ? 'inline-block' : 'none';
    
    // Botón siguiente
    btnSiguiente.style.display = paso < 4 ? 'inline-block' : 'none';
    
    // Botón guardar
    btnGuardar.style.display = paso === 4 ? 'inline-block' : 'none';
    
    // Habilitar/deshabilitar según validaciones
    switch (paso) {
        case 1:
            btnSiguiente.disabled = !agendaSeleccionada;
            break;
        case 2:
            btnSiguiente.disabled = !horarioSeleccionado;
            break;
        case 3:
            btnSiguiente.disabled = !pacienteSeleccionado;
            break;
        case 4:
            btnGuardar.disabled = !pacienteSeleccionado || !horarioSeleccionado || !agendaSeleccionada;
            break;
    }
}

function siguientePaso() {
    if (pasoActual < 4) {
        if (validarPaso(pasoActual)) {
            const siguientePaso = pasoActual + 1;
            mostrarPaso(siguientePaso);
            
            // Acciones específicas por paso
            if (siguientePaso === 2) {
                cargarHorariosDisponibles();
            } else if (siguientePaso === 3) {
                mostrarInfoHorarioSeleccionado();
            } else if (siguientePaso === 4) {
                actualizarResumenFinal();
            }
        }
    }
}

function anteriorPaso() {
    if (pasoActual > 1) {
        mostrarPaso(pasoActual - 1);
    }
}

function validarPaso(paso) {
    switch (paso) {
        case 1:
            if (!agendaSeleccionada) {
                Swal.fire('Atención', 'Debe seleccionar una agenda', 'warning');
                return false;
            }
            break;
        case 2:
            if (!horarioSeleccionado) {
                Swal.fire('Atención', 'Debe seleccionar un horario', 'warning');
                return false;
            }
            break;
        case 3:
            if (!pacienteSeleccionado) {
                Swal.fire('Atención', 'Debe buscar y seleccionar un paciente', 'warning');
                return false;
            }
            break;
    }
    return true;
}

// ✅ INICIALIZAR CUPS AUTOCOMPLETE - CORREGIDO
function initCupsAutocomplete() {
    try {
        cupsAutocomplete = new CupsAutocomplete({
            codigoInput: document.getElementById('cups_codigo'),
            nombreInput: document.getElementById('cups_nombre'),
            hiddenInput: document.getElementById('cups_contratado_uuid'),
            resultsContainer: document.getElementById('cups_results'),
            minLength: 2,
            delay: 300
        });
        
        // ✅ EVENTO CUANDO SE SELECCIONA UN CUPS
        document.getElementById('cups_codigo').addEventListener('cupsSelected', async function(e) {
            const cups = e.detail;
            
            console.log('🔍 CUPS seleccionado, resolviendo contrato...', {
                cups_uuid: cups.uuid,
                cups_codigo: cups.codigo,
                cups_nombre: cups.nombre
            });
            
            try {
                const cupsContratadoUuid = await resolverCupsContratado(cups.uuid);
                
                if (cupsContratadoUuid) {
                    document.getElementById('cups_contratado_uuid').value = cupsContratadoUuid;
                    cups.cups_contratado_uuid = cupsContratadoUuid;
                    mostrarInfoCups(cups);
                    
                    console.log('✅ CUPS contratado configurado exitosamente:', {
                        cups_codigo: cups.codigo,
                        cups_uuid: cups.uuid,
                        cups_contratado_uuid: cupsContratadoUuid,
                        campo_valor: document.getElementById('cups_contratado_uuid').value
                    });
                } else {
                    document.getElementById('cups_contratado_uuid').value = '';
                    cupsAutocomplete.clear();
                    ocultarInfoCups();
                    console.log('⚠️ No se encontró contrato vigente para el CUPS');
                }
                
            } catch (error) {
                console.error('❌ Error resolviendo CUPS contratado:', error);
                document.getElementById('cups_contratado_uuid').value = '';
                cupsAutocomplete.clear();
                ocultarInfoCups();
            }
        });
        
        console.log('✅ CUPS Autocomplete inicializado correctamente');
        
    } catch (error) {
        console.error('❌ Error inicializando CUPS autocomplete:', error);
    }
}

// ✅ RESOLVER CUPS CONTRATADO - CORREGIDO
async function resolverCupsContratado(cupsUuid) {
    console.log('🚀 === INICIANDO resolverCupsContratado ===');
    console.log('📝 CUPS UUID recibido:', cupsUuid);
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            throw new Error('❌ No se encontró CSRF token');
        }
        
        const url = `/cups-contratados/por-cups/${cupsUuid}`;
        console.log('🔗 Consultando URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('📡 Response status:', response.status);
        
        if (!response.ok) {
            if (response.status === 404) {
                console.log('⚠️ CUPS sin contrato vigente (404)');
                
                await Swal.fire({
                    title: 'CUPS sin Contrato Vigente',
                    text: 'Este CUPS no tiene un contrato vigente. La cita se creará sin CUPS asociado.',
                    icon: 'warning',
                    confirmButtonText: 'Continuar'
                });
                
                return null;
            }
            
            throw new Error(`Error HTTP ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📥 Respuesta parseada:', result);
        
        if (result.success && result.data && result.data.uuid) {
            console.log('✅ CUPS contratado encontrado:', {
                uuid: result.data.uuid,
                tarifa: result.data.tarifa,
                estado: result.data.estado
            });
            
            return result.data.uuid;
        } else {
            console.log('⚠️ Respuesta sin éxito o sin datos:', result);
            
            await Swal.fire({
                title: 'CUPS sin Contrato',
                text: result.message || 'No se encontró un contrato vigente para este CUPS',
                icon: 'info',
                confirmButtonText: 'Continuar'
            });
            
            return null;
        }
        
    } catch (error) {
        console.error('❌ === ERROR EN resolverCupsContratado ===');
        console.error('❌ Error:', error.message);
        
        await Swal.fire({
            title: 'Error de Conexión',
            text: 'No se pudo verificar el contrato del CUPS. La cita se creará sin CUPS asociado.',
            icon: 'error',
            confirmButtonText: 'Continuar'
        });
        
        return null;
    }
}

// ✅ CONFIGURAR BOTONES DE CUPS - CORREGIDO
function setupCupsButtons() {
    document.getElementById('btnLimpiarCups').addEventListener('click', function() {
        if (cupsAutocomplete) {
            cupsAutocomplete.clear();
        } else {
            document.getElementById('cups_codigo').value = '';
            document.getElementById('cups_nombre').value = '';
            document.getElementById('cups_contratado_uuid').value = '';
        }
        
        ocultarInfoCups();
        console.log('🧹 CUPS limpiado');
    });
    
    document.getElementById('btnSincronizarCups').addEventListener('click', function() {
        sincronizarCupsDesdeServidor();
    });
}

function mostrarInfoCups(cups) {
    const infoDiv = document.getElementById('cups_info');
    const infoText = document.getElementById('cups_info_text');
    
    if (infoDiv && infoText) {
        infoText.innerHTML = `
            <strong>${cups.codigo}</strong> - ${cups.nombre}
            ${cups.categoria ? `<br><small class="text-muted">Categoría: ${cups.categoria}</small>` : ''}
            <br><small class="text-success">✅ Contrato vigente encontrado</small>
        `;
        infoDiv.style.display = 'block';
    }
}

function ocultarInfoCups() {
    const infoDiv = document.getElementById('cups_info');
    if (infoDiv) {
        infoDiv.style.display = 'none';
    }
}

async function sincronizarCupsDesdeServidor() {
    const btn = document.getElementById('btnSincronizarCups');
    const originalHtml = btn.innerHTML;
    
    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const response = await fetch('/cups/sincronizar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                title: '✅ Sincronización Exitosa',
                text: data.message || 'CUPS sincronizados correctamente',
                icon: 'success',
                timer: 3000
            });
            
            console.log('✅ CUPS sincronizados', {
                count: data.count || 0
            });
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('❌ Error sincronizando CUPS:', error);
        
        Swal.fire({
            title: 'Error',
            text: 'Error sincronizando CUPS: ' + error.message,
            icon: 'error'
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// ✅ FUNCIÓN CORREGIDA PARA FORMATEAR FECHA SIN DESFASE
function formatearFechaSinDesfase(fechaString) {
    if (!fechaString) return 'No disponible';
    
    try {
        console.log('🔍 DEBUG formatearFechaSinDesfase - Input:', fechaString);
        
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
            
            // ✅ CREAR FECHA LOCAL SIN ZONA HORARIA
            const fechaLocal = new Date(año, mes - 1, dia);
            
            // ✅ FORMATEAR COMO dd/mm/yyyy
            const fechaFormateada = fechaLocal.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            console.log('✅ Fecha formateada sin desfase:', {
                original: fechaString,
                parseado: { año, mes, dia },
                resultado: fechaFormateada
            });
            
            return fechaFormateada;
        }
        
        return fechaString;
    } catch (error) {
        console.error('Error formateando fecha:', error, fechaString);
        return fechaString;
    }
}

// ✅ ACTUALIZAR RESUMEN FINAL - CORREGIDO
function actualizarResumenFinal() {
    if (!pacienteSeleccionado || !agendaSeleccionada || !horarioSeleccionado) {
        return;
    }
    
    const fecha = formatearFechaSinDesfase(agendaSeleccionada.fecha);
    const cupsContratadoUuid = document.getElementById('cups_contratado_uuid').value;
    const cupsSeleccionado = cupsAutocomplete ? cupsAutocomplete.getSelected() : null;
    
    console.log('📋 Actualizando resumen final:', {
        fecha_original: agendaSeleccionada.fecha,
        fecha_formateada: fecha,
        cups_contratado_uuid: cupsContratadoUuid,
        cups_seleccionado: cupsSeleccionado
    });
    
    let resumen = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-user me-2"></i>Paciente</h6>
                <p class="mb-2">${pacienteSeleccionado.nombre_completo}<br>
                <small class="text-muted">Cédula: ${pacienteSeleccionado.documento}</small></p>
                
                <h6><i class="fas fa-calendar me-2"></i>Fecha y Hora</h6>
                <p class="mb-2">${fecha}<br>
                <small class="text-muted">${horarioSeleccionado.hora_inicio} - ${horarioSeleccionado.hora_fin}</small></p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-building me-2"></i>Lugar</h6>
                <p class="mb-2">${agendaSeleccionada.consultorio}<br>
                <small class="text-muted">${agendaSeleccionada.modalidad}</small></p>
                
                <h6><i class="fas fa-tag me-2"></i>Tipo</h6>
                <p class="mb-2">${agendaSeleccionada.etiqueta}<br>
                <small class="text-muted">Duración: ${agendaSeleccionada.intervalo} minutos</small></p>
            </div>
        </div>
    `;
    
    // ✅ AGREGAR INFORMACIÓN DE CUPS SI ESTÁ SELECCIONADO
    if (cupsContratadoUuid && cupsContratadoUuid !== '' && cupsSeleccionado) {
        resumen += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-file-medical me-2"></i>CUPS Seleccionado</h6>
                        <p class="mb-0">
                            <strong>${cupsSeleccionado.codigo}</strong> - ${cupsSeleccionado.nombre}
                            <br><small class="text-success">✅ Contrato vigente (UUID: ${cupsContratadoUuid})</small>
                        </p>
                    </div>
                </div>
            </div>
        `;
    } else if (cupsSeleccionado && (!cupsContratadoUuid || cupsContratadoUuid === '')) {
        resumen += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-file-medical me-2"></i>CUPS Sin Contrato</h6>
                        <p class="mb-0">
                            <strong>${cupsSeleccionado.codigo}</strong> - ${cupsSeleccionado.nombre}
                            <br><small class="text-warning">⚠️ Sin contrato vigente - La cita se creará sin CUPS</small>
                        </p>
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('resumenCita').innerHTML = resumen;
}

// ✅ PASO 1: CARGAR Y SELECCIONAR AGENDAS CON DATOS REALES
async function cargarAgendas() {
    try {
        console.log('🔄 Cargando agendas...');
        
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
        
        console.log('📥 Respuesta de agendas:', data);

        if (data.success) {
            agendasDisponibles = data.data;
            
            // ✅ MOSTRAR AGENDAS Y LUEGO ACTUALIZAR CON DATOS REALES
            await mostrarAgendasConDatosReales(agendasDisponibles);
        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error cargando agendas:', error);
        mostrarAgendas([]);
    }
}

function filtrarAgendas() {
    cargarAgendas(); // ✅ RECARGAR CON FILTROS APLICADOS
}

// ✅ NUEVA FUNCIÓN PARA MOSTRAR AGENDAS CON DATOS REALES
async function mostrarAgendasConDatosReales(agendas) {
    const container = document.getElementById('agendasDisponibles');
    const noAgendas = document.getElementById('noAgendas');
    
    if (!agendas || agendas.length === 0) {
        container.innerHTML = '';
        noAgendas.style.display = 'block';
        return;
    }
    
    noAgendas.style.display = 'none';
    
    // ✅ MOSTRAR AGENDAS INICIALMENTE CON DATOS BÁSICOS
    let html = '';
    
    for (const agenda of agendas) {
        const fecha = formatearFechaSinDesfase(agenda.fecha);
        const cardId = `agenda-card-${agenda.uuid}`;
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card agenda-card" id="${cardId}"
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
                            <div class="text-end">
                                <span class="text-info fw-semibold cupos-display" 
                                      data-agenda-uuid="${agenda.uuid}">
                                    <i class="fas fa-spinner fa-spin me-1"></i>
                                    <span class="cupos-numeros">Cargando...</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    
    // ✅ AHORA ACTUALIZAR CADA AGENDA CON DATOS REALES
    for (const agenda of agendas) {
        await actualizarCuposRealAgenda(agenda);
    }
}

// ✅ NUEVA FUNCIÓN PARA OBTENER HORARIOS REALES
async function obtenerHorariosRealesAgenda(agendaUuid, fecha) {
    try {
        console.log('🔍 Obteniendo horarios reales para agenda:', agendaUuid);
        
        const response = await fetch(`/citas/agenda/${agendaUuid}/horarios?fecha=${fecha}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success && data.data) {
            const disponibles = data.data.filter(h => h.disponible).length;
            const total = data.data.length;
            
            console.log('✅ Horarios reales obtenidos:', {
                agenda_uuid: agendaUuid,
                disponibles,
                total,
                ocupados: total - disponibles
            });
            
            return {
                disponibles,
                total,
                ocupados: total - disponibles
            };
        }
        
        console.warn('⚠️ No se pudieron obtener horarios reales');
        return { disponibles: 0, total: 0, ocupados: 0 };
        
    } catch (error) {
        console.error('❌ Error obteniendo horarios reales:', error);
        return { disponibles: 0, total: 0, ocupados: 0 };
    }
}

// ✅ NUEVA FUNCIÓN PARA ACTUALIZAR CUPOS REAL DE UNA AGENDA
async function actualizarCuposRealAgenda(agenda) {
    try {
        console.log('🔄 Actualizando cupos reales para:', agenda.uuid);
        
        const horariosReales = await obtenerHorariosRealesAgenda(agenda.uuid, agenda.fecha);
        
        const cuposDisponibles = horariosReales.disponibles;
        const cuposTotales = horariosReales.total;
        const sinCupos = cuposDisponibles <= 0;
        
        // ✅ ACTUALIZAR LA INTERFAZ
        const cardElement = document.getElementById(`agenda-card-${agenda.uuid}`);
        const cuposDisplay = cardElement?.querySelector('.cupos-display');
        const cuposNumeros = cardElement?.querySelector('.cupos-numeros');
        
        if (cuposDisplay && cuposNumeros) {
            // Actualizar números
            cuposNumeros.textContent = `${cuposDisponibles}/${cuposTotales}`;
            
            // Actualizar clases y colores
            cuposDisplay.className = `${sinCupos ? 'text-danger' : 'text-success'} fw-semibold cupos-display`;
            cuposDisplay.innerHTML = `
                <i class="fas fa-users me-1"></i>
                <span class="cupos-numeros">${cuposDisponibles}/${cuposTotales}</span>
            `;
            
            // Actualizar funcionalidad de la card
            if (sinCupos) {
                cardElement.classList.add('sin-cupos');
                cardElement.setAttribute('onclick', 'alertaSinCupos()');
                
                // Agregar badge de sin cupos
                const cardBody = cardElement.querySelector('.card-body');
                const existingBadge = cardBody.querySelector('.badge.bg-danger');
                if (!existingBadge) {
                    cardBody.insertAdjacentHTML('beforeend', 
                        '<div class="mt-2"><span class="badge bg-danger w-100">Sin cupos disponibles</span></div>'
                    );
                }
            } else {
                cardElement.classList.remove('sin-cupos');
                cardElement.setAttribute('onclick', `seleccionarAgenda('${agenda.uuid}')`);
                
                // Remover badge de sin cupos si existe
                const badge = cardElement.querySelector('.badge.bg-danger');
                if (badge) {
                    badge.parentElement.remove();
                }
            }
        }
        
        console.log('✅ Cupos actualizados en interfaz:', {
            agenda_uuid: agenda.uuid,
            disponibles: cuposDisponibles,
            total: cuposTotales,
            sin_cupos: sinCupos
        });
        
    } catch (error) {
        console.error('❌ Error actualizando cupos de agenda:', error);
        
        // En caso de error, mostrar estado de error
        const cardElement = document.getElementById(`agenda-card-${agenda.uuid}`);
        const cuposNumeros = cardElement?.querySelector('.cupos-numeros');
        if (cuposNumeros) {
            cuposNumeros.textContent = 'Error';
            cuposNumeros.parentElement.className = 'text-warning fw-semibold cupos-display';
        }
    }
}

// ✅ FUNCIÓN CORREGIDA CON LOGGING DETALLADO
function seleccionarAgenda(agendaUuid) {
    console.log('🔍 DEBUG seleccionarAgenda - UUID recibido:', agendaUuid);
    
    const agenda = agendasDisponibles.find(a => a.uuid === agendaUuid);
    
    if (!agenda) {
        console.error('❌ Agenda no encontrada!', agendaUuid);
        Swal.fire('Error', 'Agenda no encontrada', 'error');
        return;
    }
    
    // ✅ VERIFICAR CUPOS REALES DEL DOM
    const cardElement = document.getElementById(`agenda-card-${agenda.uuid}`);
    const cuposDisplay = cardElement?.querySelector('.cupos-numeros');
    const cuposText = cuposDisplay?.textContent || '';
    
    // Extraer cupos disponibles del texto "X/Y"
    const cuposMatch = cuposText.match(/^(\d+)\/(\d+)$/);
    const cuposDisponibles = cuposMatch ? parseInt(cuposMatch[1]) : 0;
    
        console.log('🔍 Verificando cupos reales:', {
        cupos_text: cuposText,
        cupos_disponibles: cuposDisponibles,
        sin_cupos: cuposDisponibles <= 0
    });
    
    if (cuposDisponibles <= 0) {
        alertaSinCupos();
        return;
    }
    
    agendaSeleccionada = agenda;
    
    // ✅ ESTABLECER VALORES EN CAMPOS OCULTOS CON LOGGING
    const agendaUuidField = document.getElementById('agenda_uuid');
    const fechaField = document.getElementById('fecha');
    
    if (agendaUuidField) {
        agendaUuidField.value = agenda.uuid;
        console.log('✅ agenda_uuid establecido:', agenda.uuid);
    } else {
        console.error('❌ Campo agenda_uuid no encontrado!');
    }
    
    if (fechaField) {
        fechaField.value = agenda.fecha;
        console.log('✅ fecha establecida:', agenda.fecha);
    } else {
        console.error('❌ Campo fecha no encontrado!');
    }
    
    // ✅ VERIFICAR VALORES FINALES
    console.log('🔍 Valores finales de campos ocultos:', {
        agenda_uuid: document.getElementById('agenda_uuid')?.value,
        fecha: document.getElementById('fecha')?.value
    });
    
    console.log('✅ Agenda seleccionada:', {
        uuid: agenda.uuid,
        fecha: agenda.fecha,
        consultorio: agenda.consultorio
    });
    
    // Actualizar estilos visuales
    document.querySelectorAll('.agenda-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    
    // Habilitar siguiente paso
    actualizarBotones(pasoActual);
}

function alertaSinCupos() {
    Swal.fire({
        title: 'Sin cupos disponibles',
        text: 'Esta agenda no tiene cupos disponibles. Por favor seleccione otra agenda.',
        icon: 'warning',
        confirmButtonText: 'Entendido'
    });
}

// ✅ PASO 2: CARGAR HORARIOS
async function cargarHorariosDisponibles() {
    if (!agendaSeleccionada) {
        return;
    }
    
    // Mostrar info de agenda seleccionada
    mostrarInfoAgendaSeleccionada();
    
    try {
        console.log('🔍 Cargando horarios para agenda:', {
            agenda_uuid: agendaSeleccionada.uuid,
            fecha: agendaSeleccionada.fecha
        });
        
        const response = await fetch(`/citas/agenda/${agendaSeleccionada.uuid}/horarios?fecha=${agendaSeleccionada.fecha}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        console.log('📥 Respuesta de horarios:', {
            success: data.success,
            horarios_count: data.data ? data.data.length : 0,
            data: data
        });

        if (data.success) {
            horariosDisponibles = data.data;
            mostrarHorarios(horariosDisponibles);
            
            // ✅ ACTUALIZAR CUPOS EN LA INTERFAZ
            if (data.agenda) {
                actualizarCuposEnInterfaz(data.agenda);
            }
        } else {
            throw new Error(data.error);
        }

    } catch (error) {
        console.error('Error cargando horarios:', error);
        mostrarHorarios([]);
    }
}

// ✅ NUEVA FUNCIÓN PARA ACTUALIZAR CUPOS EN LA INTERFAZ
function actualizarCuposEnInterfaz(agenda) {
    try {
        // Calcular cupos reales
        const disponibles = horariosDisponibles.filter(h => h.disponible).length;
        const ocupados = horariosDisponibles.filter(h => !h.disponible).length;
        const total = horariosDisponibles.length;
        
        console.log('📊 Actualizando cupos en interfaz:', {
            total,
            disponibles,
            ocupados
        });
        
        // Actualizar la card de agenda seleccionada si existe
        const agendaCard = document.querySelector(`[onclick*="${agenda.uuid}"]`);
        if (agendaCard) {
            const cuposSpan = agendaCard.querySelector('.fw-semibold');
            if (cuposSpan) {
                cuposSpan.innerHTML = `<i class="fas fa-users me-1"></i>${disponibles}/${total}`;
                cuposSpan.className = disponibles > 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            }
        }
        
    } catch (error) {
        console.error('Error actualizando cupos en interfaz:', error);
    }
}

// ✅ FUNCIÓN CORREGIDA PARA MOSTRAR INFO DE AGENDA SIN DESFASE
function mostrarInfoAgendaSeleccionada() {
    if (!agendaSeleccionada) return;
    
    const fecha = formatearFechaSinDesfase(agendaSeleccionada.fecha);
    const info = `
        <div class="row">
            <div class="col-md-6">
                <strong>Agenda Seleccionada:</strong><br>
                <i class="fas fa-building me-1"></i> ${agendaSeleccionada.consultorio}<br>
                <i class="fas fa-calendar me-1"></i> ${fecha}<br>
                <i class="fas fa-clock me-1"></i> ${agendaSeleccionada.hora_inicio} - ${agendaSeleccionada.hora_fin}
            </div>
            <div class="col-md-6">
                <i class="fas fa-tag me-1"></i> ${agendaSeleccionada.etiqueta}<br>
                <i class="fas fa-laptop me-1"></i> ${agendaSeleccionada.modalidad}<br>
                <i class="fas fa-stopwatch me-1"></i> Intervalo: ${agendaSeleccionada.intervalo} minutos
            </div>
        </div>
    `;
    
    document.getElementById('agendaSeleccionadaInfo').innerHTML = info;
}

function mostrarHorarios(horarios) {
    console.log('🔍 DEBUG mostrarHorarios - Horarios recibidos:', horarios);
    
    const container = document.getElementById('horariosDisponibles');
    const noHorarios = document.getElementById('noHorarios');
    
    if (!horarios || horarios.length === 0) {
        console.log('⚠️ No hay horarios disponibles');
        container.innerHTML = '';
        noHorarios.style.display = 'block';
        return;
    }
    
    noHorarios.style.display = 'none';
    
    let html = '';
    horarios.forEach((horario, index) => {
        console.log(`🔍 DEBUG - Procesando horario ${index}:`, horario);
        
        const disponible = horario.disponible;
        
        // ✅ ESTILOS DIFERENTES PARA DISPONIBLES Y OCUPADOS
        let btnClass, icon, opacity, title, clickAction;
        
        if (disponible) {
            btnClass = 'btn-outline-success';
            icon = 'fa-check-circle';
            opacity = '';
            title = 'Horario disponible - Click para seleccionar';
            clickAction = `seleccionarHorario('${horario.hora_inicio}', '${horario.hora_fin}')`;
        } else {
            btnClass = 'btn-outline-danger';
            icon = 'fa-times-circle';
            opacity = 'style="opacity: 0.6;"';
            title = `Ocupado por: ${horario.ocupado_por?.paciente || 'Paciente no identificado'}`;
            clickAction = 'alertaHorarioOcupado()';
        }
        
        const selectedClass = horarioSeleccionado?.hora_inicio === horario.hora_inicio ? 'selected' : '';
        const disabled = disponible ? '' : 'disabled';
        
        // ✅ EXTRAER SOLO LAS HORAS (HH:MM) DE LOS HORARIOS
        const horaInicio = extraerHora(horario.hora_inicio);
        const horaFin = extraerHora(horario.hora_fin);
        
        console.log(`✅ Horario ${index} procesado:`, {
            original_inicio: horario.hora_inicio,
            original_fin: horario.hora_fin,
            horaInicio,
            horaFin,
            disponible
        });
        
        // ✅ MOSTRAR TODOS LOS HORARIOS (disponibles y ocupados)
        html += `
            <div class="col-md-3 col-lg-2 mb-2">
                <button type="button" 
                        class="btn ${btnClass} w-100 horario-btn ${selectedClass}" 
                        title="${title}"
                        data-hora-inicio="${horaInicio}"
                        data-hora-fin="${horaFin}"
                        ${disabled}
                        ${opacity}
                        onclick="${clickAction}">
                    <i class="fas ${icon} me-1"></i>
                    <div>${horaInicio}</div>
                    ${!disponible ? '<small class="text-muted">Ocupado</small>' : ''}
                </button>
            </div>
        `;
    });
    
    console.log('🔍 DEBUG - HTML generado:', html);
    
    container.innerHTML = html;
}

// ✅ NUEVA FUNCIÓN para alertar horario ocupado
function alertaHorarioOcupado() {
    Swal.fire({
        title: 'Horario Ocupado',
        text: 'Este horario ya está ocupado por otro paciente.',
        icon: 'warning',
        confirmButtonText: 'Entendido'
    });
}

// ✅ FUNCIÓN AUXILIAR PARA EXTRAER HORA
function extraerHora(fechaHora) {
    if (!fechaHora) return '';
    
    console.log('🔍 DEBUG extraerHora - Input:', fechaHora);
    
    // Si viene como "2025-09-09T08:26:00" o "2025-09-09T08:26:00.000000Z"
    if (fechaHora.includes('T')) {
        const parteHora = fechaHora.split('T')[1];
        const hora = parteHora.substring(0, 5); // "08:26"
        console.log('🔍 DEBUG extraerHora - Extraído de timestamp:', hora);
        return hora;
    }
    
    // Si viene como "08:26:00"
    if (fechaHora.includes(':')) {
        const hora = fechaHora.substring(0, 5); // "08:26"
        console.log('🔍 DEBUG extraerHora - Extraído de hora:', hora);
        return hora;
    }
    
    // Si ya viene como "08:26"
    console.log('🔍 DEBUG extraerHora - Ya en formato correcto:', fechaHora);
    return fechaHora;
}

// ✅ FUNCIÓN CORREGIDA PARA EXTRAER FECHA SIN DESFASE
function extraerFecha(fechaCompleta) {
    if (!fechaCompleta) return '';
    
    console.log('🔍 DEBUG extraerFecha - Input:', fechaCompleta);
    
    // Si viene como "2025-09-12T00:00:00" o similar, extraer solo la fecha
    if (fechaCompleta.includes('T')) {
        const fecha = fechaCompleta.split('T')[0]; // "2025-09-12"
        console.log('🔍 DEBUG extraerFecha - Extraído de timestamp:', fecha);
        return fecha;
    }
    
    // Si ya viene como "2025-09-12"
    if (fechaCompleta.match(/^\d{4}-\d{2}-\d{2}$/)) {
        console.log('🔍 DEBUG extraerFecha - Ya en formato correcto:', fechaCompleta);
        return fechaCompleta;
    }
    
    // Si viene como Date object, usar métodos UTC para evitar zona horaria
    if (fechaCompleta instanceof Date) {
        const year = fechaCompleta.getUTCFullYear();
        const month = String(fechaCompleta.getUTCMonth() + 1).padStart(2, '0');
        const day = String(fechaCompleta.getUTCDate()).padStart(2, '0');
        const fecha = `${year}-${month}-${day}`;
        console.log('🔍 DEBUG extraerFecha - Convertido de Date con UTC:', fecha);
        return fecha;
    }
    
    // Si viene como string de fecha "Sep 12, 2025" o similar
    if (fechaCompleta && typeof fechaCompleta === 'string') {
        try {
            // Crear fecha local sin zona horaria
            const partes = fechaCompleta.split('-');
            if (partes.length === 3) {
                const year = partes[0];
                const month = partes[1].padStart(2, '0');
                const day = partes[2].padStart(2, '0');
                const fecha = `${year}-${month}-${day}`;
                console.log('🔍 DEBUG extraerFecha - Parseado manual:', fecha);
                return fecha;
            }
        } catch (error) {
            console.error('Error parseando fecha:', error);
        }
    }
    
    console.log('🔍 DEBUG extraerFecha - Sin cambios:', fechaCompleta);
    return fechaCompleta;
}

// ✅ FUNCIÓN CORREGIDA seleccionarHorario
function seleccionarHorario(horaInicio, horaFin) {
    console.log('🔍 DEBUG seleccionarHorario - Parámetros recibidos:', {
        horaInicio,
        horaFin
    });
    
    if (!agendaSeleccionada) {
        console.error('❌ No hay agenda seleccionada');
        return;
    }
    
    // ✅ EXTRAER FECHA SIN DESFASE USANDO LA FUNCIÓN CORREGIDA
    const fechaBase = extraerFecha(agendaSeleccionada.fecha);
    
    console.log('🔍 DEBUG - Fecha base extraída:', {
        fechaOriginal: agendaSeleccionada.fecha,
        fechaBase: fechaBase
    });
    
    // Verificar que horaInicio y horaFin sean válidos
    if (!horaInicio || !horaFin) {
        console.error('❌ Horas inválidas:', { horaInicio, horaFin });
        return;
    }
    
    // Asegurar formato HH:MM:SS para las horas
    const horaInicioFormateada = horaInicio.includes(':') ? 
        (horaInicio.split(':').length === 2 ? horaInicio + ':00' : horaInicio) : 
        horaInicio + ':00:00';
    const horaFinFormateada = horaFin.includes(':') ? 
        (horaFin.split(':').length === 2 ? horaFin + ':00' : horaFin) : 
        horaFin + ':00:00';
    
    // ✅ CONSTRUIR FECHAS EN FORMATO ISO LOCAL (sin Z al final)
    const fechaInicioCorrecta = `${fechaBase}T${horaInicioFormateada}`;
    const fechaFinalCorrecta = `${fechaBase}T${horaFinFormateada}`;
    
    console.log('🔍 DEBUG - Fechas construidas:', {
        fechaBase,
        horaInicioFormateada,
        horaFinFormateada,
        fechaInicioCorrecta,
        fechaFinalCorrecta
    });
    
    horarioSeleccionado = {
        fecha_inicio: fechaInicioCorrecta,
        fecha_final: fechaFinalCorrecta,
        hora_inicio: horaInicio.includes(':') ? horaInicio.substring(0, 5) : horaInicio,
        hora_fin: horaFin.includes(':') ? horaFin.substring(0, 5) : horaFin
    };
    
    // ✅ ASIGNAR FECHAS CORRECTAS A LOS CAMPOS OCULTOS
    document.getElementById('fecha_inicio').value = fechaInicioCorrecta;
    document.getElementById('fecha_final').value = fechaFinalCorrecta;
    document.getElementById('fecha').value = fechaBase; // ✅ FECHA SIN HORA
    
    console.log('✅ Horario seleccionado final:', horarioSeleccionado);
    console.log('✅ Valores en campos del formulario:', {
        fecha: document.getElementById('fecha').value,
        fecha_inicio: document.getElementById('fecha_inicio').value,
        fecha_final: document.getElementById('fecha_final').value
    });
    
    // Actualizar estilos visuales
    document.querySelectorAll('.horario-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Marcar el botón seleccionado
    const botones = document.querySelectorAll('.horario-btn');
    botones.forEach(btn => {
        const btnHoraInicio = btn.getAttribute('data-hora-inicio');
        if (btnHoraInicio === horaInicio) {
            btn.classList.add('selected');
        }
    });
    
    // Habilitar siguiente paso
    actualizarBotones(pasoActual);
}

// ✅ PASO 3: BUSCAR PACIENTE
// ✅ FUNCIÓN CORREGIDA PARA MOSTRAR INFO DE HORARIO SIN DESFASE
function mostrarInfoHorarioSeleccionado() {
    if (!agendaSeleccionada || !horarioSeleccionado) return;
    
    const fecha = formatearFechaSinDesfase(agendaSeleccionada.fecha);
    const info = `
        <div class="row">
            <div class="col-md-6">
                <strong>Horario Seleccionado:</strong><br>
                <i class="fas fa-calendar me-1"></i> ${fecha}<br>
                <i class="fas fa-clock me-1"></i> ${horarioSeleccionado.hora_inicio} - ${horarioSeleccionado.hora_fin}
            </div>
            <div class="col-md-6">
                <strong>Consultorio:</strong><br>
                <i class="fas fa-building me-1"></i> ${agendaSeleccionada.consultorio}<br>
                <i class="fas fa-laptop me-1"></i> ${agendaSeleccionada.modalidad}
            </div>
        </div>
    `;
    
    document.getElementById('horarioSeleccionadoInfo').innerHTML = info;
}

async function buscarPaciente() {
    const documento = document.getElementById('buscar_documento').value.trim();
    
    if (!documento) {
        Swal.fire('Atención', 'Ingrese un número de cédula', 'warning');
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
        } else {
            mostrarPacienteNoEncontrado();
        }

    } catch (error) {
        console.error('Error buscando paciente:', error);
        Swal.fire('Error', 'Error buscando paciente: ' + error.message, 'error');
    }
}

// ✅ FUNCIÓN CORREGIDA CON LOGGING DETALLADO
function mostrarPacienteEncontrado(paciente) {
    console.log('🔍 DEBUG mostrarPacienteEncontrado - Paciente recibido:', paciente);
    
    pacienteSeleccionado = paciente;
    
    // ✅ VERIFICAR QUE EL UUID EXISTE
    if (!paciente.uuid) {
        console.error('❌ Paciente no tiene UUID!', paciente);
        Swal.fire('Error', 'El paciente encontrado no tiene un UUID válido', 'error');
        return;
    }
    
    // ✅ ESTABLECER EL UUID EN EL CAMPO OCULTO
    const pacienteUuidField = document.getElementById('paciente_uuid');
    if (pacienteUuidField) {
        pacienteUuidField.value = paciente.uuid;
        console.log('✅ paciente_uuid establecido:', paciente.uuid);
    } else {
        console.error('❌ Campo paciente_uuid no encontrado en el DOM!');
    }
    
    // ✅ VERIFICAR QUE SE ESTABLECIÓ CORRECTAMENTE
    console.log('🔍 Valor final del campo paciente_uuid:', document.getElementById('paciente_uuid')?.value);
    
    const detalles = `
        <strong>${paciente.nombre_completo}</strong><br>
        <small>Documento: ${paciente.documento}</small><br>
        <small>Teléfono: ${paciente.telefono || 'No registrado'}</small><br>
        <small class="text-success">UUID: ${paciente.uuid}</small>
    `;
    
    document.getElementById('pacienteDetalles').innerHTML = detalles;
    document.getElementById('pacienteInfo').style.display = 'block';
    document.getElementById('pacienteNoEncontrado').style.display = 'none';
    
    // Habilitar siguiente paso
    actualizarBotones(pasoActual);
}

function mostrarPacienteNoEncontrado() {
    pacienteSeleccionado = null;
    document.getElementById('paciente_uuid').value = '';
    document.getElementById('pacienteInfo').style.display = 'none';
    document.getElementById('pacienteNoEncontrado').style.display = 'block';
    
    // Deshabilitar siguiente paso
    actualizarBotones(pasoActual);
}

// ✅ SUBMIT DEL FORMULARIO - CORREGIDO
document.getElementById('citaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('🔍 === INICIANDO SUBMIT DE CITA ===');
    
    // ✅ VALIDACIONES BÁSICAS
    if (!pacienteSeleccionado) {
        Swal.fire('Atención', 'Debe seleccionar un paciente', 'warning');
        return;
    }
    
    if (!agendaSeleccionada) {
        Swal.fire('Atención', 'Debe seleccionar una agenda', 'warning');
        return;
    }
    
    if (!horarioSeleccionado) {
        Swal.fire('Atención', 'Debe seleccionar un horario', 'warning');
        return;
    }
    
    const nota = document.getElementById('nota').value.trim();
    if (!nota) {
        Swal.fire('Atención', 'Las notas adicionales son obligatorias', 'warning');
        document.getElementById('nota').focus();
        return;
    }
    
    // ✅ CREAR FormData Y AGREGAR CUPS MANUALMENTE SI ES NECESARIO
    const formData = new FormData(this);
    
    // ✅ VERIFICAR Y FORZAR CUPS CONTRATADO UUID
    const cupsContratadoUuid = document.getElementById('cups_contratado_uuid').value;
    if (cupsContratadoUuid && cupsContratadoUuid.trim() !== '') {
        // ✅ ASEGURAR QUE SE INCLUYA EN FormData
        formData.set('cups_contratado_uuid', cupsContratadoUuid.trim());
        console.log('✅ CUPS contratado UUID forzado en FormData:', cupsContratadoUuid);
    } else {
        console.log('ℹ️ No hay CUPS contratado para incluir');
    }
    
    // ✅ LOG DETALLADO DE TODOS LOS CAMPOS
    console.log('📋 === DATOS DEL FORMULARIO ===');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: "${value}"`);
    }
    
    // ✅ VERIFICAR CAMPOS CRÍTICOS
    const camposCriticos = {
        paciente_uuid: formData.get('paciente_uuid'),
        agenda_uuid: formData.get('agenda_uuid'),
        fecha: formData.get('fecha'),
        fecha_inicio: formData.get('fecha_inicio'),
        fecha_final: formData.get('fecha_final'),
        cups_contratado_uuid: formData.get('cups_contratado_uuid'),
        nota: formData.get('nota')
    };
    
    console.log('🔍 === CAMPOS CRÍTICOS ===');
    Object.entries(camposCriticos).forEach(([campo, valor]) => {
        const estado = valor && valor.trim() !== '' ? '✅ OK' : '❌ FALTA';
        console.log(`  ${campo}: "${valor}" - ${estado}`);
    });
    
    // ✅ VALIDAR CAMPOS OBLIGATORIOS
    if (!camposCriticos.paciente_uuid) {
        Swal.fire('Error', 'UUID del paciente no establecido', 'error');
        return;
    }
    
    if (!camposCriticos.agenda_uuid) {
        Swal.fire('Error', 'UUID de la agenda no establecido', 'error');
        return;
    }
    
    if (!camposCriticos.fecha_inicio || !camposCriticos.fecha_final) {
        Swal.fire('Error', 'Fechas de la cita no establecidas', 'error');
        return;
    }
    
    // ✅ LOG ESPECÍFICO DE CUPS
    if (camposCriticos.cups_contratado_uuid) {
        console.log('✅ === CUPS CONTRATADO INCLUIDO ===');
        console.log(`  UUID: ${camposCriticos.cups_contratado_uuid}`);
        
        const cupsSeleccionado = cupsAutocomplete ? cupsAutocomplete.getSelected() : null;
        if (cupsSeleccionado) {
            console.log('  CUPS Código:', cupsSeleccionado.codigo);
            console.log('  CUPS Nombre:', cupsSeleccionado.nombre);
        }
    } else {
        console.log('ℹ️ === CITA SIN CUPS CONTRATADO ===');
    }
    
    const btnGuardar = document.getElementById('btnGuardar');
    const originalText = btnGuardar.innerHTML;
    
    try {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';
        
        console.log('📤 === ENVIANDO CITA AL SERVIDOR ===');
        
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        console.log('📥 === RESPUESTA DEL SERVIDOR ===');
        console.log('Status:', response.status);
        console.log('Success:', data.success);
        console.log('Data:', data);
        
        if (data.success) {
            console.log('🎉 === CITA CREADA EXITOSAMENTE ===');
            
            // ✅ VERIFICAR SI LA CITA TIENE CUPS CONTRATADO
            if (data.data && data.data.cups_contratado_uuid) {
                console.log('✅ Cita creada CON CUPS contratado:', data.data.cups_contratado_uuid);
            } else if (camposCriticos.cups_contratado_uuid) {
                console.log('⚠️ Se envió CUPS pero no se reflejó en la respuesta');
            }
            
            await Swal.fire({
                title: '¡Cita Agendada!',
                text: data.message || 'La cita ha sido creada exitosamente',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            
            // ✅ REDIRECCIONAR
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                window.location.href = '/citas';
            }
            
        } else {
            console.error('❌ === ERROR DEL SERVIDOR ===');
            console.error('Error:', data.error);
            console.error('Errors:', data.errors);
            
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        }
        
    } catch (error) {
        console.error('❌ === ERROR CRÍTICO ===');
        console.error('Error:', error);
        
        Swal.fire({
            title: 'Error',
            text: 'Error agendando cita: ' + error.message,
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

// ✅ FUNCIONES DE DEBUG MEJORADAS
window.debugFormulario = function() {
    console.log('=== 🔍 DEBUG MANUAL DEL FORMULARIO ===');
    
    console.log('1. Estado de variables globales:');
    console.log('  pacienteSeleccionado:', pacienteSeleccionado);
    console.log('  agendaSeleccionada:', agendaSeleccionada);
    console.log('  horarioSeleccionado:', horarioSeleccionado);
    
    console.log('2. Campos ocultos del DOM:');
    console.log('  paciente_uuid:', document.getElementById('paciente_uuid')?.value);
    console.log('  agenda_uuid:', document.getElementById('agenda_uuid')?.value);
    console.log('  fecha:', document.getElementById('fecha')?.value);
    console.log('  fecha_inicio:', document.getElementById('fecha_inicio')?.value);
    console.log('  fecha_final:', document.getElementById('fecha_final')?.value);
    console.log('  cups_contratado_uuid:', document.getElementById('cups_contratado_uuid')?.value);
    
    console.log('3. Estado de CUPS:');
    if (cupsAutocomplete) {
        const selected = cupsAutocomplete.getSelected();
        console.log('  CUPS seleccionado:', selected);
        console.log('  CUPS contratado UUID:', selected?.cups_contratado_uuid);
    }
    
    console.log('4. Elemento del campo CUPS:');
    const cupsField = document.getElementById('cups_contratado_uuid');
    console.log('  Elemento existe:', !!cupsField);
    console.log('  Valor actual:', cupsField?.value);
    console.log('  Tipo de input:', cupsField?.type);
    console.log('  Nombre del campo:', cupsField?.name);
    
    console.log('5. FormData simulado:');
    const form = document.getElementById('citaForm');
    const formData = new FormData(form);
    
    // ✅ FORZAR CUPS SI EXISTE
    const cupsUuid = document.getElementById('cups_contratado_uuid')?.value;
    if (cupsUuid && cupsUuid.trim() !== '') {
        formData.set('cups_contratado_uuid', cupsUuid.trim());
        console.log('  ✅ CUPS forzado en FormData:', cupsUuid);
    }
    
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: "${value}"`);
    }
    
    console.log('=== FIN DEBUG ===');
};

// ✅ FUNCIÓN PARA VERIFICAR CUPS ESPECÍFICAMENTE
window.debugCups = function() {
    console.log('=== 🔍 DEBUG ESPECÍFICO DE CUPS ===');
    
    const cupsField = document.getElementById('cups_contratado_uuid');
    console.log('1. Campo cups_contratado_uuid:');
    console.log('  Existe:', !!cupsField);
    console.log('  Valor:', cupsField?.value);
    console.log('  Atributos:', {
        id: cupsField?.id,
        name: cupsField?.name,
        type: cupsField?.type,
        required: cupsField?.required
    });
    
    console.log('2. CUPS Autocomplete:');
    if (cupsAutocomplete) {
        const selected = cupsAutocomplete.getSelected();
        console.log('  Seleccionado:', selected);
        console.log('  UUID contratado:', selected?.cups_contratado_uuid);
    } else {
        console.log('  No inicializado');
    }
    
    console.log('3. Campos de CUPS en el DOM:');
    console.log('  cups_codigo:', document.getElementById('cups_codigo')?.value);
    console.log('  cups_nombre:', document.getElementById('cups_nombre')?.value);
    
    console.log('=== FIN DEBUG CUPS ===');
};

// ✅ FUNCIÓN PARA VERIFICAR ESTADO COMPLETO
window.debugEstadoCompleto = function() {
    console.log('=== 🔍 DEBUG ESTADO COMPLETO ===');
    
    console.log('📊 PASO ACTUAL:', pasoActual);
    
    console.log('📋 AGENDAS DISPONIBLES:', agendasDisponibles?.length || 0);
    if (agendaSeleccionada) {
        console.log('✅ AGENDA SELECCIONADA:', {
            uuid: agendaSeleccionada.uuid,
            consultorio: agendaSeleccionada.consultorio,
            fecha: agendaSeleccionada.fecha
        });
    } else {
        console.log('❌ NO HAY AGENDA SELECCIONADA');
    }
    
    console.log('⏰ HORARIOS DISPONIBLES:', horariosDisponibles?.length || 0);
    if (horarioSeleccionado) {
        console.log('✅ HORARIO SELECCIONADO:', horarioSeleccionado);
    } else {
        console.log('❌ NO HAY HORARIO SELECCIONADO');
    }
    
    if (pacienteSeleccionado) {
        console.log('✅ PACIENTE SELECCIONADO:', {
            uuid: pacienteSeleccionado.uuid,
            nombre: pacienteSeleccionado.nombre_completo,
            documento: pacienteSeleccionado.documento
        });
    } else {
        console.log('❌ NO HAY PACIENTE SELECCIONADO');
    }
    
    console.log('💊 ESTADO DE CUPS:');
    const cupsUuid = document.getElementById('cups_contratado_uuid')?.value;
    if (cupsUuid && cupsUuid.trim() !== '') {
        console.log('✅ CUPS CONTRATADO UUID:', cupsUuid);
        if (cupsAutocomplete) {
            const selected = cupsAutocomplete.getSelected();
            if (selected) {
                console.log('✅ CUPS SELECCIONADO:', {
                    codigo: selected.codigo,
                    nombre: selected.nombre
                });
            }
        }
    } else {
        console.log('ℹ️ SIN CUPS SELECCIONADO');
    }
    
    console.log('🔧 VALIDACIÓN DE FORMULARIO:');
    const esValido = pacienteSeleccionado && agendaSeleccionada && horarioSeleccionado;
    console.log('  Formulario válido:', esValido ? '✅ SÍ' : '❌ NO');
    
    if (!esValido) {
        console.log('  Falta:');
        if (!pacienteSeleccionado) console.log('    - Paciente');
        if (!agendaSeleccionada) console.log('    - Agenda');
        if (!horarioSeleccionado) console.log('    - Horario');
    }
    
    console.log('=== FIN DEBUG COMPLETO ===');
};

// ✅ FUNCIÓN PARA SIMULAR ENVÍO (TESTING)
window.simularEnvio = function() {
    console.log('🧪 === SIMULACIÓN DE ENVÍO ===');
    
    if (!pacienteSeleccionado || !agendaSeleccionada || !horarioSeleccionado) {
        console.log('❌ No se puede simular - faltan datos obligatorios');
        debugEstadoCompleto();
        return;
    }
    
    const form = document.getElementById('citaForm');
    const formData = new FormData(form);
    
    // Forzar CUPS si existe
    const cupsUuid = document.getElementById('cups_contratado_uuid')?.value;
    if (cupsUuid && cupsUuid.trim() !== '') {
        formData.set('cups_contratado_uuid', cupsUuid.trim());
    }
    
    console.log('📤 DATOS QUE SE ENVIARÍAN:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: "${value}"`);
    }
    
    console.log('✅ Simulación completada - revisar datos arriba');
};

// ✅ FUNCIÓN PARA LIMPIAR TODO Y REINICIAR
window.reiniciarFormulario = function() {
    console.log('🔄 Reiniciando formulario...');
    
    // Limpiar variables globales
    agendaSeleccionada = null;
    horarioSeleccionado = null;
    pacienteSeleccionado = null;
    
    // Limpiar campos del formulario
    document.getElementById('citaForm').reset();
    
    // Limpiar CUPS
    if (cupsAutocomplete) {
        cupsAutocomplete.clear();
    }
    ocultarInfoCups();
    
    // Limpiar displays
    document.getElementById('pacienteInfo').style.display = 'none';
    document.getElementById('pacienteNoEncontrado').style.display = 'none';
    
    // Volver al paso 1
    mostrarPaso(1);
    
    // Recargar agendas
    cargarAgendasConDatosReales();
    
    console.log('✅ Formulario reiniciado');
};

// ✅ EXPONER FUNCIONES PARA DEBUGGING EN CONSOLA
window.citas = {
    debug: debugFormulario,
    debugCups: debugCups,
    debugCompleto: debugEstadoCompleto,
    simular: simularEnvio,
    reiniciar: reiniciarFormulario,
    
    // Getters para inspeccionar estado
    get agenda() { return agendaSeleccionada; },
    get horario() { return horarioSeleccionado; },
    get paciente() { return pacienteSeleccionado; },
    get paso() { return pasoActual; },
    get agendas() { return agendasDisponibles; },
    get horarios() { return horariosDisponibles; }
};

console.log('🚀 === SISTEMA DE CITAS INICIALIZADO ===');
console.log('📋 Funciones de debug disponibles:');
console.log('  - citas.debug() - Debug general del formulario');
console.log('  - citas.debugCups() - Debug específico de CUPS');
console.log('  - citas.debugCompleto() - Debug completo del estado');
console.log('  - citas.simular() - Simular envío del formulario');
console.log('  - citas.reiniciar() - Reiniciar formulario completo');
console.log('📊 Variables de estado disponibles:');
console.log('  - citas.agenda - Agenda seleccionada');
console.log('  - citas.horario - Horario seleccionado');
console.log('  - citas.paciente - Paciente seleccionado');
console.log('  - citas.paso - Paso actual');
console.log('  - citas.agendas - Lista de agendas disponibles');
console.log('  - citas.horarios - Lista de horarios disponibles');

// Inicializar vista
mostrarPaso(1);
</script>



@endpush
@endsection
