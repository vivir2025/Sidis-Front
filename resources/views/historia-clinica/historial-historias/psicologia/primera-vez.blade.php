{{-- resources/views/historia-clinica/historial-historias/psicologia/primera-vez.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Psicología - Primera Vez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <p>PSICOLOGÍA PRIMERA VEZ - PROGRAMA DE GESTIÓN DEL RIESGO</p>
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
                $ocupacion = $paciente['ocupacion'] ?? 'N/A';
                $tipoDocumento = $paciente['tipo_documento'] ?? 'CC';
                $documento = $paciente['documento'] ?? 'N/A';
                $nombreCompleto = $paciente['nombre_completo'] ?? 'N/A';
                
                $departamento = $paciente['departamento']['nombre'] ?? 'N/A';
                $municipio = $paciente['municipio']['nombre'] ?? 'N/A';
                $direccion = $paciente['direccion'] ?? 'N/A';
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
                    <div class="dato-valor">{{ $historia['acompanante'] ?? 'N/A' }}</div>
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

        {{-- ✅ HISTORIA CLÍNICA - PRIMERA VEZ --}}
        @php
            $complementaria = $historia['complementaria'] ?? null;
        @endphp

        <fieldset>
            <legend>HISTORIA CLÍNICA - PRIMERA VEZ PSICOLOGÍA</legend>

            <div style="display: flex; gap: 20px;">
                {{-- 1. MOTIVO DE CONSULTA --}}
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">1. MOTIVO DE CONSULTA</div>
                    <div class="campo-contenido">
                        {{ $historia['motivo_consulta'] ?? 'N/A' }}
                    </div>
                </div>

                {{-- 2. ESTRUCTURA FAMILIAR --}}
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">2. ESTRUCTURA FAMILIAR (FAMILIARES O PERSONAS CON LAS QUE CONVIVE EL PACIENTE)</div>
                    <div class="campo-contenido">
                   
                        {{ $complementaria['estructura_familiar'] ?? 'No registrado' }}
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">3. RED DE APOYO FAMILIAR QUE CONSIDERA EL PACIENTE (FAMILIAR O FAMILIARES A LOS QUE EL PACIENTE CONSIDERE COMO SU RED DE APOYO)</div>
                    <div class="campo-contenido">
                        {{ $complementaria['psicologia_red_apoyo'] ?? 'N/A' }}
                    </div>

                </div>
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">4. COMPORTAMIENTO EN CONSULTA</div>
                    <div class="campo-contenido">
                        {{ $complementaria['psicologia_comportamiento_consulta'] ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 20px;">
                
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">5. TRATAMIENTO ACTUAL Y ADHERENCIA (DESCRIPCIÓN DEL PACIENTE SOBRE EL TRATAMIENTO Y LA ADHERENCIA AL MISMO)</div>
                    <div class="campo-contenido">
                        {{ $complementaria['psicologia_tratamiento_actual_adherencia'] ?? 'N/A' }}
                    </div>

                </div>
               
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">6. DESCRIPCIÓN DEL PROBLEMA (DESCRIPCIÓN DEL PACIENTE DE LA SITUACIÓN QUE LO AFECTA)</div>
                    <div class="campo-contenido">
                        {{ $complementaria['psicologia_descripcion_problema'] ?? 'N/A' }}
                    </div>
  
                </div>
            </div>

            <div style="display: flex; gap: 20px;">
                
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">7. ANÁLISIS Y CONCLUSIONES</div>
                    <div class="campo-contenido">
                        {{ $complementaria['analisis_conclusiones'] ?? 'N/A' }}
                    </div>
                </div>

               
                <div class="campo-historia" style="flex: 1;">
                    <div class="campo-titulo">8. PLAN DE INTERVENCIÓN Y RECOMENDACIONES</div>
                    <div class="campo-contenido">
                        {{ $complementaria['psicologia_plan_intervencion_recomendacion'] ?? 'N/A' }}
                    </div>
                </div>
            </div>

        </fieldset>

        {{-- ✅ FINALIDAD CON BORDE AZUL --}}
        <div class="observacion-box">
            <strong>FINALIDAD:</strong> {{ $historia['finalidad'] ?? 'NO APLICA' }}
        </div>

        {{-- ✅ TABLA DE DIAGNÓSTICOS --}}
    <fieldset>
        <legend>DIAGNÓSTICOS</legend>
        <table>
            <thead>
                <tr>
                    <th colspan="4">FORMATO DIAGNÓSTICO {{ date('Y-m-d') }}</th>
                </tr>
                <tr>
                    <th>CÓDIGO</th>
                    <th>DIAGNÓSTICO</th>
                    <th>CLASIFICACIÓN</th>
                    <th>TIPO</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($historia['diagnosticos']) && is_array($historia['diagnosticos']))
                    @foreach($historia['diagnosticos'] as $diag)
                    <tr>
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
                    <td colspan="4" style="text-align: center;">No hay diagnósticos registrados</td>
                </tr>
                @endif
            </tbody>
        </table>
    </fieldset>

        {{-- ✅ CAUSA EXTERNA CON BORDE AZUL --}}
        <div class="info-box">
            <strong>CAUSA EXTERNA:</strong> {{ $historia['causa_externa'] ?? 'OTRA' }}
        </div>
        

        {{-- ✅ MEDICAMENTOS (SI EXISTEN) --}}
        @if(!empty($historia['medicamentos']) && is_array($historia['medicamentos']) && count($historia['medicamentos']) > 0)
        <fieldset>
            <legend>MEDICAMENTOS FORMULADOS</legend>
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

        {{-- ✅ REMISIONES (SI EXISTEN) --}}
       @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
        <fieldset>
            <legend>REMISIONES</legend>
            <table>
                <thead>
                    <tr>
                        <th>Remisión</th>
                        <th>Tipo</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['remisiones'] as $remision)
                    <tr>
                        <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $remision['remision']['tipo'] ?? 'N/A' }}</td>
                        <td>{{ $remision['observacion'] ?? 'Sin observaciones' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ NOTA ADICIONAL (SI EXISTE) --}}
        @if(!empty($historia['adicional']))
        <div class="info-box">
            <strong>NOTA ADICIONAL:</strong> {{ $historia['adicional'] }}
        </div>
        @endif

        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalProfesion = 'PSICOLOGÍA';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                
                // ✅ NOMBRE COMPLETO (viene directo del backend)
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
                
                // ✅ ESPECIALIDAD (acceder al array anidado)
                $profesionalProfesion = isset($medico['especialidad']['nombre']) 
                    ? strtoupper($medico['especialidad']['nombre']) 
                    : 'PSICOLOGÍA';
                
                // ✅ REGISTRO PROFESIONAL
                $profesionalRegistro = $medico['registro_profesional'] ?? 'N/A';
                
                // ✅ FIRMA
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
                        {{ $tipoDocumento }}-{{ $documento }}<br>
                        {{ $nombreCompleto }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('historia-clinica.historial-historias.partials.scripts')
</body>
</html>
