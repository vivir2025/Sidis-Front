{{-- resources/views/historia-clinica/historial-historias/nutricionista/primera-vez.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Nutricionista - Primera Vez</title>
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
                    <p>HISTORIA CLÍNICA NUTRICIONISTA PRIMERA VEZ</p>
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
                
                // Manejar tipo_documento como string o array (offline/online)
                $tipoDoc = $paciente['tipo_documento'] ?? 'CC';
                $tipoDocumento = is_array($tipoDoc) ? ($tipoDoc['abreviacion'] ?? $tipoDoc['nombre'] ?? 'CC') : $tipoDoc;
                
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

            @if(!empty($complementaria['habito_intestinal']))
            <div class="campo-historia">
                <div class="campo-titulo">HÁBITO INTESTINAL</div>
                <div class="campo-contenido">{{ $complementaria['habito_intestinal'] }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ ANTECEDENTES --}}
        <fieldset>
            <legend>ANTECEDENTES</legend>
            
            <div class="examen-fisico-grid">
                {{-- 1. QUIRÚRGICOS --}}
                <div class="examen-item">
                    <div class="examen-label">1. QUIRÚRGICOS</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['quirurgicos'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['quirurgicos_observaciones']))
                    <div class="examen-obs">{{ $complementaria['quirurgicos_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 2. ALÉRGICOS --}}
                <div class="examen-item">
                    <div class="examen-label">2. ALÉRGICOS</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['alergicos'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['alergicos_observaciones']))
                    <div class="examen-obs">{{ $complementaria['alergicos_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 3. FAMILIARES --}}
                <div class="examen-item">
                    <div class="examen-label">3. FAMILIARES</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['familiares'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['familiares_observaciones']))
                    <div class="examen-obs">{{ $complementaria['familiares_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 4. PSA --}}
                <div class="examen-item">
                    <div class="examen-label">4. PSA</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['psa'] ?? 'NINGUNA') }}</div>
                    @if(!empty($complementaria['psa_observaciones']))
                    <div class="examen-obs">{{ $complementaria['psa_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 5. FARMACOLÓGICOS --}}
                <div class="examen-item">
                    <div class="examen-label">5. FARMACOLÓGICOS</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['farmacologicos'] ?? 'NINGUNA') }}</div>
                    @if(!empty($complementaria['farmacologicos_observaciones']))
                    <div class="examen-obs">{{ $complementaria['farmacologicos_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 6. SUEÑO --}}
                <div class="examen-item">
                    <div class="examen-label">6. SUEÑO</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['sueno'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['sueno_observaciones']))
                    <div class="examen-obs">{{ $complementaria['sueno_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 7. TABAQUISMO --}}
                <div class="examen-item">
                    <div class="examen-label">7. TABAQUISMO</div>
                    <div class="examen-valor">{{ strtoupper($historia['tabaquismo'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['tabaquismo_observaciones']))
                    <div class="examen-obs">{{ $complementaria['tabaquismo_observaciones'] }}</div>
                    @endif
                </div>

                {{-- 8. EJERCICIO --}}
                <div class="examen-item">
                    <div class="examen-label">8. EJERCICIO</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ejercicio'] ?? 'NIEGA') }}</div>
                    @if(!empty($complementaria['ejercicio_observaciones']))
                    <div class="examen-obs">{{ $complementaria['ejercicio_observaciones'] }}</div>
                    @endif
                </div>
            </div>
        </fieldset>

        {{-- ✅ ANTECEDENTES GINECOLÓGICOS --}}
        <fieldset>
            <legend>ANTECEDENTES GINECOLÓGICOS</legend>
            
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">MÉTODO ANTICONCEPTIVO</div>
                    <div class="dato-valor">{{ strtoupper($complementaria['metodo_conceptivo'] ?? 'NO') }}</div>
                </div>
                @if(isset($complementaria['metodo_conceptivo']) && $complementaria['metodo_conceptivo'] == 'SI')
                <div class="dato-item">
                    <div class="dato-label">MÉTODO ANTICONCEPTIVO ¿CUÁL?</div>
                    <div class="dato-valor">{{ $complementaria['metodo_conceptivo_cual'] ?? 'N/A' }}</div>
                </div>
                @endif
                <div class="dato-item">
                    <div class="dato-label">EMBARAZO ACTUAL</div>
                    <div class="dato-valor">{{ strtoupper($complementaria['embarazo_actual'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">SEMANAS DE GESTACIÓN</div>
                    <div class="dato-valor">{{ $complementaria['semanas_gestacion'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CLIMATERIO</div>
                    <div class="dato-valor">{{ strtoupper($complementaria['climatero'] ?? 'NO') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ ALIMENTACIÓN --}}
        <fieldset>
            <legend>ALIMENTACIÓN</legend>
            
            <div class="examen-fisico-grid">
                <div class="examen-item">
                    <div class="examen-label">TOLERANCIA VÍA ORAL</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['tolerancia_via_oral'] ?? 'N/A') }}</div>
                </div>

                <div class="examen-item">
                    <div class="examen-label">PERCEPCIÓN DEL APETITO</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['percepcion_apetito'] ?? 'N/A') }}</div>
                    @if(!empty($complementaria['percepcion_apetito_observacion']))
                    <div class="examen-obs">{{ $complementaria['percepcion_apetito_observacion'] }}</div>
                    @endif
                </div>

                <div class="examen-item">
                    <div class="examen-label">ALIMENTOS PREFERIDOS</div>
                    <div class="examen-valor">{{ $complementaria['alimentos_preferidos'] ?? 'N/A' }}</div>
                </div>

                <div class="examen-item">
                    <div class="examen-label">ALIMENTOS RECHAZADOS</div>
                    <div class="examen-valor">{{ $complementaria['alimentos_rechazados'] ?? 'NINGUNA' }}</div>
                </div>

                <div class="examen-item">
                    <div class="examen-label">SUPLEMENTOS O COMPLEMENTOS NUTRICIONALES Y/O VITAMÍNICOS</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['suplemento_nutricionales'] ?? 'NO') }}</div>
                </div>

                <div class="examen-item">
                    <div class="examen-label">¿HA LLEVADO ALGUNA DIETA ESPECIAL?</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['dieta_especial'] ?? 'NO') }}</div>
                    @if(isset($complementaria['dieta_especial']) && $complementaria['dieta_especial'] == 'SI' && !empty($complementaria['dieta_especial_cual']))
                    <div class="examen-obs">{{ $complementaria['dieta_especial_cual'] }}</div>
                    @endif
                </div>
            </div>
        </fieldset>

        {{-- ✅ HORARIOS DE COMIDA --}}
        <fieldset>
            <legend>HORARIOS DE COMIDA</legend>
            
            <div class="examen-fisico-grid">
                {{-- DESAYUNO --}}
                <div class="examen-item">
                    <div class="examen-label">DESAYUNO HORA</div>
                    <div class="examen-valor">{{ $complementaria['desayuno_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['desayuno_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['desayuno_hora_observacion'] }}</div>
                    @endif
                </div>

                {{-- MEDIA MAÑANA --}}
                <div class="examen-item">
                    <div class="examen-label">MEDIA MAÑANA HORA</div>
                    <div class="examen-valor">{{ $complementaria['media_manana_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['media_manana_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['media_manana_hora_observacion'] }}</div>
                    @endif
                </div>

                {{-- ALMUERZO --}}
                <div class="examen-item">
                    <div class="examen-label">ALMUERZO HORA</div>
                    <div class="examen-valor">{{ $complementaria['almuerzo_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['almuerzo_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['almuerzo_hora_observacion'] }}</div>
                    @endif
                </div>

                {{-- MEDIA TARDE --}}
                <div class="examen-item">
                    <div class="examen-label">MEDIA TARDE HORA</div>
                    <div class="examen-valor">{{ $complementaria['media_tarde_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['media_tarde_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['media_tarde_hora_observacion'] }}</div>
                    @endif
                </div>

                {{-- CENA --}}
                <div class="examen-item">
                    <div class="examen-label">CENA HORA</div>
                    <div class="examen-valor">{{ $complementaria['cena_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['cena_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['cena_hora_observacion'] }}</div>
                    @endif
                </div>

                {{-- REFRIGERIO NOCTURNO --}}
                <div class="examen-item">
                    <div class="examen-label">REFRIGERIO NOCTURNO HORA</div>
                    <div class="examen-valor">{{ $complementaria['refrigerio_nocturno_hora'] ?? 'NO REALIZA' }}</div>
                    @if(!empty($complementaria['refrigerio_nocturno_hora_observacion']))
                    <div class="examen-obs">{{ $complementaria['refrigerio_nocturno_hora_observacion'] }}</div>
                    @endif
                </div>
            </div>
        </fieldset>

        {{-- ✅ MEDIDAS ANTROPOMÉTRICAS --}}
        <fieldset>
            <legend>MEDIDAS ANTROPOMÉTRICAS</legend>
            
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
                    <div class="dato-label">PERÍMETRO ABDOMINAL</div>
                    <div class="dato-valor">{{ $historia['perimetro_abdominal'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CLASIFICACIÓN ESTADO NUTRICIONAL</div>
                    <div class="dato-valor">{{ $historia['clasificacion'] ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">PESO IDEAL</div>
                    <div class="dato-valor">{{ $complementaria['peso_ideal'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">INTERPRETACIÓN</div>
                    <div class="dato-valor">{{ $complementaria['interpretacion'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">META A MESES</div>
                    <div class="dato-valor">{{ $complementaria['meta_meses'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ ANÁLISIS NUTRICIONAL --}}
        @if(!empty($complementaria['analisis_nutricional']))
        <fieldset>
            <legend>ANÁLISIS NUTRICIONAL</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $complementaria['analisis_nutricional'] }}</div>
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
                                    CONFIRMADO NUEVO
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

        {{-- ✅ NOTA ADICIONAL (SI EXISTE) --}}
        @if(!empty($historia['observaciones_generales']))
        <div class="observacion-box">
            <strong>NOTA ADICIONAL:</strong><br>
            {{ $historia['observaciones_generales'] }}
        </div>
        @endif

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
                    <div class="firma-titulo">PROFESIONAL QUE ATIENDE</div>
                    <div class="firma-info">
                        {{ $profesionalNombre }}<br>
                        {{ $profesionalProfesion }}<br>
                        RM: {{ $profesionalRegistro }}<br>
                        Firma Digital:
                    </div>
                </div>
                <div class="firma-item">
                    <div class="firma-titulo">FIRMA PACIENTE</div>
                    <div class="firma-info">
                        {{ $tipoDocumento }} - {{ $documento }} {{ $nombreCompleto }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('historia-clinica.historial-historias.partials.scripts')
</body>
</html>
