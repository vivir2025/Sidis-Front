{{-- resources/views/agendas/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Ver Agenda - SIDIS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Detalles de la Agenda
                    </h1>
                    <p class="text-muted mb-0">Información completa de la agenda médica</p>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('agendas.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    
                    @if($agenda['estado'] !== 'ANULADA')
                        <a href="{{ route('agendas.edit', $agenda['uuid']) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    @endif
                    
                    <button type="button" class="btn btn-danger" onclick="eliminarAgenda('{{ $agenda['uuid'] }}', '{{ formatearFecha($agenda['fecha']) }} - {{ $agenda['consultorio'] }}')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de la Agenda -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert {{ getAlertClass($agenda['estado']) }} d-flex align-items-center" role="alert">
                <i class="fas {{ getEstadoIcon($agenda['estado']) }} me-3"></i>
                <div class="flex-grow-1">
                    <strong>Estado: {{ ucfirst(strtolower($agenda['estado'])) }}</strong>
                    @if($agenda['estado'] === 'ANULADA')
                        <p class="mb-0">Esta agenda ha sido anulada y no está disponible para citas.</p>
                    @elseif($agenda['estado'] === 'LLENA')
                        <p class="mb-0">Esta agenda está completa. No hay cupos disponibles.</p>
                    @else
                        <p class="mb-0">Esta agenda está activa y disponible para programar citas.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información de la Agenda
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Fecha y Horario -->
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">
                                    <i class="fas fa-calendar me-2 text-primary"></i>Fecha
                                </label>
                                <div class="info-value">
                                    <div class="fw-bold">{{ formatearFecha($agenda['fecha']) }}</div>
                                    <small class="text-muted">{{ getDayName($agenda['fecha']) }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">
                                    <i class="fas fa-clock me-2 text-primary"></i>Horario
                                </label>
                                <div class="info-value">
                                    <div class="fw-bold">{{ $agenda['hora_inicio'] }} - {{ $agenda['hora_fin'] }}</div>
                                    <small class="text-muted">Intervalo: {{ $agenda['intervalo'] ?? 15 }} minutos</small>
                                </div>
                            </div>
                        </div>

                        <!-- Modalidad y Consultorio -->
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">
                                    <i class="fas fa-laptop-medical me-2 text-primary"></i>Modalidad
                                </label>
                                <div class="info-value">
                                    <span class="badge {{ $agenda['modalidad'] === 'Telemedicina' ? 'bg-info' : 'bg-secondary' }} fs-6">
                                        {{ $agenda['modalidad'] ?? 'Ambulatoria' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">
                                    <i class="fas fa-door-open me-2 text-primary"></i>Consultorio
                                </label>
                                <div class="info-value">
                                    <div class="fw-bold">{{ $agenda['consultorio'] ?? 'No especificado' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Etiqueta -->
                        <div class="col-12">
                            <div class="info-group">
                                <label class="info-label">
                                    <i class="fas fa-tag me-2 text-primary"></i>Etiqueta
                                </label>
                                <div class="info-value">
                                    <div class="fw-bold">{{ $agenda['etiqueta'] ?? 'Sin etiqueta' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Proceso y Brigada -->
                        @if(!empty($agenda['proceso']) || !empty($agenda['brigada']))
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">
                                        <i class="fas fa-cogs me-2 text-primary"></i>Proceso
                                    </label>
                                    <div class="info-value">
                                        @if(!empty($agenda['proceso']))
                                            <div class="fw-bold">{{ $agenda['proceso']['nombre'] ?? 'Proceso no especificado' }}</div>
                                            @if(!empty($agenda['proceso']['n_cups']))
                                                <small class="text-muted">CUPS: {{ $agenda['proceso']['n_cups'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">
                                        <i class="fas fa-users me-2 text-primary"></i>Brigada
                                    </label>
                                    <div class="info-value">
                                        @if(!empty($agenda['brigada']))
                                            <div class="fw-bold">{{ $agenda['brigada']['nombre'] ?? 'Brigada no especificada' }}</div>
                                        @else
                                            <span class="text-muted">No asignada</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información de Cupos -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-users me-2"></i>Información de Cupos
        </h5>
    </div>
    <div class="card-body">
        {{-- ✅ DEBUG TEMPORAL - AGREGAR ESTO --}}
        @if(config('app.debug'))
        <div class="alert alert-info mb-3">
            <strong>🔍 DEBUG - Información detallada:</strong>
            <br><strong>UUID Agenda:</strong> {{ $agenda['uuid'] }}
            <br><strong>Total calculado:</strong> {{ calcularTotalCupos($agenda) }}
            <br><strong>Ocupados calculados:</strong> {{ calcularCuposOcupados($agenda) }}
            <br><strong>Cupos disponibles (BD):</strong> {{ $agenda['cupos_disponibles'] ?? 'null' }}
            <br><strong>Intervalo:</strong> {{ $agenda['intervalo'] ?? 'null' }} minutos
            <br><strong>Horario:</strong> {{ $agenda['hora_inicio'] }} - {{ $agenda['hora_fin'] }}
            
            {{-- Intentar mostrar citas --}}
            @php
                $citasDebug = obtenerCitasDeAgenda($agenda['uuid']);
            @endphp
            <br><strong>Citas encontradas:</strong> {{ count($citasDebug) }}
            @if(count($citasDebug) > 0)
                <br><strong>Primeras citas:</strong>
                @foreach(array_slice($citasDebug, 0, 3) as $cita)
                    <br>- {{ $cita['uuid'] ?? 'sin-uuid' }} ({{ $cita['estado'] ?? 'sin-estado' }})
                @endforeach
            @endif
        </div>
        @endif
                    <!-- Barra de Progreso -->
                    <div class="mt-3">
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ calcularPorcentajeOcupacion($agenda) }}%" 
                                 aria-valuenow="{{ calcularPorcentajeOcupacion($agenda) }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">Porcentaje de ocupación de la agenda</small>
                    </div>
                </div>
            </div>
        </div>
{{-- ✅ DEBUG TEMPORAL - EXPLORAR CITAS --}}
@if(config('app.debug'))
<div class="alert alert-warning mb-3">
    <strong>🔍 EXPLORANDO CITAS - Información detallada:</strong>
    
    {{-- Verificar OfflineService --}}
    @php
        try {
            $offlineService = app(\App\Services\OfflineService::class);
            $user = auth()->user() ?? session('usuario');
            $sedeId = $user['sede_id'] ?? 1;
            
            echo "<br><strong>🔍 OfflineService:</strong>";
            echo "<br>- Usuario existe: " . ($user ? 'SÍ' : 'NO');
            echo "<br>- Sede ID: " . $sedeId;
            
            // Intentar obtener TODAS las citas sin filtros
            $todasCitas = $offlineService->getCitasOffline($sedeId);
            echo "<br>- Total citas en OfflineService: " . count($todasCitas);
            
            if (count($todasCitas) > 0) {
                echo "<br><strong>📋 Primeras 3 citas:</strong>";
                foreach (array_slice($todasCitas, 0, 3) as $i => $cita) {
                    echo "<br>  Cita " . ($i+1) . ":";
                    echo "<br>    - UUID: " . ($cita['uuid'] ?? 'NULL');
                    echo "<br>    - Agenda UUID: " . ($cita['agenda_uuid'] ?? 'NULL');
                    echo "<br>    - Estado: " . ($cita['estado'] ?? 'NULL');
                    echo "<br>    - Fecha: " . ($cita['fecha_hora'] ?? $cita['fecha'] ?? 'NULL');
                }
            }
            
        } catch (\Exception $e) {
            echo "<br>❌ Error OfflineService: " . $e->getMessage();
        }
    @endphp
    
    {{-- Verificar base de datos directa --}}
    @php
        try {
            echo "<br><br><strong>🔍 Base de Datos Directa:</strong>";
            
            // Verificar si existe la tabla citas
            $tablas = \DB::select("SHOW TABLES LIKE 'citas'");
            echo "<br>- Tabla 'citas' existe: " . (count($tablas) > 0 ? 'SÍ' : 'NO');
            
            if (count($tablas) > 0) {
                // Contar todas las citas
                $totalCitas = \DB::table('citas')->count();
                echo "<br>- Total citas en BD: " . $totalCitas;
                
                if ($totalCitas > 0) {
                    // Mostrar estructura de las primeras citas
                    $primerasCitas = \DB::table('citas')->limit(3)->get();
                    echo "<br><strong>📋 Primeras 3 citas de BD:</strong>";
                    foreach ($primerasCitas as $i => $cita) {
                        echo "<br>  Cita BD " . ($i+1) . ":";
                        echo "<br>    - ID: " . ($cita->id ?? 'NULL');
                        echo "<br>    - UUID: " . ($cita->uuid ?? 'NULL');
                        echo "<br>    - Agenda UUID: " . ($cita->agenda_uuid ?? 'NULL');
                        echo "<br>    - Agenda ID: " . ($cita->agenda_id ?? 'NULL');
                        echo "<br>    - Estado: " . ($cita->estado ?? 'NULL');
                    }
                    
                    // Buscar específicamente por esta agenda
                    $citasEstaAgenda = \DB::table('citas')
                        ->where('agenda_uuid', $agenda['uuid'])
                        ->get();
                    echo "<br>- Citas con agenda_uuid '{$agenda['uuid']}': " . count($citasEstaAgenda);
                    
                    // Buscar por agenda_id si existe
                    if (isset($agenda['id'])) {
                        $citasAgendaId = \DB::table('citas')
                            ->where('agenda_id', $agenda['id'])
                            ->get();
                        echo "<br>- Citas con agenda_id '{$agenda['id']}': " . count($citasAgendaId);
                    }
                }
            }
            
        } catch (\Exception $e) {
            echo "<br>❌ Error BD: " . $e->getMessage();
        }
    @endphp
    
    {{-- Verificar otras posibles tablas --}}
    @php
        try {
            echo "<br><br><strong>🔍 Otras tablas posibles:</strong>";
            
            $tablasRelacionadas = ['appointments', 'cita', 'agendamiento', 'reservas'];
            foreach ($tablasRelacionadas as $tabla) {
                $existe = \DB::select("SHOW TABLES LIKE '{$tabla}'");
                if (count($existe) > 0) {
                    $count = \DB::table($tabla)->count();
                    echo "<br>- Tabla '{$tabla}': {$count} registros";
                }
            }
            
        } catch (\Exception $e) {
            echo "<br>❌ Error verificando tablas: " . $e->getMessage();
        }
    @endphp
    
    {{-- Información de la agenda actual --}}
    <br><br><strong>📋 Datos de la agenda actual:</strong>
    @foreach($agenda as $key => $value)
        @if(is_string($value) || is_numeric($value))
            <br>- {{ $key }}: {{ $value }}
        @endif
    @endforeach
</div>
@endif

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Información del Sistema -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-group mb-3">
                        <label class="info-label">UUID</label>
                        <div class="info-value">
                            <code class="small">{{ $agenda['uuid'] }}</code>
                        </div>
                    </div>

                    @if(!empty($agenda['sede']))
                        <div class="info-group mb-3">
                            <label class="info-label">Sede</label>
                            <div class="info-value">{{ $agenda['sede']['nombre'] ?? 'Sede no especificada' }}</div>
                        </div>
                    @endif

                    @if(!empty($agenda['usuario_creo']))
                        <div class="info-group mb-3">
                            <label class="info-label">Creado por</label>
                            <div class="info-value">{{ $agenda['usuario_creo']['nombre_completo'] ?? 'Usuario no especificado' }}</div>
                        </div>
                    @endif

                    <div class="info-group mb-3">
                        <label class="info-label">Fecha de Creación</label>
                        <div class="info-value">
                            {{ formatearFechaHora($agenda['created_at'] ?? null) }}
                        </div>
                    </div>

                    @if(!empty($agenda['updated_at']) && $agenda['updated_at'] !== $agenda['created_at'])
                        <div class="info-group mb-3">
                            <label class="info-label">Última Actualización</label>
                            <div class="info-value">
                                {{ formatearFechaHora($agenda['updated_at']) }}
                            </div>
                        </div>
                    @endif

                    <!-- Estado de Sincronización -->
                    @if(isset($agenda['sync_status']))
                        <div class="info-group mb-3">
                            <label class="info-label">Estado de Sincronización</label>
                            <div class="info-value">
                                @if($agenda['sync_status'] === 'synced')
                                    <span class="badge bg-success">Sincronizado</span>
                                @elseif($agenda['sync_status'] === 'pending')
                                    <span class="badge bg-warning">Pendiente</span>
                                @else
                                    <span class="badge bg-danger">Error</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($agenda['estado'] === 'ACTIVO')
                            <a href="/citas/create?agenda={{ $agenda['uuid'] }}" class="btn btn-success">
                                <i class="fas fa-plus"></i> Programar Cita
                            </a>
                        @endif
                        
                        <a href="/citas?agenda={{ $agenda['uuid'] }}" class="btn btn-info">
                            <i class="fas fa-list"></i> Ver Citas de esta Agenda
                        </a>
                        
                        @if($agenda['estado'] !== 'ANULADA')
                            <a href="{{ route('agendas.edit', $agenda['uuid']) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar Agenda
                            </a>
                        @endif
                        
                        <button type="button" class="btn btn-outline-primary" onclick="duplicarAgenda('{{ $agenda['uuid'] }}')">
                            <i class="fas fa-copy"></i> Duplicar Agenda
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary" onclick="imprimirAgenda()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.info-group {
    margin-bottom: 1rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}

.info-value {
    font-size: 1rem;
    color: #212529;
}

.alert-success {
    background-color: #d1e7dd;
    border-color: #badbcc;
    color: #0f5132;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c2c7;
    color: #842029;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}
</style>
@endpush

@push('scripts')
<script>
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
                Swal.fire('¡Eliminado!', data.message, 'success').then(() => {
                    window.location.href = '{{ route("agendas.index") }}';
                });
            } else {
                throw new Error(data.error);
            }

        } catch (error) {
            console.error('Error eliminando agenda:', error);
            Swal.fire('Error', 'Error eliminando agenda: ' + error.message, 'error');
        }
    }
}

function duplicarAgenda(uuid) {
    Swal.fire({
        title: '¿Duplicar Agenda?',
        text: 'Se creará una nueva agenda con los mismos datos',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, duplicar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/agendas/create?duplicate=${uuid}`;
        }
    });
}

function imprimirAgenda() {
    window.print();
}
</script>
@endpush
@endsection

@php
function formatearFecha($fecha) {
    if (!$fecha) return 'No especificada';
    try {
        return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    } catch (\Exception $e) {
        return $fecha;
    }
}

function formatearFechaHora($fechaHora) {
    if (!$fechaHora) return 'No especificada';
    try {
        return \Carbon\Carbon::parse($fechaHora)->format('d/m/Y H:i');
    } catch (\Exception $e) {
        return $fechaHora;
    }
}

function getDayName($fecha) {
    if (!$fecha) return '';
    try {
        $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $days[\Carbon\Carbon::parse($fecha)->dayOfWeek];
    } catch (\Exception $e) {
        return '';
    }
}

function getAlertClass($estado) {
    return match($estado) {
        'ACTIVO' => 'alert-success',
        'LLENA' => 'alert-warning',
        'ANULADA' => 'alert-danger',
        default => 'alert-info'
    };
}

function getEstadoIcon($estado) {
    return match($estado) {
        'ACTIVO' => 'fa-check-circle',
        'LLENA' => 'fa-exclamation-triangle',
        'ANULADA' => 'fa-times-circle',
        default => 'fa-info-circle'
    };
}

// ✅ CALCULAR TOTAL DE CUPOS POSIBLES
function calcularTotalCupos($agenda) {
    try {
        if (empty($agenda['hora_inicio']) || empty($agenda['hora_fin'])) {
            return 0;
        }

        $intervalo = (int) ($agenda['intervalo'] ?? 15);
        if ($intervalo <= 0) $intervalo = 15;

        $inicio = \Carbon\Carbon::parse($agenda['hora_inicio']);
        $fin = \Carbon\Carbon::parse($agenda['hora_fin']);
        
        $duracionMinutos = $fin->diffInMinutes($inicio);
        
        if ($duracionMinutos <= 0) return 0;
        
        return floor($duracionMinutos / $intervalo);
        
    } catch (\Exception $e) {
        return 0;
    }
}

// ✅ OBTENER CITAS REALES DE ESTA AGENDA
function obtenerCitasDeAgenda($agendaUuid) {
    try {
        \Log::info('🔍 Iniciando búsqueda de citas', [
            'agenda_uuid' => $agendaUuid
        ]);
        
        // Método 1: Intentar con OfflineService
        try {
            $offlineService = app(\App\Services\OfflineService::class);
            $user = auth()->user() ?? session('usuario');
            $sedeId = $user['sede_id'] ?? 1;
            
            \Log::info('🔍 Datos del usuario', [
                'user_exists' => $user ? 'sí' : 'no',
                'sede_id' => $sedeId
            ]);
            
            // Obtener TODAS las citas primero
            $todasLasCitas = $offlineService->getCitasOffline($sedeId);
            
            \Log::info('🔍 Total de citas encontradas', [
                'total_citas' => count($todasLasCitas)
            ]);
            
            // Mostrar algunas citas para debug
            if (count($todasLasCitas) > 0) {
                $primerasCitas = array_slice($todasLasCitas, 0, 3);
                foreach ($primerasCitas as $index => $cita) {
                    \Log::info("🔍 Cita ejemplo #{$index}", [
                        'uuid' => $cita['uuid'] ?? 'sin-uuid',
                        'agenda_uuid' => $cita['agenda_uuid'] ?? 'sin-agenda-uuid',
                        'estado' => $cita['estado'] ?? 'sin-estado',
                        'fecha_hora' => $cita['fecha_hora'] ?? 'sin-fecha'
                    ]);
                }
            }
            
            // Filtrar por agenda específica
            $citasDeEstaAgenda = array_filter($todasLasCitas, function($cita) use ($agendaUuid) {
                $citaAgendaUuid = $cita['agenda_uuid'] ?? null;
                $coincide = $citaAgendaUuid === $agendaUuid;
                
                if (!$coincide) {
                    \Log::debug('❌ Cita no coincide', [
                        'cita_agenda_uuid' => $citaAgendaUuid,
                        'buscando_agenda_uuid' => $agendaUuid
                    ]);
                }
                
                return $coincide;
            });
            
            \Log::info('🔍 Citas filtradas por agenda', [
                'agenda_uuid' => $agendaUuid,
                'citas_de_esta_agenda' => count($citasDeEstaAgenda)
            ]);
            
            // Filtrar solo citas activas
            $citasActivas = array_filter($citasDeEstaAgenda, function($cita) {
                $estado = $cita['estado'] ?? '';
                $esActiva = !in_array($estado, ['CANCELADA', 'NO_ASISTIO']);
                
                \Log::debug('🔍 Estado de cita', [
                    'uuid' => $cita['uuid'] ?? 'sin-uuid',
                    'estado' => $estado,
                    'es_activa' => $esActiva ? 'sí' : 'no'
                ]);
                
                return $esActiva;
            });
            
            \Log::info('✅ Resultado final de citas', [
                'agenda_uuid' => $agendaUuid,
                'total_citas' => count($todasLasCitas),
                'citas_de_agenda' => count($citasDeEstaAgenda),
                'citas_activas' => count($citasActivas)
            ]);
            
            return $citasActivas;
            
        } catch (\Exception $e) {
            \Log::error('❌ Error con OfflineService', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // Método 2: Intentar directamente con la base de datos
        try {
            \Log::info('🔍 Intentando con base de datos directa');
            
            $citas = \DB::table('citas')
                ->where('agenda_uuid', $agendaUuid)
                ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
                ->get()
                ->toArray();
            
            \Log::info('✅ Citas desde BD directa', [
                'agenda_uuid' => $agendaUuid,
                'citas_encontradas' => count($citas)
            ]);
            
            return $citas;
            
        } catch (\Exception $e) {
            \Log::error('❌ Error con BD directa', [
                'error' => $e->getMessage()
            ]);
        }
        
        return [];
        
    } catch (\Exception $e) {
        \Log::error('❌ Error general obteniendo citas', [
            'agenda_uuid' => $agendaUuid,
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

// ✅ CALCULAR CUPOS OCUPADOS BASADO EN CITAS REALES
function calcularCuposOcupados($agenda) {
    try {
        $agendaUuid = $agenda['uuid'] ?? null;
        if (!$agendaUuid) return 0;
        
        $citas = obtenerCitasDeAgenda($agendaUuid);
        $ocupados = count($citas);
        
        \Log::info('✅ Cupos ocupados calculados desde citas', [
            'agenda_uuid' => $agendaUuid,
            'citas_encontradas' => $ocupados
        ]);
        
        return $ocupados;
        
    } catch (\Exception $e) {
        \Log::error('❌ Error calculando cupos ocupados', [
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

// ✅ CALCULAR CUPOS DISPONIBLES
function calcularCuposDisponibles($agenda) {
    try {
        $total = calcularTotalCupos($agenda);
        $ocupados = calcularCuposOcupados($agenda);
        $disponibles = max(0, $total - $ocupados);
        
        \Log::info('✅ Cupos disponibles calculados', [
            'agenda_uuid' => $agenda['uuid'] ?? 'sin-uuid',
            'total' => $total,
            'ocupados' => $ocupados,
            'disponibles' => $disponibles
        ]);
        
        return $disponibles;
        
    } catch (\Exception $e) {
        return 0;
    }
}

// ✅ CALCULAR PORCENTAJE DE OCUPACIÓN
function calcularPorcentajeOcupacion($agenda) {
    try {
        $total = calcularTotalCupos($agenda);
        if ($total === 0) return 0;
        
        $ocupados = calcularCuposOcupados($agenda);
        return round(($ocupados / $total) * 100);
        
    } catch (\Exception $e) {
        return 0;
    }
}
@endphp
