{{-- resources/views/historia-clinica/historial-historias/nutricionista/control.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Nutricionista - Control</title>
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
                    <p>HISTORIA CLÍNICA NUTRICIONISTA CONTROL</p>
                    <p>PROGRAMA DE GESTIÓN DEL RIESGO CARDIORENAL</p>
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

        {{-- ✅ ACUDIENTE --}}
        <fieldset>
            <legend>ACUDIENTE</legend>
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">NOMBRE ACOMPAÑANTE</div>
                    <div class="dato-valor">{{ $historia['acompanante'] ?? 'NO PRESENTA ACOMPAÑANTE' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PARENTESCO</div>
                    <div class="dato-valor">{{ $historia['acu_parentesco'] ?? '' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $historia['acu_telefono'] ?? '' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ HISTORIA CLÍNICA --}}
        @php
            $complementaria = $historia['complementaria'] ?? [];
        @endphp

        <fieldset>
            <legend>HISTORIA CLÍNICA</legend>
            
            @if(!empty($historia['motivo_consulta']))
            <div class="campo-historia">
                <div class="campo-titulo">MOTIVO DE CONSULTA</div>
                <div class="campo-contenido">{{ $historia['motivo_consulta'] }}</div>
            </div>
            @endif

            @if(!empty($complementaria['enfermedad_diagnostica']))
            <div class="campo-historia">
                <div class="campo-titulo">ENFERMEDAD(ES) DIAGNOSTICADA(S)</div>
                <div class="campo-contenido">{{ $complementaria['enfermedad_diagnostica'] }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ MEDIDAS ANTROPOMÉTRICAS --}}
        <fieldset>
            <legend>MEDIDAS ANTROPOMÉTRICAS</legend>
            
            <div class="datos-grid-4">
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
                    <div class="dato-label">CLASIFICACIÓN</div>
                    <div class="dato-valor">{{ $historia['clasificacion'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ TIEMPOS DE COMIDA --}}
        <fieldset>
            <legend>TIEMPOS DE COMIDA</legend>
            
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">TIEMPO DE COMIDA DESAYUNO</div>
                    <div class="dato-valor">{{ $complementaria['comida_desayuno'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TIEMPO DE COMIDA 1/2 DESAYUNO</div>
                    <div class="dato-valor">{{ $complementaria['comida_medio_desayuno'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TIEMPO DE COMIDA ALMUERZO</div>
                    <div class="dato-valor">{{ $complementaria['comida_almuerzo'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TIEMPO DE COMIDA 1/2 TARDE</div>
                    <div class="dato-valor">{{ $complementaria['comida_medio_almuerzo'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TIEMPO DE COMIDA CENA</div>
                    <div class="dato-valor">{{ $complementaria['comida_cena'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ GRUPOS DE ALIMENTOS --}}
        <fieldset>
            <legend>GRUPOS DE ALIMENTOS</legend>
            
            <div class="examen-fisico-grid">
                {{-- LÁCTEO --}}
                <div class="examen-item">
                    <div class="examen-label">LÁCTEO</div>
                    <div class="examen-valor">{{ $complementaria['lacteo'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['lacteo_observacion']))
                    <div class="examen-obs">{{ $complementaria['lacteo_observacion'] }}</div>
                    @endif
                </div>

                {{-- HUEVO --}}
                <div class="examen-item">
                    <div class="examen-label">HUEVO</div>
                    <div class="examen-valor">{{ $complementaria['huevo'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['huevo_observacion']))
                    <div class="examen-obs">{{ $complementaria['huevo_observacion'] }}</div>
                    @endif
                </div>

                {{-- EMBUTIDO --}}
                <div class="examen-item">
                    <div class="examen-label">EMBUTIDO</div>
                    <div class="examen-valor">{{ $complementaria['embutido'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['embutido_observacion']))
                    <div class="examen-obs">{{ $complementaria['embutido_observacion'] }}</div>
                    @endif
                </div>

                {{-- CARNE ROJA --}}
                <div class="examen-item">
                    <div class="examen-label">CARNE ROJA</div>
                    <div class="examen-valor">{{ $complementaria['carne_roja'] ?? 'N/A' }}</div>
                </div>

                {{-- CARNE BLANCA --}}
                <div class="examen-item">
                    <div class="examen-label">CARNE BLANCA</div>
                    <div class="examen-valor">{{ $complementaria['carne_blanca'] ?? 'N/A' }}</div>
                </div>

                {{-- CARNE VÍSCERA --}}
                <div class="examen-item">
                    <div class="examen-label">CARNE VÍSCERA</div>
                    <div class="examen-valor">{{ $complementaria['carne_vicera'] ?? 'N/A' }}</div>
                </div>

                {{-- OBSERVACIÓN CARNES --}}
                @if(!empty($complementaria['carne_observacion']))
                <div class="examen-item" style="grid-column: 1 / -1;">
                    <div class="examen-label">CARNE OBSERVACIÓN</div>
                    <div class="examen-obs">{{ $complementaria['carne_observacion'] }}</div>
                </div>
                @endif

                {{-- LEGUMINOSAS SECAS --}}
                <div class="examen-item">
                    <div class="examen-label">LEGUMINOSAS SECAS</div>
                    <div class="examen-valor">{{ $complementaria['leguminosas'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['leguminosas_observacion']))
                    <div class="examen-obs">{{ $complementaria['leguminosas_observacion'] }}</div>
                    @endif
                </div>

                {{-- FRUTAS EN JUGO --}}
                <div class="examen-item">
                    <div class="examen-label">FRUTAS EN JUGO</div>
                    <div class="examen-valor">{{ $complementaria['frutas_jugo'] ?? 'N/A' }}</div>
                </div>

                {{-- FRUTAS EN PORCIÓN --}}
                <div class="examen-item">
                    <div class="examen-label">FRUTAS EN PORCIÓN</div>
                    <div class="examen-valor">{{ $complementaria['frutas_porcion'] ?? 'N/A' }}</div>
                </div>

                {{-- FRUTAS OBSERVACIÓN --}}
                @if(!empty($complementaria['frutas_observacion']))
                <div class="examen-item" style="grid-column: 1 / -1;">
                    <div class="examen-label">FRUTAS OBSERVACIÓN</div>
                    <div class="examen-obs">{{ $complementaria['frutas_observacion'] }}</div>
                </div>
                @endif

                {{-- VERDURAS Y HORTALIZAS --}}
                <div class="examen-item">
                    <div class="examen-label">VERDURAS Y HORTALIZAS</div>
                    <div class="examen-valor">{{ $complementaria['verduras_hortalizas'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['vh_observacion']))
                    <div class="examen-obs">{{ $complementaria['vh_observacion'] }}</div>
                    @endif
                </div>

                {{-- CEREALES --}}
                <div class="examen-item">
                    <div class="examen-label">CEREALES</div>
                    <div class="examen-valor">{{ $complementaria['cereales'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['cereales_observacion']))
                    <div class="examen-obs">{{ $complementaria['cereales_observacion'] }}</div>
                    @endif
                </div>

                {{-- RTP --}}
                <div class="examen-item">
                    <div class="examen-label">RTP</div>
                    <div class="examen-valor">{{ $complementaria['rtp'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['rtp_observacion']))
                    <div class="examen-obs">{{ $complementaria['rtp_observacion'] }}</div>
                    @endif
                </div>

                {{-- AZÚCARES Y DULCES --}}
                <div class="examen-item">
                    <div class="examen-label">AZÚCARES Y DULCES</div>
                    <div class="examen-valor">{{ $complementaria['azucar_dulce'] ?? 'N/A' }}</div>
                    @if(!empty($complementaria['ad_observacion']))
                    <div class="examen-obs">{{ $complementaria['ad_observacion'] }}</div>
                    @endif
                </div>
            </div>
        </fieldset>

        {{-- ✅ DIAGNÓSTICO NUTRICIONAL --}}
        @if(!empty($complementaria['diagnostico_nutri']))
        <fieldset>
            <legend>DIAGNÓSTICO NUTRICIONAL</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $complementaria['diagnostico_nutri'] }}</div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ PLAN A SEGUIR --}}
        @if(!empty($complementaria['plan_seguir_nutri']))
        <fieldset>
            <legend>PLAN A SEGUIR</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $complementaria['plan_seguir_nutri'] }}</div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ ANÁLISIS NUTRICIONAL --}}
        @if(!empty($complementaria['analisis_nutricional']))
        <fieldset>
            <legend>ANÁLISIS NUTRICIONAL</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $complementaria['analisis_nutricional'] }}</div>
            </div>
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
                        <th>NOMBRE</th>
                        <th>OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['remisiones'] as $remision)
                    <tr>
                        <td>{{ $remision['remision']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $remision['observacion'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ AYUDAS DIAGNÓSTICAS --}}
        @if(!empty($historia['cups']) && count($historia['cups']) > 0)
        <fieldset>
            <legend>FORMATO AYUDA DIAGNOSTICAS</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>CUPS</th>
                        <th>OBSERVACIÓN</th>
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

        {{-- ✅ MEDICAMENTOS --}}
        @if(!empty($historia['medicamentos']) && count($historia['medicamentos']) > 0)
        <fieldset>
            <legend>FORMATO MEDICAMENTO</legend>
            <table>
                <thead>
                    <tr>
                        <th>MEDICAMENTO</th>
                        <th>DOSIS</th>
                        <th>CANTIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['medicamentos'] as $med)
                    <tr>
                        <td>{{ $med['medicamento']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $med['dosis'] ?? 'N/A' }}</td>
                        <td>{{ $med['cantidad'] ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ DIAGNÓSTICOS --}}
        <fieldset>
            <legend>DIAGNÓSTICO</legend>
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
                                    IMPRESIÓN DIAGNOSTICA
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
        </fieldset>

        {{-- ✅ CAUSA EXTERNA --}}
        <div class="observacion-box">
            <strong>CAUSA EXTERNA:</strong> {{ $historia['causa_externa'] ?? 'OTRA' }}
        </div>

        {{-- ✅ FINALIDAD --}}
        <div class="observacion-box">
            <strong>FINALIDAD:</strong> {{ $historia['finalidad'] ?? 'NO APLICA' }}
        </div>

        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalProfesion = 'NUTRICIONISTA';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
                $profesionalProfesion = isset($medico['especialidad']['nombre']) 
                    ? strtoupper($medico['especialidad']['nombre']) 
                    : 'NUTRICIONISTA';
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
