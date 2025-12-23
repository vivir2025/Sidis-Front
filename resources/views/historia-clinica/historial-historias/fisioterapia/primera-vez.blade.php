{{-- resources/views/historia-clinica/historial-historias/fisioterapia/primera-vez.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Fisioterapia - Primera Vez</title>
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome/css/all.min.css') }}">
    @include('historia-clinica.historial-historias.partials.styles')
</head>
<body>
    <div class="container-historia">
        {{-- ✅ BOTÓN REGRESAR (NO SE IMPRIME) --}}
        <div class="no-print">
            <a href="{{ route('historia-clinica.index') }}" class="btn-regresar">
                <i class="fas fa-arrow-left"></i>
                <span>Regresar a Historias Clínicas</span>
            </a>
        </div>

        {{-- ✅ ENCABEZADO CON BORDE AZUL --}}
        <div class="header-box">
            <div class="header-content">
                <div>
                    <img class="header-logo" src="{{ asset('images/logo-fundacion.png') }}" alt="Logo">
                </div>
                <div class="header-text">
                    <h3>FUNDACIÓN NACER PARA VIVIR IPS</h3>
                    <p>NIT: 900817959-1</p>
                    <p>HISTORIA CLÍNICA FISIOTERAPIA PRIMERA VEZ</p>
                    <p>APERTURA PROGRAMA DE GESTIÓN DEL RIESGO CARDIORENAL</p>
                    <p>{{ $historia['cita']['fecha'] ?? date('Y-m-d') }}</p>
                </div>
                <div>
                    <img class="header-logo" src="{{ asset('images/logo-fundacion.png') }}" alt="Logo">
                </div>
            </div>
        </div>

        {{-- ✅ DATOS DEL PACIENTE --}}
        @php
            $paciente = $historia['cita']['paciente'] ?? null;
            $edad = 'N/A';
            $sexo = 'N/A';
            $estadoCivil = 'N/A';
            $telefono = 'N/A';
            $direccion = 'N/A';
            $departamento = 'N/A';
            $municipio = 'N/A';
            $empresa = 'N/A';
            $regimen = 'N/A';
            $ocupacion = 'N/A';
            $nombreCompleto = 'N/A';
            $brigada = 'N/A';
            $documento = 'N/A';
            $tipoDocumento = 'CC';
            $fechaNacimiento = 'N/A';
            
            if ($paciente) {
                if (isset($paciente['fecha_nacimiento']) && $paciente['fecha_nacimiento']) {
                    $fechaNac = \Carbon\Carbon::parse($paciente['fecha_nacimiento']);
                    $edad = $fechaNac->age . ' Años';
                    $fechaNacimiento = $paciente['fecha_nacimiento'];
                }
                
                $sexo = ($paciente['sexo'] ?? '') == 'M' ? 'MASCULINO' : (($paciente['sexo'] ?? '') == 'F' ? 'FEMENINO' : 'N/A');
                $estadoCivil = $paciente['estado_civil'] ?? 'N/A';
                $telefono = $paciente['telefono'] ?? 'N/A';
                $tipoDocumento = $paciente['tipo_documento'] ?? 'CC';
                $documento = $paciente['documento'] ?? 'N/A';
                $nombreCompleto = $paciente['nombre_completo'] ?? 'N/A';
                
                $departamento = $paciente['departamento']['nombre'] ?? 'N/A';
                $municipio = $paciente['municipio']['nombre'] ?? 'N/A';
                $direccion = ($paciente['direccion'] ?? '') . ' - ' . $municipio;
                $empresa = $paciente['empresa']['nombre'] ?? 'N/A';
                $regimen = $paciente['regimen']['nombre'] ?? 'N/A';
                $ocupacion = $paciente['ocupacion']['nombre'] ?? 'N/A';
                $brigada = $paciente['brigada']['nombre'] ?? 'N/A';
            }
        @endphp

        <fieldset>
            <legend>DATOS PACIENTE</legend>
            
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">NOMBRE</div>
                    <div class="dato-valor">{{ $tipoDocumento }} {{ $documento }}</div>
                    <div class="dato-valor">{{ $nombreCompleto }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FECHA NACIMIENTO Y EDAD</div>
                    <div class="dato-valor">{{ $fechaNacimiento }}</div>
                    <div class="dato-valor">{{ $edad }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">SEXO</div>
                    <div class="dato-valor">{{ $sexo }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ESTADO CIVIL</div>
                    <div class="dato-valor">{{ $estadoCivil }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $telefono }}</div>
                </div>
            </div>

            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">DIRECCIÓN</div>
                    <div class="dato-valor">{{ $direccion }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ASEGURADORA</div>
                    <div class="dato-valor">{{ $empresa }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">RÉGIMEN</div>
                    <div class="dato-valor">{{ $regimen }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">OCUPACIÓN</div>
                    <div class="dato-valor">{{ $ocupacion }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">BRIGADA</div>
                    <div class="dato-valor">{{ $brigada }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ ACUDIENTE (SI EXISTE) --}}
        @if(!empty($historia['acompanante']))
        <fieldset>
            <legend>ACUDIENTE</legend>
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">NOMBRE ACOMPAÑANTE</div>
                    <div class="dato-valor">{{ $historia['acompanante'] ?? 'Sin acompañante' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PARENTESCO</div>
                    <div class="dato-valor">{{ $historia['acu_parentesco'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $historia['acu_telefono'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ MOTIVO DE CONSULTA --}}
        @if(!empty($historia['motivo_consulta']))
        <fieldset>
            <legend>MOTIVO CONSULTA</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $historia['motivo_consulta'] }}</div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ DATOS FÍSICOS --}}
        <fieldset>
            <legend>DATOS FÍSICOS</legend>
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">PESO KG</div>
                    <div class="dato-valor">{{ $historia['peso'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TALLA CM</div>
                    <div class="dato-valor">{{ $historia['talla'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">IMC</div>
                    <div class="dato-valor">{{ $historia['imc'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CLASIFICACIÓN IMC</div>
                    <div class="dato-valor">{{ $historia['clasificacion'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PERÍMETRO ABDOMINAL</div>
                    <div class="dato-valor">{{ $historia['perimetro_abdominal'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>
        

        {{-- ✅ EVALUACIONES COMPLETAS --}}
        @if(!empty($historia['complementaria']))
        <fieldset>
            <legend>EVALUACIONES</legend>
            
            {{-- Grid de Evaluaciones Principales (2 columnas) --}}
            <div class="examen-fisico-grid">
                <div class="examen-item">
                    <div class="examen-label">ACTITUD POSTURAL:</div>
                    <div class="examen-valor">{{ strtoupper($historia['complementaria']['actitud'] ?? 'NORMAL') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">EVALUACIÓN DE SENSIBILIDAD:</div>
                    <div class="examen-valor">{{ strtoupper($historia['complementaria']['evaluacion_d'] ?? 'SUPERFICIAL') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">EVALUACIÓN DE PIEL:</div>
                    <div class="examen-valor">{{ strtoupper($historia['complementaria']['evaluacion_p'] ?? 'COLOR') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">ESTADO:</div>
                    <div class="examen-valor">{{ strtoupper($historia['complementaria']['estado'] ?? 'SECA') }}</div>
                </div>
            </div>

            {{-- Evaluación del Dolor --}}
            @if(!empty($historia['complementaria']['evaluacion_dolor']))
            <div class="campo-historia">
                <div class="campo-titulo">EVALUACIÓN DEL DOLOR</div>
                <div class="campo-contenido">{{ $historia['complementaria']['evaluacion_dolor'] }}</div>
            </div>
            @endif

            {{-- Evaluación Osteoarticular --}}
            @if(!empty($historia['complementaria']['evaluacion_os']))
            <div class="campo-historia">
                <div class="campo-titulo">EVALUACIÓN OSTEOARTICULAR</div>
                <div class="campo-contenido">{{ $historia['complementaria']['evaluacion_os'] }}</div>
            </div>
            @endif

            {{-- Evaluación Neuromuscular --}}
            @if(!empty($historia['complementaria']['evaluacion_neu']))
            <div class="campo-historia">
                <div class="campo-titulo">EVALUACIÓN NEUROMUSCULAR</div>
                <div class="campo-contenido">{{ $historia['complementaria']['evaluacion_neu'] }}</div>
            </div>
            @endif

            {{-- Enfermedad Concomitante --}}
            @if(!empty($historia['complementaria']['comitante']))
            <div class="campo-historia">
                <div class="campo-titulo">PADECE DE UNA ENFERMEDAD CONCOMITANTE</div>
                <div class="campo-contenido">{{ $historia['complementaria']['comitante'] }}</div>
            </div>
            @endif
        </fieldset>
        @endif

        {{-- ✅ PLAN DE TRATAMIENTO --}}
        @if(!empty($historia['complementaria']['plan_seguir']))
        <fieldset>
            <legend>PLAN DE TRATAMIENTO</legend>
            <div class="campo-historia">
                <div class="campo-titulo">DIAGNÓSTICO</div>
                <div class="campo-contenido">{{ $historia['complementaria']['plan_seguir'] }}</div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ DIAGNÓSTICOS --}}
        <fieldset>
            <legend>FORMATO DIAGNÓSTICA</legend>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>CÓDIGO</th>
                        <th>DIAGNÓSTICO</th>
                        <th>CLASIFICACIÓN</th>
                        <th>TIPO</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($historia['diagnosticos']) && count($historia['diagnosticos']) > 0)
                        @foreach($historia['diagnosticos'] as $index => $diag)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $diag['diagnostico']['codigo'] ?? 'N/A' }}</td>
                            <td>{{ $diag['diagnostico']['nombre'] ?? 'N/A' }}</td>
                            <td>{{ $diag['tipo'] == 'PRINCIPAL' ? 'PRINCIPAL' : 'TIPO' }}</td>
                            <td>
                                @if($diag['tipo_diagnostico'] == 'IMPRESION_DIAGNOSTICA')
                                    IMPRESIÓN DIAGNÓSTICA
                                @elseif($diag['tipo_diagnostico'] == 'CONFIRMADO_NUEVO')
                                    CONFIRMADO NUEVO
                                @elseif($diag['tipo_diagnostico'] == 'CONFIRMADO_REPETIDO')
                                    REPETIDO
                                @else
                                    {{ $diag['tipo_diagnostico'] }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="5" style="text-align: center;">No hay diagnósticos registrados</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            {{-- Causa Externa y Finalidad --}}
            <div style="margin-top: 8px;">
                <div class="campo-historia">
                    <strong>CAUSA EXTERNA:</strong> {{ $historia['causa_externa'] ?? 'ENFERMEDAD GENERAL' }}
                </div>
                <div class="campo-historia">
                    <strong>FINALIDAD:</strong> {{ $historia['finalidad'] ?? 'NO APLICA' }}
                </div>
            </div>
        </fieldset>

        {{-- ✅ MEDICAMENTOS --}}
        @if(!empty($historia['medicamentos']) && count($historia['medicamentos']) > 0)
        <fieldset>
            <legend>MEDICAMENTOS</legend>
            <table>
                <thead>
                    <tr>
                        <th>MEDICAMENTO</th>
                        <th>CANTIDAD</th>
                        <th>DOSIS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['medicamentos'] as $med)
                    <tr>
                        <td>{{ $med['medicamento']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $med['cantidad'] ?? 'N/A' }}</td>
                        <td>{{ $med['dosis'] ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ REMISIONES --}}
        @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
        <fieldset>
            <legend>REMISIÓN</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>REMISIÓN</th>
                        <th>OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['remisiones'] as $remision)
                    <tr>
                        <td>{{ $remision['remision']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $remision['observacion'] ?? 'Sin observaciones' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ AYUDAS DIAGNÓSTICAS --}}
        @if(!empty($historia['cups']) && count($historia['cups']) > 0)
        <fieldset>
            <legend>AYUDAS DIAGNÓSTICAS</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>CUPS</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['cups'] as $cup)
                    <tr>
                        <td>{{ $cup['cups']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $cup['cups']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $cup['observacion'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalProfesion = 'FISIOTERAPIA';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
                $profesionalProfesion = isset($medico['especialidad']['nombre']) 
                    ? strtoupper($medico['especialidad']['nombre']) 
                    : 'FISIOTERAPIA';
                $profesionalRegistro = $medico['registro_profesional'] ?? 'N/A';
                $profesionalFirma = $medico['firma'] ?? null;
            }
        @endphp

        <div class="firmas-box">
            <div class="firmas-content">
                <div class="firma-item">
                    @if($profesionalFirma)
                        <img class="firma-imagen" src="data:image/jpeg;base64,{{ $profesionalFirma }}" alt="Firma Digital">
                    @endif
                    <div class="firma-titulo">FIRMA DIGITAL</div>
                    <div class="firma-info">
                        PROFESIONAL: {{ $profesionalNombre }}<br>
                        {{ $profesionalProfesion }}<br>
                        RM: {{ $profesionalRegistro }}
                    </div>
                </div>
                <div class="firma-item">
                    <div class="firma-titulo">FIRMA PACIENTE</div>
                    <div class="firma-info">
                        {{ $tipoDocumento }}-{{ $documento }} {{ $nombreCompleto }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('historia-clinica.historial-historias.partials.scripts')
</body>
</html>
